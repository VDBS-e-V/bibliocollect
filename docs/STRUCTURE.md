# Generic Directory Structure

```text
app/
├── Foundation/
│   ├── Console/
│   ├── Contracts/
│   ├── Exceptions/
│   ├── Generation/
│   ├── Http/
│   ├── Providers/
│   ├── Support/
│   └── Validation/
├── Modules/
│   ├── README.md
│   └── _Template/
└── Surfaces/
    ├── README.md
    └── _Template/

resources/
├── css/
│   ├── app.css
│   ├── tokens.css
│   ├── foundation/
│   ├── components/
│   ├── patterns/
│   └── parts/
└── views/
    ├── components/
    ├── patterns/
    ├── templates/
    ├── layouts/
    └── pages/

tests/
├── Architecture/
├── Feature/
└── Unit/

docs/
config/
.github/workflows/
```

The `_Template` folders are references only. Real modules should create only the directories they need.
