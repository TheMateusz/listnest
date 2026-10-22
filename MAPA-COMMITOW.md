# Mapa etapów rozwoju

Gałąź `codex/development-history` jest rekonstrukcją z zachowanych wersji. Daty modelowe opisuje HISTORIA-ROZWOJU.md; daty commitów są datami ich utworzenia. Oryginał pozostaje na `main`.

| Etap | Commit | Zmiana | Dodane / usunięte linie | Wersja źródłowa |
| --- | --- | --- | --- | --- |
| 1 | `462bea4` | Zaimportowano pierwszą wersję list produktów | +1434 / -0 | `5753786` |
| 2 | `69d66ce` | Zmieniono nazwę wtyczki na ListNest | +1168 / -1427 | `bc29186` |
| 3 | `5c81fae` | Spisano zakres pierwszego wydania | +46 / -0 | `b590884` |
| 4 | `1d6e32f` | Zebrano inicjalizację w jednej klasie | +334 / -241 | `5328cba` |
| 5 | `b1b6e12` | Przeniesiono listy do sesji WooCommerce | +611 / -140 | `fb95966` |
| 6 | `d7624d5` | Przebudowano przyciski, popup i szablony list | +1641 / -392 | `84125f1` |
| 7 | `205241b` | Dodano podsumowanie produktów do Contact Form 7 | +1540 / -340 | `33e3d5e` |
| 8 | `abb6028` | Dodano własny formularz i zapis zapytań | +2518 / -301 | `9c43932` |
| 9 | `36f24cb` | Dodano walidację pełnego wyboru wariantu | +29 / -23 | `d5bff14` |
| 10 | `b32e1bc` | Ograniczono edycję list do obsługi sklepu | +37 / -19 | `d5bff14` |
| 11 | `fad60bd` | Dodano wspólną obsługę powrotu z formularzy i ochronę przed cache | +57 / -4 | `d5bff14` |
| 12 | `9932ac5` | Umożliwiono usuwanie niedostępnych produktów z listy | +37 / -30 | `d5bff14` |
| 13 | `6b7b334` | Zablokowano ponowne wysłanie tego samego zapytania | +65 / -17 | `d5bff14` |
| 14 | `b08c0e1` | Zachowano sesję klienta przy wysyłce przez CF7 | +15 / -3 | `d5bff14` |
| 15 | `ecbfab4` | Dodano ustawienia retencji i usuwania danych | +114 / -4 | `d5bff14` |
| 16 | `d69a684` | Dopasowano przyciski popupu do motywów blokowych | +3 / -3 | `d5bff14` |
| 17 | `b329bd0` | Dołączono licencję do paczki wtyczki | +339 / -0 | `fd93755` |
| 18 | `40e6b0e` | Uzupełniono wymagania i instrukcje wydania 1.0.0 | +52 / -51 | `fd93755` |
| 19 | `87a6dad` | Odświeżono polskie tłumaczenie i katalog komunikatów | +291 / -356 | `fd93755` |
| 20 | `3c8d510` | Przygotowano oddzielne środowisko z danymi testowymi | +203 / -0 | `4ca09b6` |
| 21 | `6ad007e` | Dodano kontrolę składni PHP i JavaScript | +18 / -0 | `4ca09b6` |
| 22 | `742f6f5` | Dodano regresje wariantów, uprawnień i retencji | +136 / -0 | `4ca09b6` |
| 23 | `606c1fe` | Dodano scenariusze formularza z osobnymi sesjami gości | +186 / -0 | `4ca09b6` |
| 24 | `a82ef5b` | Dodano test scalania wariantów przy ponownym dodaniu | +28 / -0 | `4ca09b6` |
| 25 | `3f7cde8` | Dodano regresję wysyłki formularza CF7 przez REST | +53 / -0 | `4ca09b6` |
| 26 | `44cda81` | Dodano scenariusze awarii zapisu i poczty | +51 / -0 | `4ca09b6` |
| 27 | `2375fb2` | Dodano regresję zachowania danych przy odinstalowaniu | +21 / -0 | `4ca09b6` |
| 28 | `17b697c` | Dodano narzędzie budowania katalogów tłumaczeń | +74 / -0 | `4ca09b6` |
| 29 | `3bf1588` | Dodano budowanie ZIP z samym katalogiem wtyczki | +56 / -0 | `4ca09b6` |
| 30 | `87a9a66` | Połączono regresje w macierz środowisk i kontrolę wydania | +296 / -0 | `4ca09b6` |
| 31 | `4289206` | Opisano instalację i konfigurację listy krok po kroku | +37 / -0 | `fc6b77a` |
| 32 | `ef786c6` | Zaktualizowano plan po przygotowaniu pierwszego wydania | +148 / -8 | `fc6b77a` |
| 33 | `db00662` | Zebrano zakres pierwszego wydania w changelogu | +13 / -0 | `fc6b77a` |
| 34 | `23b04a7` | Dodano opis projektu i zasady współpracy | +89 / -0 | `fc6b77a` |

Ostatni etap dodaje szablony zgłoszeń, dokument historii oraz tę mapę. Zmiany pośrednie przeszły kontrolę składni; testów funkcjonalnych nie uruchamiano osobno dla każdego commitu. Końcowe pliki wtyczki i narzędzi są identyczne z zachowaną wersją main.

Przykłady przeglądania zmian:

```powershell
git log --reverse --oneline codex/development-history
git log --stat codex/development-history -- listnest
git log -p codex/development-history -- listnest/includes/class-listnest-ajax.php
```
