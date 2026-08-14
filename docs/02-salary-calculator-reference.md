# Documentazione di Riferimento — Calcolatore RAL → Netto
### Sistema Tributario Italiano per Lavoro Dipendente (2025–2026)

> **Scopo:** Questo documento costituisce la base tecnica per l'implementazione del prototipo di calcolatore del salario netto. Combina l'analisi di buste paga reali (anonimizzate) con fonti ufficiali italiane.
>
> **Nota sulla privacy:** I nomi utilizzati nei confronti sono fittizi. I valori numerici e le aliquote sono reali e verificati su documentazione ufficiale.

---

## 1. Fonti Ufficiali Consultate

| Fonte | Contenuto |
|---|---|
| [Agenzia delle Entrate — Aliquote IRPEF](https://www.agenziaentrate.gov.it/portale/imposta-sul-reddito-delle-persone-fisiche-irpef-/aliquote-e-calcolo-dell-irpef-cittadini) | Scaglioni e aliquote IRPEF 2025/2026 |
| [Circolare n.4 del 16/05/2025 — Agenzia Entrate](https://www.agenziaentrate.gov.it/portale/documents/20143/8410823/Circolare+lavoro+dipendente+LB2025+DD+IRPEF+n.+4+del+16+maggio+2025.pdf/36979eaa-9fc5-a4ec-a7aa-136497c53f91) | Lavoro dipendente, LB2025, detrazioni |
| [Directio — Addizionali Regionali 2026](https://directio.it/News/Details/11189/addizionale-regionale-irpef-aliquote-2026) | Aliquote Lombardia e Toscana per scaglione |
| [TuttoCalcolo — Addizionale Milano](https://www.tuttocalcolo.it/addizionale-irpef/lombardia/milano) | Addizionale comunale Milano: 0,80% |
| [CAF Informa — Detrazioni Lavoro Dipendente](https://cafinforma.it/detrazioni-lavoro-dipendente-2026/) | Formule detrazioni per reddito 2026 |
| [GEPS — Legge 199/2025](https://www.geps.it/legge-di-bilancio-2026-l-199-2025-10905/) | Cuneo fiscale strutturale 2026 |
| Busta paga reale "Marco Ferretti" — Luglio 2026 | CCNL Terziario, 3^ livello, indeterminato |
| Busta paga reale "Sofia Monteiro" — Maggio 2026 | CCNL Terziario, 5^ livello, determinato |

---

## 2. Struttura del Sistema — Flusso di Calcolo

```
RAL (Retribuzione Annua Lorda)
  │
  ├── (-) Contributi INPS dipendente (9,19%)   → Imponibile previdenziale
  │
  └── Imponibile fiscale (RAL - INPS)
        │
        ├── IRPEF lorda (scaglioni progressivi)
        │     └── (-) Detrazione per lavoro dipendente
        │           └── = IRPEF netta
        │
        ├── (-) Addizionale Regionale (Lombardia: progressiva per scaglioni)
        ├── (-) Addizionale Comunale (Milano: 0,80% flat)
        │
        └── (+) Benefici cuneo fiscale (L.199/2025, se applicabile)

NETTO ANNUALE = RAL - INPS - IRPEF netta - Add.Reg. - Add.Com.
NETTO MENSILE = NETTO ANNUALE / 12  (distribuzione media annua)
```

---

## 3. CCNL Applicato — Terziario Confcommercio (H011)

Entrambe le buste paga analizzate appartengono al **CCNL Terziario/Commercio (Confcommercio)**, il CCNL più diffuso in Italia (circa 3 milioni di lavoratori).

### Composizione della retribuzione mensile

| Voce | Descrizione | Obbligatorietà |
|---|---|---|
| `MC01` Paga Base | Stipendio base per livello/scatto | Obbligatorio CCNL |
| `MC02` Contingenza | Indennità storica (dal 1975) | Obbligatorio |
| `MC04` III Elemento | Indennità regionale (accordi territoriali) | Obbligatorio |
| `MC09` Superminimo | Indennità individuale negoziata | Facoltativo |
| `MCT` Totale Retribuzione | Somma di tutte le voci | = RAL/12 (o /14) |

### Mensilità — 14 pagamenti annuali

Il CCNL Terziario prevede **14 mensilità**:
- **12 mensilità ordinarie** (gennaio–dicembre)
- **Tredicesima (13°)**: pagata a dicembre, importo = 1 mensilità
- **Quattordicesima (14°)**: pagata a luglio, importo proporzionale ai mesi lavorati

> **Impatto sul calcolatore:** Se l'utente inserisce la RAL, questa **include già** le 14 mensilità.
> Mensilità ordinaria = RAL / 14
> Il netto mensile medio si calcola distribuendo il netto annuale su 12 mesi.

---

## 4. Contributi INPS — Quota a Carico del Dipendente

### Aliquote 2025–2026 (verificate su entrambe le buste)

| Soglia reddito annuo | Aliquota dipendente | Aliquota datore di lavoro |
|---|---|---|
| Fino a €52.190 | **9,19%** | 23,81% |
| Oltre €52.190 | **10,19%** (+1% IVS) | 23,81% |

**Verifica empirica:**
- Marco Ferretti (€2.154,30 lordo/mese): €197,95 / €2.154,30 = **9,19%** ✓
- Sofia Monteiro (€1.660,08 lordo/mese): €152,55 / €1.660,08 = **9,19%** ✓

### Altri enti (contributi minori di categoria)

Presenti in busta come voce `39402 CONTRIBUTI ALTRI ENTI`:
- Marco Ferretti: €3,08 (~0,14% del lordo)
- Sofia Monteiro: €2,83 (~0,17% del lordo)

Si tratta di contributi al fondo di categoria (es. EBITER, FONDO MARIO NEGRI). Importo ridotto ma presente in ogni busta del CCNL Terziario.

> **Semplificazione per il prototipo:** Si considera solo INPS al 9,19%. La voce "altri enti" è dichiarata come semplificazione.

### Base imponibile INPS

La base di calcolo è la **retribuzione lorda mensile**, con esclusione di:
- Buoni pasto (entro i limiti di legge)
- Rimborsi spese documentati
- TFR (non è una trattenuta sul netto)

---

## 5. IRPEF — Imposta sul Reddito delle Persone Fisiche

### 5.1 Scaglioni e Aliquote

**Fonte:** Agenzia delle Entrate — confermato dalle buste paga analizzate.

#### Anno d'imposta 2025
| Scaglione | Aliquota | IRPEF dovuta |
|---|---|---|
| Da €0 a €28.000 | 23% | 23% × reddito |
| Da €28.001 a €50.000 | **35%** | €6.440 + 35% sulla parte eccedente €28.000 |
| Oltre €50.000 | 43% | €14.140 + 43% sulla parte eccedente €50.000 |

#### Anno d'imposta 2026 (novità introdotte dalla L.199/2025)
| Scaglione | Aliquota | IRPEF dovuta |
|---|---|---|
| Da €0 a €28.000 | 23% | 23% × reddito |
| Da €28.001 a €50.000 | **33%** ⬇ | €6.440 + 33% sulla parte eccedente €28.000 |
| Oltre €50.000 | 43% | **€13.700** + 43% sulla parte eccedente €50.000 |

> **Nota 2026:** La riduzione dal 35% al 33% nel secondo scaglione avvantaggia i redditi tra €28.001 e €50.000. Per redditi superiori a €200.000, le detrazioni da oneri sono ridotte di €440.

#### Verifica empirica (Sofia Monteiro, Maggio 2026)
- Imponibile IRPEF mensile: €1.505,53
- IRPEF lorda mensile: €346,27
- Aliquota effettiva applicata: €346,27 / €1.505,53 = **23%** ✓ (reddito nel primo scaglione)

### 5.2 Base Imponibile IRPEF

```
Imponibile IRPEF = RAL - Contributi INPS dipendente - Oneri deducibili
```

Nelle buste analizzate: `ONERI DED.` = €0. Per il caso standard si assume assenza di oneri deducibili.

### 5.3 Detrazioni per Lavoro Dipendente

**Fonte:** Art. 13 TUIR — confermato dalla Legge di Bilancio 2026 (invariato rispetto al 2025).

Le detrazioni si sottraggono direttamente dall'**IRPEF lorda** (non dalla base imponibile):

| Reddito complessivo | Formula detrazione |
|---|---|
| Fino a €15.000 | **€1.955** (importo fisso) |
| €15.001 – €28.000 | €1.910 + €1.190 × (28.000 – reddito) / 13.000 |
| €28.001 – €50.000 | €1.910 × (50.000 – reddito) / 22.000 |
| Oltre €50.000 | **€0** (azzerata) |

**Regole aggiuntive:**
- Reddito ≤ €28.000, contratto a **tempo indeterminato**: detrazione minima garantita = **€1.380**
- Reddito ≤ €28.000, contratto a **tempo determinato**: detrazione minima = **€690**
- Reddito tra €25.000 e €35.000: **+€65** di detrazione aggiuntiva
- La detrazione è proporzionata ai **giorni lavorati** nell'anno (365 = anno intero)

---

## 6. Addizionali Regionali e Comunali

Vengono pagate l'anno successivo tramite trattenute mensili in busta paga (da marzo a novembre).

### 6.1 Addizionale Regionale Lombardia 2026

**Fonte:** Directio — aliquote ufficiali pubblicate il 28/01/2026.
**Riferimento normativo:** Art. 72, comma 1, L.R. Lombardia n. 10/2003.

| Scaglione di reddito | Aliquota |
|---|---|
| Fino a €15.000 | 1,23% |
| €15.001 – €28.000 | 1,58% |
| €28.001 – €50.000 | 1,72% |
| Oltre €50.000 | 1,73% |

Il calcolo è progressivo per scaglioni: ogni aliquota si applica solo alla parte di reddito compresa in quella fascia.

### 6.2 Addizionale Comunale Milano 2026

**Aliquota unica: 0,80%** (flat, indipendente dal livello di reddito).
**Fonte:** TuttoCalcolo — Addizionale Comunale Milano 2026.

### 6.3 Addizionale Regionale Toscana 2026 (riferimento buste paga analizzate)

| Scaglione di reddito | Aliquota |
|---|---|
| Fino a €15.000 | 1,42% |
| €15.001 – €28.000 | 1,43% |
| €28.001 – €50.000 | 3,32% |
| Oltre €50.000 | 3,33% |

> **Nota importante:** Le buste analizzate appartengono a dipendenti residenti in **Toscana**, non in Lombardia. Le addizionali nelle loro buste seguono le aliquote toscane. Il calcolatore del progetto utilizza le aliquote **Lombardia/Milano** come da specifica (dipendente residente a Milano).

### 6.4 Modalità di pagamento in busta paga

Le addizionali dell'anno precedente vengono trattenute in rate mensili:
- Marco Ferretti: `9117 RATA ADDIZ.REGIONALE A.P. TOSCANA` → €35,40/mese (residuo annuo: €106,18)
- Marco Ferretti: `9119 RATA ADD.COMUNALE A.P.` → €13,90/mese (residuo annuo: €41,68)

> Per il calcolatore: mostrare le addizionali come **totale annuale dovuto**.

---

## 7. Cuneo Fiscale — Legge 199/2025 (Legge di Bilancio 2026)

Misura strutturale che riduce il carico fiscale sul lavoro dipendente. Ha sostituito il vecchio "esonero contributivo" (2022–2024).

### Come funziona

| Reddito annuo | Beneficio |
|---|---|
| Fino a €20.000 | Somma esente da IRPEF e INPS — percentuale variabile sul reddito |
| €20.001 – €40.000 | Detrazione aggiuntiva IRPEF di €1.000 (decresce progressivamente fino a zero a €40.000) |
| Oltre €40.000 | Nessun beneficio |

> **Dal 2025:** I contributi INPS tornano al 9,19% standard. Il beneficio non riduce più l'INPS ma arriva come bonus/detrazione fiscale senza impatto sulla posizione previdenziale.

### Nelle buste analizzate

| Voce | Marco Ferretti | Sofia Monteiro |
|---|---|---|
| `9835 INCREM.ASSOG.5% L.199/25` | €141,76 | €105,48 |
| `9824 SOMMA ART.1 C.4 L.207/24` | — | €72,27 |
| `8992 TRATT.INT. DL 3/20` | — | €101,92 |

Sofia Monteiro beneficia di più misure perché il suo reddito (≈€23.241/anno) è nella fascia di reddito più agevolata.

> **Per il prototipo:** Il cuneo fiscale è dichiarato come semplificazione fuori scope. Da menzionare in sede di colloquio come elemento noto ma escluso per complessità.

---

## 8. Confronto Anonimizzato — Le Due Buste Paga

> I nomi reali sono stati sostituiti con pseudonimi per tutelare la privacy. I dati numerici sono reali.

### Profilo Marco Ferretti (Luglio 2026)

| Campo | Valore |
|---|---|
| Livello CCNL | 3^ Impiegato |
| Tipo contratto | Tempo indeterminato |
| Data assunzione | 11/09/2023 |
| Retribuzione mensile lorda | €2.154,30 |
| RAL annua stimata (×14) | **€30.160,20** |
| INPS trattenuto (mese) | €197,95 (9,19%) |
| Altri enti | €3,08 |
| IRPEF netta (mese) | €148,89 |
| Addiz. Reg. Toscana (rata mensile) | €35,40 |
| Addiz. Comunale (rata mensile) | €13,90 + €6,22 |
| Netto busta | **€1.742,00** |
| Buoni pasto (non imponibili) | €91,00 |
| TFR maturato nel mese | €148,81 |
| TFR spettante totale | €5.527,93 |

**Riepilogo IRPEF annuale (dati progressivi da busta):**
- Imponibile IRPEF anno: €14.466,74
- IRPEF lorda anno: €3.527,03
- Detrazioni lavoro (anno): €1.909,01
- IRPEF netta anno: €1.618,02

---

### Profilo Sofia Monteiro (Maggio 2026)

| Campo | Valore |
|---|---|
| Livello CCNL | 5^ Impiegato |
| Tipo contratto | Tempo determinato (scadenza 31/07/2026) |
| Data assunzione | 02/02/2026 |
| Retribuzione mensile lorda | €1.660,08 |
| RAL annua stimata (×14) | **€23.241,12** |
| INPS trattenuto (mese) | €152,55 (9,19%) |
| Altri enti | €2,83 |
| IRPEF netta (mese) | €180,23 |
| Netto busta | **€1.498,00** |
| Buoni pasto (non imponibili) | €133,00 |
| Benefici cuneo fiscale (totale voci) | €279,67 |
| TFR maturato nel mese | €114,67 |
| TFR spettante totale | €453,21 |

**Riepilogo IRPEF annuale (dati progressivi da busta, 5 mesi):**
- Imponibile IRPEF anno: €5.950,33
- IRPEF lorda anno: €1.368,57
- Detrazioni lavoro (anno): €637,38
- IRPEF netta anno: €731,19

---

## 9. TFR — Trattamento di Fine Rapporto

### Cos'è
Il TFR è la liquidazione italiana: si accumula mensilmente e viene corrisposto al lavoratore alla cessazione del rapporto di lavoro (oppure trasferito a un fondo pensione o all'INPS su scelta del lavoratore).

### Formula legale (Art. 2120 Codice Civile)

```
TFR annuo    = Retribuzione annua utile TFR / 13,5
TFR mensile  = TFR annuo / 12
```

### Osservazione empirica dalle buste

| | Marco Ferretti | Sofia Monteiro |
|---|---|---|
| Retribuzione utile TFR | €2.154,30 | €1.660,08 |
| TFR maturato nel mese | €148,81 | €114,67 |
| Divisore osservato | ~14,47 | ~14,47 |

> **Nota tecnica:** Il divisore empirico osservato (~14,47) si discosta dal divisore legale (13,5). La differenza potrebbe essere legata a specificità del CCNL Terziario o alla proporzionalizzazione per periodi non interi. Per il prototipo si utilizza il divisore legale 13,5 — semplificazione da citare in sede di colloquio.

### Destinazione del TFR

- Reddito dipendente ≤ €50.000/anno: il TFR rimane in azienda (salvo scelta diversa del lavoratore)
- Reddito dipendente > €50.000/anno: obbligo di versamento al Fondo di Tesoreria INPS

### Per il calcolatore

```
TFR mensile ≈ (RAL / 14) / 13,5
```

Il TFR **non viene detratto dal netto percepito**. Va mostrato come voce informativa separata ("accantonamento mensile").

---

## 10. Buoni Pasto

Presenti in entrambe le buste analizzate:
- Marco Ferretti: €91,00/mese (voce 584)
- Sofia Monteiro: €133,00/mese (voce 584)

### Limiti di esenzione fiscale e contributiva

| Tipo | Limite giornaliero esente da IRPEF e INPS |
|---|---|
| Buono pasto cartaceo | €4,00/giorno |
| Buono pasto elettronico | **€8,00/giorno** |

Entro i limiti indicati non concorrono alla formazione del reddito imponibile.

> Per il calcolatore: parametro opzionale. Se presente, riduce la base imponibile.

---

## 11. Alta Retribuzione — Progressività del Sistema

### Impatto IRPEF per livello di reddito (2026)

| RAL | IRPEF lorda | Aliquota media effettiva |
|---|---|---|
| €20.000 | €4.600 | 23,0% |
| €28.000 | €6.440 | 23,0% |
| €35.000 | €8.570 | 24,5% |
| €50.000 | €13.700 | 27,4% |
| €70.000 | €22.300 | 31,9% |
| €100.000 | €35.200 | 35,2% |
| €200.000 | €78.200 | 39,1% |

> Nota: i valori 2026 utilizzano l'aliquota del 33% per il secondo scaglione.

### Azzeramento delle detrazioni ad alto reddito

- Oltre €50.000: **detrazione lavoro dipendente = €0**
- Oltre €200.000: **riduzione di €440** sulle detrazioni da oneri (mutuo, spese mediche, ecc.)

### Contributo aggiuntivo INPS

Redditi superiori a **€52.190/anno**: l'aliquota INPS a carico del dipendente sale da 9,19% a **10,19%** (+1% IVS).

### Addizionali progressive — confronto Milano

| RAL | Add. Reg. Lombardia | Add. Com. Milano | Totale |
|---|---|---|---|
| €20.000 | ~€316 (1,58%) | €160 (0,8%) | **€476** |
| €35.000 | ~€603 (1,72%) | €280 (0,8%) | **€883** |
| €60.000 | ~€1.038 (1,73%) | €480 (0,8%) | **€1.518** |

---

## 12. Tredicesima e Quattordicesima — Regole

### Tredicesima (13°)
- Obbligatoria per tutti i CCNL
- Corrisposta a **dicembre**
- Importo = 1 mensilità lorda (proporzionale ai mesi lavorati nell'anno)
- Soggetta a **INPS e IRPEF** come le mensilità ordinarie
- La detrazione annuale per lavoro dipendente la copre già (nessuna doppia deduzione)

### Quattordicesima (14°)
- Prevista dal **CCNL Terziario/Commercio** (e da altri CCNL di categoria)
- Corrisposta a **luglio**
- Importo = 1 mensilità lorda (proporzionale all'anzianità contrattuale)
- Soggetta a **INPS e IRPEF** come le mensilità ordinarie

### Impatto sul calcolo del netto mensile

Se la RAL include già 13° e 14°:
- Lordo mensile ordinario = RAL / 14
- Il netto mensile reale varia (luglio e dicembre sono mesi "doppi")
- Per il calcolatore: **netto mensile medio = netto annuale / 12**

---

## 13. Voci Speciali in Busta Paga

### `9835` — INCREM.ASSOG.5% L.199/25
- Incremento dell'imponibile assoggettato al beneficio del 5% (Legge di Bilancio 2026)
- Beneficiari: dipendenti con reddito fino a €20.000
- Effetto: riduce l'IRPEF netta in busta paga
- Valori: Marco Ferretti €141,76 | Sofia Monteiro €105,48

### `9824` — SOMMA ART.1 C.4 L.207/24
- Somma corrisposta ai dipendenti con reddito ≤ €20.000 che non concorre alla formazione del reddito (esente IRPEF)
- Base normativa: Art. 1, comma 4, L. 207/2024 (Legge di Bilancio 2025)
- Valore osservato: Sofia Monteiro €72,27/mese

### `8992` — TRATT.INT. DL 3/20
- Trattamento integrativo ex "bonus Renzi" — €1.200/anno per redditi ≤ €15.000
- Valore osservato: Sofia Monteiro €101,92/mese

### `39217` — CONTR.C/DIPE NON DEDUCIBILI
- Quota di contributi non deducibili fiscalmente
- Marco Ferretti: €1,08 | Sofia Monteiro: €0,83

---

## 14. Esempio di Calcolo Completo — Caso Standard (Milano, Indeterminato)

### Caso: RAL €30.000

**Step 1 — Contributi INPS dipendente**
```
€30.000 × 9,19% = €2.757,00/anno
```

**Step 2 — Imponibile fiscale**
```
€30.000 - €2.757,00 = €27.243,00
```

**Step 3 — IRPEF lorda (scaglioni 2026)**
```
€27.243 × 23% = €6.265,89
```

**Step 4 — Detrazione per lavoro dipendente**
```
Reddito €27.243 → fascia €15.001–€28.000
Detrazione = 1.910 + 1.190 × (28.000 - 27.243) / 13.000
           = 1.910 + 1.190 × 0,0582
           = 1.910 + 69,26
           = €1.979,26
```

**Step 5 — IRPEF netta**
```
€6.265,89 - €1.979,26 = €4.286,63/anno
```

**Step 6 — Addizionale Regionale Lombardia**
```
Primo scaglione  (€15.000 × 1,23%):              €184,50
Secondo scaglione ((€27.243 - €15.000) × 1,58%): €193,44
Totale addizionale regionale:                     €377,94/anno
```

**Step 7 — Addizionale Comunale Milano**
```
€27.243 × 0,80% = €217,94/anno
```

**Step 8 — Netto annuale**
```
€30.000 - €2.757,00 - €4.286,63 - €377,94 - €217,94 = €22.360,49/anno
```

**Step 9 — Netto mensile medio**
```
€22.360,49 / 12 = €1.863,37/mese
```

### Riepilogo

| Voce | Importo annuo | % sul lordo |
|---|---|---|
| RAL (lordo) | €30.000,00 | 100,00% |
| (-) INPS dipendente | -€2.757,00 | 9,19% |
| (-) IRPEF netta | -€4.286,63 | 14,29% |
| (-) Addizionale Regionale | -€377,94 | 1,26% |
| (-) Addizionale Comunale | -€217,94 | 0,73% |
| **NETTO ANNUALE** | **€22.360,49** | **74,53%** |
| **NETTO MENSILE MEDIO** | **€1.863,37** | |
| TFR accantonato (informativo) | ~€148,15/mese | non detratto |

---

## 15. Semplificazioni del Prototipo

Le seguenti semplificazioni sono accettabili e vanno dichiarate in sede di colloquio:

| Semplificazione | Motivazione |
|---|---|
| Contributi INPS fissi al 9,19% | La soglia di €52.190 è raramente raggiunta nei casi base |
| Nessun contributo "altri enti" | Importo marginale (~0,14%), non incide sul quadro generale |
| Detrazione non proporzionata ai giorni | Si assume anno lavorativo pieno (365 giorni) |
| Cuneo fiscale escluso | Meccanismo variabile e complesso — dichiarato out of scope |
| Nessun onere deducibile | Caso standard senza mutuo, spese mediche, ecc. |
| TFR con divisore legale 13,5 | Lo scarto osservato (~14,47) è specifico del CCNL; il divisore legale è accettabile |
| Netto mensile = annuale / 12 | Non distingue luglio/dicembre (mesi con 14° e 13°) dai mesi ordinari |


---

*Documento redatto il 14/08/2026. Aliquote verificate su fonti ufficiali (Agenzia delle Entrate, Regione Lombardia, Comune di Milano). Valori soggetti ad aggiornamento con successive leggi di bilancio.*
