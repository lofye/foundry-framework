<?php

declare(strict_types=1);

namespace Foundry\Context;

use Foundry\Support\FeatureNaming;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class ExecutionSpecPlanService
{
    public function __construct(
        private readonly Paths $paths,
        private readonly FeatureNameValidator $featureNameValidator = new FeatureNameValidator(),
        private readonly ?ExecutionSpecResolver $resolver = null,
    ) {}

    /**
     * @return array{
     *     status:string,
     *     feature:string,
     *     spec:?string,
     *     plan:?string,
     *     error?:string,
     *     details?:array<string,mixed>
     * }
     */
    public function createPlan(string $providedFeature, string $providedId, bool $force = false): array
    {
        $feature = FeatureNaming::canonical(trim($providedFeature));
        $id = trim($providedId);

        $featureValidation = $this->featureNameValidator->validate($feature);
        if (!$featureValidation->valid) {
            return $this->error($feature, null, null, 'feature_invalid', [
                'provided_feature' => $providedFeature,
            ]);
        }

        if (preg_match('/^' . ExecutionSpecFilename::ID_PATTERN . '$/', $id) !== 1) {
            return $this->error($feature, null, null, 'spec_id_invalid', [
                'provided_id' => $providedId,
            ]);
        }

        if ($this->featureExists($feature)) {
            try {
                $catalog = new ExecutionSpecCatalog($this->paths);
                $catalog->assertContiguous($feature, $catalog->entries($feature));
            } catch (FoundryError $error) {
                return $this->error($feature, null, null, $this->mapResolverError($error->errorCode), [
                    'error_code' => $error->errorCode,
                    'error_details' => $error->details,
                ]);
            }
        }

        $resolver = $this->resolver ?? new ExecutionSpecResolver($this->paths);

        try {
            $executionSpec = $resolver->resolveWithinFeature($feature, $id);
        } catch (FoundryError $error) {
            return $this->error($feature, null, null, $this->mapResolverError($error->errorCode), [
                'error_code' => $error->errorCode,
                'error_details' => $error->details,
            ]);
        }

        $parsedPath = ExecutionSpecFilename::parseActivePath($executionSpec->path);
        if ($parsedPath === null) {
            return $this->error($feature, null, null, 'spec_path_invalid', [
                'spec_path' => $executionSpec->path,
            ]);
        }

        $relativeSpecPath = $executionSpec->path;
        $relativeOutcomePath = (str_starts_with($executionSpec->path, 'Modules/') ? 'Modules' : 'Features') . '/' . $this->pascalFromSlug($feature) . '/outcomes/' . $parsedPath['name'] . '.md';
        $absoluteOutcomePath = $this->paths->join($relativeOutcomePath);

        if (file_exists($absoluteOutcomePath) && !$force) {
            return $this->error($feature, $relativeSpecPath, $relativeOutcomePath, 'plan_already_exists');
        }

        $outcomeDirectory = dirname($absoluteOutcomePath);
        if (file_exists($outcomeDirectory) && !is_dir($outcomeDirectory)) {
            return $this->error($feature, $relativeSpecPath, $relativeOutcomePath, 'plan_directory_create_failed');
        }

        if (!is_dir($outcomeDirectory) && !mkdir($outcomeDirectory, 0777, true) && !is_dir($outcomeDirectory)) {
            return $this->error($feature, $relativeSpecPath, $relativeOutcomePath, 'plan_directory_create_failed');
        }

        $contents = $this->renderPlan($parsedPath['name']);
        if (is_dir($absoluteOutcomePath)) {
            return $this->error($feature, $relativeSpecPath, $relativeOutcomePath, 'plan_write_failed');
        }

        if (file_put_contents($absoluteOutcomePath, $contents) === false) {
            return $this->error($feature, $relativeSpecPath, $relativeOutcomePath, 'plan_write_failed');
        }

        return [
            'status' => 'created',
            'feature' => $feature,
            'spec' => $relativeSpecPath,
            'plan' => $relativeOutcomePath,
        ];
    }

    private function renderPlan(string $specName): string
    {
        $relativePath = 'stubs/specs/implementation-plan.stub.md';
        $contents = file_get_contents($this->paths->frameworkJoin($relativePath));

        if ($contents === false) {
            throw new FoundryError(
                'EXECUTION_SPEC_PLAN_STUB_MISSING',
                'filesystem',
                ['path' => $relativePath],
                'Implementation plan stub could not be read.',
            );
        }

        $rendered = str_replace('{{spec_name}}', $specName, $contents);
        $expectedHeading = '# Implementation Plan: ' . $specName;
        if ($this->firstLine($rendered) !== $expectedHeading) {
            throw new FoundryError(
                'EXECUTION_SPEC_PLAN_STUB_INVALID',
                'validation',
                ['spec_name' => $specName],
                'Implementation plan stub must render a canonical heading.',
            );
        }

        return $rendered;
    }

    /**
     * @param array<string,mixed> $details
     * @return array{
     *     status:string,
     *     feature:string,
     *     spec:?string,
     *     plan:?string,
     *     error:string,
     *     details:array<string,mixed>
     * }
     */
    private function error(string $feature, ?string $spec, ?string $plan, string $error, array $details = []): array
    {
        return [
            'status' => 'error',
            'feature' => $feature,
            'spec' => $spec,
            'plan' => $plan,
            'error' => $error,
            'details' => $details,
        ];
    }

    private function mapResolverError(string $errorCode): string
    {
        return match ($errorCode) {
            'EXECUTION_SPEC_FEATURE_NOT_FOUND' => 'feature_not_found',
            'EXECUTION_SPEC_ID_INVALID' => 'spec_id_invalid',
            'EXECUTION_SPEC_AMBIGUOUS' => 'spec_ambiguous',
            'EXECUTION_SPEC_DRAFT_ONLY' => 'spec_draft_only',
            'EXECUTION_SPEC_NOT_FOUND' => 'spec_not_found',
            'EXECUTION_SPEC_ID_SEQUENCE_INVALID' => 'spec_id_sequence_invalid',
            default => 'spec_resolution_failed',
        };
    }

    private function firstLine(string $contents): string
    {
        $firstLine = strtok(str_replace("\r\n", "\n", $contents), "\n");

        return $firstLine === false ? '' : trim($firstLine);
    }

    private function featureExists(string $feature): bool
    {
        $pascal = $this->pascalFromSlug($feature);
        $paths = [
            'Modules/' . $pascal,
            'Modules/' . $pascal . '/specs/drafts',
            'Modules/' . $pascal . '/' . $feature . '.spec.md',
            'Modules/' . $pascal . '/' . $feature . '.md',
            'Modules/' . $pascal . '/' . $feature . '.decisions.md',
            'Features/' . $pascal,
            'Features/' . $pascal . '/specs/drafts',
            'Features/' . $pascal . '/' . $feature . '.spec.md',
            'Features/' . $pascal . '/' . $feature . '.md',
            'Features/' . $pascal . '/' . $feature . '.decisions.md',
        ];

        foreach ($paths as $path) {
            if (is_dir($this->paths->join($path)) || is_file($this->paths->join($path))) {
                return true;
            }
        }

        return false;
    }
    private function pascalFromSlug(string $slug): string
    {
        $parts = array_filter(explode('-', $slug), static fn(string $part): bool => $part !== '');

        return implode('', array_map(static fn(string $part): string => ucfirst($part), $parts));
    }

}
