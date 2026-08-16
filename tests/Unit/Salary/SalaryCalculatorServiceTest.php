<?php

// Test generati da tanas:test

namespace Tests\Unit\Salary;

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
