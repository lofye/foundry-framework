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

        $this->writeFile('docs/features/extension-system/extension-system.spec.md', '# Feature Spec: extension-system');
        $this->writeFile('docs/features/extension-system/extension-system.md', '# Feature: extension-system');
        $this->writeFile('docs/features/extension-system/extension-system.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/docs/features/extension-system/specs', 0777, true);

        $list = $this->service()->list();

        $this->assertSame(['event-system', 'extension-system'], array_column($list['features'], 'slug'));
        $this->assertSame('Features/EventSystem', $list['features'][0]['path']);
        $this->assertSame('docs/features/extension-system', $list['features'][1]['path']);
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

    public function test_verify_reports_duplicate_canonical_and_legacy_features(): void
    {
        $this->writeFile('Features/EventSystem/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('Features/EventSystem/event-system.md', '# Feature: event-system');
        $this->writeFile('Features/EventSystem/event-system.decisions.md', $this->minimalDecision());

        $this->writeFile('docs/features/event-system/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('docs/features/event-system/event-system.md', '# Feature: event-system');
        $this->writeFile('docs/features/event-system/event-system.decisions.md', $this->minimalDecision());

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $this->assertSame('FEATURE_DUPLICATE_CANONICAL_AND_LEGACY', $payload['violations'][0]['code']);
    }

    public function test_verify_emits_warning_when_boundary_enforcement_disabled(): void
    {
        $this->writeFile('.foundry/config/features.json', json_encode(['enforce_boundaries' => false], JSON_THROW_ON_ERROR));

        $payload = $this->service()->verify();

        $this->assertSame('ok', $payload['status']);
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

    public function test_list_prefers_modules_workspace_when_present(): void
    {
        $this->writeFile('Modules/EventSystem/event-system.spec.md', '# Feature Spec: event-system');
        $this->writeFile('Modules/EventSystem/event-system.md', '# Feature: event-system');
        $this->writeFile('Modules/EventSystem/event-system.decisions.md', $this->minimalDecision());

        $list = $this->service()->list();

        $this->assertSame('Modules/EventSystem', $list['features'][0]['path']);
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
        mkdir($this->project->root . '/Modules', 0777, true);
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
        mkdir($this->project->root . '/Modules', 0777, true);
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
        mkdir($this->project->root . '/Modules', 0777, true);
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
        mkdir($this->project->root . '/Modules', 0777, true);
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/src', 0777, true);
        mkdir($this->project->root . '/Features/Blog/tests', 0777, true);
        $this->writeFile('app/features/blog/action.php', "<?php\n");

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('APP_FEATURE_OWNED_SOURCE_OUTSIDE_FEATURE_ROOT', $codes);
    }

    public function test_verify_allows_non_executable_application_feature_without_src_and_tests_when_explicit(): void
    {
        mkdir($this->project->root . '/Modules', 0777, true);
        $this->writeFile('Features/Research/research.spec.md', '# Feature Spec: research');
        $this->writeFile('Features/Research/research.md', '# Feature: research');
        $this->writeFile('Features/Research/research.decisions.md', $this->minimalDecision());
        $this->writeFile('Features/Research/feature.json', json_encode(['executable' => false], JSON_THROW_ON_ERROR));

        $payload = $this->service()->verify();

        $this->assertSame('ok', $payload['status']);
    }

    public function test_verify_rejects_optional_specs_path_when_present_as_file(): void
    {
        mkdir($this->project->root . '/Modules', 0777, true);
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

    public function test_verify_rejects_legacy_feature_context_files_for_application_feature(): void
    {
        mkdir($this->project->root . '/Modules', 0777, true);
        $this->writeFile('Features/Blog/blog.spec.md', '# Feature Spec: blog');
        $this->writeFile('Features/Blog/blog.md', '# Feature: blog');
        $this->writeFile('Features/Blog/blog.decisions.md', $this->minimalDecision());
        mkdir($this->project->root . '/Features/Blog/src', 0777, true);
        mkdir($this->project->root . '/Features/Blog/tests', 0777, true);
        $this->writeFile('docs/features/blog/blog.spec.md', '# Feature Spec: blog');

        $payload = $this->service()->verify();

        $this->assertSame('failed', $payload['status']);
        $codes = array_column($payload['violations'], 'code');
        $this->assertContains('APP_FEATURE_LEGACY_CONTEXT_LOCATION', $codes);
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
