<?php

namespace App\Http\Controllers;

use App\Enums\Regione;
use App\Services\SalaryCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SalaryController extends Controller
{
    public function __construct(private readonly SalaryCalculatorService $salaryCalculatorService) {}

    /**
     * Mostra la pagina con il form di inserimento della RAL.
     */
    public function index(): View
    {
        return view('calcolator', ['regioni' => Regione::cases()]);
    }

    /**
     * Valida la RAL inviata, esegue il calcolo e ritorna il breakdown in JSON.
     * Nessuna eccezione arriva mai all'utente come stacktrace: in caso di errore
     * imprevisto nel calcolo, viene loggato e restituito un messaggio leggibile.
     */
    public function calcola(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ral' => ['required', 'numeric', 'gt:0'],
            'regione' => ['required', Rule::enum(Regione::class)],
        ], [
            'ral.required' => 'La RAL è obbligatoria.',
            'ral.numeric' => 'La RAL deve essere un valore numerico.',
            'ral.gt' => 'La RAL deve essere maggiore di zero.',
            'regione.required' => 'La regione è obbligatoria.',
            'regione.enum' => 'La regione selezionata non è valida.',
        ]);

        try {
            $risultato = $this->salaryCalculatorService->calcola(
                ral: (float) $validated['ral'],
                regione: Regione::from($validated['regione']),
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Non è stato possibile completare il calcolo. Riprova più tardi.',
            ], 500);
        }

        return response()->json($risultato);
    }
}
