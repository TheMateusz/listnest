# ListNest - plan doprowadzenia do publikacji

Data przeglądu: 22 października 2026. Podstawa: aktualny kod roboczy, ROADMAP.md, konfiguracja wp-env oraz oficjalne materiały WordPress.org. Dokument uzupełnia istniejącą roadmapę; zaznaczone tam funkcje traktujemy jako zaimplementowane, ale jeszcze nie jako potwierdzone testami.

## 1. Kierunek produktu

Rekomendowane pozycjonowanie: **listy zapytań ofertowych i próbek dla WooCommerce, bez checkoutu i bez obowiązkowego konta**.

Pierwsze wydanie powinno dopracować jeden przebieg: właściciel sklepu tworzy listę i stronę, klient dodaje produkty z wariantami oraz ilościami, wysyła zapytanie, a sklep znajduje je w panelu i otrzymuje powiadomienie. Własny formularz jest podstawową ścieżką; Contact Form 7 jest opcjonalną integracją.

Nie reklamować jeszcze trwałej wishlisty między urządzeniami: obecne listy korzystają z sesji WooCommerce i wygasają wraz z nią. Zaawansowane wyceny, PDF, CRM i współdzielenie list zostawić na później.

## 2. Co już istnieje

- Klasy rozdzielające inicjalizację, sesję, AJAX, szablony, formularze, wiadomości i zapytania.
- Wiele definicji list, ustawienia przycisków, przekierowanie, zmiana przycisku i popup.
- Produkty proste i obsługa wariantów, ilości, aktualizacja i usuwanie pozycji.
- Sesja WooCommerce, limity pozycji i ilości, nonce, sanitizacja i kontrola publikacji przy dodawaniu.
- Własny formularz, honeypot, limit zgłoszeń, wiadomość do sklepu i opcjonalna kopia do klienta.
- Zapytania zapisane jako osobny typ wpisu, status wysyłki, eksport i usuwanie danych osobowych.
- Integracja CF7, szablony nadpisywane w motywie, pliki tłumaczeń PL, CSS i obsługa dialogu.
- Nagłówki wtyczki, readme.txt, uninstall.php i deklaracja HPOS.
- Środowisko .wp-env.json, dane demonstracyjne i skrypt dev/e2e-form.sh.

Wynik wykonanych kontroli: 19 plików PHP przechodzi `php -l`; oba pliki JavaScript przechodzą `node --check`. Nie uruchamiano WordPressa, testów E2E ani Plugin Check. To przegląd statyczny i plan, nie pełny audyt bezpieczeństwa ani potwierdzenie gotowości produkcyjnej. Projekt zawiera wcześniejsze lokalne zmiany; nie były nadpisywane.

## 3. Konkretne ustalenia z kodu

