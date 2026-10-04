# Foundation Checklist

## New project
- [ ] Fresh Laravel application
- [ ] Livewire
- [ ] Pest
- [ ] Pint
- [ ] PHPStan/Larastan
- [ ] Copy foundation structure
- [ ] Define initial Surfaces
- [ ] Define domain Modules
- [ ] Define permission model
- [ ] Define navigation model
- [ ] Define design tokens
- [ ] Canonical `.env.example`
- [ ] CI
- [ ] Architecture tests
- [ ] Health endpoint
- [ ] Backup/restore strategy
- [ ] Deployment documentation

## Before production
- [ ] `APP_DEBUG=false`
- [ ] secrets excluded from git
- [ ] dedicated database user
- [ ] migrations tested on production DB engine
- [ ] backup tested
- [ ] restore tested
- [ ] health check tested
- [ ] error pages verified
- [ ] tests green
- [ ] static analysis acceptable
- [ ] asset build successful
