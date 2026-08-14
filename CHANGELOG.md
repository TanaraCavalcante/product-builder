## [2026-08-15] - Installazione Laravel Boost e piano calcolatore RAL

### Aggiunto
- Installato e configurato Laravel Boost (guidelines, skills, MCP server) per assistere Claude Code nella verifica del codice prodotto
- Creato `docs/plan-salary-calculator.md` con il piano di implementazione del calcolatore RAL → Netto

### Modificato
- Consolidato il changelog di branch (`CHANGELOG-main.md`) in `CHANGELOG.md`, dato che il lavoro procede direttamente su `main` senza branch di feature
- Applicati i fix di stile Pint richiesti dalle guidelines di Boost (import FQCN espliciti in `User.php`, `UserFactory.php`, `bootstrap/providers.php`, `config/auth.php`)

## [2026-08-14] - Configurazione ambiente senza database e pulizia docs

### Modificato
- Semplificato `.env.example` per un'app senza database (session/cache/queue su file/sync, rimossi DB/Redis/AWS/mail non utilizzati)
- Rimossi riferimenti a Livewire dalla checklist di `docs/01-project-spec.md` (stack confermato: Blade + Bootstrap 5 CDN + JS puro)