| Priorytet | Ustalenie | Działanie / warunek zamknięcia |
| --- | --- | --- |
| P0 | `ListNest_Ajax::sanitize_attributes()` wywołuje `mb_substr()` bez alternatywy. | Zapewnić bezpieczne działanie bez mbstring i sprawdzić polskie znaki. |
| P0 | `resolve_product()` dopuszcza opublikowany produkt variable bez variation_id. `sanitize_attributes()` czyści wartości „dowolnych” atrybutów, ale nie sprawdza ich względem opcji produktu i nie wymusza kompletu wyborów. | Walidować po stronie serwera typ produktu, kompletny wybór i dozwolone wartości; odrzucać spreparowane żądania. Ustalić wspólną politykę dla grouped/external i shortcode. |
| P0 | Klucz pozycji zależy od przekazanego zestawu atrybutów. Pominięcie stałych atrybutów może tworzyć inną pozycję tego samego wariantu. | Budować kanoniczny komplet atrybutów przed wyliczeniem klucza; identyczne wybory zawsze sumują ilość. |
| P0 | Wiele pól POST trafia bez wcześniejszej kontroli typu do funkcji oczekujących tekstu. | Testować tablice zamiast tekstu, błędny JSON i nadmiernie duże dane; zwracać kontrolowany błąd bez fatal error i ostrzeżeń. |
| P0 | `ListNest_Item::prepare_rows()` ponownie odczytuje produkty, ale nie odrzuca istniejącego produktu po zmianie statusu na prywatny lub szkic. | Ustalić ponowną walidację przed wyświetleniem i wysłaniem; usunięte i niedostępne pozycje mają czytelny komunikat. |
| P0 | `DONOTCACHEPAGE` ustawiane jest podczas renderowania shortcode lub tagu CF7; istniejący cache może obsłużyć żądanie wcześniej. | Wcześniejsze oznaczanie znanych stron, instrukcja wykluczeń i test z cache oraz dwoma niezależnymi klientami. Osobno zbadać samodzielny shortcode formularza. |
| P1 | Definicja listy jest identyfikowana slugiem. | Zablokować przypadkową zmianę używanego klucza lub dodać ostrzeżenie i migrację; test polskich nazw. Docelowo rozważyć stabilny identyfikator wewnętrzny. |
| P1 | Aktualizacja listy ignoruje wynik `set_list()`. | Nie pokazywać potwierdzenia zapisu, gdy magazyn zgłasza błąd. |
| P1 | Status `sent` wynika z wartości zwróconej przez `wp_mail()`. | Opisywać go jako przyjęcie przez mechanizm wysyłki, nie potwierdzone doręczenie; dodać widoczny stan błędu i ręczne ponowienie. |
| P1 | Udane zapisanie zapytania pozwala pokazać sukces mimo błędu maila; to sensowna ochrona przed utratą kontaktu. | Komunikat klienta powinien mówić o przyjęciu zapytania. Sklep musi łatwo znaleźć zgłoszenia z błędem powiadomienia. Sprawdzić także awarię bazy i częściowy zapis metadanych. |
| P1 | Usunięcie wtyczki kasuje wszystkie definicje list i zapytania, także w multisite. | Dodać świadomą opcję usuwania danych, domyślnie zachować dane klienta; zaktualizować dokumentację i przetestować odinstalowanie. |
| P1 | Brak widocznej konfiguracji automatycznej retencji zapytań. | Dodać ustawienie okresu przechowywania i porcjowane czyszczenie; przetestować exporter/eraser na wielu stronach wyników. |
| P1 | Automatyczny przycisk opiera się na klasycznym hooku WooCommerce. | Sprawdzić szablony blokowe produktu; zapewnić działającą i opisaną metodę osadzenia, a zakres zgodności podać w readme. |
| P1 | Nagłówki deklarują konkretne wersje WP/WC, a roadmapa nadal ma niezamknięte testy. | Wpisać wyłącznie wersje rzeczywiście sprawdzone; potwierdzić rzeczywiste minimum PHP całego zestawu z WooCommerce. |

P0 oznacza blokadę wydania. P1 oznacza zalecenie przed publicznym 1.0; jeśli część zostanie odłożona, ograniczenie musi być jawne i nie może naruszać głównej ścieżki ani prywatności.

## 4. Etapy realizacji

### Etap A - stabilizacja rdzenia

1. Naprawić P0 z tabeli i ujednolicić walidację dodawania, aktualizacji oraz wysyłania.
2. Przetestować rozdzielenie sesji gości, logowanie/wylogowanie, wygaśnięcie i dwa równoległe żądania dodania.
3. Dodać ochronę przed podwójnym wysłaniem tego samego formularza. Sam nonce nie jest jednorazowym identyfikatorem zgłoszenia.
4. Zweryfikować role: administrator, shop manager, editor, subscriber, gość; osobno definicje list i dane kontaktowe.
5. Sprawdzić limit zgłoszeń przy równoległych żądaniach i przy wielu użytkownikach za jednym adresem IP. Kopia do klienta nie powinna umożliwiać masowego wysyłania spamu.

Gotowe, gdy: nieprawidłowe żądania są odrzucane, poprawne pozycje zachowują wariant i ilość, sesje nie mieszają danych, brak ostrzeżeń PHP w tych scenariuszach.

### Etap B - konfiguracja dla właściciela sklepu

