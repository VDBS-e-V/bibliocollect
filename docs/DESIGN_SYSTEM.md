# Design System

## Hierarchy
```text
Token → Component → Pattern → Template → Page
```

## Tokens
`resources/css/tokens.css` is the canonical CSS token source.

Prefer semantic names:
```css
--bg-page
--bg-surface
--text-primary
--text-muted
--border-default
--action-primary
--action-danger
--focus-ring
```

Avoid product-specific names such as `--invoice-overdue-red`.

## Components
Small reusable building blocks:
Button, Input, Select, Badge, Alert, Card, Modal, Tabs.

## Patterns
Repeated compositions:
PageHeader, SearchAndFilter, EmptyState, FormActions, DataTableToolbar.

## Templates
Abstract page compositions:
List, Detail, Form, Dashboard, Settings.

## Pages
Bind route, surface, layout, template, authorization and domain logic.

Components should consume semantic tokens rather than hard-coded values.
