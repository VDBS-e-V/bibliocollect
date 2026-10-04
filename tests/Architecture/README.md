# Architecture Tests

Recommended rules:
- Modules must not depend on Surfaces.
- Foundation must not depend on concrete Modules.
- Actions live in `Actions`.
- Queries live in `Queries`.
- Models do not import Blade/Livewire.
- No circular module dependencies.
- Pages may depend on Templates/Patterns/Components, not the reverse.
- Components must not depend on Pages.
- Secrets must never be committed.