1. Dodać krótki ekran startowy: utworzenie listy, wybór/utworzenie strony, odbiorca wiadomości i test konfiguracji. Bez obowiązkowego rozbudowanego kreatora.
2. Pokazać gotowe shortcode z kopiowaniem i link do podglądu strony listy.
3. Ostrzegać o nieopublikowanej stronie, brakującym odbiorcy i wybranym CF7, gdy integracja jest niedostępna.
4. Spiąć docelową stronę listy z konfiguracją przycisku, żeby nie ustawiać tego samego adresu w kilku miejscach.
5. Dodać filtr błędów wysyłki, ręczne ponowienie powiadomienia oraz prosty status obsługi zapytania: nowe / w trakcie / zamknięte. Status obsługi oddzielić od statusu maila.
6. Dopracować retencję, zachowanie przy usuwaniu wtyczki i komunikaty o czasie życia listy.

Gotowe, gdy: nowy użytkownik na czystym sklepie konfiguruje pierwszą działającą listę bez edycji kodu i samodzielnie odnajduje zapytanie.

### Etap C - testy i zgodność

Istniejący skrypt E2E to punkt wyjścia. Uruchamiać go wyłącznie w izolowanym środowisku deweloperskim: usuwa wszystkie transienty. Przypiąć wersje środowiska zamiast polegać wyłącznie na `latest-stable`, aby wyniki były odtwarzalne. Uzupełnić repozytorium o prostą instrukcję uruchamiania i automatyzację kontroli.

Minimalna macierz:

- WordPress: zadeklarowane minimum i bieżąca stabilna wersja; WooCommerce: zgodne kombinacje minimalna i bieżąca.
- PHP: deklarowane minimum, jeśli cały zestaw je obsługuje, oraz wersje użyte do docelowych wdrożeń.
- Storefront i aktualny domyślny motyw blokowy; klasyczny i blokowy szablon produktu.
- Gość i zalogowany klient; dwa niezależne profile przeglądarki; sesja po wylogowaniu i po wygaśnięciu.
- Produkt prosty, wariant stały i „dowolny”, atrybut lokalny i globalny, polskie znaki, brak wyboru, błędny wariant, zmiana statusu i usunięcie produktu.
- Dodanie, ponowne dodanie, ilość 0/ujemna/ponad limit, usunięcie pozycji, kilka list, pusta i pełna lista.
- Własny formularz i CF7: sukces, błąd walidacji, wygasły nonce, honeypot, limit, błąd maila i błąd zapisu.
- Cache produktu i listy, odświeżenie nonce, brak JavaScript dla formularza, przerwane połączenie.
- Eksport/usunięcie danych, role, retencja, aktualizacja wersji i uninstall; multisite, jeśli deklarowane jako wspierane.
- Telefon i desktop, klawiatura, fokus popupu i powrót do przycisku, czytnik ekranu, komunikaty błędów, kontrast.

Narzędzia: PHP lint, JS syntax check, Plugin Check, PHPCS z WordPress Coding Standards oraz testy integracyjne najważniejszych przypadków walidacji/sesji/formularza. Wyniki zapisać wraz z wersjami. Plugin Check nie zastępuje testów funkcjonalnych ani ręcznego review.

Gotowe, gdy: zero nierozwiązanych błędów Plugin Check, ostrzeżenia ocenione i uzasadnione, brak błędów ListNest w logach oraz wszystkie krytyczne scenariusze zakończone powodzeniem.

### Etap D - beta i wydanie 1.0.0

1. Zbudować instalowalny ZIP beta wyłącznie z katalogiem `listnest/`; przetestować sam ZIP na czystej instalacji.
2. Przeprowadzić pilotaż na 2-3 sklepach testowych lub stagingach. Zebrać uwagi o konfiguracji, wariantach i odbiorze zapytań.
3. Poprawić blokery, sprawdzić aktualizację beta → 1.0.0 z zachowaniem list i zapytań.
4. Ujednolicić wersję w nagłówku, stałej, changelogu i Stable tag. Zweryfikować nazwę/slug i login Contributors; nie zakładać dostępności nazwy ListNest.
5. Dopisać rzeczywiste ograniczenia, wymagania, konfigurację cache, działanie maila, dane przechowywane i sposób zgłaszania błędów.
6. Przygotować licencję, ikonę, bannery i prawdziwe zrzuty ekranów odpowiadające readme. Zweryfikować tłumaczenia po zmianie tekstów.
7. Sprawdzić finalny ZIP przez Plugin Check i zgłosić go do WordPress.org. Po akceptacji przygotować SVN trunk/tags i katalog assets; publikować kolejne wersje z changelogiem.

