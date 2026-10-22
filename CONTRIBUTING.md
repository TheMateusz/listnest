# Praca nad ListNest

Zmiany rozwijaj na osobnej gałęzi. W opisie zgłoszenia podaj oczekiwane zachowanie, kroki odtworzenia i wersje WordPressa, WooCommerce, PHP oraz używanego motywu. Usuń dane klientów z logów i zrzutów.

Przed zmianą kodu przeczytaj [instrukcję testowania](dev/TESTOWANIE.md). Uruchom `npm run test:syntax`, a dla zmian zachowania odpowiednie testy integracyjne w oddzielnej instalacji. W pull requeście podaj wykonane kontrole i ich wyniki; zaznacz, których nie uruchomiono.

## Commity

Jeden commit powinien realizować spójny cel i zawierać wymagane zależności. Stosuj krótki tytuł, np. `fix: validate variation attributes before adding an item`. W treści wyjaśnij przyczynę, wynik i ograniczenia, jeśli nie wynikają z diffu.

Używaj prefiksów `feat`, `fix`, `refactor`, `test`, `build`, `ci` i `docs`. Zapisuj rzeczywiste daty commitów. Rekonstrukcje historyczne oznaczaj jawnie. Nie dodawaj danych środowiska, baz, logów, paczek ani danych klientów.

## Pull request

Opisz problem i efekt zmiany. Dla zmian interfejsu dołącz zrzut z danymi demonstracyjnymi. Aktualizuj dokumentację i tłumaczenia, jeżeli zmienia się publiczne zachowanie lub tekst interfejsu.
