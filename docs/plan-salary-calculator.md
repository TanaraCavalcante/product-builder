# Piano: Calcolatore RAL → Netto

Data: 2026-08-15
Stato: IN REVISIONE

## Obiettivo

Costruire un prototipo web (Laravel 12 + Blade) in cui l'utente inserisce la RAL (e opzioni correlate) e, cliccando "Calcola", vede sulla stessa pagina — senza reload — il breakdown completo del salario netto secondo la normativa italiana per un dipendente a tempo indeterminato residente a Milano (CCNL Terziario/Commercio), come da `docs/01-project-spec.md`.

Nessun login, nessuna persistenza: ogni richiesta è un calcolo stateless.

## Approccio tecnico

- **Backend**: `SalaryCalculatorService` (PHP puro, nessun Eloquent) contiene tutta la logica di calcolo, organizzata a step commentati nello stesso ordine di `docs/02-salary-calculator-reference.md`. `SalaryController` valida l'input, chiama il Service dentro un try/catch e risponde in JSON.
- **Frontend**: Blade puro + Bootstrap 5/Font Awesome/Chart.js via CDN, nessun build step (Vite/npm già rimossi dal progetto). JS separato in `public/js/`, incluso via `<script src>`, che fa `fetch()` POST verso `/calcola` e aggiorna il DOM.
- **Nessun database**: sessione/cache/queue già configurati su file/sync (vedi `.env.example`).

## Attività

### Fase 1 — Database e modelli
- [ ] Nessuna: il prototipo non usa persistenza, non ci sono migration né modelli da creare.

### Fase 2 — Controller e rotte (logica di calcolo)
- [ ] Creare `app/Services/SalaryCalculatorService.php` con un metodo `calcola(array $input): array`, diviso in step commentati: INPS → imponibile fiscale → IRPEF lorda per scaglioni 2026 → detrazione lavoro dipendente (con minimo garantito differenziato per tipo di contratto: €1.380 indeterminato / €690 determinato, sotto €28.000) → IRPEF netta → addizionale regionale Lombardia per scaglioni → addizionale comunale Milano (0,80% flat) → netto annuale → netto mensile medio (annuale / 12) → TFR mensile informativo (RAL / 14 / 13,5)
- [ ] Validazione input: `ral` obbligatorio, numerico, maggiore di zero (bloccante); `tredicesima_inclusa`, `quattordicesima_inclusa` booleani; `tipo_contratto` opzionale (`indeterminato` default / `determinato`)
- [ ] Creare `app/Http/Controllers/SalaryController.php` con `index()` (GET `/`, mostra la view) e `calcola(Request $request)` (POST `/calcola`, valida, chiama il Service in try/catch, ritorna JSON con risultato oppure errore leggibile — mai uno stacktrace all'utente)
- [ ] Aggiungere le rotte in `routes/web.php`

### Fase 3 — Test
- [ ] Test unitari su `SalaryCalculatorService`: caso di riferimento RAL €30.000 → netto €22.360,49/anno (€1.863,37/mese), RAL bassa (€15.000), RAL alta (€80.000), edge case RAL molto alta (€200.000+)
- [ ] Test differenza detrazione minima tra contratto determinato e indeterminato per reddito ≤ €28.000
- [ ] Test di validazione: RAL €0 o negativa deve restituire errore, non un calcolo
- [ ] Test feature sulla rotta POST `/calcola` (status code e struttura della risposta JSON)
- [ ] Generare gli stub con `/tanas:test` prima di implementare (ciclo TDD red-green-refactor)

### Fase 4 — Frontend
- [ ] Definire e validare con l'utente il design (layout, disposizione form/risultati, gerarchia visiva dei valori principali) prima di scrivere qualunque Blade
- [ ] Creare la view Blade (form + sezione risultati inizialmente nascosta + sezione errori dedicata), pulita e organizzata
- [ ] Includere via CDN: Bootstrap 5, Font Awesome, Chart.js (per il grafico opzionale lordo/netto)
- [ ] Usare esclusivamente classi Bootstrap esistenti per il layout responsivo (grid, form-control, card, badge, ecc.)
- [ ] Creare `public/css/app.css`, file separato e minimale, solo per gli aggiustamenti che le classi Bootstrap non coprono
- [ ] Creare `public/js/salary-calculator.js`, file separato incluso via `<script src>`, che intercetta il submit, fa `fetch()` POST a `/calcola`, aggiorna il DOM con i risultati o mostra gli errori nella sezione dedicata
- [ ] Aggiungere l'avviso non bloccante ("sei sicuro che il valore sia giusto?") quando RAL > €200.000, gestito lato JS sotto il campo input
- [ ] Verificare la responsività su mobile, tablet e desktop

## Migration necessarie

Nessuna.

## Rotte da aggiungere

| Metodo | URI | Controller/Azione | Note |
|---|---|---|---|
| GET | `/` | `SalaryController@index` | Mostra il form |
| POST | `/calcola` | `SalaryController@calcola` | Valida, calcola, ritorna JSON |

## Rischi e note

- Le aliquote regionali/comunali sono fisse per Milano/Lombardia; supportare altre regioni richiederebbe una tabella di aliquote configurabile — esplicitamente fuori scope (vedi nota su Toscana in `docs/02-salary-calculator-reference.md` sezione 6.3).
- Dipendenza da CDN esterni (Bootstrap, Font Awesome, Chart.js): senza connessione internet l'app perde stile e interattività, non essendoci fallback locale.
- Il design visivo della Fase 4 non è stato ancora definito con l'utente: è un passo esplicito da completare prima di iniziare l'implementazione Blade, non un dettaglio da improvvisare in corsa.
- Le semplificazioni dichiarate in `docs/02-salary-calculator-reference.md` (sezione 15 — cuneo fiscale escluso, nessun onere deducibile, TFR con divisore legale, ecc.) restano valide e vanno citate in un eventuale colloquio.

> NOTA: dato que cada regioao pode obter um calcolo diferente, lembrar de manter na view que o calculo de RAL foram baseadas nas regas de Milano, informado as leis de taxaçao daquelea regiao, caso utente considere pra outro lugar o valor pode sair defasado.
executar um plano por vez de modo a analisar bem o que foi escrito em cada fase, so passar a prossima em caso de autorizaçao.