Nie ma potrzeby tworzenia nowego publicznego repozytorium wyłącznie z powodu zmiany nazwy - to wybór organizacyjny, nie warunek publikacji. Przed upublicznieniem sprawdzić historię pod kątem sekretów i praw do kodu.

## 5. Co zostawić na kolejne wydania

| Wersja | Proponowany zakres | Powód |
| --- | --- | --- |
| 1.1 | Wygodne bloki edytora listy/przycisku, licznik listy, wybór kategorii dla przycisku. | Łatwiejsze wdrożenie i obsługa współczesnych motywów. |
| 1.2 | Trwałe listy konta klienta, opcjonalne udostępnianie przez unikalny link. | Oddzielny projekt uprawnień, wygasania i prywatności; nie opierać linku na ID wpisu. |
| 1.3+ | Eksport CSV/PDF, przekształcenie zapytania w ofertę/zamówienie, integracje CRM. | Rozwijać dopiero po potwierdzeniu potrzeb pierwszych użytkowników. |

## 6. Kolejność i szacowanie

Najpierw A, następnie B i C, na końcu D. Orientacyjnie dla jednej osoby: A 3-6 dni roboczych, B 4-8, C 4-7, D 2-4 plus czas na pilotaż i review WordPress.org. To wstępny szacunek, nie termin: testy sesji/cache i szablonów blokowych mogą ujawnić dodatkową pracę. Dla szybszego pierwszego wydania można odłożyć statusy obsługi i rozbudowany ekran startowy, zachowując podstawowe instrukcje konfiguracji.

Najbliższy konkretny krok: uruchomić istniejące wp-env i zebrać bazowy raport Plugin Check oraz E2E, a potem poprawić walidację wariantów, typy danych wejściowych i mbstring. Każda poprawka P0 powinna dostać test odtwarzający problem.

## 7. Warunki publikacji

- [ ] Zamknięte P0 i określony zakres wspieranych wersji oraz produktów.
- [ ] Przetestowany cały przebieg klient → zapis → panel → powiadomienie.
- [ ] Potwierdzona izolacja sesji i bezpieczne działanie z cache.
- [ ] Sprawdzone uprawnienia, dane osobowe i skutki odinstalowania.
- [ ] Wyniki Plugin Check oraz scenariuszy funkcjonalnych zapisane dla finalnej paczki.
- [ ] Dokumentacja, tłumaczenia, licencje, grafiki i numery wersji są zgodne z paczką.
- [ ] ZIP instaluje się na czystym WordPressie i aktualizuje poprzednią wersję bez utraty danych.
- [ ] Wyznaczony kanał wsparcia i sposób obsługi zgłoszeń po premierze.

## Oficjalne źródła

- Wytyczne katalogu: https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/
- Plugin Check i ograniczenia narzędzia: https://wordpress.org/plugins/plugin-check/
- Zgłoszenie wtyczki: https://wordpress.org/plugins/developers/add/
- Planowanie i utrzymanie: https://developer.wordpress.org/plugins/wordpress-org/planning-submitting-and-maintaining-plugins/

Spełnienie checklisty ułatwia zgłoszenie, ale decyzję o przyjęciu podejmuje zespół WordPress.org.

## Aktualizacja po pierwszym etapie realizacji - 22.10.2026

Dodano lokalny runner macierzy Docker/wp-env, trzy izolowane profile z przypiętymi wersjami, testy regresji i testy HTTP oraz raporty JSON. Poprawiono w AJAX walidację typów pól, wymaganie wariantu, dozwolone wartości i komplet atrybutów; usunięto zależność tej ścieżki od mbstring. Zakończono 57 sprawdzeń funkcjonalnych (19 na profil) i statyczny Plugin Check bez zgłoszonych błędów dla wszystkich trzech profili. Szczegóły, ograniczenia i dwa wpisy w logach wymagające analizy: `dev/WYNIKI-TESTOW.md`. Instrukcja: `dev/TESTOWANIE.md`. Pozostałe pozycje planu nadal obowiązują; nie jest to jeszcze zgoda na wydanie produkcyjne.
