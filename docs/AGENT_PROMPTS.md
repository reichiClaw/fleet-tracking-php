# Agentenplan fuer die weitere Umsetzung

Dieses Dokument enthaelt die konkrete Aufgabenaufteilung fuer AI-Agenten. Die Anwendung ist bereits als Laravel-App ohne Docker angelegt. Folge-Agenten sollen bestehende Muster respektieren und keine Container-Konfiguration einfuehren.

## Agent 1: Auth, Rollen und Rechte haerten

Prompt:

```text
Arbeite in der bestehenden Laravel-Fuhrparkmanagement-App. Erweitere die vorhandene Rollenlogik sauber, ohne Docker einzufuehren. Rollen: admin und fleet_manager bleiben Pflicht. Pruefe alle Web- und API-Routen auf korrekten Zugriffsschutz, ergaenze Policies fuer Vehicle, Loan, Driver, Category und User, und schreibe Feature-Tests fuer erlaubte und verbotene Zugriffe. Behalte die einfache Bedienung bei und vermeide unnoetige neue Dependencies.
```

## Agent 2: Datenmodell und Workflows erweitern

Prompt:

```text
Arbeite im bestehenden Laravel-Projekt. Verfeinere das Fuhrpark-Datenmodell fuer reale Nutzung: Statusuebergaenge zentralisieren, parallele aktive Verleihvorgaenge auch auf DB-/Service-Ebene verhindern, Kilometer- und Betriebsstunden-Regeln testen, Audit Logs fuer alle Statuswechsel ausbauen. Schreibe Migrations nur rueckwaertskompatibel und fuege Feature-Tests fuer Check-in, Hersteller-Auschecken, Verleih und Rueckgabe hinzu.
```

## Agent 3: Fahrzeugverwaltung und Excel-Import verbessern

Prompt:

```text
Verbessere in der bestehenden Laravel-App die Fahrzeugverwaltung und den Excel/CSV-Import. Implementiere eine Import-Vorschau, validiere Pflichtspalten, zeige zeilenweise Fehler an, und erzeuge fuer jedes Fahrzeug weiterhin automatisch einen QR-Token. Unterstuetze Spalten: inventory_number, category, manufacturer, model, serial_number, license_plate, year, location, current_km, current_operating_hours. Fuege Tests fuer gueltige Importe, fehlerhafte Zeilen und Updates bestehender Fahrzeuge hinzu.
```

## Agent 4: QR-Codes und Etikettendruck

Prompt:

```text
Erweitere die vorhandene QR-Code-Funktion. Baue einen Admin-Bereich fuer QR-Code-Neugenerierung, Einzel- und Sammeldruck von Etiketten, und eine mobile Scan-Zielseite mit Schnellaktionen je nach Fahrzeugstatus. QR-Codes duerfen keine sensiblen Daten enthalten, sondern nur die bestehende Token-URL. Schreibe Tests fuer Scan, Token-Rotation und Zugriffsschutz.
```

## Agent 5: Foto-, Signatur- und SFTP-Speicher

Prompt:

```text
Arbeite in der Laravel-App an der Dateiablage. Haerte den Upload fuer Fahrzeugfotos und Signaturen: private Downloads ueber autorisierte Controller, MIME-/Groessenvalidierung, optionale SFTP-Disk per .env, Fehlerbehandlung bei nicht erreichbarem SFTP, und Tests fuer lokale Speicherung. Dateien sollen nicht direkt oeffentlich erreichbar sein. Speichere Metadaten weiter in vehicle_photos und vehicle_signatures.
```

## Agent 6: Frontend mobile-first ausbauen

Prompt:

```text
Verbessere das Blade/Tailwind-Frontend der bestehenden App fuer Tablet und Smartphone. Baue Wizard-artige Formulare fuer Check-in, Verleih und Rueckgabe, Fotovorschau vor Upload, bessere Status-Badges, leere Zustaende, und klarere Fehlermeldungen. Keine SPA einfuehren. Bestehende Routen und Controller moeglichst beibehalten. Ergaenze Feature- oder Browser-nahe Tests fuer zentrale Seiten.
```

## Agent 7: REST API fuer Mobile App vervollstaendigen

Prompt:

```text
Erweitere die bestehende Laravel Sanctum REST API unter /api/v1. Implementiere API Resources, konsistente JSON-Fehlerantworten, Pagination/Filter fuer Listen, Endpunkte fuer Fahrer, Kategorien, Fotos, Signaturen, Schaeden und Hersteller-Auschecken. Web und API sollen dieselben Services verwenden. Dokumentiere die API in docs/API.md und schreibe Feature-Tests fuer Auth, Rechte und Hauptworkflows.
```

## Agent 8: PDF-Protokolle und Historie

Prompt:

```text
Ergaenze PDF-Protokolle fuer Check-in, Hersteller-Auschecken, Verleih und Rueckgabe. Nutze eine Laravel-kompatible PDF-Bibliothek, speichere generierte PDFs privat ueber die konfigurierte Fleet-Disk, und biete autorisierte Downloads an. Jede Fahrzeugdetailseite soll eine klare Historie mit Protokoll-Downloads anzeigen. Fuege Tests fuer PDF-Erzeugung und Zugriffsschutz hinzu.
```

## Agent 9: Deployment und Betrieb

Prompt:

```text
Pruefe und erweitere die Deployment-Dokumentation fuer klassischen Apache/Nginx-Betrieb ohne Docker. Ergaenze Produktionscheckliste, Backup-/Restore-Skripte fuer Datenbank und storage/app/fleet, Update-Prozess mit Wartungsmodus, Cache-Befehle, Dateirechte und Hinweise fuer HTTPS hinter Reverse Proxy. Keine Container-Dateien einfuehren.
```

## Agent 10: Qualitaetssicherung

Prompt:

```text
Fuehre eine Qualitaetssicherung der bestehenden Laravel-App durch. Starte composer install, npm install, php artisan migrate:fresh --seed, php artisan test und npm run build. Behebe Fehler mit kleinen Commits. Erweitere Tests fuer kritische Statuslogik: Fahrzeuge nicht doppelt verleihen, Rueckgabe von inaktiven Loans verhindern, Hersteller-Auschecken bei verliehenem Fahrzeug verhindern, Rollenzugriffe pruefen.
```
