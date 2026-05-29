<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Support\ApiSurfaceRegistry;
use PHPUnit\Framework\TestCase;

final class ApiSurfaceRegistryTest extends TestCase
{
    public function test_describe_returns_classification_metadata(): void
    {
        $registry = new ApiSurfaceRegistry();
        $description = $registry->describe();

        $this->assertSame(1, $description['schema_version']);
        $this->assertArrayHasKey('policy', $description);
        $this->assertArrayHasKey('php_namespace_rules', $description);
        $this->assertArrayHasKey('cli_commands', $description);
        $this->assertArrayHasKey('generated_metadata_formats', $description);
    }

    public function test_classifies_php_symbols_by_namespace_and_exact_extension_hook(): void
    {
        $registry = new ApiSurfaceRegistry();

        $public = $registry->classifyPhpSymbol('Foundry\\Feature\\FeatureAction');
        $extension = $registry->classifyPhpSymbol('Foundry\\Compiler\\Extensions\\CompilerExtension');
        $explainContributor = $registry->classifyPhpSymbol('Foundry\\Explain\\Contributors\\ExplainContributorInterface');
        $explainContribution = $registry->classifyPhpSymbol('Foundry\\Explain\\Contributors\\ExplainContribution');
        $internal = $registry->classifyPhpSymbol('Foundry\\Compiler\\Passes\\ValidatePass');
        $experimental = $registry->classifyPhpSymbol('Foundry\\AI\\AIProvider');

        $this->assertSame('public_api', $public['classification']);
        $this->assertSame('namespace_rule', $public['matched_by']);
        $this->assertSame('extension_api', $extension['classification']);
        $this->assertSame('exact_symbol', $extension['matched_by']);
        $this->assertSame('extension_api', $explainContributor['classification']);
        $this->assertSame('exact_symbol', $explainContributor['matched_by']);
        $this->assertSame('extension_api', $explainContribution['classification']);
        $this->assertSame('exact_symbol', $explainContribution['matched_by']);
        $this->assertSame('internal_api', $internal['classification']);
        $this->assertSame('experimental_api', $experimental['classification']);
        $this->assertSame('experimental', $experimental['stability']);
    }

