Implemented the first Thresholds v2 slice on top of the existing app.

The core work is in [ThresholdsV2Repository.php](/Users/lo_fye/code/thresholds/app/Support/ThresholdsV2Repository.php), [ThresholdsV2FeatureHandlers.php](/Users/lo_fye/code/thresholds/app/Support/ThresholdsV2FeatureHandlers.php), and [ThresholdsV2JobRunners.php](/Users/lo_fye/code/thresholds/app/Support/ThresholdsV2JobRunners.php). I added explicit v2 tables to [Database.php](/Users/lo_fye/code/thresholds/app/Support/Database.php) and the three migration files, plus new Foundry feature surfaces for:
- `GET /my/thresholds`, `POST /my/thresholds`
- `GET /my/journals`, `POST /my/journals`
- `GET /my/insights`
- `POST /my/thresholds/export`
- job features `workflow_streak_update`, `insights_generate`, and `notifications_dispatch`

The new feature manifests live under [app/features/thresholds_create](/Users/lo_fye/code/thresholds/app/features/thresholds_create), [app/features/thresholds_view_timeline](/Users/lo_fye/code/thresholds/app/features/thresholds_view_timeline), [app/features/journals_create_entry](/Users/lo_fye/code/thresholds/app/features/journals_create_entry), [app/features/journals_view](/Users/lo_fye/code/thresholds/app/features/journals_view), [app/features/insights_view](/Users/lo_fye/code/thresholds/app/features/insights_view), [app/features/thresholds_export](/Users/lo_fye/code/thresholds/app/features/thresholds_export), [app/features/workflow_streak_update](/Users/lo_fye/code/thresholds/app/features/workflow_streak_update), [app/features/insights_generate](/Users/lo_fye/code/thresholds/app/features/insights_generate), and [app/features/notifications_dispatch](/Users/lo_fye/code/thresholds/app/features/notifications_dispatch). I also added v2 docs in [THRESHOLDS_V2.md](/Users/lo_fye/code/thresholds/docs/THRESHOLDS_V2.md) and updated [README.md](/Users/lo_fye/code/thresholds/README.md).

Verification:
- `php vendor/bin/foundry generate indexes --json`
- `php vendor/bin/foundry verify contracts --json`
- `php vendor/bin/phpunit`
- `XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text`

Results:
- contracts verification passed
- full PHPUnit suite passed: `120` tests, `2225` assertions
- `foundry explain` is still Pro-gated in this local environment, so verification is via manifests, generated indexes, docs alignment, and runtime tests
- overall project coverage is still low at `44.50%`
- the new v2 PHP layer is mostly covered, but [ThresholdsV2FeatureHandlers.php](/Users/lo_fye/code/thresholds/app/Support/ThresholdsV2FeatureHandlers.php) is at `89.63%` line coverage, so the spec’s `>= 90%` bar is not cleanly satisfied yet

There are also generated artifact changes in `app/generated/*`, a new generated [query_index.php](/Users/lo_fye/code/thresholds/app/generated/query_index.php), and an empty untracked `app/.foundry/` directory from Foundry tooling.

Natural next steps:
1. Move the dashboard and home flows onto the v2 threshold/journal/insight surfaces instead of the legacy moments model.
2. Add threshold editing/detail routes so the `thresholds.edit` permission target is exercised by runtime behavior, not just manifests/docs.
3. Close the remaining v2 coverage gap and, if Pro is available later, add real `foundry explain` assertions for the representative targets.

