# Calcolatore RAL → Netto
### Specifica di Progetto — Prototipo Web

---

## Descrizione del Progetto

Applicazione web che consente a un utente di inserire la propria **RAL (Retribuzione Annua Lorda)** e ottenere immediatamente una proiezione dettagliata del **salario netto annuale e mensile**, con il breakdown completo di tutte le trattenute previste dalla normativa italiana vigente.

Il prototipo è pensato per un caso standard e semplificato:
- Dipendente con contratto a tempo indeterminato
- Residente e lavorante in una delle **6 regioni supportate** (Lombardia, Piemonte, Veneto, Toscana, Emilia-Romagna, Lazio) — originariamente limitato a **Milano**, generalizzato in un secondo momento (vedi `docs/plans/plan-salary-calculator.md`, Fase 5)
- Nessuna agevolazione fiscale particolare
- CCNL Terziario/Commercio come riferimento

---

## Contesto di Utilizzo

Lo strumento si rivolge a chiunque voglia capire la propria busta paga: quante tasse paga, quanto riceve davvero in mano ogni mese e come si distribuisce il lordo tra le varie voci fiscali e previdenziali.

---

## Funzionalità Richieste

### Input utente

| Campo | Tipo | Obbligatorio |
|---|---|---|
| RAL (€) | Numero intero o decimale, maggiore di zero | Sì |
| Regione | Selezione tra le 6 regioni supportate (default in UI: Toscana) | Sì |

> **Nota:** i toggle "tredicesima/quattordicesima inclusa" e il selettore "tipo di contratto" previsti in una prima bozza sono stati rimossi. La RAL rappresenta già il totale annuo (nessuna formula dipende da come si distribuisce nei mesi), e il prototipo copre solo il caso a tempo indeterminato dichiarato nella consegna del progetto — un selettore determinato/indeterminato non avrebbe comunque cambiato alcun risultato, essendo il minimo garantito Art. 13 TUIR irraggiungibile assumendo un anno lavorativo pieno (vedi `docs/plan-salary-calculator.md`, sezione Rischi e note).
>
> **Nota (Fase 5):** il campo "Regione" è stato aggiunto in un secondo momento, dopo il completamento del prototipo originale (limitato a Milano/Lombardia), su richiesta esplicita di generalizzazione — vedi `docs/plans/plan-salary-calculator.md`.

### Output — Risultati da mostrare

| Voce | Descrizione |
|---|---|
| **Netto annuale** | Quanto il dipendente porta a casa in un anno |
| **Netto mensile medio** | Netto annuale distribuito su 12 mesi |
| **Contributi INPS** | Quota previdenziale a carico del dipendente (9,19%) |
| **IRPEF lorda** | Imposta sul reddito calcolata per scaglioni progressivi |
| **Detrazione lavoro dipendente** | Sgravio fiscale previsto per i lavoratori dipendenti |
| **IRPEF netta** | IRPEF lorda al netto della detrazione |
| **Addizionale Regionale** | Imposta regionale progressiva, variabile in base alla Regione scelta |
| **Addizionale Comunale** | Imposta comunale, approssimata sull'aliquota del capoluogo della Regione scelta |
| **Totale trattenute** | Somma di tutte le voci detratte |
| **Incidenza % sul lordo** | Percentuale complessiva di tassazione |

### Output informativi (bonus, se implementati)

- TFR mensile accantonato (informativo, non è una trattenuta)
- Rappresentazione grafica della distribuzione lordo/netto

---

## Comportamento dell'Applicazione

1. L'utente apre la pagina e vede il form di inserimento
2. Compila la RAL e seleziona la Regione
3. Clicca su **"Calcola"**
4. I risultati appaiono nella stessa pagina, sotto il form, senza ricaricare la pagina
5. Tutti i testi, label e risultati sono in **italiano**

---

## Checklist di Implementazione

### Setup progetto
- [ ] Nuovo progetto Laravel 12
- [ ] Installazione Bootstrap 5 (CDN)
- [ ] Struttura cartelle: `app/Services/`, `docs/`
- [ ] File di documentazione tecnica in `docs/`

### Logica di calcolo — `SalaryCalculatorService`
- [ ] Calcolo INPS dipendente (9,19% fino a €52.190, 10,19% oltre)
- [ ] Calcolo imponibile fiscale (RAL - INPS)
- [ ] Calcolo IRPEF lorda per scaglioni 2026
  - [ ] Scaglione 1: fino a €28.000 → 23%
  - [ ] Scaglione 2: €28.001–€50.000 → 33%
  - [ ] Scaglione 3: oltre €50.000 → 43%
