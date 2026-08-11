# API аэропортов

## Популярные аэропорты

`GET /api/airports/popular`

Возвращает список активных популярных аэропортов для быстрых подсказок в форме поиска рейсов.

### Ответ

```json
{
  "data": [
    {
      "iata_code": "SVO",
      "city": "Moscow",
      "name": "Sheremetyevo International Airport",
      "label": "Moscow — Sheremetyevo International Airport (SVO)",
      "additional_names": "Moscow — Sheremetyevo International Airport (SVO)\nSVO\nMOW\nRU",
      "is_popular_destination": true
    }
  ]
}
```

## Все аэропорты

`GET /api/airports/search`

Возвращает весь активный список аэропортов для автодополнения и справочников.  
Если не передавать `q`, метод вернёт все активные аэропорты; для полного списка используйте `limit=5000`.

### Параметры

- `q` — строка поиска по городу, названию, IATA или additional names
- `limit` — лимит результатов, от `1` до `5000`

### Ответ

```json
{
  "data": [
    {
      "code": "SVO",
      "iata_code": "SVO",
      "city": "Moscow",
      "name": "Sheremetyevo International Airport",
      "label": "Moscow — Sheremetyevo International Airport (SVO)",
      "additional_names": "Moscow — Sheremetyevo International Airport (SVO)\nSVO\nMOW\nRU",
      "is_popular_destination": true
    }
  ]
}
```

## Поиск рейсов

`GET /api/flights/search`

Ищет цены по маршруту и диапазону дат.

### Параметры

- `origin` — IATA вылета
- `destination` — IATA прилёта
- `from` — дата начала диапазона `Y-m-d`
- `to` — дата конца диапазона `Y-m-d`
- `one_way` — `true`/`false`
- `direct` — `true`/`false`
- `length` — длительность поездки в днях для round-trip

### Ответ

```json
{
  "success": true,
  "meta": {
    "origin": "KZN",
    "destination": "KGD",
    "from": "2026-09-01",
    "to": "2026-09-30",
    "one_way": false,
    "direct": false,
    "length": 7,
    "months": ["2026-09"],
    "total_days": 30,
    "covered_days": 18,
    "coverage_percent": 60,
    "generated_at": "2026-08-10T11:49:39Z",
    "partner_id": "..."
  },
  "analysis": {
    "classification": "🟢 Выгодно",
    "score": 78.4,
    "best": {
      "date": "2026-09-05",
      "price": 8100,
      "airline": "N4",
      "flight_number": "731",
      "departure_at": "2026-09-05T15:10:00+03:00",
      "arrival_at": "2026-09-05T23:55:00+02:00",
      "transfers": 1,
      "duration": 585,
      "stops": ["LED"],
      "source": "v2/month-matrix"
    },
    "best_flights": [
      {
        "date": "2026-09-05",
        "price": 8100,
        "airline": "N4",
        "flight_number": "731",
        "departure_at": "2026-09-05T15:10:00+03:00",
        "return_at": "2026-09-12T18:30:00+02:00",
        "arrival_at": "2026-09-05T23:55:00+02:00",
        "transfers": 1,
        "duration": 585,
        "stops": ["LED"],
        "legs": {
          "out": {
            "origin": "KZN",
            "destination": "KGD",
            "date": "2026-09-05",
            "departure_at": "2026-09-05T15:10:00+03:00",
            "arrival_at": "2026-09-05T23:55:00+02:00",
            "airline": "N4",
            "flight_number": "731",
            "transfers": 1,
            "duration": 585,
            "stops": ["LED"]
          },
          "back": {
            "origin": "KGD",
            "destination": "KZN",
            "date": "2026-09-12",
            "departure_at": "2026-09-12T18:30:00+02:00",
            "arrival_at": "2026-09-12T22:50:00+03:00",
            "airline": "N4",
            "flight_number": null,
            "transfers": 0,
            "duration": 200,
            "stops": []
          }
        }
      }
    ]
  },
  "coverage": [
    {
      "month": "2026-09",
      "days": 30,
      "covered_days": 18,
      "coverage_percent": 60,
      "window_start": "2026-09-01",
      "window_end": "2026-09-30"
    }
  ],
  "diagnostics": {
    "missing_dates_count": 12,
    "missing_dates_sample": ["2026-09-03"],
    "source_coverage": {
      "v1/calendar": { "covered_days": 10, "total_days": 30, "coverage_percent": 33.3 },
      "v2/month-matrix": { "covered_days": 12, "total_days": 30, "coverage_percent": 40 },
      "v2/latest": { "covered_days": 9, "total_days": 30, "coverage_percent": 30 }
    }
  },
  "requests": []
}
```

## Создание подписки

`POST /api/subscriptions`

Создаёт подписку на маршрут.

### Параметры

- `user_id` — ID пользователя, если запрос не идёт от авторизованного пользователя
- `origin_iata` — IATA аэропорта вылета
- `destination_iata` — IATA аэропорта прилёта, необязательно
- `date_from` — дата начала периода вылета (`YYYY-MM-DD`), необязательно
- `date_to` — дата окончания периода вылета (`YYYY-MM-DD`), необязательно, не раньше `date_from`
- `trip_type` — `round_trip` или `one_way`
- `max_desired_price` — максимальная желаемая цена, необязательно
- `min_stay_days` — минимальный срок пребывания, необязательно
- `max_stay_days` — максимальный срок пребывания, необязательно
- `channel` — `email` или `telegram`, по умолчанию `email`
- `is_active` — `true`/`false`, по умолчанию `true`

### Пример запроса

```json
{
  "user_id": 1,
  "origin_iata": "KZN",
  "destination_iata": "AER",
  "date_from": "2026-09-01",
  "date_to": "2026-12-31",
  "trip_type": "round_trip",
  "max_desired_price": 18000,
  "min_stay_days": 3,
  "max_stay_days": 14,
  "channel": "email",
  "is_active": true
}
```

## Сигналы для главной страницы

`GET /api/signals?limit=8`

Возвращает последние найденные сигналы для ленточного блока на главной.
В выдачу попадают только one-way сигналы со скидкой больше 50%, без повторов по направлению.

### Параметры

- `limit` — количество сигналов, по умолчанию `8`, максимум `20`

### Пример ответа

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "origin_iata": "KZN",
      "destination_iata": "MOW",
      "origin_city": "Казань",
      "destination_city": "Москва",
      "route_label": "Казань - Москва",
      "price": 3000,
      "price_label": "3 000 ₽",
      "deviation_percent": -45,
      "deviation_label": "-45%",
      "departure_date": "2026-09-01",
      "return_date": "2026-09-10",
      "score": 89,
      "created_at": "2026-08-10T20:10:00.000000Z"
    }
  ]
}
```

### Ответ

```json
{
  "success": true,
  "data": {
    "id": 12,
    "user_id": 1,
    "origin_iata": "KZN",
    "destination_iata": "AER",
    "date_from": "2026-09-01",
    "date_to": "2026-12-31",
    "trip_type": "round_trip",
    "max_desired_price": 18000,
    "min_stay_days": 3,
    "max_stay_days": 14,
    "channel": "email",
    "is_active": true,
    "created_at": "2026-08-10T19:31:00.000000Z"
  }
}
```