    public function test_classifies_cli_commands_by_stability(): void
    {
        $registry = new ApiSurfaceRegistry();

        $stable = $registry->classifyCliCommand(['compile', 'graph']);
        $cacheInspect = $registry->classifyCliCommand(['cache', 'inspect']);
        $scaffold = $registry->classifyCliCommand(['new', 'demo-app']);
        $graphInspect = $registry->classifyCliCommand(['graph', 'inspect']);
        $graphVisualize = $registry->classifyCliCommand(['graph', 'visualize']);
        $graphExport = $registry->classifyCliCommand(['export', 'graph']);
        $explain = $registry->classifyCliCommand(['explain', 'publish_post']);
        $generatePrompt = $registry->classifyCliCommand(['generate', 'Add', 'bookmarks']);
        $generateWorkflow = $registry->classifyCliCommand(['generate', '--workflow=generate-workflow.json']);
        $generateTemplate = $registry->classifyCliCommand(['generate', '--template=feature.recipe']);
        $licenseStatus = $registry->classifyCliCommand(['license', 'status']);
        $features = $registry->classifyCliCommand(['features']);
        $login = $registry->classifyCliCommand(['login', '--user=demo', '--token=abc']);
        $logout = $registry->classifyCliCommand(['logout']);
        $whoami = $registry->classifyCliCommand(['whoami']);
        $entitlements = $registry->classifyCliCommand(['entitlements']);
        $featureList = $registry->classifyCliCommand(['feature:list']);
        $featureInspect = $registry->classifyCliCommand(['feature:inspect', 'event-system']);
        $featureMap = $registry->classifyCliCommand(['feature:map']);
        $inspectContext = $registry->classifyCliCommand(['inspect', 'context', 'event-bus']);
        $contextInit = $registry->classifyCliCommand(['context', 'init', 'event-bus']);
        $contextDoctor = $registry->classifyCliCommand(['context', 'doctor', '--feature=event-bus']);
        $contextRepair = $registry->classifyCliCommand(['context', 'repair', '--feature=event-bus']);
        $contextCheckAlignment = $registry->classifyCliCommand(['context', 'check-alignment', '--feature=event-bus']);
        $completion = $registry->classifyCliCommand(['completion', 'bash']);
        $implementFeature = $registry->classifyCliCommand(['implement', 'feature', 'event-bus']);
        $implementSpec = $registry->classifyCliCommand(['implement', 'spec', 'event-bus/001-initial']);
        $planFeature = $registry->classifyCliCommand(['plan', 'feature', 'event-bus']);
        $planList = $registry->classifyCliCommand(['plan:list']);
        $planReplay = $registry->classifyCliCommand(['plan:replay', '123e4567-e89b-12d3-a456-426614174000']);
        $planShow = $registry->classifyCliCommand(['plan:show', '123e4567-e89b-12d3-a456-426614174000']);
        $planUndo = $registry->classifyCliCommand(['plan:undo', '123e4567-e89b-12d3-a456-426614174000']);
        $specNew = $registry->classifyCliCommand(['spec:new', 'execution-spec-system', 'add-cli-command']);
        $specPlan = $registry->classifyCliCommand(['spec:plan', 'execution-spec-system', '008']);
        $specLogEntry = $registry->classifyCliCommand(['spec:log-entry', 'execution-spec-system', '004']);
        $specValidate = $registry->classifyCliCommand(['spec:validate']);
        $verifyContext = $registry->classifyCliCommand(['verify', 'context', '--feature=event-bus']);
        $verifyStateStore = $registry->classifyCliCommand(['verify', 'state-store']);
        $verifyMarketplace = $registry->classifyCliCommand(['verify', 'marketplace']);
        $verifyFeatures = $registry->classifyCliCommand(['verify', 'features']);
        $inspectStateStore = $registry->classifyCliCommand(['inspect', 'state-store']);
        $inspectMarketplace = $registry->classifyCliCommand(['inspect', 'marketplace']);
        $init = $registry->classifyCliCommand(['init']);
        $examplesList = $registry->classifyCliCommand(['examples:list']);
        $examplesLoad = $registry->classifyCliCommand(['examples:load', 'blog-api']);
        $packSearch = $registry->classifyCliCommand(['pack', 'search', 'blog']);
        $packPurchase = $registry->classifyCliCommand(['pack', 'purchase', 'vendor/premium-pack']);
        $packList = $registry->classifyCliCommand(['pack', 'list']);
        $observeTrace = $registry->classifyCliCommand(['observe:trace', 'publish_post']);
        $observeProfile = $registry->classifyCliCommand(['observe:profile']);
        $observeCompare = $registry->classifyCliCommand(['observe:compare', 'run-a', 'run-b']);
        $history = $registry->classifyCliCommand(['history']);
        $historicalExtract = $registry->classifyCliCommand(['historical-specs:extract', '--dry-run']);
        $historicalEvidence = $registry->classifyCliCommand(['historical-specs:evidence', '--dry-run']);
        $preCanonicalImport = $registry->classifyCliCommand(['precanonical:import', '--source=_import/precanonical/marked-archive.md']);
        $regressions = $registry->classifyCliCommand(['regressions']);
        $inspectCliSurface = $registry->classifyCliCommand(['inspect', 'cli-surface']);
        $verifyCliSurface = $registry->classifyCliCommand(['verify', 'cli-surface']);
        $internal = $registry->classifyCliCommand(['queue:work']);

        $this->assertNotNull($stable);
        $this->assertNotNull($cacheInspect);
        $this->assertNotNull($scaffold);
        $this->assertNotNull($graphInspect);
        $this->assertNotNull($graphVisualize);
        $this->assertNotNull($graphExport);
        $this->assertNotNull($explain);
        $this->assertNotNull($generatePrompt);
        $this->assertNotNull($generateWorkflow);
        $this->assertNotNull($generateTemplate);
        $this->assertNotNull($licenseStatus);
        $this->assertNotNull($features);
        $this->assertNotNull($login);
        $this->assertNotNull($logout);
        $this->assertNotNull($whoami);
        $this->assertNotNull($entitlements);
        $this->assertNotNull($featureList);
        $this->assertNotNull($featureInspect);
        $this->assertNotNull($featureMap);
        $this->assertNotNull($inspectContext);
        $this->assertNotNull($contextInit);
        $this->assertNotNull($contextDoctor);
        $this->assertNotNull($contextRepair);
        $this->assertNotNull($contextCheckAlignment);
        $this->assertNotNull($completion);
        $this->assertNotNull($implementFeature);
        $this->assertNotNull($implementSpec);
        $this->assertNotNull($planFeature);
        $this->assertNotNull($planList);
        $this->assertNotNull($planReplay);
        $this->assertNotNull($planShow);
        $this->assertNotNull($planUndo);
        $this->assertNotNull($specNew);
        $this->assertNotNull($specLogEntry);
        $this->assertNotNull($specValidate);
        $this->assertNotNull($verifyContext);
        $this->assertNotNull($verifyStateStore);
        $this->assertNotNull($verifyMarketplace);
        $this->assertNotNull($verifyFeatures);
        $this->assertNotNull($inspectStateStore);
        $this->assertNotNull($inspectMarketplace);
        $this->assertNotNull($init);
        $this->assertNotNull($examplesList);
        $this->assertNotNull($examplesLoad);
        $this->assertNotNull($packSearch);
        $this->assertNotNull($packPurchase);
        $this->assertNotNull($packList);
        $this->assertNotNull($observeTrace);
        $this->assertNotNull($observeProfile);
        $this->assertNotNull($observeCompare);
        $this->assertNotNull($history);
        $this->assertNotNull($historicalExtract);
        $this->assertNotNull($historicalEvidence);
        $this->assertNotNull($preCanonicalImport);
        $this->assertNotNull($regressions);
        $this->assertNotNull($inspectCliSurface);
        $this->assertNotNull($verifyCliSurface);
        $this->assertNotNull($internal);
        $this->assertSame('stable', $stable['stability']);
        $this->assertSame('Architecture', $stable['category']);
        $this->assertSame('compile', $stable['command_type']);
        $this->assertSame('stable', $cacheInspect['stability']);
        $this->assertSame('Build', $cacheInspect['category']);
        $this->assertSame('cache', $cacheInspect['command_type']);
        $this->assertSame('stable', $scaffold['stability']);
        $this->assertSame('new', $scaffold['signature']);
        $this->assertSame('App Scaffolding', $scaffold['category']);
        $this->assertSame('new', $scaffold['command_type']);
        $this->assertSame('stable', $graphInspect['stability']);
        $this->assertSame('Architecture', $graphInspect['category']);
        $this->assertSame('graph', $graphInspect['command_type']);
        $this->assertTrue($graphInspect['supports_pipeline_stage_filter']);
        $this->assertTrue($graphInspect['supports_extension_filter']);
        $this->assertSame('stable', $graphVisualize['stability']);
        $this->assertSame('stable', $graphExport['stability']);
        $this->assertSame('experimental', $explain['stability']);
        $this->assertSame('core', $explain['availability']);
        $this->assertStringContainsString('--neighbors', $explain['usage']);
        $this->assertStringContainsString('--git', $explain['usage']);
        $this->assertStringContainsString('--diff', $explain['usage']);
        $this->assertSame('Architecture', $explain['category']);
        $this->assertSame('explain', $explain['command_type']);
        $this->assertTrue($explain['supports_pipeline_stage_filter']);
        $this->assertStringContainsString('explain [<target>]', $explain['usage']);
        $this->assertSame('generate <intent>', $generatePrompt['signature']);
        $this->assertSame('generate <intent>', $generateWorkflow['signature']);
        $this->assertSame('generate <intent>', $generateTemplate['signature']);
        $this->assertStringContainsString('--mode=<new|modify|repair>', $generatePrompt['usage']);
        $this->assertStringContainsString('--workflow=<file>', $generateWorkflow['usage']);
        $this->assertStringContainsString('--template=<template_id>', $generateTemplate['usage']);
        $this->assertStringContainsString('--explain', $generatePrompt['usage']);
        $this->assertStringContainsString('--allow-dirty', $generatePrompt['usage']);
        $this->assertStringContainsString('--allow-pack-install', $generatePrompt['usage']);
        $this->assertStringContainsString('--git-commit', $generatePrompt['usage']);
        $this->assertSame('App Scaffolding', $generatePrompt['category']);
        $this->assertSame('generate', $generatePrompt['command_type']);
        $this->assertSame('stable', $init['stability']);
        $this->assertSame('App Scaffolding', $init['category']);
        $this->assertSame('init', $init['command_type']);
        $this->assertStringContainsString('--example=<blog-api|extensions-migrations>', $init['usage']);
        $this->assertSame('stable', $examplesList['stability']);
        $this->assertSame('App Scaffolding', $examplesList['category']);
        $this->assertSame('examples', $examplesList['command_type']);
        $this->assertStringContainsString('taxonomy', $examplesList['summary']);
        $this->assertSame('stable', $examplesLoad['stability']);
        $this->assertSame('App Scaffolding', $examplesLoad['category']);
        $this->assertSame('examples', $examplesLoad['command_type']);
        $this->assertStringContainsString('<blog-api|extensions-migrations>', $examplesLoad['usage']);
        $this->assertSame('Monetization', $licenseStatus['category']);
        $this->assertSame('license', $licenseStatus['command_type']);
        $this->assertSame('Monetization', $features['category']);
        $this->assertSame('features', $features['command_type']);
        $this->assertFalse($features['supports_pipeline_stage_filter']);
        $this->assertSame('experimental', $login['stability']);
        $this->assertSame('Extensions', $login['category']);
        $this->assertSame('login', $login['command_type']);
        $this->assertSame('experimental', $logout['stability']);
        $this->assertSame('Extensions', $logout['category']);
        $this->assertSame('logout', $logout['command_type']);
        $this->assertSame('experimental', $whoami['stability']);
        $this->assertSame('Extensions', $whoami['category']);
        $this->assertSame('whoami', $whoami['command_type']);
        $this->assertSame('experimental', $entitlements['stability']);
        $this->assertSame('Extensions', $entitlements['category']);
        $this->assertSame('entitlements', $entitlements['command_type']);
        $this->assertSame('stable', $featureList['stability']);
        $this->assertSame('feature:list', $featureList['command_type']);
        $this->assertSame('stable', $featureInspect['stability']);
        $this->assertSame('feature:inspect', $featureInspect['command_type']);
        $this->assertSame('stable', $featureMap['stability']);
        $this->assertSame('feature:map', $featureMap['command_type']);
        $this->assertSame('stable', $inspectContext['stability']);
        $this->assertSame('Architecture', $inspectContext['category']);
        $this->assertSame('inspect', $inspectContext['command_type']);
        $this->assertSame('stable', $contextInit['stability']);
        $this->assertSame('Architecture', $contextInit['category']);
        $this->assertSame('context', $contextInit['command_type']);
        $this->assertSame('stable', $contextDoctor['stability']);
        $this->assertSame('Architecture', $contextDoctor['category']);
        $this->assertSame('context', $contextDoctor['command_type']);
        $this->assertSame('stable', $contextRepair['stability']);
        $this->assertSame('Architecture', $contextRepair['category']);
        $this->assertSame('context', $contextRepair['command_type']);
        $this->assertSame('context repair --feature=<feature>', $contextRepair['usage']);
        $this->assertSame('stable', $contextCheckAlignment['stability']);
        $this->assertSame('Architecture', $contextCheckAlignment['category']);
        $this->assertSame('context', $contextCheckAlignment['command_type']);
        $this->assertSame('stable', $completion['stability']);
        $this->assertSame('Reference', $completion['category']);
        $this->assertSame('completion', $completion['command_type']);
        $this->assertSame('completion <bash|zsh>', $completion['usage']);
        $this->assertSame('stable', $implementFeature['stability']);
        $this->assertSame('App Scaffolding', $implementFeature['category']);
        $this->assertSame('implement', $implementFeature['command_type']);
        $this->assertSame('stable', $implementSpec['stability']);
        $this->assertSame('App Scaffolding', $implementSpec['category']);
        $this->assertSame('implement', $implementSpec['command_type']);
        $this->assertSame('stable', $planFeature['stability']);
        $this->assertSame('App Scaffolding', $planFeature['category']);
        $this->assertSame('plan', $planFeature['command_type']);
        $this->assertSame('experimental', $planList['stability']);
        $this->assertSame('App Scaffolding', $planList['category']);
        $this->assertSame('plan:list', $planList['command_type']);
        $this->assertSame('experimental', $planReplay['stability']);
        $this->assertSame('App Scaffolding', $planReplay['category']);
        $this->assertSame('plan:replay', $planReplay['command_type']);
        $this->assertSame('plan:replay <plan_id> [--strict] [--dry-run]', $planReplay['usage']);
        $this->assertSame('experimental', $planShow['stability']);
        $this->assertSame('App Scaffolding', $planShow['category']);
        $this->assertSame('plan:show', $planShow['command_type']);
        $this->assertSame('experimental', $planUndo['stability']);
        $this->assertSame('App Scaffolding', $planUndo['category']);
        $this->assertSame('plan:undo', $planUndo['command_type']);
        $this->assertSame('plan:undo <plan_id> [--dry-run] [--yes]', $planUndo['usage']);
        $this->assertSame('stable', $specNew['stability']);
        $this->assertSame('App Scaffolding', $specNew['category']);
        $this->assertSame('spec:new', $specNew['command_type']);
        $this->assertSame('stable', $specPlan['stability']);
        $this->assertSame('App Scaffolding', $specPlan['category']);
        $this->assertSame('spec:plan', $specPlan['command_type']);
        $this->assertSame('spec:plan <feature> <id> [--force]', $specPlan['usage']);
        $this->assertSame('stable', $specLogEntry['stability']);
        $this->assertSame('Verification', $specLogEntry['category']);
        $this->assertSame('spec:log-entry', $specLogEntry['command_type']);
        $this->assertSame('spec:log-entry <feature>/<id>-<slug>|<id>-<slug>|<feature> <id>', $specLogEntry['usage']);
        $this->assertSame('stable', $specValidate['stability']);
        $this->assertSame('Verification', $specValidate['category']);
        $this->assertSame('spec:validate', $specValidate['command_type']);
        $this->assertSame('spec:validate [--require-outcomes] [--require-plans]', $specValidate['usage']);
        $this->assertSame('stable', $verifyContext['stability']);
        $this->assertSame('Verification', $verifyContext['category']);
        $this->assertSame('verify', $verifyContext['command_type']);
        $this->assertSame('stable', $verifyStateStore['stability']);
        $this->assertSame('Verification', $verifyStateStore['category']);
        $this->assertSame('stable', $verifyMarketplace['stability']);
        $this->assertSame('Verification', $verifyMarketplace['category']);
        $this->assertSame('stable', $inspectMarketplace['stability']);
        $this->assertSame('Extensions', $inspectMarketplace['category']);
        $this->assertSame('verify', $verifyStateStore['command_type']);
        $this->assertSame('stable', $verifyFeatures['stability']);
        $this->assertSame('Verification', $verifyFeatures['category']);
        $this->assertSame('verify', $verifyFeatures['command_type']);
        $this->assertSame('stable', $inspectStateStore['stability']);
        $this->assertSame('Architecture', $inspectStateStore['category']);
        $this->assertSame('inspect', $inspectStateStore['command_type']);
        $this->assertSame('experimental', $packSearch['stability']);
        $this->assertSame('Extensions', $packSearch['category']);
        $this->assertSame('pack', $packSearch['command_type']);
        $this->assertSame('experimental', $packPurchase['stability']);
        $this->assertSame('Extensions', $packPurchase['category']);
        $this->assertSame('pack', $packPurchase['command_type']);
        $this->assertSame('experimental', $packList['stability']);
        $this->assertSame('Extensions', $packList['category']);
        $this->assertSame('pack', $packList['command_type']);
        $this->assertSame('experimental', $observeTrace['stability']);
        $this->assertSame('Observability', $observeTrace['category']);
        $this->assertSame('observe', $observeTrace['command_type']);
        $this->assertTrue($observeTrace['supports_pipeline_stage_filter']);
        $this->assertSame('experimental', $observeProfile['stability']);
        $this->assertSame('experimental', $observeCompare['stability']);
        $this->assertSame('experimental', $history['stability']);
        $this->assertSame('experimental', $historicalExtract['stability']);
        $this->assertSame('App Scaffolding', $historicalExtract['category']);
        $this->assertSame('historical-specs:extract', $historicalExtract['command_type']);
        $this->assertSame('experimental', $historicalEvidence['stability']);
        $this->assertSame('App Scaffolding', $historicalEvidence['category']);
        $this->assertSame('historical-specs:evidence', $historicalEvidence['command_type']);
        $this->assertSame('experimental', $preCanonicalImport['stability']);
        $this->assertSame('App Scaffolding', $preCanonicalImport['category']);
        $this->assertSame('precanonical:import', $preCanonicalImport['command_type']);
        $this->assertSame('experimental', $regressions['stability']);
        $this->assertSame('stable', $inspectCliSurface['stability']);
        $this->assertSame('Reference', $inspectCliSurface['category']);
        $this->assertSame('inspect', $inspectCliSurface['command_type']);
        $this->assertSame('stable', $verifyCliSurface['stability']);
        $this->assertSame('Reference', $verifyCliSurface['category']);
        $this->assertSame('verify', $verifyCliSurface['command_type']);
        $this->assertSame('internal', $internal['stability']);
        $this->assertSame('Runtime', $internal['category']);
        $this->assertSame('queue', $internal['command_type']);
    }

