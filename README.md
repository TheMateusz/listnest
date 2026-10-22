# ListNest

**Listy produktów i zapytania ofertowe dla WooCommerce.**

ListNest pozwala klientowi zebrać produkty, wybrać warianty i ilości, a następnie wysłać zapytanie do sklepu. Sprawdza się przy wycenach, próbkach i listach życzeń. Zapytania trafiają do panelu WordPressa oraz do systemu pocztowego sklepu.

![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777BB4)
![WordPress 6.5+](https://img.shields.io/badge/WordPress-6.5%2B-21759B)
![WooCommerce 9.0+](https://img.shields.io/badge/WooCommerce-9.0%2B-96588A)
![GPL v2 or later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)

## Możliwości

- Wiele niezależnych list z własnymi ustawieniami i przyciskami.
- Produkty proste i wariantowe, ilości, edycja oraz usuwanie pozycji.
- Listy dla gości i zalogowanych klientów w sesji WooCommerce.
- Wbudowany formularz zapytania lub integracja z Contact Form 7.
- Zapis zapytań i statusu powiadomienia w panelu sklepu.
- Eksport i usuwanie danych osobowych, retencja oraz opcjonalne czyszczenie przy odinstalowaniu.
- Szablony nadpisywane w motywie, polskie tłumaczenie i obsługa HPOS.

## Szybki start

1. Zainstaluj WordPress 6.5+, PHP 7.4+ i WooCommerce 9.0+.
2. Skopiuj katalog `listnest/` do `wp-content/plugins/` i aktywuj wtyczkę.
3. Utwórz listę w panelu **Lists**. Jej slug, np. `probki`, jest kluczem listy.
4. Włącz automatyczny przycisk na stronie produktu.
5. Utwórz stronę z shortcode:

```text
[listnest list_key="probki"]
```

W ustawieniach listy wybierz wbudowany formularz i podaj adres odbiorcy. Samodzielny przycisk i formularz można osadzić tak:

```text
[listnest_button list="probki"]
[listnest_form list="probki"]
```

Przy automatycznie włączonym formularzu pod listą nie dodawaj drugiego shortcode formularza. Wyklucz strony list i formularzy z pełnego cache oraz sprawdź wysyłkę poczty na swoim serwerze.

Pełny opis instalacji: [instrukcja](INSTRUKCJA-INSTALACJI.md). Tagi CF7, filtry, akcje i nadpisywanie szablonów: [dokumentacja wtyczki](listnest/readme.txt).

## Rozwój i testy

```powershell
npm run test:syntax
npm run test:plan
npm test
npm run build:release
```

Kontrola składni wymaga Node.js i PHP. Testy integracyjne wymagają dodatkowo Docker Desktop oraz `wp-env`; szczegóły i profile zawiera [instrukcja testowania](dev/TESTOWANIE.md). Paczka instalacyjna powstaje w `releases/`. Narzędzia i dane demonstracyjne z `dev/` służą wyłącznie środowisku testowemu.

[Raport dotychczasowych testów](dev/WYNIKI-TESTOW.md) opisuje konkretne przebiegi; nie jest gwarancją zgodności z każdym motywem, serwerem i cache. CI w tym repozytorium sprawdza składnię PHP oraz JavaScript.

## Struktura repozytorium

| Katalog | Zawartość |
| --- | --- |
| `listnest/` | Kod instalowanej wtyczki, szablony, zasoby i tłumaczenia |
| `dev/` | Regresje, dane testowe, budowanie wydania i raport |
| `.github/` | Kontrola składni oraz szablony zgłoszeń i pull requestów |

[Roadmapa](ROADMAP.md) · [Plan rozwoju](PLAN-ROZWOJU.md) · [Zmiany](CHANGELOG.md) · [Zasady współpracy](CONTRIBUTING.md)

## Historia projektu

Pierwszy zapisany commit jest importem wcześniejszego kodu z 29 września 2026. Model prac obejmujący 22 sierpnia–22 października został opisany oddzielnie jako [rekonstrukcja rozwoju](HISTORIA-ROZWOJU.md). Modelowe daty nie są datami wykonania commitów.

## Autor i licencja

Mateusz Matusiak (**TheMateusz**). Kod udostępniony na licencji [GPL-2.0-or-later](listnest/LICENSE).
