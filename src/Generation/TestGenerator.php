<?php

declare(strict_types=1);

namespace Foundry\Generation;

use Foundry\Support\FeatureNaming;
use Foundry\Testing\AuthTestGenerator;
use Foundry\Testing\ContractTestGenerator;
use Foundry\Testing\FeatureTestGenerator;
use Foundry\Testing\JobTestGenerator;

final class TestGenerator
{
    public function __construct(
        private readonly ContractTestGenerator $contract = new ContractTestGenerator(),
        private readonly FeatureTestGenerator $feature = new FeatureTestGenerator(),
        private readonly AuthTestGenerator $auth = new AuthTestGenerator(),
        private readonly JobTestGenerator $job = new JobTestGenerator(),
    ) {}

    /**
     * @param array<int,string> $required
     * @return array<int,string>
     */
    public function generate(string $featureName, string $featurePath, array $required): array
    {
        $testsPath = rtrim($featurePath, '/') . '/tests';
        if (!is_dir($testsPath)) {
            mkdir($testsPath, 0777, true);
        }

        $required = array_values(array_unique(array_map('strval', $required)));
        sort($required);
        $codeSafeFeature = FeatureNaming::codeSafe($featureName);

        $written = [];

        if (in_array('contract', $required, true)) {
            $path = $testsPath . '/' . $codeSafeFeature . '_contract_test.php';
            file_put_contents($path, $this->contract->generate($featureName));
            $written[] = $path;
        }

        if (in_array('feature', $required, true)) {
            $path = $testsPath . '/' . $codeSafeFeature . '_feature_test.php';
            file_put_contents($path, $this->feature->generate($featureName));
            $written[] = $path;
        }

        if (in_array('auth', $required, true)) {
            $path = $testsPath . '/' . $codeSafeFeature . '_auth_test.php';
            file_put_contents($path, $this->auth->generate($featureName));
            $written[] = $path;
        }

        if (in_array('job', $required, true)) {
            $path = $testsPath . '/' . $codeSafeFeature . '_job_test.php';
            file_put_contents($path, $this->job->generate($featureName));
            $written[] = $path;
        }

        return $written;
    }
}
