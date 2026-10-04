# Generic Laravel Web Foundation

Domain-neutral foundation for modern Laravel web applications.

## Baseline stack
- PHP 8.4+
- Laravel 13
- Livewire 4
- Blade
- Tailwind CSS 4
- Vite
- Pest
- SQLite for local development/tests
- MySQL/MariaDB for production
- Pint + PHPStan/Larastan

This is a structural overlay for a fresh Laravel project, not a finished product or admin template.

## Core model
- Foundation = reusable infrastructure
- Modules = vertical business domains
- Surfaces = application areas
- Design hierarchy = Token → Component → Pattern → Template → Page
- Actions change state
- Queries read/derive
- Services contain reusable domain logic
- Authorization is explicit and server-side
- Architecture and production-readiness are testable

See `docs/ARCHITECTURE.md` and `docs/STRUCTURE.md`.