    public function test_cli_command_signatures_are_unique(): void
    {
        $registry = new ApiSurfaceRegistry();
        $signatures = array_values(array_map(
            static fn(array $entry): string => (string) ($entry['signature'] ?? ''),
            $registry->cliCommands(),
        ));

        $this->assertSame($signatures, array_values(array_unique($signatures)));
    }

    public function test_resolve_cli_signature_handles_additional_branch_variants(): void
    {
        $registry = new ApiSurfaceRegistry();

        $this->assertNull($registry->resolveCliSignature([]));
        $this->assertSame('license activate', $registry->resolveCliSignature(['license', 'activate']));
        $this->assertSame('license deactivate', $registry->resolveCliSignature(['license', 'deactivate']));
        $this->assertSame('entitlements', $registry->resolveCliSignature(['entitlements']));
        $this->assertNull($registry->resolveCliSignature(['license', 'unknown']));

        $this->assertSame('pack remove', $registry->resolveCliSignature(['pack', 'remove']));
        $this->assertSame('pack purchase', $registry->resolveCliSignature(['pack', 'purchase']));
        $this->assertSame('pack info', $registry->resolveCliSignature(['pack', 'info']));
        $this->assertNull($registry->resolveCliSignature(['pack', 'unknown']));

        $this->assertNull($registry->resolveCliSignature(['implement', 'unknown']));
        $this->assertNull($registry->resolveCliSignature(['plan', 'unknown']));
        $this->assertSame('cache clear', $registry->resolveCliSignature(['cache', 'clear']));
        $this->assertNull($registry->resolveCliSignature(['cache', 'unknown']));
        $this->assertNull($registry->resolveCliSignature(['context', 'unknown']));

        $this->assertNull($registry->resolveCliSignature(['graph', 'unknown']));
        $this->assertSame('export openapi', $registry->resolveCliSignature(['export', 'openapi']));
        $this->assertNull($registry->resolveCliSignature(['export', 'unknown']));
        $this->assertSame('preview notification', $registry->resolveCliSignature(['preview', 'notification']));
        $this->assertNull($registry->resolveCliSignature(['preview', 'other']));

        $this->assertSame('init app', $registry->resolveCliSignature(['init', 'app']));
        $this->assertSame('migrate definitions', $registry->resolveCliSignature(['migrate', 'definitions']));
        $this->assertNull($registry->resolveCliSignature(['migrate', 'unknown']));
        $this->assertSame('codemod run', $registry->resolveCliSignature(['codemod', 'run']));
        $this->assertNull($registry->resolveCliSignature(['codemod', 'unknown']));

        $this->assertNull($registry->resolveCliSignature(['inspect']));
        $this->assertNull($registry->resolveCliSignature(['verify']));
        $this->assertNull($registry->resolveCliSignature(['unknown-command']));
        $this->assertSame('generate <intent>', $registry->resolveCliSignature(['generate', '--workflow']));
        $this->assertSame('historical-specs:extract', $registry->resolveCliSignature(['historical-specs:extract']));
        $this->assertSame('historical-specs:evidence', $registry->resolveCliSignature(['historical-specs:evidence']));
        $this->assertSame('precanonical:import', $registry->resolveCliSignature(['precanonical:import']));
    }

