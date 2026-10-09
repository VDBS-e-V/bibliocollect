# Conventions

## PHP
- constructor injection
- explicit return types
- enums for bounded states
- transactions around multi-write business operations
- small public APIs
- business logic outside controllers/Livewire screens

## Naming
Writes:
`Create*`, `Update*`, `Delete*`, `Archive*`, `Publish*`, `Approve*`, `Assign*`

Reads:
`Get*`, `Find*`, `Search*`, `List*`, `Calculate*`

## Database
- append-only migrations after release
- foreign keys and unique constraints where appropriate
- explicit indexes for common queries
- UTC timestamps internally unless the domain requires otherwise
- integer minor units for money

## Configuration
- `.env.example` is canonical
- secrets never committed
- use `config()` instead of direct `env()` outside config files
- production runs with debug disabled

## Git
Seit der Zusammenarbeit zu zweit ist `main` geschützt: nur Pull Requests mit grünen Tests, Squash-Merge, kein Direkt-Push. Ablauf, Branch-Namen
(`feature/<nr>-…`, `fix/<nr>-…`, `chore/…`), Regeln für Migrationen und das Veröffentlichen per Tag stehen in `CONTRIBUTING.md`.
