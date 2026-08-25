<?php

namespace App\Enums;

/**
 * Regioni italiane supportate dal calcolatore per l'addizionale regionale e comunale IRPEF.
 * Ogni caso espone i propri scaglioni 2026, nello stesso formato atteso da
 * SalaryCalculatorService::applicaScaglioni() (fino_a/aliquota progressivi).
 *
 * L'addizionale comunale varia per comune (oltre 7.900 in Italia), non per regione:
 * questo enum la approssima usando l'aliquota del capoluogo di regione, senza
 * modellare le soglie di esenzione per basso reddito che molti comuni applicano
 * (semplificazione dichiarata, coerente con le altre approssimazioni del prototipo).
 */
enum Regione: string
{
    case Lombardia = 'lombardia';
    case Piemonte = 'piemonte';
    case Veneto = 'veneto';
    case Toscana = 'toscana';
    case EmiliaRomagna = 'emilia-romagna';
    case Lazio = 'lazio';

    public function label(): string
    {
        return match ($this) {
            self::Lombardia => 'Lombardia',
            self::Piemonte => 'Piemonte',
            self::Veneto => 'Veneto',
            self::Toscana => 'Toscana',
            self::EmiliaRomagna => 'Emilia-Romagna',
            self::Lazio => 'Lazio',
        };
    }

    /**
     * Capoluogo di regione usato per approssimare l'addizionale comunale.
     */
    public function comuneRiferimento(): string
    {
        return match ($this) {
            self::Lombardia => 'Milano',
            self::Piemonte => 'Torino',
            self::Veneto => 'Venezia',
            self::Toscana => 'Firenze',
            self::EmiliaRomagna => 'Bologna',
            self::Lazio => 'Roma',
        };
    }

    /**
     * @return array<int, array{fino_a: float|null, aliquota: float}>
     */
    public function scaglioniAddizionaleComunale(): array
    {
        return match ($this) {
            // Milano 2026: aliquota unica, non a scaglioni
            self::Lombardia => [
                ['fino_a' => null, 'aliquota' => 0.008],
            ],
            // Torino 2026 (comune.torino.it): a scaglioni, non aliquota unica
            self::Piemonte => [
                ['fino_a' => 15000.0, 'aliquota' => 0.008],
                ['fino_a' => 28000.0, 'aliquota' => 0.008],
                ['fino_a' => 50000.0, 'aliquota' => 0.011],
                ['fino_a' => null, 'aliquota' => 0.012],
            ],
            // Venezia 2026 (comune.venezia.it): aliquota unica
            self::Veneto => [
                ['fino_a' => null, 'aliquota' => 0.008],
            ],
            // Firenze (comune.firenze.it): aliquota unica, la più bassa tra i capoluoghi
            self::Toscana => [
                ['fino_a' => null, 'aliquota' => 0.002],
            ],
            // Bologna: aliquota unica. NOTA: fonte secondaria, non confermata sul sito
            // ufficiale del Comune — da verificare prima di un uso reale.
            self::EmiliaRomagna => [
                ['fino_a' => null, 'aliquota' => 0.008],
            ],
            // Roma Capitale, Delibera Assemblea Capitolina n. 186 del 19/12/2024: aliquota unica
            self::Lazio => [
                ['fino_a' => null, 'aliquota' => 0.009],
            ],
        };
    }

    /**
     * @return array<int, array{fino_a: float|null, aliquota: float}>
     */
    public function scaglioniAddizionaleRegionale(): array
    {
        return match ($this) {
            // Art. 72 L.R. Lombardia n. 10/2003
            self::Lombardia => [
                ['fino_a' => 15000.0, 'aliquota' => 0.0123],
                ['fino_a' => 28000.0, 'aliquota' => 0.0158],
                ['fino_a' => 50000.0, 'aliquota' => 0.0172],
                ['fino_a' => null, 'aliquota' => 0.0173],
            ],
            // Regione Piemonte, aliquote 2026-2027 (regione.piemonte.it)
            self::Piemonte => [
                ['fino_a' => 15000.0, 'aliquota' => 0.0162],
                ['fino_a' => 28000.0, 'aliquota' => 0.0268],
                ['fino_a' => 50000.0, 'aliquota' => 0.0331],
                ['fino_a' => null, 'aliquota' => 0.0333],
            ],
            // Regione del Veneto: aliquota unica, non a scaglioni
            self::Veneto => [
                ['fino_a' => null, 'aliquota' => 0.0123],
            ],
            // Fonte: Dipartimento delle Finanze (finanze.gov.it), regione 17. Le fonti
            // secondarie online riportano scaglioni intermedi diversi tra loro: questi
            // valori sono stati scelti come quelli della fonte ufficiale.
            self::Toscana => [
                ['fino_a' => 15000.0, 'aliquota' => 0.0142],
                ['fino_a' => 28000.0, 'aliquota' => 0.0143],
                ['fino_a' => 50000.0, 'aliquota' => 0.0332],
                ['fino_a' => null, 'aliquota' => 0.0333],
            ],
            // L.R. Emilia-Romagna n. 1/2025, aliquote 2026
            self::EmiliaRomagna => [
                ['fino_a' => 15000.0, 'aliquota' => 0.0133],
                ['fino_a' => 28000.0, 'aliquota' => 0.0193],
                ['fino_a' => 50000.0, 'aliquota' => 0.0278],
                ['fino_a' => null, 'aliquota' => 0.0333],
            ],
            // L.R. Lazio n. 20/2025: solo 2 scaglioni, allineati ai 3 scaglioni IRPEF 2026
            self::Lazio => [
                ['fino_a' => 28000.0, 'aliquota' => 0.0173],
                ['fino_a' => null, 'aliquota' => 0.0333],
            ],
        };
    }
}