    public function test_classifies_configuration_and_generated_metadata_paths(): void
    {
        $registry = new ApiSurfaceRegistry();

        $manifest = $registry->classifyConfigurationArtifact('app/features/list_posts/feature.yaml');
        $platformConfig = $registry->classifyConfigurationArtifact('config/cache.php');
        $packRegistry = $registry->classifyConfigurationArtifact('.foundry/packs/installed.json');
        $packManifest = $registry->classifyConfigurationArtifact('Packs/foundry/blog/foundry.json');
        $generated = $registry->classifyGeneratedMetadata('app/generated/routes.php');
        $registryCache = $registry->classifyGeneratedMetadata('.foundry/cache/registry.json');
        $cacheMetadata = $registry->classifyGeneratedMetadata('app/.foundry/build/manifests/compile_cache.json');
        $qualityMetadata = $registry->classifyGeneratedMetadata('app/.foundry/build/quality/summary.json');
        $historyMetadata = $registry->classifyGeneratedMetadata('app/.foundry/build/history/build-abc.json');
        $planMetadata = $registry->classifyGeneratedMetadata('.foundry/plans/20260423T010203Z_123e4567-e89b-12d3-a456-426614174000.json');

        $this->assertNotNull($manifest);
        $this->assertNotNull($platformConfig);
        $this->assertNotNull($packRegistry);
        $this->assertNotNull($packManifest);
        $this->assertNotNull($generated);
        $this->assertNotNull($registryCache);
        $this->assertNotNull($cacheMetadata);
        $this->assertNotNull($qualityMetadata);
        $this->assertNotNull($historyMetadata);
        $this->assertNotNull($planMetadata);
        $this->assertSame('public_api', $manifest['classification']);
        $this->assertSame('experimental_api', $platformConfig['classification']);
        $this->assertSame('extension_api', $packRegistry['classification']);
        $this->assertSame('extension_api', $packManifest['classification']);
        $this->assertSame('internal_api', $generated['classification']);
        $this->assertSame('internal_api', $registryCache['classification']);
        $this->assertSame('internal_api', $cacheMetadata['classification']);
        $this->assertSame('internal_api', $qualityMetadata['classification']);
        $this->assertSame('internal_api', $historyMetadata['classification']);
        $this->assertSame('internal_api', $planMetadata['classification']);
    }

