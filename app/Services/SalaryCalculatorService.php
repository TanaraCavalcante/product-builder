<?php

namespace App\Services;

/**
 * Calcola la proiezione del netto annuale/mensile a partire dalla RAL, per il caso
 * semplificato: dipendente a tempo indeterminato, residente e lavorante a Milano
 * (CCNL Terziario/Commercio), senza agevolazioni particolari.
 *
 * Le formule e le relative fonti sono documentate per intero in
 * docs/02-salary-calculator-reference.md — questa classe ne è la traduzione in codice,
 * passo per passo, nello stesso ordine del documento.
 *
 * Il metodo pubblico calcola() assume input già validati (RAL > 0): la validazione
 * dell'input HTTP è responsabilità del SalaryController.
 */
class SalaryCalculatorService
{
    // Il prototipo copre solo il caso a tempo indeterminato, come da consegna del progetto
    private const TIPO_CONTRATTO = 'indeterminato';

    // Soglia oltre la quale l'aliquota INPS a carico del dipendente sale dal 9,19% al 10,19%
    private const INPS_SCAGLIONI = [
        ['fino_a' => 52190.0, 'aliquota' => 0.0919],
        ['fino_a' => null, 'aliquota' => 0.1019],
    ];

    // IRPEF 2026 (L.199/2025): il secondo scaglione è sceso dal 35% al 33% rispetto al 2025
    private const IRPEF_SCAGLIONI = [
        ['fino_a' => 28000.0, 'aliquota' => 0.23],
        ['fino_a' => 50000.0, 'aliquota' => 0.33],
        ['fino_a' => null, 'aliquota' => 0.43],
    ];

    // Addizionale regionale Lombardia 2026 (Art. 72 L.R. Lombardia n. 10/2003)
    private const ADDIZIONALE_REGIONALE_SCAGLIONI = [
        ['fino_a' => 15000.0, 'aliquota' => 0.0123],
        ['fino_a' => 28000.0, 'aliquota' => 0.0158],
        ['fino_a' => 50000.0, 'aliquota' => 0.0172],
        ['fino_a' => null, 'aliquota' => 0.0173],
    ];

    // Addizionale comunale Milano 2026: aliquota unica, non a scaglioni
    private const ADDIZIONALE_COMUNALE_MILANO = 0.008;

    // Bonus aggiuntivo di detrazione per la fascia di reddito €25.000-€35.000 (Art. 13 TUIR)
    private const DETRAZIONE_BONUS_SOGLIA_MIN = 25000.0;

    private const DETRAZIONE_BONUS_SOGLIA_MAX = 35000.0;

    private const DETRAZIONE_BONUS_IMPORTO = 65.0;

    /**
     * @return array<string, mixed> il breakdown completo RAL → netto
     */
    public function calcola(float $ral): array
    {
        // Step 1 — Contributi INPS a carico del dipendente
        $inps = $this->applicaScaglioni($ral, self::INPS_SCAGLIONI);

        // Step 2 — Imponibile fiscale: la RAL al netto dei soli contributi INPS
        $imponibileFiscale = $ral - $inps;

        // Step 3 — IRPEF lorda per scaglioni progressivi
        $irpefLorda = $this->applicaScaglioni($imponibileFiscale, self::IRPEF_SCAGLIONI);

        // Step 4 — Detrazione per lavoro dipendente (formula + bonus)
        $detrazione = $this->calcolaDetrazioneLavoroDipendente($imponibileFiscale);

        // Step 5 — IRPEF netta: la detrazione non può mai far scendere l'imposta sotto zero
        $irpefNetta = max(0.0, $irpefLorda - $detrazione['totale']);

        // Step 6 — Addizionale regionale Lombardia per scaglioni progressivi
        $addizionaleRegionale = $this->applicaScaglioni($imponibileFiscale, self::ADDIZIONALE_REGIONALE_SCAGLIONI);

        // Step 7 — Addizionale comunale Milano: aliquota unica sull'intero imponibile
        $addizionaleComunale = $imponibileFiscale * self::ADDIZIONALE_COMUNALE_MILANO;

        // Step 8 — Totale trattenute e netto annuale
        $totaleTrattenute = $inps + $irpefNetta + $addizionaleRegionale + $addizionaleComunale;
        $nettoAnnuale = $ral - $totaleTrattenute;

        // Step 9 — Netto mensile medio: distribuzione semplificata su 12 mesi
        // (non distingue i mesi con tredicesima/quattordicesima dai mesi ordinari)
        $nettoMensileMedio = $nettoAnnuale / 12;

        // Step 10 — TFR mensile informativo (Art. 2120 Codice Civile): accantonato
        // dall'azienda, non è una trattenuta e non riduce il netto percepito
        $tfrMensileInformativo = ($ral / 14) / 13.5;

        return [
            'input' => [
                'ral' => round($ral, 2),
                'tipo_contratto' => self::TIPO_CONTRATTO,
            ],
            'inps' => round($inps, 2),
            'imponibile_fiscale' => round($imponibileFiscale, 2),
            'irpef_lorda' => round($irpefLorda, 2),
            'detrazione_lavoro_dipendente' => round($detrazione['totale'], 2),
            'detrazione_dettaglio' => $detrazione,
            'irpef_netta' => round($irpefNetta, 2),
            'addizionale_regionale' => round($addizionaleRegionale, 2),
            'addizionale_comunale' => round($addizionaleComunale, 2),
            'totale_trattenute' => round($totaleTrattenute, 2),
            'incidenza_percentuale' => round($totaleTrattenute / $ral * 100, 2),
            'netto_annuale' => round($nettoAnnuale, 2),
            'netto_mensile_medio' => round($nettoMensileMedio, 2),
            'tfr_mensile_informativo' => round($tfrMensileInformativo, 2),
        ];
    }

