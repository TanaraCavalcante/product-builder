## [2026-08-25] - Rifiniture UI selezione regione e allineamento documentazione

### Modificato
- `resources/views/calcolator.blade.php`: i pulsanti "Calcola"/"Reset" sono stati spostati sotto il select "Regione" (prima erano accanto al solo campo RAL, il che suggeriva che bastasse quella per calcolare)
- Regione preselezionata di default nel form: Toscana (il default del parametro `SalaryCalculatorService::calcola()`, usato solo se `regione` viene omessa, resta Lombardia)
- `README.md`, `docs/01-project-spec.md`: allineati alla generalizzazione multi-regione (non più descritti come limitati a Milano/Lombardia)
- `docs/plans/plan-salary-calculator.md`: aggiunta la Fase 5 (generalizzazione multi-regione, fuori piano originale) come nuova sezione; annotate come superate le note storiche che dichiaravano il supporto multi-regione "fuori scope"

## [2026-08-25] - Generalizzazione multi-regione del calcolatore

### Aggiunto
- `App\Enums\Regione`: enum con 6 regioni (Lombardia, Piemonte, Veneto, Toscana, Emilia-Romagna, Lazio), ciascuna con i propri scaglioni di addizionale regionale e comunale (quest'ultima approssimata con l'aliquota del capoluogo di regione, senza modellare le soglie di esenzione comunali per basso reddito)
- Select "Regione" nel form del calcolatore; le etichette "Addizionale Regionale/Comunale" nel breakdown si aggiornano in base alla regione scelta
- Validazione `regione` (obbligatoria, deve essere uno dei valori dell'enum) in `SalaryController`
- Test unitari e di feature per Piemonte/Torino (scaglioni regionali e comunali diversi da Lombardia/Milano) e per la validazione di `regione`

### Modificato
- `SalaryCalculatorService::calcola()` accetta ora un parametro `Regione $regione` (default `Lombardia`, per compatibilità con l'uso esistente); addizionale regionale e comunale non sono più costanti fisse ma derivano dalla regione scelta
- Risposta JSON: aggiunti `input.regione`, `input.regione_label`, `input.comune_riferimento`
