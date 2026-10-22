# ListNest 1.0.0 - instalacja

Gotowa paczka: `releases/listnest-1.0.0.zip`. Wgrywaj cały ZIP przez panel WordPressa; nie pakuj całego repozytorium. Paczka zawiera tylko katalog `listnest/`, 28 plików, licencję i komplet 147 polskich tłumaczeń.

## Wymagania

WordPress 6.5+, PHP 7.4+ i aktywny WooCommerce 9.0+. Sprawdzone zestawy: WP 6.5 / WC 9.0.2 / PHP 7.4.33 oraz WP 7.1.3 / WC 11.2.1 / PHP 8.2.35 i 8.3.36. Historyczny zestaw służy do sprawdzania kompatybilności; do nowego sklepu wybierz utrzymywane wersje. Contact Form 7 jest opcjonalny; jego wersja 6.2.3 wymaga PHP 8.3.

## Pierwsza lista

1. W WordPressie otwórz **Wtyczki → Dodaj wtyczkę → Wyślij wtyczkę na serwer**, wybierz ZIP, zainstaluj i aktywuj.
2. Otwórz **Listy → Dodaj nową listę**, nadaj nazwę i opublikuj. Zwróć uwagę na slug, np. `probki` - to klucz listy. Po osadzeniu shortcode nie zmieniaj go bez aktualizacji wszystkich miejsc osadzenia.
3. W opcjach włącz przycisk na stronach produktów. Wybierz wbudowany formularz zapytania i wpisz adres odbiorcy.
4. Utwórz stronę, np. „Moje próbki”, z shortcode `[listnest list_key="probki"]`.
5. W ustawieniach przycisku/popupu wybierz tę stronę jako cel. Dodatkowe shortcode są dostępne w **Listy → Konfiguracja i prywatność** (w angielskim panelu: **Lists → Setup and privacy**).
6. Wyklucz stronę listy i formularza z cache całych stron, również w CDN lub cache serwera. Wyczyść istniejące kopie tych stron.
7. Dodaj produkt i wariant, sprawdź ilości i wyślij zapytanie. Powinno pojawić się w **Listy → Zapytania**. Sprawdź osobno skrzynkę odbiorczą; status wysyłki nie gwarantuje doręczenia. W razie potrzeby skonfiguruj SMTP.

Listy klientów przechowywane są w sesji WooCommerce i wygasają razem z nią. Nie synchronizują się między urządzeniami. Brak JavaScript nie blokuje wysyłki wbudowanego formularza, ale dodawanie produktów wymaga JavaScript.

## Dane klientów

W ekranie konfiguracji można ustawić liczbę dni przechowywania zapytań. Wartość 0 oznacza brak automatycznego kasowania. WP-Cron usuwa codziennie do 100 przeterminowanych zapytań, włącznie z Koszem. Sprawdź wykonywanie cron na hostingu. Ta operacja nie usuwa wiadomości ze skrzynek pocztowych.

Po odinstalowaniu listy i zapytania domyślnie pozostają w bazie. Zaznaczenie opcji trwałego usuwania danych zmienia to zachowanie; przed użyciem wykonaj kopię. Eksport i usuwanie danych konkretnej osoby są dostępne przez standardowe narzędzia prywatności WordPressa.

## Podgląd testowy i Plugin Check

Osobna instalacja paczki ZIP działa pod `http://localhost:8894/`. Plugin Check jest zainstalowany i aktywny: `http://localhost:8894/wp-admin/tools.php?page=plugin-check`.

To lokalne środowisko demonstracyjne z przechwytywaniem poczty; nie wysyła prawdziwych maili. Domyślny użytkownik wp-env: `admin`, hasło: `password`. Nie wystawiaj tego środowiska do Internetu ani nie używaj tych danych w sklepie.

Wyniki: `dev/WYNIKI-TESTOW.md`; automatyzacja: `dev/TESTOWANIE.md`. Paczkę można zbudować ponownie komendą `npm run build:release`.

## Publikacja w katalogu

Kod i paczka są przygotowane do instalacji i zgłoszenia do WordPress.org. Samo zgłoszenie, wybór dostępnego slugu, konto właściciela oraz końcowa akceptacja katalogu wymagają procesu po stronie WordPress.org. Repozytorium SVN, grafiki marketingowe i obsługę zgłoszeń użytkowników przygotuj przy publikacji; nie są częścią ZIP-a instalacyjnego. Paczka nie została wysłana do katalogu w ramach tej pracy.
