# App Scaffolding Examples

This folder provides minimal app-scaffolding source-of-truth definition examples.

Copy these definition files into a Foundry app's `app/definitions/*` tree, then run the commands below from that app with `php vendor/bin/foundry ...`.

## A. Starter
- `starter/server-rendered.starter.yaml`

## B. Resource CRUD
- `blog/posts.resource.yaml`
- `listing/posts.list.yaml`

## C. Admin
- `admin/posts.admin.yaml`

## D. Uploads
- `uploads/avatar.uploads.yaml`

Use these as deterministic inputs for:
- `php vendor/bin/foundry generate starter server-rendered --json`
- `php vendor/bin/foundry generate starter api --json`
- `php vendor/bin/foundry generate resource posts --definition=app/definitions/resources/posts.resource.yaml --json`
- `php vendor/bin/foundry inspect resource posts --json`
- `php vendor/bin/foundry generate admin-resource posts --definition=app/definitions/admin/posts.admin.yaml --json`
- `php vendor/bin/foundry generate uploads avatar --json`

`listing/posts.list.yaml` is the companion listing definition used by the resource and admin projections after generation.