- [ ] Calcolo detrazione per lavoro dipendente
  - [ ] Fascia ≤ €15.000 → €1.955 fisso
  - [ ] Fascia €15.001–€28.000 → formula proporzionale
  - [ ] Fascia €28.001–€50.000 → formula decrescente
  - [ ] Oltre €50.000 → €0
  - [x] ~~Detrazione minima garantita per contratto indeterminato (€1.380)~~ — non implementata: irraggiungibile assumendo un anno lavorativo pieno (la formula base non scende mai sotto €1.910 nella fascia dove il minimo si applicherebbe)
  - [ ] Bonus €65 per redditi tra €25.000 e €35.000
- [ ] Calcolo IRPEF netta (lorda - detrazione)
- [ ] Calcolo Addizionale Regionale per scaglioni, in base alla Regione scelta (Lombardia, Piemonte, Veneto, Toscana, Emilia-Romagna, Lazio — vedi `App\Enums\Regione`)
  - [ ] Lombardia: ≤ €15.000 → 1,23% · €15.001–€28.000 → 1,58% · €28.001–€50.000 → 1,72% · oltre €50.000 → 1,73%
- [ ] Calcolo Addizionale Comunale in base al capoluogo della Regione scelta (Milano 0,80% flat; le altre 5 regioni in `App\Enums\Regione::scaglioniAddizionaleComunale()`)
- [ ] Calcolo netto annuale
- [ ] Calcolo netto mensile medio (netto / 12)
- [ ] Calcolo TFR mensile informativo (RAL / 14 / 13,5)
- [ ] Gestione mensilità: 12, 13 o 14 a seconda degli input
- [ ] Restituzione struttura dati completa con tutte le voci

### Interattività — JavaScript
- [ ] Intercettare il click su "Calcola"
- [ ] Inviare i dati al controller via `fetch()` (POST)
- [ ] Aggiornare la sezione risultati nella pagina senza ricaricarla
- [ ] Mostrare/nascondere la sezione risultati in base allo stato
- [ ] Gestire errori di validazione restituiti dal server

### Interfaccia utente — Blade + Bootstrap 5
- [ ] Layout responsivo (mobile-friendly)
- [ ] Form con tutti i campi di input
- [ ] Pulsante "Calcola" ben visibile
- [ ] Sezione risultati con tabella breakdown trattenute
- [ ] Evidenza visiva su netto annuale e mensile (valori principali in grande)
- [ ] Badge o etichette per le percentuali
- [ ] (Opzionale) grafico a torta o a barre lordo vs netto
- [ ] Testi e label tutti in italiano

### Qualità e correttezza
- [ ] Verifica del calcolo con il caso di esempio: RAL €30.000 → netto €22.425,52/anno (€1.868,79/mese)
- [ ] Test manuale con RAL bassa (€15.000), media (€30.000) e alta (€80.000)
- [ ] Gestione edge case: RAL €0, RAL molto alta (€200.000+)

---

## Semplificazioni Dichiarate

Elementi noti ma esclusi per semplicità del prototipo:

| Elemento | Motivo dell'esclusione |
|---|---|
| Cuneo fiscale (L.199/2025) | Calcolo variabile e complesso |
| Contributi "altri enti" di categoria | Importo marginale (~0,14%) |
| Oneri deducibili (mutuo, spese mediche) | Caso non standard |
| Proporzionalizzazione giorni lavorati | Si assume anno intero |
| Addizionali anno precedente (rate mensili) | Il calcolatore mostra il dovuto annuale, non le rate |
| Buoni pasto | Parametro opzionale, non incluso nel caso base |
| Soglie di esenzione dell'addizionale comunale (es. Firenze <€25.000, Roma <€14.000) | Comunale approssimata sul capoluogo di regione, senza modellare le esenzioni per basso reddito di ogni comune |
| Addizionale comunale per comune reale dell'utente | Approssimata con l'aliquota del capoluogo di regione (oltre 7.900 comuni in Italia, fuori scope) |

---

## Stack Tecnico

| Componente | Tecnologia |
|---|---|
| Framework backend | Laravel 12 |
| Interattività frontend | JavaScript (Fetch API) |
| CSS / UI | Bootstrap 5 (CDN) |
| Logica di calcolo | `App\Services\SalaryCalculatorService` |
| Template | Blade |
| Grafici (opzionale) | Chart.js (CDN) |

---

## Riferimento per i Calcoli

Vedere `docs/02-salary-calculator-reference.md` per:
- Formule complete con fonti ufficiali
- Verifica empirica su buste paga reali
- Esempio di calcolo step-by-step (RAL €30.000)
- Aliquote aggiornate al 2026
