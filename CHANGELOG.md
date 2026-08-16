## [2026-08-15] - Grafico distribuzione RAL e fix cache asset

### Aggiunto
- Grafico a ciambella (Chart.js via CDN) con la distribuzione della RAL: netto, INPS, IRPEF netta, addizionale regionale, addizionale comunale, con tooltip e legenda
- Query string di versione (`?v={{ filemtime(...) }}`) su `app.css` e `salary-calculator.js`, per evitare che il browser serva una versione in cache dopo una modifica (nessun build step/Vite per gestirlo automaticamente)

### Corretto
- Il grafico veniva creato mentre il contenitore era ancora `d-none`: Chart.js misurava un'area 0x0 e restava vuoto anche dopo aver mostrato la sezione. Ora il grafico viene creato solo dopo aver reso visibile la sezione risultati

## [2026-08-15] - Interfaccia del calcolatore RAL (Fase 4)

### Aggiunto
- `resources/views/calcolator.blade.php`: form RAL, pulsante Calcola, pulsante Reset (visibile solo a campo compilato), sezione risultati (breakdown completo, badge bonus/incidenza), sezione errori dedicata, avviso Milano/Lombardia, avviso non bloccante per RAL > €200.000
- `public/css/app.css`: 2 regole minime non coperte da Bootstrap (max-width container, spinner input number)
- `public/js/salary-calculator.js`: `fetch()` POST verso `/calcola` con token CSRF, aggiornamento del DOM senza reload

### Corretto
- Messaggi di validazione della RAL in italiano espliciti nel `SalaryController` (senza, la chiave di traduzione appariva grezza, es. `validation.gt.numeric`, perché il progetto non pubblica i lang file del framework)

## [2026-08-15] - Copertura test calcolatore RAL (Fase 3)

### Aggiunto
- `tests/Unit/Salary/SalaryCalculatorServiceTest.php`: caso di riferimento RAL €30.000, RAL bassa/alta/molto alta, soglia massimale INPS, bonus detrazione €65
- `tests/Feature/Salary/SalaryControllerTest.php`: `GET /`, `POST /calcola` con RAL valida (struttura JSON) e casi di validazione (RAL assente/zero/negativa/non numerica)
- Configurazione test DB salvata come `none` (nessun database, coerente con il progetto)

### Modificato
- Spostato `docs/plan-salary-calculator.md` in `docs/plans/plan-salary-calculator.md`; aggiunto riepilogo di avanzamento delle fasi in cima al documento

## [2026-08-15] - Implementazione logica di calcolo (Fase 2)

### Aggiunto
- `app/Services/SalaryCalculatorService.php`: calcolo completo RAL → netto (INPS, IRPEF a scaglioni, detrazione lavoro dipendente con bonus, addizionale regionale Lombardia e comunale Milano, netto annuale/mensile, TFR informativo), verificato contro il caso di riferimento RAL €30.000 → netto €22.425,52/anno
- `app/Http/Controllers/SalaryController.php`: `index()` per la view, `calcola()` con validazione e try/catch, risposta in JSON
- Rotte `GET /` e `POST /calcola` in `routes/web.php`

### Modificato
- Rinominata la view `welcome.blade.php` in `calcolator.blade.php`
- Rimosso il selettore "tipo di contratto" dallo scope: verificato empiricamente che non incide mai sul risultato assumendo un anno lavorativo pieno (già semplificazione dichiarata); il prototipo copre solo il caso a tempo indeterminato
- Rimossi dalla spec i toggle "tredicesima/quattordicesima inclusa": nessuna formula dipende da questi valori, dato che la RAL rappresenta già il totale annuo

### Corretto
- `docs/02-salary-calculator-reference.md`: applicato il bonus di €65 (Art. 13 TUIR) mancante nell'esempio di calcolo RAL €30.000, corretto il valore del TFR mensile informativo (€158,73, non €148,15), aggiunte fonti governative primarie (Agenzia Entrate, Gazzetta Ufficiale, Regione Lombardia) e nota di trasparenza sull'aliquota INPS

## [2026-08-15] - Installazione Laravel Boost e piano calcolatore RAL

### Aggiunto
- Installato e configurato Laravel Boost (guidelines, skills, MCP server) per assistere Claude Code nella verifica del codice prodotto
- Creato `docs/plan-salary-calculator.md` con il piano di implementazione del calcolatore RAL → Netto

### Modificato
- Consolidato il changelog di branch (`CHANGELOG-main.md`) in `CHANGELOG.md`, dato che il lavoro procede direttamente su `main` senza branch di feature
- Applicati i fix di stile Pint richiesti dalle guidelines di Boost (import FQCN espliciti in `User.php`, `UserFactory.php`, `bootstrap/providers.php`, `config/auth.php`)

## [2026-08-14] - Configurazione ambiente senza database e pulizia docs

### Modificato
- Semplificato `.env.example` per un'app senza database (session/cache/queue su file/sync, rimossi DB/Redis/AWS/mail non utilizzati)
- Rimossi riferimenti a Livewire dalla checklist di `docs/01-project-spec.md` (stack confermato: Blade + Bootstrap 5 CDN + JS puro)
