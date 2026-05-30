<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\FeatureSystem\FeatureWorkspaceService;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class FeatureWorkspaceServiceTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_list_prefers_canonical_layout_and_sorts_deterministically(): void
    {
        $this->writeFile('Features/EventSystem/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('Features/EventSystem/event-system.md', '# Feature: event-system');
        $this->writeFile('Features/EventSystem/event-system.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/EventSystem/specs', 0777, true);
        mkdir($this->project->root . '/Features/EventSystem/src', 0777, true);
        mkdir($this->project->root . '/Features/EventSystem/tests', 0777, true);

        $this->writeFile('Features/ExtensionSystem/extension-system.spec.md', '# Feature Spec: extension-system');
        $this->writeFile('Features/ExtensionSystem/extension-system.md', '# Feature: extension-system');
        $this->writeFile('Features/ExtensionSystem/extension-system.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/ExtensionSystem/specs', 0777, true);

        $list = $this->service()->list();

        $this->assertSame(['event-system', 'extension-system'], array_column($list['features'], 'slug'));
        $this->assertSame('Features/EventSystem', $list['features'][0]['path']);
        $this->assertSame('Features/ExtensionSystem', $list['features'][1]['path']);
    }

    public function test_empty_workspace_without_features_root_verifies_cleanly(): void
    {
        $payload = $this->service()->verify();

        $this->assertSame('ok', $payload['status']);
        $this->assertSame([], $payload['violations']);
        $this->assertSame([], $this->service()->list()['features']);
    }

    public function test_non_directory_entries_under_features_root_are_ignored(): void
    {
        $this->writeFile('Features/README.md', "# Features\n");

        $payload = $this->service()->verify();

        $this->assertSame('ok', $payload['status']);
        $this->assertSame([], $this->service()->list()['features']);
    }

    public function test_inspect_reads_manifest_dependencies_in_sorted_order(): void
    {
        $this->writeFile('Features/EventSystem/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('Features/EventSystem/event-system.md', '# Feature: event-system');
        $this->writeFile('Features/EventSystem/event-system.decisions.md', $this->minimalDecision());
        $this->writeFile('Features/EventSystem/feature.json', json_encode([
            'slug' => 'event-system',
            'name' => 'EventSystem',
            'dependencies' => ['mcp-server', 'extension-system', 'mcp-server'],
            'boundary' => ['enforced' => true],
        ], JSON_THROW_ON_ERROR));

        $payload = $this->service()->inspect('event-system');

        $this->assertSame(
            ['extension-system', 'mcp-server'],
            $payload['feature']['dependencies'],
        );
    }

    public function test_inspect_skips_non_matching_rows_before_returning_requested_feature(): void
    {
        $this->writeFile('Features/Alpha/alpha.spec.md', '# Feature Spec: alpha');
        $this->writeFile('Features/Alpha/alpha.md', '# Feature: alpha');
        $this->writeFile('Features/Alpha/alpha.decisions.md', $this->minimalDecision());
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());

        $payload = $this->service()->inspect('blog');

        $this->assertSame('blog', $payload['feature']['slug']);
    }

    public function test_inspect_unknown_feature_reports_canonical_not_found_error(): void
    {
        $this->expectException(\Foundry\Support\FoundryError::class);
        $this->expectExceptionMessage('Feature not found');

        $this->service()->inspect('missing-feature');
    }

    public function test_map_returns_owned_paths_for_canonical_feature(): void
    {
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        $this->writeFile('Features/Blog/src/Action.php', "<?php\n");
        $this->writeFile('Features/Blog/tests/blog_feature_test.php', "<?php\n");
        $this->writeFile('Features/Blog/docs/notes.md', "# Notes\n");

        $payload = $this->service()->map();

        $this->assertSame('blog', $payload['features'][0]['slug']);
        $this->assertSame(
            [
                'Features/Blog/blog.decisions.md',
                'Features/Blog/blog.md',
                'Features/Blog/blog.spec.md',
                'Features/Blog/docs/notes.md',
                'Features/Blog/src/Action.php',
                'Features/Blog/tests/blog_feature_test.php',
            ],
            $payload['features'][0]['owned_paths'],
        );
    }

    public function test_feature_yaml_supplies_canonical_slug_when_context_is_absent(): void
    {
        $this->writeFile('Features/PublishPost/feature.yaml', "feature: publish_post\n");
        mkdir($this->project->root . '/Features/PublishPost/src', 0777, true);
        mkdir($this->project->root . '/Features/PublishPost/tests', 0777, true);

        $list = $this->service()->list();
        $payload = $this->service()->verify();

        $this->assertSame('publish-post', $list['features'][0]['slug']);
        $this->assertSame('failed', $payload['status']);
        $this->assertContains('APP_FEATURE_MISSING_CONTEXT', array_column($payload['violations'], 'code'));
    }

    public function test_pascal_feature_directory_without_manifest_derives_default_slug(): void
    {
        mkdir($this->project->root . '/Features/EditorialCalendar/src', 0777, true);
        mkdir($this->project->root . '/Features/EditorialCalendar/tests', 0777, true);

        $list = $this->service()->list();

        $this->assertSame('editorial-calendar', $list['features'][0]['slug']);
    }

    public function test_verify_reports_legacy_docs_feature_context(): void
    {
        $this->writeFile('Features/EventSystem/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('Features/EventSystem/event-system.md', '# Feature: event-system');
        $this->writeFile('Features/EventSystem/event-system.decisions.md', $this->minimalDecision());

        $this->writeFile('docs/features/event-system/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('docs/features/event-system/event-system.md', '# Feature: event-system');
        $this->writeFile('docs/features/event-system/event-system.decisions.md', $this->minimalDecision());

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $this->assertSame('DOCS_FEATURES_LEGACY_CONTEXT_PRESENT', $payload['violations'][0]['code']);
        $this->assertSame('docs/features/event-system', $payload['violations'][0]['path']);
    }

    public function test_verify_emits_warning_when_boundary_enforcement_disabled(): void
    {
        $this->writeFile('.foundry/config/features.json', json_encode(['enforce_boundaries' => false], JSON_THROW_ON_ERROR));

        $payload = $this->service()->verify();

        $this->assertSame('ok', $payload['status']);
        $this->assertSame('disabled', $payload['enforcement']);
        $this->assertSame('FEATURE_BOUNDARY_ENFORCEMENT_DISABLED', $payload['warnings'][0]['code']);
    }

    public function test_verify_reads_boundary_enforcement_from_nested_php_config(): void
    {
        $this->writeFile('config/foundry/features.php', "<?php return ['features' => ['enforce_boundaries' => false]];\n");

        $payload = $this->service()->verify();

        $this->assertSame('disabled', $payload['enforcement']);
        $this->assertSame('FEATURE_BOUNDARY_ENFORCEMENT_DISABLED', $payload['warnings'][0]['code']);
    }

    public function test_verify_reads_boundary_enforcement_from_top_level_php_config(): void
    {
        $this->writeFile('config/foundry/features.php', "<?php return ['enforce_boundaries' => false];\n");

        $payload = $this->service()->verify();

        $this->assertSame('disabled', $payload['enforcement']);
        $this->assertSame('FEATURE_BOUNDARY_ENFORCEMENT_DISABLED', $payload['warnings'][0]['code']);
    }

    public function test_verify_does_not_require_empty_optional_feature_directories(): void
    {
        $this->writeFile('Features/EventSystem/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('Features/EventSystem/event-system.md', '# Feature: event-system');
        $this->writeFile('Features/EventSystem/event-system.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/EventSystem/src', 0777, true);
        mkdir($this->project->root . '/Features/EventSystem/tests', 0777, true);

        $payload = $this->service()->verify();
        $list = $this->service()->list();

        $this->assertSame('ok', $payload['status']);
        $this->assertSame([], $payload['violations']);
        $this->assertFalse($list['features'][0]['has_specs']);
        $this->assertTrue($list['features'][0]['has_src']);
        $this->assertTrue($list['features'][0]['has_tests']);
    }

    public function test_list_excludes_framework_modules_from_application_feature_workspace(): void
    {
        $this->writeFile('Modules/EventSystem/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('Modules/EventSystem/event-system.md', '# Feature: event-system');
        $this->writeFile('Modules/EventSystem/event-system.decisions.md', $this->minimalDecision());

        $list = $this->service()->list();

        $this->assertSame([], $list['features']);
    }

    public function test_verify_reports_framework_module_misplaced_under_features_root_when_modules_root_exists(): void
    {
        $this->writeFile('Modules/StateStore/state-store.spec.md', '# Feature Spec: state-store');
        $this->writeFile('Modules/StateStore/state-store.md', '# Feature: state-store');
        $this->writeFile('Modules/StateStore/state-store.decisions.md', $this->minimalDecision());
        $this->writeFile('Features/StateStore/state-store.spec.md', '# Feature Spec: state-store');
        $this->writeFile('Features/StateStore/state-store.md', '# Feature: state-store');
        $this->writeFile('Features/StateStore/state-store.decisions.md', $this->minimalDecision());

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $this->assertSame('FRAMEWORK_MODULE_DUPLICATE_LOCATION', $payload['violations'][0]['code']);
        $this->assertSame('Features/StateStore', $payload['violations'][0]['path']);
        $this->assertSame('Modules/StateStore', $payload['violations'][0]['details']['expected_path']);
    }

    public function test_verify_reports_duplicate_framework_module_location_when_modules_and_features_both_exist(): void
    {
        $this->writeFile('Modules/StateStore/state-store.spec.md', '# Feature Spec: state-store');
        $this->writeFile('Modules/StateStore/state-store.md', '# Feature: state-store');
        $this->writeFile('Modules/StateStore/state-store.decisions.md', $this->minimalDecision());
        $this->writeFile('Features/StateStore/state-store.spec.md', '# Feature Spec: state-store');
        $this->writeFile('Features/StateStore/state-store.md', '# Feature: state-store');
        $this->writeFile('Features/StateStore/state-store.decisions.md', $this->minimalDecision());

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('FRAMEWORK_MODULE_DUPLICATE_LOCATION', $codes);
    }

    public function test_verify_accepts_valid_application_feature_layout_under_features_root(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/src', 0777, true);
        mkdir($this->project->root . '/Features/Blog/tests', 0777, true);
        mkdir($this->project->root . '/Features/Blog/specs', 0777, true);
        mkdir($this->project->root . '/Features/Blog/plans', 0777, true);
        mkdir($this->project->root . '/Features/Blog/docs', 0777, true);

        $payload = $this->service()->verify();

        $this->assertSame('ok', $payload['status']);
    }

    public function test_verify_rejects_executable_application_feature_without_src_directory(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/tests', 0777, true);

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('APP_FEATURE_RUNTIME_SRC_MISSING', $codes);
    }

    public function test_verify_rejects_executable_application_feature_without_tests_directory(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/src', 0777, true);

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('APP_FEATURE_RUNTIME_TESTS_MISSING', $codes);
    }

    public function test_verify_rejects_feature_owned_legacy_app_feature_source_path_when_attributable(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/src', 0777, true);
        mkdir($this->project->root . '/Features/Blog/tests', 0777, true);
        $this->writeFile('app/features/blog/action.php', "<?php\n");

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('APP_FEATURES_LEGACY_DIRECTORY_PRESENT', $codes);
    }

    public function test_verify_allows_non_executable_application_feature_without_src_and_tests_when_explicit(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Research/research.spec.md', '# Feature Spec: research');
        $this->writeFile('Features/Research/research.md', '# Feature: research');
        $this->writeFile('Features/Research/research.decisions.md', $this->minimalDecision());
        $this->writeFile('Features/Research/feature.json', json_encode(['executable' => false], JSON_THROW_ON_ERROR));

        $payload = $this->service()->verify();

        $this->assertSame('ok', $payload['status']);
    }

    public function test_invalid_feature_json_keeps_feature_executable_by_default(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Research/research.spec.md', '# Feature Spec: research');
        $this->writeFile('Features/Research/research.md', '# Feature: research');
        $this->writeFile('Features/Research/research.decisions.md', $this->minimalDecision());
        $this->writeFile('Features/Research/feature.json', '{not-json');

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $this->assertContains('APP_FEATURE_RUNTIME_SRC_MISSING', array_column($payload['violations'], 'code'));
        $this->assertContains('APP_FEATURE_RUNTIME_TESTS_MISSING', array_column($payload['violations'], 'code'));
    }

    public function test_verify_rejects_optional_specs_path_when_present_as_file(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/src', 0777, true);
        mkdir($this->project->root . '/Features/Blog/tests', 0777, true);
        $this->writeFile('Features/Blog/specs', "not-a-directory\n");

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('APP_FEATURE_SPECS_PATH_INVALID', $codes);
    }

    public function test_verify_rejects_optional_outcomes_and_docs_paths_when_present_as_files(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/src', 0777, true);
        mkdir($this->project->root . '/Features/Blog/tests', 0777, true);
        $this->writeFile('Features/Blog/outcomes', "not-a-directory\n");
        $this->writeFile('Features/Blog/docs', "not-a-directory\n");

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('APP_FEATURE_OUTCOMES_PATH_INVALID', $codes);
        $this->assertContains('APP_FEATURE_DOCS_PATH_INVALID', $codes);
    }

    public function test_verify_rejects_legacy_feature_context_files_for_application_feature(): void
    {
        $this->ensureDirectory('Modules');
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/src', 0777, true);
        mkdir($this->project->root . '/Features/Blog/tests', 0777, true);
        $this->writeFile('docs/features/blog/blog.spec.md', '# Feature Spec: blog');

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('DOCS_FEATURES_LEGACY_CONTEXT_PRESENT', $codes);
    }

    public function test_docs_features_without_app_context_files_are_ignored(): void
    {
        $this->writeFile('docs/features/blog/readme.md', "# Blog docs\n");

        $payload = $this->service()->verify();

        $this->assertSame('ok', $payload['status']);
        $this->assertSame([], $payload['violations']);
    }

    private function service(): FeatureWorkspaceService
    {
        return new FeatureWorkspaceService(new Paths($this->project->root));
    }

    private function writeFile(string $relativePath, string $contents): void
    {
        $absolutePath = $this->project->root . '/' . $relativePath;
        $directory = dirname($absolutePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($absolutePath, $contents);
    }

    private function ensureDirectory(string $relativePath): void
    {
        $absolutePath = $this->project->root . '/' . $relativePath;
        if (!is_dir($absolutePath)) {
            mkdir($absolutePath, 0777, true);
        }
    }

    private function minimalDecision(): string
    {
        return <<<'MD'
### Decision: baseline

Timestamp: 2026-05-03T10:00:00-04:00

**Context**

- baseline

**Decision**

- baseline

**Reasoning**

- baseline

**Alternatives Considered**

- baseline

**Impact**

- baseline

**Spec Reference**

- baseline
MD;
    }
}