    /**
     * Applica una tassazione a scaglioni progressivi: ogni fascia di reddito viene
     * tassata solo con l'aliquota della propria fascia (non con quella più alta
     * raggiunta). È lo stesso algoritmo usato da INPS, IRPEF e addizionale regionale:
     * cambia solo la tabella di soglie/aliquote passata come parametro.
     *
     * @param  array<int, array{fino_a: float|null, aliquota: float}>  $scaglioni  l'ultimo
     *                                                                             scaglione ha 'fino_a' => null, che significa "senza limite superiore"
     */
    private function applicaScaglioni(float $base, array $scaglioni): float
    {
        $importo = 0.0;
        $sogliaPrecedente = 0.0;

        foreach ($scaglioni as $scaglione) {
            $limiteScaglione = $scaglione['fino_a'] ?? PHP_FLOAT_MAX;
            $ampiezzaScaglione = $limiteScaglione - $sogliaPrecedente;
            $baseImponibileScaglione = min(max($base - $sogliaPrecedente, 0.0), $ampiezzaScaglione);

            $importo += $baseImponibileScaglione * $scaglione['aliquota'];

            if ($base <= $limiteScaglione) {
                break;
            }

            $sogliaPrecedente = $limiteScaglione;
        }

        return $importo;
    }

    /**
     * Detrazione per lavoro dipendente (Art. 13 TUIR): formula base a scaglioni "a
     * scomparsa" (non progressiva come IRPEF, ogni fascia ha una propria formula),
     * corretta da un bonus fisso per la fascia di reddito €25.000-€35.000.
     *
     * Nota: l'Art. 13 TUIR prevede anche un minimo garantito (€1.380 per il tempo
     * indeterminato) per i casi in cui la detrazione viene proporzionata ai giorni
     * lavorati nell'anno. Questo prototipo assume sempre un anno lavorativo pieno
     * (semplificazione dichiarata in docs/02-salary-calculator-reference.md, sezione 15),
     * quindi la formula base non scende mai sotto €1.910 e il minimo non è mai
     * determinante: non è stato implementato per evitare codice morto.
     *
     * @return array{totale: float, formula_base: float, bonus_applicato: bool, bonus_nota: ?string}
     */
    private function calcolaDetrazioneLavoroDipendente(float $imponibileFiscale): array
    {
        $formulaBase = match (true) {
            $imponibileFiscale <= 15000.0 => 1955.0,
            $imponibileFiscale <= 28000.0 => 1910 + 1190 * (28000 - $imponibileFiscale) / 13000,
            $imponibileFiscale <= 50000.0 => 1910 * (50000 - $imponibileFiscale) / 22000,
            default => 0.0,
        };

        $detrazione = $formulaBase;

        $bonusApplicato = $imponibileFiscale >= self::DETRAZIONE_BONUS_SOGLIA_MIN
            && $imponibileFiscale <= self::DETRAZIONE_BONUS_SOGLIA_MAX;

        if ($bonusApplicato) {
            $detrazione += self::DETRAZIONE_BONUS_IMPORTO;
        }

        return [
            'totale' => $detrazione,
            'formula_base' => round($formulaBase, 2),
            'bonus_applicato' => $bonusApplicato,
            'bonus_nota' => $bonusApplicato
                ? sprintf(
                    'Bonus di €%s per imponibile fiscale tra €25.000 e €35.000 (Art. 13 TUIR, confermato dalla Legge di Bilancio 2026).',
                    number_format(self::DETRAZIONE_BONUS_IMPORTO, 0)
                )
                : null,
        ];
    }
}
