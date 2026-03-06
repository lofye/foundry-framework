# Foundry Agent Guide

Use this when working on a Foundry application (not framework internals).

## Install
```bash
composer require lofye/foundry:^0.1
php vendor/bin/foundry init app . --name=acme/my-foundry-app --force
composer install
```

## Command Rule
- In app repos, always use `php vendor/bin/foundry ...`
- In the framework repo itself, use `php bin/foundry ...`

## Safe Edit Loop
```bash
php vendor/bin/foundry inspect feature <feature> --json
php vendor/bin/foundry inspect context <feature> --json
# edit files under app/features/<feature>/*
php vendor/bin/foundry generate indexes --json
php vendor/bin/foundry verify feature <feature> --json
php vendor/bin/foundry verify contracts --json
vendor/bin/phpunit
```

## Rules
- Treat `app/features/*` as source of truth.
- Do not hand-edit `app/generated/*`; regenerate instead.
- Keep edits feature-local unless explicitly doing framework work.
