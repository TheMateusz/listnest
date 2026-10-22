# Automatyczne testy ListNest

Local służy do ręcznego przeglądania. Automaty uruchamiają oddzielne instalacje przez Docker Desktop (Linux containers) i wp-env. Nie podłączamy ich do bazy ani katalogu strony Local.

Wymagania: Node.js 20.12+ (zalecane 22+), PHP do lokalnego lintowania, działający Docker Desktop oraz `wp-env` (przygotowano na 11.16.0). Komendy wykonuj z głównego katalogu repozytorium.

```powershell
npm run test:plan
npm run test:syntax
npm test
npm run test:matrix
node dev/test-matrix.mjs --profile=minimum
```

Do ręcznego podglądu na standardowym porcie 8888 użyj `wp-env start` (konfiguracja główna również ma przypięte wersje), a po pracy `wp-env stop`. Testy macierzy zatrzymują swoje profile po zakończeniu.

Profile mają osobne pliki konfiguracji, bazy i porty:

| Profil | WordPress | PHP | WooCommerce | CF7 | Port |
| --- | --- | --- | --- | --- | --- |
| minimum | 6.5 | 7.4 | 9.0.2 | 5.9.8 | 8891 |
| current82 | 7.1.3 | 8.2 | 11.2.1 | 5.9.8 | 8892 |
| current83 | 7.1.3 | 8.3 | 11.2.1 | 6.2.3 | 8893 |

Profil minimum jest historycznym testem zgodności, nie propozycją wersji do sklepu produkcyjnego. W wydaniu 1.0.0 minimum WooCommerce podniesiono do przetestowanej gałęzi 9.0. Wersje przypięto po sprawdzeniu paczek 22.10.2026; Plugin Check to 2.1.0, Storefront 4.6.2. CF7 6.2.3 wymaga PHP 8.3, dlatego profil 8.2 używa starszego CF7. Automatyczne aktualizacje w testowych instalacjach są wyłączone, aby starszy profil nie mieszał plików z nowszym WordPressem. Raport zapisuje faktycznie zainstalowane wersje.

Runner tworzy konfiguracje w `.test-env/`, uruchamia profil, zapisuje wersje, tworzy dane demonstracyjne, wykonuje regresje PHP na prawdziwych obiektach WooCommerce, testy HTTP z oddzielnymi sesjami gości i Plugin Check, a potem zatrzymuje profil. Wynik i wyjścia kontroli trafiają do `test-results/<profil>.json`. Błąd daje niezerowy kod wyjścia; brak testu nie oznacza sukcesu. Raport może zawierać wyniki częściowe. Bazy pozostają po zatrzymaniu; nie są automatycznie kasowane. Profile uruchamiane są kolejno, aby oszczędzić RAM.

Na Windows runner obsługuje wykryty błąd `EPERM` przy zmianie nazwy rozpakowanego katalogu: kopiuje gotowy katalog do docelowego miejsca wyłącznie we własnym cache `.test-env/`, bez usuwania danych, i ponawia start. Raport zlicza takie obejścia. Inne błędy nie są ukrywane. Limit czasu pojedynczej komendy wynosi 15 minut.

Poczta jest przechwytywana przez deweloperski MU-plugin i zapisywana w `wp-content/listnest-mail.log`, bez rzeczywistej wysyłki. Nie kopiować MU-pluginu na produkcję. Fixture zmienia ustawienia wyłącznie dedykowanej instalacji testowej.

Zakres pierwszego zestawu: wymaganie wariantu, relacja wariant-produkt, dozwolone wartości atrybutów, kanoniczne scalanie atrybutów, błędny nonce, tablica zamiast tekstu, dodawanie przez gościa, izolacja sesji, walidacja formularza, wysłanie i opróżnienie listy. Test formularza przyjmuje istniejący limit antyspamowy: po wielu powtórzeniach w ciągu 10 minut może wymagać odczekania lub wyczyszczenia wyłącznie transientów `listnest_rl_*` w bazie testowej.

Zestaw rozszerzono o 22 kontrole prywatności, uprawnień, retencji, niedostępnych produktów, zapisu tekstu oraz blokady duplikatów; CF7 REST sprawdza gościa i zalogowanego użytkownika. `test-variation.mjs` sprawdza rzeczywiste dodanie wariantu i scalanie ilości. Sam HTTP nie wykonuje JavaScript; interfejs, wybór wariantu i powrót fokusu popupu sprawdzono dodatkowo w przeglądarce na motywie Twenty Twenty-Five. Do przyszłego CI warto dołożyć utrwalony zestaw Playwright na obu motywach i konfiguracje konkretnych cache/CDN.

Runner uruchamia Plugin Check z `--require=wp-content/plugins/plugin-check/cli.php`, co włącza również kontrole runtime. `node dev/check-release.mjs` uruchamia osobne środowisko na 8894, instaluje ZIP bez mapowanego kodu projektu, porównuje pliki i wykonuje rozszerzone testy. Wymaga wcześniej zbudowanej paczki i cache profilu current83. Pozostawia instalację paczki uruchomioną do podglądu. Opcjonalne `--keep-running` w runnerze macierzy również pozostawia profil uruchomiony.

Dodatkowe scenariusze na uruchomionym current83: `./dev/check-failures.ps1` symuluje awarie poczty i zapisu; `wp-env run cli wp eval-file wp-content/listnest-dev/uninstall-regression.php --config=.test-env/current83.json` sprawdza zachowanie uninstall i usuwa **testowe** listy/zapytania przy wariancie opt-in. Najpierw ustaw `WP_ENV_HOME` na absolutny `.test-env/home` tego repozytorium. Po teście uninstall uruchom ponownie seed. Nigdy nie uruchamiaj fixture na sklepie produkcyjnym.

Dokumentacja wp-env: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/
