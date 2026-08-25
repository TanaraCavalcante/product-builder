# Calcolatore RAL → Netto

Prototipo web che permette di inserire la RAL (Retribuzione Annua Lorda) e la Regione di residenza/lavoro, e ottenere, sulla stessa pagina e senza reload, il breakdown completo del salario netto secondo la normativa fiscale italiana: contributi INPS, IRPEF a scaglioni, detrazione lavoro dipendente, addizionali regionale e comunale (variabili in base alla Regione scelta, approssimata sul capoluogo), netto annuale/mensile e TFR informativo. Il calcolo assume un dipendente a tempo indeterminato (CCNL Terziario/Commercio). Regioni supportate: Lombardia, Piemonte, Veneto, Toscana, Emilia-Romagna, Lazio.

Prototipo stateless: nessun login, nessun database.

## Stack

- Laravel 12
- Blade + JavaScript vanilla (Fetch API)
- Bootstrap 5 e Font Awesome (CDN)
- Chart.js (CDN) — grafico a ciambella della distribuzione della RAL

## Documentazione

- [`docs/01-project-spec.md`](docs/01-project-spec.md) — specifica funzionale del prototipo
- [`docs/02-salary-calculator-reference.md`](docs/02-salary-calculator-reference.md) — riferimento tecnico per le formule di calcolo (INPS, IRPEF, addizionali, TFR)
- [`docs/plans/plan-salary-calculator.md`](docs/plans/plan-salary-calculator.md) — piano di implementazione, completato

## Setup locale

Nessun database richiesto.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```
