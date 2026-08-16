// Intercetta il submit del form, invia la RAL al backend via fetch() e aggiorna
// il DOM con il risultato, senza ricaricare la pagina.

const RAL_SOGLIA_AVVISO = 200000;

const form = document.getElementById('calcolatore-form');
const ralInput = document.getElementById('ral');
const ralAvviso = document.getElementById('ral-avviso');
const ralPulisciBtn = document.getElementById('ral-pulisci');
const erroreBox = document.getElementById('errore');
const risultatoSection = document.getElementById('risultato');

const formatterValuta = new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' });

function formattaValuta(valore) {
    return formatterValuta.format(valore);
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
            body: JSON.stringify({ ral: ralInput.value }),
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
    document.getElementById('riga-addizionale-regionale').textContent = '- ' + formattaValuta(dati.addizionale_regionale);
    document.getElementById('riga-addizionale-comunale').textContent = '- ' + formattaValuta(dati.addizionale_comunale);
    document.getElementById('riga-totale-trattenute').textContent = '- ' + formattaValuta(dati.totale_trattenute);

    document.getElementById('badge-incidenza').textContent =
        dati.incidenza_percentuale.toFixed(2).replace('.', ',') + '%';

    document
        .getElementById('badge-bonus')
        .classList.toggle('d-none', !dati.detrazione_dettaglio.bonus_applicato);

    document.getElementById('tfr-informativo').textContent = formattaValuta(dati.tfr_mensile_informativo);

    risultatoSection.classList.remove('d-none');
}
