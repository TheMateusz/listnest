# ListNest 1.0.0 - końcowy raport

Data: 22.10.2026. Paczka `releases/listnest-1.0.0.zip`: 83505 bajtów, 28 plików.
SHA256: `bf37ae3a3d7ac328a078c71e7b27d7d03c88764f18389a04a89cd784891659a0`.

| Instalacja | WordPress | WooCommerce | PHP | CF7 | Sprawdzenia funkcjonalne | Plugin Check 2.1.0 |
| --- | --- | --- | --- | --- | --- | --- |
| minimum | 6.5 | 9.0.2 | 7.4.33 | 5.9.8 | 46/46 | statyczny i runtime: bez zgłoszonych błędów |
| current82 | 7.1.3 | 11.2.1 | 8.2.35 | 5.9.8 | 46/46 | statyczny i runtime: bez zgłoszonych błędów |
| current83 | 7.1.3 | 11.2.1 | 8.3.36 | 6.2.3 | 46/46 | statyczny i runtime: bez zgłoszonych błędów |
| Osobna instalacja ZIP bez mapowanego kodu | 7.1.3 | 11.2.1 | 8.3.36 | 6.2.3 | 46/46 | statyczny i runtime: bez zgłoszonych błędów |

Każdy zestaw: 9 regresji produktu/wariantów, 22 kontrole uprawnień i danych, 10 testów wbudowanego formularza HTTP oraz 5 kontroli CF7 REST. Łącznie 184 zaliczone sprawdzenia w czterech przebiegach. Wszystkie 28 plików zainstalowanej paczki porównano z ZIP-em przez SHA256.

Niezależnie zaliczono po 4 kontrole rzeczywistego AJAX wariantów na current83 i instalacji ZIP: dodanie, zachowanie opcji w sesji, scalanie identycznych wyborów i sumowanie ilości. Zestaw wariantów dołączono do runnerów na przyszłość. Oddzielnie przeszły 2 warianty uninstall oraz 3 scenariusze awarii zapisu/poczty.

## Plugin Check w panelu WordPressa

Plugin Check jest zainstalowany i aktywny na `http://localhost:8894/wp-admin/tools.php?page=plugin-check`. Wybrano ListNest i wszystkie kategorie: General, Plugin Repo, Security, Performance, Accessibility; włączono błędy i ostrzeżenia. Końcowy komunikat: **Checks complete. No errors found.**

Zrzut: `releases/plugin-check-result.png`. Próba dodatkowego eksportu JSON przez przeglądarkę zawiesiła narzędzie; ocena opiera się na zapisanych wcześniej raportach CLI i zrzucie.

## Potwierdzony zakres

- Wymaganie i dozwolone wartości wariantu, relacja wariant-produkt, brak zależności tej ścieżki od mbstring.
- Odrzucanie tablic w polach tekstowych, nonce, izolacja sesji gości i nagłówki no-cache/private strony listy.
- Ukrywanie szczegółów prywatnych produktów, możliwość usunięcia niedostępnej pozycji i blokada jej wysłania.
- Administrator i shop manager mają dostęp do list/danych; editor i subscriber go nie mają.
- Eksport i usuwanie 51 zgłoszeń w partiach po 50, retencja starych zgłoszeń, wyłączanie zadania cron.
- Zachowanie ukośników i cudzysłowów w zapisanej wiadomości, blokada duplikatów i ponowienie po jej wygaśnięciu.
- Wbudowany formularz bez JavaScript: walidacja, zapis, wysyłka i czyszczenie listy.
- CF7 REST dla gościa i zalogowanego, czyszczenie listy i odrzucanie pustej listy. Test wykrył i potwierdził naprawę brakującego nonce REST dla zalogowanych.
- Błąd maila przy poprawnym zapisie daje potwierdzenie przyjęcia i zapisuje failed. Łączna awaria zapisu i maila daje błąd i zachowuje listę.
- Domyślny uninstall zachowuje dane; zaznaczone usuwanie kasuje dane i ustawienia.
- Przeglądarka na Twenty Twenty-Five: wybór koloru/rozmiaru, aktywacja przycisku, dodanie wariantu z ZIP-a, poprawne opcje na liście, popup, Escape i powrót fokusu. Podgląd: `releases/listnest-list-preview.png`.
- Polski katalog: 147/147 tłumaczeń; MO odczytany i zweryfikowany przez GNUTranslations. Kontrole składni i diff przeszły.

## Ograniczenia i środowisko

Nie sprawdzono wszystkich cache/CDN, multisite ani migracji z każdej wcześniejszej wersji roboczej. Nie wykonywano prawdziwej wysyłki SMTP ani zmian na produkcji. Końcowy przebieg UI dotyczył Twenty Twenty-Five; trwały zestaw Playwright na obu motywach pozostaje rozszerzeniem CI. Minimum WooCommerce podniesiono do przetestowanej gałęzi 9.0.

