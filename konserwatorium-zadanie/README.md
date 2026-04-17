# Zadanie Rekrutacyjne - Konserwatorium Muzyczne

## Wymagania
- PHP >= 8.1 + Composer

## Uruchomienie

z poziomu folderu aplikacji

```bash
composer install
php -S localhost:8000 -t public/
```

Otwórz: http://localhost:8000

## Struktura projektu

- `config/csv_mapping.php` — mapowanie kolumn CSV
- `data/tickets.csv` — plik danych
- `src/Domain/` — obiekt domenowy Event
- `src/Repository/` — interfejs + implementacja CSV
- `src/Service/` — logika biznesowa
- `src/Controller/` — punkt wejścia
- `public/` — document root (`index.html` + `app.js` + `api/index.php`)

## API

| Endpoint | Opis |
|---|---|
| `GET /api/?action=events` | Lista eventów (opcjonalne filtry: city, dateFrom, dateTo, category) |
| `GET /api/?action=utm-ranking` | Top 10 kampanii UTM (wszystkie statusy) |
| `GET /api/?action=utm-ranking-confirmed` | Top 10 kampanii UTM (tylko status=confirmed) |

## Funkcjonalności frontendu

- **Lista eventów** z paginacją po stronie frontu (20 rekordów na stronę)
- **Filtrowanie** po mieście, zakresie dat i kategorii — wyniki odświeżają paginację od strony 1
- **Dwa rankingi UTM** obok siebie: wszystkie statusy vs. tylko confirmed 

## Uwagi do implementacji

- Ranking UTM (wszystkie statusy) liczy `ticket_qty` niezależnie od statusu — zadanie nie sprecyzowało filtru,
- Ranking UTM (confirmed) liczy wyłącznie rekordy ze `status=confirmed` — w związku z powyższą informacją,
- Puste wartości `utm_campaign` są pomijane dla obu wylistowań rankingowych,
- Brakujące wartości w rekordach CSV są traktowane jako wartości domyślne (`""` / `0` / `false`) — zakładam poprawność pliku wejściowego,
- Zmiana nazw kolumn w CSV: edycja tylko `config/csv_mapping.php`,
- Zmiana struktury CSV (nowe/usunięte pola): edycja `config/csv_mapping.php` + `src/Domain/Event.php`, jeśli zmieniane pole jest używane w logice Service (np. `status`, `ticketQty`, `utmCampaign`), wymagana jest również zmiana odpowiedniego Service
- Podmiana CSV na bazę danych: nowa klasa implementująca `EventRepositoryInterface`
- Walidacja inputu znajduje się w kontrolerze — przy rozbudowie aplikacji warto wyciągnąć ją do osobnej klasy, przy dwóch endpointach uznałem to za delikatny 'overkill',

### Uwagi do przesłanego pliku tickets.csv

- Utworzony na potrzeby testów
- Jeden `event_id` może mieć wiele zamówień — `totalTickets` to suma `ticket_qty` dla `status=confirmed`,
- Kolumna `category` jest przypisana do **zamówienia** — ten sam `event_id` może wystąpić w zamówieniach obu kategorii (`kids` i `adults`), dlatego sumy wyników dla poszczególnych kategorii mogą przekraczać łączną liczbę unikalnych eventów,