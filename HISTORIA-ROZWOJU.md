# ListNest — historia i rekonstrukcja rozwoju

Dokument sporządzony 22.10.2026. Okres modelowy: 22.08–22.10.2026.

## Jak czytać ten dokument

To jawna rekonstrukcja kolejności prac na podstawie obecnego kodu, roadmapy i Git. Daty w harmonogramie są proponowanym rozłożeniem prac, a nie dowodem ich wykonania w tych dniach. Opisy commitów są propozycjami; nie utworzono ich w repozytorium. Nie odtworzono nieistniejących wersji plików ani wyników testów. Dokument nie potwierdza wdrożenia produkcyjnego.

## Historia zapisana w Git

| Data zapisana w Git | Commit | Zapisana zmiana |
| --- | --- | --- |
| 29.09.2026 | `5753786` | Import pierwotnego kodu wtyczki. |
| 29.09.2026 | `bc29186` | Zmiana nazwy na ListNest. |
| 29.09.2026 | `b590884` | Dodanie roadmapy. |
| 29.09.2026 | `5328cba` | Klasa inicjalizująca, prefiksy i deklaracja HPOS. |
| 29.09.2026 | `fb95966` | Sesja WooCommerce, nonce i oczyszczanie danych. |
| 29.09.2026 | `84125f1` | Automatyczny przycisk, popup, szablony i CSS. |
| 29.09.2026 | `33e3d5e` | CF7, tłumaczenia, readme i odinstalowanie. |
| 01.10.2026 | `9c43932` | Własny formularz oraz zapis zapytań. |

Daty commitów są metadanymi repozytorium. Pierwszy commit importuje gotowy kod, więc nie pozwala ustalić daty rozpoczęcia programowania. Po sporządzeniu modelu zmiany robocze zapisano w nowych commitach z rzeczywistą datą 22.10.2026. Ich zakres obejmuje zabezpieczenia i ustawienia, tłumaczenia, narzędzia testowania oraz dokumentację repozytorium.

## Proponowana ścieżka prac — rekonstrukcja

Każdy wiersz poniżej jest modelowym etapem, nie historycznym wpisem Git. Przerwy między datami pozostawiają czas na analizę i sprawdzanie działania; nie oznaczają udokumentowanych godzin pracy.

| Data modelowa | Proponowany commit | Zakres i powód zmiany |
| --- | --- | --- |
| 22.08 | `docs: spisz zakres pierwszej wersji list produktów` | Opisać dodawanie produktów do listy i wysyłanie zapytania bez zamówienia. Ustalić, że sklep może mieć kilka list. |
| 25.08 | `feat: dodaj szkielet wtyczki i definicje list` | Wprowadzić plik startowy oraz typ wpisu dla konfiguracji list. |
| 27.08 | `feat: dodaj pierwszą listę przez shortcode` | Wyświetlić prostą tabelę produktów. Na tym etapie interfejs może być podstawowy. |
| 31.08 | `feat: obsłuż dodawanie produktu do listy` | Połączyć przycisk z żądaniem AJAX i magazynem pozycji. |
| 02.09 | `fix: sumuj ilości przy ponownym dodaniu produktu` | Doprecyzować identyfikację pozycji, żeby kolejne kliknięcie nie tworzyło zbędnego wiersza. To proponowany krok implementacji, nie potwierdzony historyczny błąd. |
| 04.09 | `feat: dodaj zmianę ilości i usuwanie pozycji` | Uzupełnić tabelę o edycję oraz obsługę pustej listy. |
| 08.09 | `feat: przekaż wariant i atrybuty produktu` | Rozszerzyć dane pozycji o wybór wariantu zamiast samego produktu nadrzędnego. |
| 10.09 | `refactor: wydziel obsługę pozycji i szablony` | Oddzielić przygotowanie danych od HTML, ułatwiając dalsze poprawki. |
| 14.09 | `refactor: ujednolić nazwę i prefiksy ListNest` | Uporządkować klasy, hooki, akcje AJAX, uchwyty zasobów i nazwy CSS. Odpowiednik zakresu obecnej zmiany nazwy i etapu 1. |
| 16.09 | `refactor: przenieś inicjalizację do klasy wtyczki` | Skupić rejestrację modułów w jednym miejscu i dodać sprawdzenie WooCommerce. |
| 18.09 | `refactor: zastąp sesję PHP sesją WooCommerce` | Usunąć zależność od `session_start()` i `$_SESSION`. Odpowiada zakresowi istniejącego etapu 2. |
| 21.09 | `fix: sprawdzaj nonce i uprawnienia przy zapisie` | Dodać zabezpieczenia formularzy i metaboxów oraz sanitizację danych wejściowych. |
| 23.09 | `feat: dodaj automatyczny przycisk i ustawienia listy` | Pozwolić właścicielowi sklepu włączyć przycisk i określić jego tekst. |
| 25.09 | `refactor: usuń zależności szablonów od motywu` | Zastąpić klasy konkretnego motywu neutralnymi szablonami i CSS; umożliwić nadpisanie szablonów. |
| 28.09 | `fix: popraw obsługę klawiatury w popupie` | Dodać semantykę dialogu, Escape i zarządzanie fokusem; zastąpić komunikaty `alert()`. |
| 29.09 | `feat: zintegruj listę z Contact Form 7` | Dołączać podsumowanie produktów do wiadomości i czyścić listę po udanej wysyłce. |
| 30.09 | `fix: ponów dodanie po odświeżeniu nonce` | Obsłużyć przeterminowany token na stronie produktu pochodzącej z cache, z jednym ponowieniem. |
| 01.10 | `feat: dodaj własny formularz zapytania` | Umożliwić wysłanie bez CF7: dane kontaktowe, wiadomość, zgoda i walidacja. |
| 05.10 | `feat: zapisuj zapytania i status wysyłki` | Udostępnić zapytania w panelu; współdzielić budowanie tabeli mailowej. Zakres jest już obecny w rzeczywistym commicie z 01.10, data tutaj jest wyłącznie modelowa. |
| 07.10 | `feat: dodaj ochronę formularza i obsługę prywatności` | Honeypot, limit zgłoszeń, eksport i usuwanie danych osobowych. Także zakres obecnego etapu 4b. |
| 09.10 | `fix: waliduj pełny wybór wariantu po stronie serwera` | Odrzucać brak wariantu i niedozwolone opcje; budować kanoniczny zestaw atrybutów. Oparte na obecnym diffie roboczym. |
| 12.10 | `fix: odrzucaj tablice w polach tekstowych żądań` | Kontrolować typy wejścia, zanim trafią do funkcji oczekujących tekstu. Usunąć zależność od `mb_substr()` w walidacji atrybutów. |
| 14.10 | `fix: sprawdzaj dostępność produktów przed wysłaniem` | Ponownie oceniać pozycje i blokować zapytanie z niedostępnym produktem. |
| 15.10 | `fix: popraw powrót do formularza po zapisie` | Uporządkować przekierowania i kotwicę formularza; komunikat sukcesu opisać jako przyjęcie zapytania. |
| 16.10 | `fix: ogranicz cache stron z listą i formularzem` | Wcześniej ustawiać nagłówki i znacznik wyłączenia cache. Skuteczność z konkretnymi cache wymaga osobnej weryfikacji. |
| 19.10 | `feat: dodaj retencję i opcję usuwania danych` | Wprowadzić ustawienia przechowywania; przy odinstalowaniu domyślnie zachować listy i zapytania. |
| 20.10 | `test: dodaj scenariusze regresji i macierz środowisk` | Zgrupować skrypty testowe w `dev/`. Samo istnienie skryptu nie stanowi potwierdzenia zaliczonego testu. |
| 21.10 | `build: dodaj budowanie i kontrolę paczki ZIP` | Oddzielić narzędzia deweloperskie od paczki instalacyjnej i kontrolować jej zawartość. |
| 22.10 | `docs: uzupełnij instrukcję i materiały wydania` | Uaktualnić readme, tłumaczenia, instrukcję instalacji i plan dalszych prac zgodnie z rzeczywistym stanem. |

