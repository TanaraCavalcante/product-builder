<?php

// Test generati da tanas:test

namespace Tests\Unit\Salary;

use App\Enums\Regione;
use App\Services\SalaryCalculatorService;
use PHPUnit\Framework\TestCase;

class SalaryCalculatorServiceTest extends TestCase
{
    private SalaryCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new SalaryCalculatorService;
    }

    public function test_calcola_ral_30000_matches_reference_case(): void
    {
        $risultato = $this->service->calcola(30000.0);

        $this->assertEqualsWithDelta(2757.0, $risultato['inps'], 0.01);
        $this->assertEqualsWithDelta(27243.0, $risultato['imponibile_fiscale'], 0.01);
        $this->assertEqualsWithDelta(6265.89, $risultato['irpef_lorda'], 0.01);
        $this->assertEqualsWithDelta(2044.29, $risultato['detrazione_lavoro_dipendente'], 0.01);
        $this->assertEqualsWithDelta(4221.6, $risultato['irpef_netta'], 0.01);
        $this->assertEqualsWithDelta(377.94, $risultato['addizionale_regionale'], 0.01);
        $this->assertEqualsWithDelta(217.94, $risultato['addizionale_comunale'], 0.01);
        $this->assertEqualsWithDelta(22425.52, $risultato['netto_annuale'], 0.01);
        $this->assertEqualsWithDelta(1868.79, $risultato['netto_mensile_medio'], 0.01);
        $this->assertSame('indeterminato', $risultato['input']['tipo_contratto']);
        $this->assertSame('lombardia', $risultato['input']['regione']);
        $this->assertSame('Milano', $risultato['input']['comune_riferimento']);
    }

    public function test_calcola_applica_gli_scaglioni_della_regione_selezionata(): void
    {
        $risultato = $this->service->calcola(30000.0, Regione::Piemonte);

        // Stesso imponibile fiscale di test_calcola_ral_30000_matches_reference_case (€27.243),
        // ma con gli scaglioni Piemonte (1,62%/2,68%) al posto di quelli Lombardia (1,23%/1,58%)
        $this->assertEqualsWithDelta(571.11, $risultato['addizionale_regionale'], 0.01);
        $this->assertSame('piemonte', $risultato['input']['regione']);
        $this->assertSame('Piemonte', $risultato['input']['regione_label']);
        $this->assertSame('Torino', $risultato['input']['comune_riferimento']);
    }

    public function test_calcola_applica_gli_scaglioni_comunali_di_torino_sopra_i_50000(): void
    {
        // RAL €80.000 → imponibile fiscale €72.369,9 (stesso INPS di
        // test_calcola_ral_alta_attraversa_soglia_massimale_inps), che attraversa tutti
        // e 4 gli scaglioni comunali di Torino (0,8%/0,8%/1,1%/1,2%) invece dell'aliquota
        // unica di Milano
        $risultato = $this->service->calcola(80000.0, Regione::Piemonte);

        $this->assertEqualsWithDelta(734.44, $risultato['addizionale_comunale'], 0.01);
    }

    public function test_calcola_ral_bassa(): void
    {
        $risultato = $this->service->calcola(15000.0);

        $this->assertEqualsWithDelta(1378.5, $risultato['inps'], 0.01);
        $this->assertEqualsWithDelta(12167.04, $risultato['netto_annuale'], 0.01);
        $this->assertGreaterThan(0.0, $risultato['netto_annuale']);
        $this->assertLessThan(15000.0, $risultato['netto_annuale']);
    }

    public function test_calcola_ral_alta_attraversa_soglia_massimale_inps(): void
    {
        $risultato = $this->service->calcola(80000.0);

        // Sopra i €52.190 l'aliquota INPS sale dal 9,19% al 10,19% solo sulla parte eccedente
        $this->assertEqualsWithDelta(7630.1, $risultato['inps'], 0.01);
        // Sopra i €50.000 di imponibile la detrazione lavoro dipendente si azzera
        $this->assertEqualsWithDelta(0.0, $risultato['detrazione_lavoro_dipendente'], 0.01);
    }

    public function test_calcola_ral_molto_alta(): void
    {
        $risultato = $this->service->calcola(200000.0);

        $this->assertEqualsWithDelta(0.0, $risultato['detrazione_lavoro_dipendente'], 0.01);
        $this->assertGreaterThan(0.0, $risultato['netto_annuale']);
        $this->assertLessThan(200000.0, $risultato['netto_annuale']);
    }

    public function test_bonus_detrazione_applicato_tra_25000_e_35000(): void
    {
        // RAL €30.000 → imponibile fiscale €27.243, dentro la fascia €25.000-€35.000
        $risultato = $this->service->calcola(30000.0);

        $this->assertTrue($risultato['detrazione_dettaglio']['bonus_applicato']);
        $this->assertNotNull($risultato['detrazione_dettaglio']['bonus_nota']);
    }

    public function test_bonus_detrazione_non_applicato_fuori_fascia(): void
    {
        // RAL €15.000 → imponibile fiscale €13.621,50, sotto la fascia €25.000-€35.000
        $risultato = $this->service->calcola(15000.0);

        $this->assertFalse($risultato['detrazione_dettaglio']['bonus_applicato']);
        $this->assertNull($risultato['detrazione_dettaglio']['bonus_nota']);
    }

    public function test_totale_trattenute_corrisponde_alla_differenza_tra_ral_e_netto(): void
    {
        $risultato = $this->service->calcola(30000.0);

        $this->assertEqualsWithDelta(
            30000.0 - $risultato['netto_annuale'],
            $risultato['totale_trattenute'],
            0.01
        );
    }
}
