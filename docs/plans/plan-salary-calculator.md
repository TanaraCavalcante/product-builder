# Piano: Calcolatore RAL → Netto

Data: 2026-08-15
Stato: APPROVATO

**Progresso**: Fase 1 (N/A) → Fase 2 ✅ completata → Fase 3 ✅ completata → Fase 4 ⏳ da iniziare (design da definire prima del Blade)

**Esecuzione**: una fase alla volta. Si passa alla fase successiva solo dopo che l'utente ha analizzato e autorizzato esplicitamente il lavoro della fase corrente.

## Obiettivo

Costruire un prototipo web (Laravel 12 + Blade) in cui l'utente inserisce la RAL (e opzioni correlate) e, cliccando "Calcola", vede sulla stessa pagina — senza reload — il breakdown completo del salario netto secondo la normativa italiana per un dipendente a tempo indeterminato residente a Milano (CCNL Terziario/Commercio), come da `docs/01-project-spec.md`.

Nessun login, nessuna persistenza: ogni richiesta è un calcolo stateless.

## Approccio tecnico

- **Backend**: `SalaryCalculatorService` (PHP puro, nessun Eloquent) contiene tutta la logica di calcolo, organizzata a step commentati nello stesso ordine di `docs/02-salary-calculator-reference.md`. `SalaryController` valida l'input, chiama il Service dentro un try/catch e risponde in JSON.
- **Frontend**: Blade puro + Bootstrap 5/Font Awesome/Chart.js via CDN, nessun build step (Vite/npm già rimossi dal progetto). JS separato in `public/js/`, incluso via `<script src>`, che fa `fetch()` POST verso `/calcola` e aggiorna il DOM.
- **Nessun database**: sessione/cache/queue già configurati su file/sync (vedi `.env.example`).

## Attività

### Fase 1 — Database e modelli — N/A
- [x] Non necessaria: il prototipo non usa persistenza, non ci sono migration né modelli da creare.

### Fase 2 — Controller e rotte (logica di calcolo) — COMPLETATA (2026-08-15)
- [x] Creato `app/Services/SalaryCalculatorService.php` con metodo `calcola(float $ral): array` (il tipo di contratto è stato rimosso dai parametri, vedi sezione Rischi e note), diviso in step commentati (stesso ordine di `docs/02-salary-calculator-reference.md`). Verificato via tinker contro il caso di riferimento (RAL €30.000 → netto €22.425,52/anno) e contro RAL basse/alte/molto alte (€15.000, €80.000, €200.000): risultato coerente in tutti gli scaglioni
- [x] Validazione input spostata sul Controller (Laravel `validate()`): `ral` obbligatorio, numerico, maggiore di zero; `tipo_contratto` opzionale (`indeterminato` default / `determinato`). Il Service assume input già validi — separazione netta tra livello HTTP e logica di calcolo pura
- [x] Creato `app/Http/Controllers/SalaryController.php` con `index()` (GET `/`) e `calcola(Request $request)` (POST `/calcola`, valida, chiama il Service in try/catch, ritorna JSON — mai uno stacktrace)
- [x] Aggiunte le rotte in `routes/web.php`

### Fase 3 — Test — COMPLETATA (2026-08-15)
- [x] `tests/Unit/Salary/SalaryCalculatorServiceTest.php`: caso di riferimento RAL €30.000 → netto €22.425,52/anno (€1.868,79/mese), RAL bassa (€15.000), RAL alta (€80.000, soglia massimale INPS), edge case RAL molto alta (€200.000)
- [x] Test del bonus di detrazione €65 per imponibile fiscale tra €25.000 e €35.000 (attivo/non attivo)
- [x] `tests/Feature/Salary/SalaryControllerTest.php`: `GET /` (200), `POST /calcola` con RAL valida (200 + struttura JSON completa), RAL assente/zero/negativa/non numerica (422 con errori di validazione)
- [x] Configurazione test DB: `none` (nessun database, coerente con il progetto) — salvata con `manage_test_db_config`
- [x] Suite completa: 15 test, 61 assertion, tutti verdi. Pint conforme

### Fase 4 — Frontend — DA FARE
- [ ] Definire e validare con l'utente il design (layout, disposizione form/risultati, gerarchia visiva dei valori principali) prima di scrivere qualunque Blade
- [ ] Creare la view Blade (form + sezione risultati inizialmente nascosta + sezione errori dedicata), pulita e organizzata
- [ ] Includere via CDN: Bootstrap 5, Font Awesome, Chart.js (per il grafico opzionale lordo/netto)
- [ ] Usare esclusivamente classi Bootstrap esistenti per il layout responsivo (grid, form-control, card, badge, ecc.)
- [ ] Creare `public/css/app.css`, file separato e minimale, solo per gli aggiustamenti che le classi Bootstrap non coprono
- [ ] Creare `public/js/salary-calculator.js`, file separato incluso via `<script src>`, che intercetta il submit, fa `fetch()` POST a `/calcola`, aggiorna il DOM con i risultati o mostra gli errori nella sezione dedicata
- [ ] Aggiungere l'avviso non bloccante ("sei sicuro che il valore sia giusto?") quando RAL > €200.000, gestito lato JS sotto il campo input
- [ ] Verificare la responsività su mobile, tablet e desktop
- [ ] Includere nella view un avviso visibile (non invasivo) che il calcolo si basa sulle regole fiscali di Milano/Lombardia, cosicché sia chiaro all'utente che il valore risulta impreciso se applicato ad altre regioni

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
- I toggle "tredicesima/quattordicesima inclusa" previsti in `docs/01-project-spec.md` sono stati rimossi dal form: la RAL rappresenta già il totale annuo (sezione 3 di `docs/02-salary-calculator-reference.md`), quindi non esiste alcuna formula che questi toggle modificherebbero — mantenerli come campi puramente informativi sarebbe stato fuorviante per l'utente.
- Il selettore "tipo di contratto" (determinato/indeterminato) è stato rimosso: verificato empiricamente (RAL €8.000-€32.000) che il minimo garantito Art. 13 TUIR non è mai raggiunto quando si assume un anno lavorativo pieno (semplificazione già dichiarata), quindi la formula produce lo stesso risultato per entrambi i tipi di contratto. Il prototipo copre solo il caso a tempo indeterminato, coerente con la consegna originale del progetto ("il dipendente è un impiegato a tempo indeterminato").

> NOTA (integrata): dato que cada regioao pode obter um calcolo diferente, lembrar de manter na view que o calculo de RAL foram baseadas nas regas de Milano, informado as leis de taxaçao daquelea regiao, caso utente considere pra outro lugar o valor pode sair defasado.
> — Recepita come task in Fase 4 ("avviso visibile... regole fiscali di Milano/Lombardia").
>
> executar um plano por vez de modo a analisar bem o que foi escrito em cada fase, so passar a prossima em caso de autorizaçao.
> — Recepita come regola di esecuzione, vedi riga "Esecuzione" sotto lo Stato in cima al documento.
