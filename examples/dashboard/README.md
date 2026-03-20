# Dashboard Example

`examples/dashboard` is a supplemental authenticated UI/API slice. It is useful when you want a compact example of login, profile, notifications, and media-upload style feature folders.

What it teaches:

- authenticated route structure
- profile-style read endpoints
- upload-oriented feature organization
- execution-plan inspection for auth-heavy routes

How to use it:

1. Copy `examples/dashboard/app/features/*` into a Foundry app's `app/features/` tree.
2. Optionally copy `examples/dashboard/app/platform/public/index.php`.
3. From that generated app, run:

```bash
php vendor/bin/foundry compile graph --json
php vendor/bin/foundry inspect graph --command="POST /login" --json
php vendor/bin/foundry inspect graph --feature=upload_avatar --json
php vendor/bin/foundry doctor --feature=login --json
php vendor/bin/foundry verify graph --json
php vendor/bin/foundry verify pipeline --json
```
