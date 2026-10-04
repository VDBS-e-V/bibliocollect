# Architecture

## Layers

```text
Foundation
Domain Modules
Application Surfaces
Presentation / Design System
```

### Foundation
Reusable infrastructure only:
- console/tooling
- architecture validation
- environment handling
- diagnostics
- shared contracts
- generic authorization infrastructure
- module discovery
- generation/refactoring helpers
- production/health tooling

Foundation must not know concrete product domains.

### Modules
Vertical business domains. A real application may later contain domains such as Identity, Billing, Orders, Projects or Catalog, but the foundation requires none of them.

Typical module folders:
```text
Actions
Contracts
DTOs
Enums
Events
Exceptions
Http
Jobs
Listeners
Models
Policies
Queries
Services
Support
database
routes
tests
module.json
```

### Surfaces
Application areas with their own routes/layout/navigation/interactions, e.g. Public, Application, Administration or Portal.

Modules must not depend on concrete Surfaces.

## Application logic

### Actions
Explicit state-changing use cases. Prefer imperative names such as `CreateAccount`, `PublishArticle`, `ApproveRequest`.

### Queries
Read/search/aggregate/derive without changing persistent state.

### Services
Reusable domain logic that is not naturally one Action or Query.

### DTOs
Explicit contracts when argument lists or return structures become complex.

## Design hierarchy
```text
Token → Component → Pattern → Template → Page
```

Allowed direction:
```text
Page      → Template / Pattern / Component
Template  → Pattern / Component
Pattern   → Pattern / Component
Component → Component
All       → Tokens
```

Avoid reverse dependencies and cycles.

## General data rules
- use DB constraints and indexes intentionally
- use transactions for multi-write business operations
- do not store money as floats
- keep important history immutable where appropriate
- never encode authorization only in the UI
- keep secrets out of the repository
