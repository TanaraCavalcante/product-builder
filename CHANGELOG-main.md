## [2026-08-14] - Configurazione ambiente senza database e pulizia docs

### Modificato
- Semplificato `.env.example` per un'app senza database (session/cache/queue su file/sync, rimossi DB/Redis/AWS/mail non utilizzati)
- Rimossi riferimenti a Livewire dalla checklist di `docs/01-project-spec.md` (stack confermato: Blade + Bootstrap 5 CDN + JS puro)
