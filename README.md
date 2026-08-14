# Calcolatore RAL → Netto

Prototipo web che permette di inserire la RAL (Retribuzione Annua Lorda) e ottenere il breakdown completo del salario netto secondo la normativa fiscale italiana (contratto a tempo indeterminato, residenza a Milano, CCNL Terziario/Commercio).

## Stack

- Laravel 12
- Blade + JavaScript vanilla (Fetch API)
- Bootstrap 5 (CDN)
- Chart.js (CDN, opzionale)

## Documentazione

- [`docs/01-project-spec.md`](docs/01-project-spec.md) — specifica funzionale del prototipo
- [`docs/02-salary-calculator-reference.md`](docs/02-salary-calculator-reference.md) — riferimento tecnico per le formule di calcolo (INPS, IRPEF, addizionali, TFR)

## Setup locale

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```
