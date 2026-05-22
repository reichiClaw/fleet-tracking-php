# REST API v1

Basis-URL: `/api/v1`

## Authentifizierung

### Login

```http
POST /api/v1/login
Accept: application/json
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password",
  "device_name": "mobile-app"
}
```

Antwort:

```json
{
  "token": "1|...",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

Danach:

```http
Authorization: Bearer <token>
Accept: application/json
```

## Fahrzeuge

```http
GET /api/v1/vehicles
GET /api/v1/vehicles/{vehicle}
POST /api/v1/vehicles/scan/{token}
POST /api/v1/vehicles/{vehicle}/check-in
POST /api/v1/vehicles/{vehicle}/loan
```

Filter fuer Fahrzeugliste:

```text
?status=available&search=GC&per_page=25
```

## Check-in Payload

```json
{
  "km": 120,
  "operating_hours": 44.5,
  "location": "Hauptlager",
  "external_partner": "Hersteller XY",
  "condition_notes": "Keine neuen Schaeden",
  "damage_description": null,
  "damage_severity": "minor"
}
```

## Verleih Payload

```json
{
  "borrower_type": "external_company",
  "company_name": "Subfirma Beispiel",
  "borrower_name": "Max Mustermann",
  "phone": "+49 000 000000",
  "planned_return_at": "2026-05-23 12:00:00",
  "km": 120,
  "operating_hours": 44.5,
  "location": "Ausgabe",
  "condition_notes": "Uebergabe ohne neue Schaeden"
}
```

## Rueckgabe

```http
POST /api/v1/loans/{loan}/return
```

Payload wie Check-in.
