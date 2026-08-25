<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Calcolatore RAL → Netto</title>

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" rel="stylesheet">
        <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    </head>
    <body class="bg-light">
        <main class="container py-5">
            <header class="text-center mb-4">
                <h1 class="h3"><i class="fa-solid fa-calculator text-primary me-2"></i>Calcolatore RAL → Netto</h1>
                <p class="text-muted small mb-0">
                    Il calcolo assume un dipendente a tempo indeterminato. L'addizionale regionale e
                    quella comunale seguono la regione selezionata, quest'ultima approssimata con
                    l'aliquota del capoluogo di regione (senza soglie di esenzione per basso reddito).
                </p>
            </header>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="calcolatore-form" novalidate>
                        <label for="ral" class="form-label fw-semibold">RAL — Retribuzione Annua Lorda (€)</label>
                        <div class="input-group">
                            <span class="input-group-text">€</span>
                            <input
                                type="number"
                                class="form-control"
                                id="ral"
                                name="ral"
                                min="0"
                                step="0.01"
                                placeholder="Es. 30000"
                                required
                            >
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa-solid fa-magnifying-glass-dollar me-1"></i>Calcola
                            </button>
                            <button
                                type="button"
                                id="ral-pulisci"
                                class="btn btn-danger d-none"
                                aria-label="Reset"
                            >
                                <i class="fa-solid fa-xmark me-1"></i>Reset
                            </button>
                        </div>
                        <div id="ral-avviso" class="form-text text-warning-emphasis d-none">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Sei sicuro che il valore sia giusto?
                        </div>

                        <div class="mt-3">
                            <label for="regione" class="form-label fw-semibold">Regione</label>
                            <select class="form-select" id="regione" name="regione" required>
                                @foreach ($regioni as $regione)
                                    <option value="{{ $regione->value }}" @selected($regione->value === 'lombardia')>
                                        {{ $regione->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </form>

                    <div id="errore" class="alert alert-danger mt-3 d-none" role="alert"></div>
                </div>
            </div>

            <section id="risultato" class="mt-4 d-none">
                <div class="row g-3 text-center">
                    <div class="col-6">
                        <div class="card bg-primary-subtle border-0 h-100">
                            <div class="card-body">
                                <div class="text-uppercase small text-muted">Netto annuale</div>
                                <div class="fs-3 fw-bold text-primary" id="netto-annuale">—</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card bg-primary-subtle border-0 h-100">
                            <div class="card-body">
                                <div class="text-uppercase small text-muted">Netto mensile medio</div>
                                <div class="fs-3 fw-bold text-primary" id="netto-mensile">—</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Breakdown trattenute</h2>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <tr>
                                    <td>RAL (lordo)</td>
                                    <td class="text-end fw-semibold" id="riga-ral">—</td>
                                </tr>
                                <tr>
                                    <td>Contributi INPS</td>
                                    <td class="text-end text-danger" id="riga-inps">—</td>
                                </tr>
                                <tr>
                                    <td>IRPEF lorda</td>
                                    <td class="text-end" id="riga-irpef-lorda">—</td>
                                </tr>
                                <tr>
                                    <td>
                                        Detrazione lavoro dipendente
                                        <span id="badge-bonus" class="badge text-bg-success ms-1 d-none">bonus €65 applicato</span>
                                    </td>
                                    <td class="text-end text-success" id="riga-detrazione">—</td>
                                </tr>
                                <tr>
                                    <td>IRPEF netta</td>
                                    <td class="text-end text-danger" id="riga-irpef-netta">—</td>
                                </tr>
                                <tr>
                                    <td id="riga-addizionale-regionale-label">Addizionale Regionale</td>
                                    <td class="text-end text-danger" id="riga-addizionale-regionale">—</td>
                                </tr>
                                <tr>
                                    <td id="riga-addizionale-comunale-label">Addizionale Comunale</td>
                                    <td class="text-end text-danger" id="riga-addizionale-comunale">—</td>
                                </tr>
                                <tr class="table-group-divider">
                                    <td class="fw-bold">Totale trattenute</td>
                                    <td class="text-end fw-bold text-danger" id="riga-totale-trattenute">—</td>
                                </tr>
                                <tr>
                                    <td>Incidenza % sul lordo</td>
                                    <td class="text-end">
                                        <span class="badge text-bg-secondary" id="badge-incidenza">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card shadow-sm mt-3">
                    <div class="card-body">
                        <h2 class="h6 mb-3">Distribuzione della RAL</h2>
                        <canvas id="grafico-breakdown" height="220"></canvas>
                    </div>
                </div>

                <p class="text-muted small mt-3 mb-0">
                    <i class="fa-regular fa-circle-question me-1"></i>
                    TFR accantonato (informativo, non trattenuto dal netto): <span id="tfr-informativo">—</span>/mese
                </p>
            </section>
        </main>

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script src="{{ asset('js/salary-calculator.js') }}?v={{ filemtime(public_path('js/salary-calculator.js')) }}"></script>
    </body>
</html>
