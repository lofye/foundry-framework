<?php
declare(strict_types=1);

namespace Foundry\Compiler;

use Foundry\Compiler\Analysis\ImpactAnalyzer;
use Foundry\Compiler\Diagnostics\DiagnosticBag;
use Foundry\Compiler\Extensions\CoreCompilerExtension;
use Foundry\Compiler\Extensions\ExtensionRegistry;
use Foundry\Compiler\Passes\AnalyzePass;
use Foundry\Compiler\Passes\DiscoveryPass;
use Foundry\Compiler\Passes\EmitPass;
use Foundry\Compiler\Passes\EnrichPass;
use Foundry\Compiler\Passes\LinkPass;
use Foundry\Compiler\Passes\NormalizePass;
use Foundry\Compiler\Passes\ValidatePass;
use Foundry\Support\Json;
use Foundry\Support\Paths;

final class GraphCompiler
{
    public const GRAPH_VERSION = 1;

    private readonly BuildLayout $layout;
    private readonly SourceScanner $sourceScanner;
    private readonly CompilePlanner $planner;
    private readonly ImpactAnalyzer $impactAnalyzer;

    public function __construct(
        private readonly Paths $paths,
        private readonly ?ExtensionRegistry $extensions = null,
    ) {
        $this->layout = new BuildLayout($paths);
        $this->sourceScanner = new SourceScanner($paths);
        $this->planner = new CompilePlanner();
        $this->impactAnalyzer = new ImpactAnalyzer($paths);
    }

    public function compile(CompileOptions $options = new CompileOptions()): CompileResult
    {
        $frameworkVersion = $this->frameworkVersion();

        $currentFeatures = array_map('basename', glob($this->paths->features() . '/*', GLOB_ONLYDIR) ?: []);
        sort($currentFeatures);

        $sourceFiles = $this->sourceScanner->sourceFiles();
        $sourceHashes = $this->sourceScanner->hashFiles($sourceFiles);
        $sourceHash = $this->sourceScanner->aggregateHash($sourceHashes);

        $previousManifest = $this->readJson($this->layout->compileManifestPath()) ?? [];
        $previousGraph = $this->loadPreviousGraph();

        $plan = $this->planner->plan(
            options: $options,
            previousManifest: $previousManifest,
            currentSourceHashes: $sourceHashes,
            currentFeatures: $currentFeatures,
            hasPreviousGraph: $previousGraph !== null,
            scanner: $this->sourceScanner,
            frameworkVersion: $frameworkVersion,
        );

        if ($plan->noChanges && $previousGraph !== null) {
            $diagnostics = new DiagnosticBag();
            $diagnostics->info(
                code: 'FDY0001_NO_CHANGES',
                category: 'graph',
                message: 'No source changes detected; reusing existing build artifacts.',
                pass: 'compile',
            );

            $integrity = $this->readJson($this->layout->integrityHashesPath()) ?? [];

            return new CompileResult(
                graph: $previousGraph,
                diagnostics: $diagnostics,
                plan: $plan,
                manifest: $previousManifest,
                integrityHashes: array_map('strval', $integrity),
                projections: [],
                writtenFiles: [],
            );
        }

        $graph = $this->newGraph($frameworkVersion, $sourceHash);

        if ($plan->incremental && !$plan->fallbackToFull && $previousGraph !== null) {
            foreach ($previousGraph->nodesByType('feature') as $featureNode) {
                $feature = (string) ($featureNode->payload()['feature'] ?? '');
                if ($feature === '' || in_array($feature, $plan->selectedFeatures, true)) {
                    continue;
                }

                $graph->addNode($featureNode);
            }
        }

        $diagnostics = new DiagnosticBag();
        $extensions = $this->extensionRegistry();

        $state = new CompilationState(
            paths: $this->paths,
            layout: $this->layout,
            options: $options,
            plan: $plan,
            extensions: $extensions,
            diagnostics: $diagnostics,
            graph: $graph,
            sourceHashes: $sourceHashes,
            previousManifest: $previousManifest,
        );

        $passes = $this->buildPasses($state);
        foreach ($passes as $pass) {
            $pass->run($state);
        }

        $writtenFiles = array_values(array_map(
            'strval',
            (array) ($state->analysis['written_files'] ?? []),
        ));

        return new CompileResult(
            graph: $state->graph,
            diagnostics: $state->diagnostics,
            plan: $state->plan,
            manifest: $state->manifest,
            integrityHashes: $state->integrityHashes,
            projections: $state->projections,
            writtenFiles: $writtenFiles,
        );
    }

    public function loadGraph(): ?ApplicationGraph
    {
        return $this->loadPreviousGraph();
    }

    public function impactAnalyzer(): ImpactAnalyzer
    {
        return $this->impactAnalyzer;
    }

    public function buildLayout(): BuildLayout
    {
        return $this->layout;
    }

    public function extensionRegistry(): ExtensionRegistry
    {
        return $this->extensions ?? new ExtensionRegistry([new CoreCompilerExtension()]);
    }

    /**
     * @return array<int,CompilerPass>
     */
    private function buildPasses(CompilationState $state): array
    {
        $extensions = $state->extensions;

        $passes = [];

        $passes[] = new DiscoveryPass();
        foreach ($extensions->collect(static fn ($extension): array => $extension->discoveryPasses()) as $pass) {
            $passes[] = $pass;
        }

        $passes[] = new NormalizePass();
        foreach ($extensions->collect(static fn ($extension): array => $extension->normalizePasses()) as $pass) {
            $passes[] = $pass;
        }

        $passes[] = new LinkPass();
        foreach ($extensions->collect(static fn ($extension): array => $extension->linkPasses()) as $pass) {
            $passes[] = $pass;
        }

        $passes[] = new ValidatePass();
        foreach ($extensions->collect(static fn ($extension): array => $extension->validatePasses()) as $pass) {
            $passes[] = $pass;
        }

        $passes[] = new EnrichPass();
        foreach ($extensions->collect(static fn ($extension): array => $extension->enrichPasses()) as $pass) {
            $passes[] = $pass;
        }

        $passes[] = new AnalyzePass($this->impactAnalyzer);
        foreach ($extensions->collect(static fn ($extension): array => $extension->analyzePasses()) as $pass) {
            $passes[] = $pass;
        }

        $passes[] = new EmitPass();
        foreach ($extensions->collect(static fn ($extension): array => $extension->emitPasses()) as $pass) {
            $passes[] = $pass;
        }

        return $passes;
    }

    private function newGraph(string $frameworkVersion, string $sourceHash): ApplicationGraph
    {
        return new ApplicationGraph(
            graphVersion: self::GRAPH_VERSION,
            frameworkVersion: $frameworkVersion,
            compiledAt: gmdate(DATE_ATOM),
            sourceHash: $sourceHash,
            metadata: [],
        );
    }

    private function loadPreviousGraph(): ?ApplicationGraph
    {
        $data = $this->readJson($this->layout->graphJsonPath());
        if ($data === null) {
            return null;
        }

        return ApplicationGraph::fromArray($data);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        try {
            return Json::decodeAssoc($content);
        } catch (\Throwable) {
            return null;
        }
    }

    private function frameworkVersion(): string
    {
        $composerPath = $this->paths->frameworkJoin('composer.json');
        if (!is_file($composerPath)) {
            return 'dev-main';
        }

        $content = file_get_contents($composerPath);
        if ($content === false) {
            return 'dev-main';
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return 'dev-main';
        }

        $version = $decoded['version'] ?? null;

        return is_string($version) && $version !== '' ? $version : 'dev-main';
    }
}