W logach konfiguracji wystąpiło powiadomienie o wczesnym ładowaniu domeny woocommerce, a na Windows ostrzeżenie chmod w mechanizmie plikowym WordPressa; nie deklarujemy całkowicie pustego WP_DEBUG. W zmienianym środowisku current83 pojawiła się niespójność podglądu sesji w przeglądarce; na osobnej instalacji finalnego ZIP-a potwierdzono poprawne działanie wariantu HTTP i w UI. Historyczny WordPress został odtworzony po częściowej automatycznej aktualizacji; aktualizacje testów są teraz wyłączone i pełny przebieg 6.5 powtórzono z powodzeniem.

ZIP jest artefaktem do instalacji i zgłoszenia; nie został wysłany do WordPress.org. Akceptacja katalogu i dostępność nazwy/slugu pozostają częścią procesu publikacji.

Dowody: `test-results/minimum.json`, `current82.json`, `current83.json`, `release-package.json`, `release-variation.txt`, `failures.txt` oraz zrzut wyniku. Test-results jest lokalny i ignorowany przez Git.

---

# Archiwum - pierwszy przebieg przed ukończeniem 1.0.0

Poniższe ustalenia dokumentują wcześniejszy etap. Aktualny zakres i rozstrzygnięcia znajdują się powyżej.

Data: 22.10.2026. Testowano bieżący katalog roboczy, nie paczkę wydania ZIP.

| Profil | WordPress | WooCommerce | PHP | Regresje PHP | Testy HTTP | Plugin Check (statyczny) |
| --- | --- | --- | --- | --- | --- | --- |
| current82 | 7.1.3 | 11.2.1 | 8.2.35 | 9/9 | 10/10 | brak zgłoszonych błędów |
| current83 | 7.1.3 | 11.2.1 | 8.3.36 | 9/9 | 10/10 | brak zgłoszonych błędów |
| minimum | 6.5 | 9.0.2 | 7.4.33 | 9/9 | 10/10 | brak zgłoszonych błędów |

Dodatkowo kontrola składni 27 plików PHP/JavaScript przeszła pomyślnie. `git diff --check` nie zgłosił problemów.

Łącznie: 57 zaliczonych sprawdzeń funkcjonalnych na trzech profilach; każdy profil przeszedł statyczny Plugin Check. Wszystkie środowiska macierzy zatrzymano po zakończeniu. Dane i obrazy pozostawiono do kolejnych przebiegów.

Regresje obejmują brak wariantu, niezgodną relację wariantu do produktu, wymagane i dozwolone atrybuty, błędny typ danych oraz kanoniczny komplet atrybutów. HTTP sprawdza faktyczne dodanie produktu przez gościa, nonce, tablicę zamiast pola tekstowego, izolację sesji dwóch gości, walidację i wysłanie formularza oraz opróżnienie listy.

Surowe raporty są w ignorowanym przez Git katalogu `test-results/`. Raport przechowuje konfigurację i wyjście kontroli; niezerowy kod wykonania oznacza błąd. Pierwsze nieudane próby startu nie były wynikami testów wtyczki.

Problemy środowiska napotkane i obsłużone: błąd pobierania WordPressa przez GitHub (zmiana na oficjalne ZIP), niezgodność CF7 6.2.3 z PHP 8.2 (osobne wersje w profilach), błąd Windows EPERM przy rename rozpakowanych katalogów (ograniczone do cache kopiowanie), niedokończona inicjalizacja pustej bazy minimum (odtworzenie wyłącznie nowego wolumenu tego profilu).

Podczas konfiguracji profilu PHP 8.3 w debug.log pojawiło się powiadomienie WordPressa o zbyt wczesnym ładowaniu domeny tłumaczeń `woocommerce`. Nie ustalono jeszcze źródła wywołania. W profilu minimum pojawiło się ostrzeżenie `chmod(): Operation not permitted` w mechanizmie plikowym WordPressa, na katalogu mapowanym z Windows. Wyniki nie są deklaracją czystego WP_DEBUG ani pełnej zgodności całej witryny; te dwa wpisy wymagają dalszego sprawdzenia.

Pozostaje: runtime Plugin Check, interfejs i klawiatura w przeglądarce, motyw blokowy, CF7 REST, zalogowany klient, cache, awarie poczty/bazy, ochrona przed podwójnym wysłaniem, eksport/usuwanie danych oraz instalacja i aktualizacja finalnego ZIP. Nie zweryfikowano WooCommerce 7.0 wskazanego jako minimum w nagłówku. Te wyniki nie oznaczają jeszcze gotowości do publikacji 1.0.
