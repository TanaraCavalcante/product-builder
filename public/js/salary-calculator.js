// Intercetta il submit del form, invia la RAL al backend via fetch() e aggiorna
// il DOM con il risultato, senza ricaricare la pagina.

const RAL_SOGLIA_AVVISO = 200000;

const form = document.getElementById('calcolatore-form');
const ralInput = document.getElementById('ral');
const regioneSelect = document.getElementById('regione');
const ralAvviso = document.getElementById('ral-avviso');
const ralPulisciBtn = document.getElementById('ral-pulisci');
const erroreBox = document.getElementById('errore');
const risultatoSection = document.getElementById('risultato');

const formatterValuta = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' });

function formattaValuta(valore) {
    return formatterValuta.format(valore);
}

// Istanza del grafico: va distrutta e ricreata ad ogni calcolo, altrimenti Chart.js
// solleva un errore "Canvas is already in use" riusando lo stesso <canvas>.
let graficoBreakdown = null;

function renderGrafico(dati) {
    if (graficoBreakdown) {
        graficoBreakdown.destroy();
    }

    const contesto = document.getElementById('grafico-breakdown');

    graficoBreakdown = new Chart(contesto, {
        type: 'doughnut',
        data: {
            labels: ['Netto annuale', 'INPS', 'IRPEF netta', 'Addizionale Regionale', 'Addizionale Comunale'],
            datasets: [
                {
                    data: [
                        dati.netto_annuale,
                        dati.inps,
                        dati.irpef_netta,
                        dati.addizionale_regionale,
                        dati.addizionale_comunale,
                    ],
                    backgroundColor: ['#198754', '#dc3545', '#fd7e14', '#ffc107', '#6f42c1'],
                },
            ],
        },
        options: {
            plugins: {
                legend: { position: 'bottom' },
                tooltip: {
                    callbacks: {
                        label: (voce) => `${voce.label}: ${formattaValuta(voce.raw)}`,
                    },
                },
            },
        },
    });
}

// Avviso non bloccante: una RAL molto alta potrebbe indicare zeri di troppo per errore.
// Il pulsante "pulisci" appare solo quando il campo contiene qualcosa da cancellare.
ralInput.addEventListener('input', () => {
    const valore = parseFloat(ralInput.value);
    ralAvviso.classList.toggle('d-none', !(valore > RAL_SOGLIA_AVVISO));
    ralPulisciBtn.classList.toggle('d-none', ralInput.value === '');
});

ralPulisciBtn.addEventListener('click', () => {
    ralInput.value = '';
    ralPulisciBtn.classList.add('d-none');
    ralAvviso.classList.add('d-none');
    nascondiErrore();
    risultatoSection.classList.add('d-none');
    ralInput.focus();
});

form.addEventListener('submit', async (event) => {
    event.preventDefault();

    nascondiErrore();

    try {
        const risposta = await fetch('/calcola', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ ral: ralInput.value, regione: regioneSelect.value }),
        });

        const dati = await risposta.json();

        if (!risposta.ok) {
            mostraErrore(estraiMessaggioErrore(dati));
            return;
        }

        mostraRisultato(dati);
    } catch (errore) {
        mostraErrore('Impossibile contattare il server. Controlla la connessione e riprova.');
    }
});

function estraiMessaggioErrore(dati) {
    if (dati.errors) {
        return Object.values(dati.errors).flat().join(' ');
    }

    return dati.message || 'Si è verificato un errore imprevisto.';
}

function mostraErrore(messaggio) {
    erroreBox.textContent = messaggio;
    erroreBox.classList.remove('d-none');
    risultatoSection.classList.add('d-none');
}

function nascondiErrore() {
    erroreBox.classList.add('d-none');
    erroreBox.textContent = '';
}

function mostraRisultato(dati) {
    document.getElementById('netto-annuale').textContent = formattaValuta(dati.netto_annuale);
    document.getElementById('netto-mensile').textContent = formattaValuta(dati.netto_mensile_medio);

    document.getElementById('riga-ral').textContent = formattaValuta(dati.input.ral);
    document.getElementById('riga-inps').textContent = '- ' + formattaValuta(dati.inps);
    document.getElementById('riga-irpef-lorda').textContent = formattaValuta(dati.irpef_lorda);
    document.getElementById('riga-detrazione').textContent = '- ' + formattaValuta(dati.detrazione_lavoro_dipendente);
    document.getElementById('riga-irpef-netta').textContent = '- ' + formattaValuta(dati.irpef_netta);
    document.getElementById('riga-addizionale-regionale-label').textContent =
        `Addizionale Regionale (${dati.input.regione_label})`;
    document.getElementById('riga-addizionale-regionale').textContent = '- ' + formattaValuta(dati.addizionale_regionale);
    document.getElementById('riga-addizionale-comunale-label').textContent =
        `Addizionale Comunale (${dati.input.comune_riferimento})`;
    document.getElementById('riga-addizionale-comunale').textContent = '- ' + formattaValuta(dati.addizionale_comunale);
    document.getElementById('riga-totale-trattenute').textContent = '- ' + formattaValuta(dati.totale_trattenute);

    document.getElementById('riga-netto-mese-ordinario').textContent =
        formattaValuta(dati.netto_mensile_dettaglio.mese_ordinario);
    document.getElementById('riga-netto-mese-luglio').textContent =
        formattaValuta(dati.netto_mensile_dettaglio.mese_luglio_con_14esima);
    document.getElementById('riga-netto-mese-dicembre').textContent =
        formattaValuta(dati.netto_mensile_dettaglio.mese_dicembre_con_13esima);

    document.getElementById('badge-incidenza').textContent =
        dati.incidenza_percentuale.toFixed(2).replace('.', ',') + '%';

    document
        .getElementById('badge-bonus')
        .classList.toggle('d-none', !dati.detrazione_dettaglio.bonus_applicato);

    document.getElementById('tfr-informativo').textContent = formattaValuta(dati.tfr_mensile_informativo);

    // Il canvas deve essere visibile (non display:none) prima che Chart.js lo misuri,
    // altrimenti calcola un'area 0x0 e il grafico resta vuoto anche dopo aver tolto d-none
    risultatoSection.classList.remove('d-none');

    renderGrafico(dati);
}