107 files changed
+4355
-10
Undo
README.md
app/Support/Database.php
app/Support/Domain.php
app/Support/Html.php
app/Support/ThresholdsV2FeatureHandlers.php
app/Support/ThresholdsV2JobRunners.php
app/Support/ThresholdsV2Repository.php
app/features/insights_generate/action.php
app/features/insights_generate/cache.yaml
app/features/insights_generate/context.manifest.json
app/features/insights_generate/events.yaml
app/features/insights_generate/feature.yaml
app/features/insights_generate/input.schema.json
app/features/insights_generate/jobs.yaml
app/features/insights_generate/output.schema.json
app/features/insights_generate/permissions.yaml
app/features/insights_generate/prompts.md
app/features/insights_view/action.php
app/features/insights_view/cache.yaml
app/features/insights_view/context.manifest.json
app/features/insights_view/events.yaml
app/features/insights_view/feature.yaml
app/features/insights_view/input.schema.json
app/features/insights_view/jobs.yaml
app/features/insights_view/output.schema.json
app/features/insights_view/permissions.yaml
app/features/insights_view/prompts.md
app/features/journals_create_entry/action.php
app/features/journals_create_entry/cache.yaml
app/features/journals_create_entry/context.manifest.json
app/features/journals_create_entry/events.yaml
app/features/journals_create_entry/feature.yaml
app/features/journals_create_entry/input.schema.json
app/features/journals_create_entry/jobs.yaml
app/features/journals_create_entry/output.schema.json
app/features/journals_create_entry/permissions.yaml
app/features/journals_create_entry/prompts.md
app/features/journals_view/action.php
app/features/journals_view/cache.yaml
app/features/journals_view/context.manifest.json
app/features/journals_view/events.yaml
app/features/journals_view/feature.yaml
app/features/journals_view/input.schema.json
app/features/journals_view/jobs.yaml
app/features/journals_view/output.schema.json
app/features/journals_view/permissions.yaml
app/features/journals_view/prompts.md
app/features/notifications_dispatch/action.php
app/features/notifications_dispatch/cache.yaml
app/features/notifications_dispatch/context.manifest.json
app/features/notifications_dispatch/events.yaml
app/features/notifications_dispatch/feature.yaml
app/features/notifications_dispatch/input.schema.json
app/features/notifications_dispatch/jobs.yaml
app/features/notifications_dispatch/output.schema.json
app/features/notifications_dispatch/permissions.yaml
app/features/notifications_dispatch/prompts.md
app/features/thresholds_create/action.php
app/features/thresholds_create/cache.yaml
app/features/thresholds_create/context.manifest.json
app/features/thresholds_create/events.yaml
app/features/thresholds_create/feature.yaml
app/features/thresholds_create/input.schema.json
app/features/thresholds_create/jobs.yaml
app/features/thresholds_create/output.schema.json
app/features/thresholds_create/permissions.yaml
app/features/thresholds_create/prompts.md
app/features/thresholds_export/action.php
app/features/thresholds_export/cache.yaml
app/features/thresholds_export/context.manifest.json
app/features/thresholds_export/events.yaml
app/features/thresholds_export/feature.yaml
app/features/thresholds_export/input.schema.json
app/features/thresholds_export/jobs.yaml
app/features/thresholds_export/output.schema.json
app/features/thresholds_export/permissions.yaml
app/features/thresholds_export/prompts.md
app/features/thresholds_view_timeline/action.php
app/features/thresholds_view_timeline/cache.yaml
app/features/thresholds_view_timeline/context.manifest.json
app/features/thresholds_view_timeline/events.yaml
app/features/thresholds_view_timeline/feature.yaml
app/features/thresholds_view_timeline/input.schema.json
app/features/thresholds_view_timeline/jobs.yaml
app/features/thresholds_view_timeline/output.schema.json
app/features/thresholds_view_timeline/permissions.yaml
app/features/thresholds_view_timeline/prompts.md
app/features/workflow_streak_update/action.php
app/features/workflow_streak_update/cache.yaml
app/features/workflow_streak_update/context.manifest.json
app/features/workflow_streak_update/events.yaml
app/features/workflow_streak_update/feature.yaml
app/features/workflow_streak_update/input.schema.json
app/features/workflow_streak_update/jobs.yaml
app/features/workflow_streak_update/output.schema.json
app/features/workflow_streak_update/permissions.yaml
app/features/workflow_streak_update/prompts.md
app/platform/migrations/202603060001_thresholds_sqlite_schema.sql
app/platform/migrations/202603060002_thresholds_postgres_schema.sql
app/platform/migrations/202603060003_thresholds_mysql_schema.sql
app/platform/public/sitemap.xml
docs/THRESHOLDS_V2.md
tests/thresholds_v2_docs_alignment_test.php
tests/thresholds_v2_feature_files_test.php
tests/thresholds_v2_graph_verification_test.php
tests/thresholds_v2_jobs_test.php
tests/thresholds_v2_runtime_test.php

------------------------------------------------------------------------------------------