    public function test_unknown_symbols_and_paths_return_expected_defaults_or_null(): void
    {
        $registry = new ApiSurfaceRegistry();

        $defaultSymbol = $registry->classifyPhpSymbol('Acme\\Package\\CustomSymbol');
        $this->assertSame('internal_api', $defaultSymbol['classification']);
        $this->assertSame('default_internal', $defaultSymbol['matched_by']);
        $this->assertSame('Acme\\Package\\CustomSymbol', $defaultSymbol['identifier']);

        $this->assertNull($registry->classifyCliCommand(['']));
        $this->assertNull($registry->classifyCliCommand(''));
        $this->assertNull($registry->classifyConfigurationArtifact('docs/unknown-config.txt'));
        $this->assertNull($registry->classifyGeneratedMetadata('tmp/random-output.bin'));
    }

    public function test_resolve_cli_signature_and_pattern_helpers_cover_remaining_branches(): void
    {
        $registry = new ApiSurfaceRegistry();

        $this->assertSame('implement feature', $registry->resolveCliSignature(['implement', 'feature']));
        $this->assertSame('implement spec', $registry->resolveCliSignature(['implement', 'spec']));
        $this->assertSame('plan feature', $registry->resolveCliSignature(['plan', 'feature']));
        $this->assertSame('cache inspect', $registry->resolveCliSignature(['cache', 'inspect']));
        $this->assertNull($registry->resolveCliSignature(['license']));
        $this->assertNull($registry->resolveCliSignature(['pack']));
        $this->assertSame('context init', $registry->resolveCliSignature(['context', 'init']));
        $this->assertSame('context doctor', $registry->resolveCliSignature(['context', 'doctor']));
        $this->assertSame('context repair', $registry->resolveCliSignature(['context', 'repair']));
        $this->assertSame('context check-alignment', $registry->resolveCliSignature(['context', 'check-alignment']));
        $this->assertSame('doctor', $registry->resolveCliSignature(['doctor']));

        $this->assertSame('inspect platform', $registry->resolveCliSignature(['inspect', 'platform']));

        $method = new \ReflectionMethod($registry, 'matchesPattern');
        $this->assertFalse($method->invoke($registry, 'app/generated/routes.php', ''));
    }
}