## Opis przebiegu projektu do wykorzystania z oznaczeniem rekonstrukcji

Rekonstrukcja zakłada rozpoczęcie od prostego przebiegu: dodanie produktu, obejrzenie listy, zmiana ilości i usunięcie pozycji. W kolejnych etapach dochodzi obsługa wariantów i podział kodu na moduły. Następnie porządkowane są nazwy oraz sposób inicjalizacji. Magazyn list przechodzi na sesję WooCommerce, a formularze otrzymują kontrolę nonce i danych wejściowych.

Kolejna część prac obejmuje wygląd niezależny od motywu, dostępność popupu i wysyłkę zapytań. Integrację z Contact Form 7 uzupełnia własny formularz, aby podstawowy przebieg nie wymagał dodatkowej wtyczki. Zapytania zostają zapisane w panelu, co pozwala oddzielić przyjęcie zgłoszenia od powodzenia powiadomienia mailowego.

Końcowy etap modelu skupia się na przypadkach brzegowych: niepełnym wyborze wariantu, nieprawidłowych typach danych, produktach wycofanych z publikacji, przekierowaniach i cache. Dochodzą ustawienia retencji, bezpieczniejsze odinstalowanie oraz narzędzia testowania i budowy paczki. Ten opis odtwarza sensowną kolejność prac; nie jest relacją autora z konkretnych dni.

## Jak prowadzić dalsze commity

Przy kolejnych zmianach warto zachować podział według odpowiedzialności: walidacja produktów; formularz i przekierowania; cache i interfejs; retencja i uninstall; testy i narzędzia; dokumentacja i tłumaczenia. Granice trzeba ustalić na podstawie zależności w diffie, żeby pośrednie commity nie odwoływały się do brakujących metod lub klas. Nie wystarczy dodawać całych plików według nazw.

Nowe commity powinny otrzymać rzeczywistą datę utworzenia. Jeżeli powstanie osobna gałąź edukacyjna odtwarzająca etapy, należy oznaczyć ją jako rekonstrukcję w README i opisach commitów. Nie dopisywać celowo błędów i fikcyjnych nieudanych testów wyłącznie dla uwiarygodnienia historii.

## Wdrożenie — brakujące potwierdzenia

Wykaz commitów nie dowodzi uruchomienia na sklepie. Do rzeczywistego protokołu wdrożenia trzeba dołączyć datę instalacji, wersję lub hash paczki, środowisko i wersje WP/WC/PHP, wyniki testów, kopię zapasową i sposób wycofania oraz wynik sprawdzenia po instalacji. Tych danych nie należy uzupełniać domysłami. Wyniki istniejących testów należy odczytać z raportów i powiązać z badaną wersją kodu przed uznaniem ich za dowód gotowości wydania.

