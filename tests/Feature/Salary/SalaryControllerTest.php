<?php

// Test generati da tanas:test

namespace Tests\Feature\Salary;

use Tests\TestCase;

class SalaryControllerTest extends TestCase
{
    public function test_index_returns_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_calcola_returns_expected_json_structure_for_valid_ral(): void
    {
        $response = $this->postJson('/calcola', ['ral' => 30000]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'input' => ['ral', 'tipo_contratto'],
            'inps',
            'imponibile_fiscale',
            'irpef_lorda',
            'detrazione_lavoro_dipendente',
            'detrazione_dettaglio' => ['totale', 'formula_base', 'bonus_applicato', 'bonus_nota'],
            'irpef_netta',
            'addizionale_regionale',
            'addizionale_comunale',
            'totale_trattenute',
            'incidenza_percentuale',
            'netto_annuale',
            'netto_mensile_medio',
            'tfr_mensile_informativo',
        ]);
        $response->assertJsonPath('netto_annuale', 22425.52);
    }

    public function test_calcola_requires_ral(): void
    {
        $response = $this->postJson('/calcola', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ral');
    }

    public function test_calcola_rejects_zero_ral(): void
    {
        $response = $this->postJson('/calcola', ['ral' => 0]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ral');
    }

    public function test_calcola_rejects_negative_ral(): void
    {
        $response = $this->postJson('/calcola', ['ral' => -1000]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ral');
    }

    public function test_calcola_rejects_non_numeric_ral(): void
    {
        $response = $this->postJson('/calcola', ['ral' => 'trenta mila']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ral');
    }
}
