<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\CLI\Commands\PackCommand;
use PHPUnit\Framework\TestCase;

final class PackCommandRenderInternalsTest extends TestCase
{
    public function test_render_purchase_formats_all_statuses(): void
    {
        $command = new PackCommand();

        $pending = $this->invoke($command, 'renderPurchase', [[
            'status' => 'pending',
            'pack' => 'vendor/premium-pack',
            'checkout_url' => 'https://marketplace.example/checkout/session_123',
        ]]);
        $this->assertStringContainsString('Purchase initiated.', $pending);
        $this->assertStringContainsString('Status: pending', $pending);
        $this->assertStringContainsString('Checkout URL: https://marketplace.example/checkout/session_123', $pending);

        $success = $this->invoke($command, 'renderPurchase', [[
            'status' => 'success',
            'pack' => 'vendor/premium-pack',
        ]]);
        $this->assertStringContainsString('Purchase completed.', $success);
        $this->assertStringContainsString('Entitlement refreshed: yes', $success);

        $alreadyEntitled = $this->invoke($command, 'renderPurchase', [[
            'status' => 'already_entitled',
            'pack' => 'vendor/premium-pack',
        ]]);
        $this->assertStringContainsString('Purchase not required.', $alreadyEntitled);
        $this->assertStringContainsString('Status: already entitled', $alreadyEntitled);

        $notPurchasable = $this->invoke($command, 'renderPurchase', [[
            'status' => 'not_purchasable',
            'pack' => 'vendor/free-pack',
        ]]);
        $this->assertStringContainsString('Purchase not available.', $notPurchasable);
        $this->assertStringContainsString('Status: free', $notPurchasable);

        $partial = $this->invoke($command, 'renderPurchase', [[
            'status' => 'partial',
            'pack' => 'vendor/premium-pack',
            'code' => 'MARKETPLACE_PURCHASE_ENTITLEMENT_REFRESH_FAILED',
        ]]);
        $this->assertStringContainsString('Purchase completed with warnings.', $partial);
        $this->assertStringContainsString('Code: MARKETPLACE_PURCHASE_ENTITLEMENT_REFRESH_FAILED', $partial);

        $failed = $this->invoke($command, 'renderPurchase', [[
            'status' => 'error',
            'pack' => 'vendor/premium-pack',
            'code' => 'MARKETPLACE_PURCHASE_FAILED',
        ]]);
        $this->assertStringContainsString('Purchase failed.', $failed);
        $this->assertStringContainsString('Code: MARKETPLACE_PURCHASE_FAILED', $failed);
    }

    public function test_render_install_lists_remote_source_details(): void
    {
        $command = new PackCommand();

        $output = $this->invoke($command, 'renderInstall', [[
            'pack' => 'vendor/premium-pack',
            'version' => '1.0.0',
            'install_path' => '.foundry/packs/vendor/premium-pack/1.0.0',
            'source' => [
                'type' => 'registry',
                'download_url' => 'https://downloads.example/vendor-premium-pack-1.0.0.zip',
            ],
        ]]);

        $this->assertStringContainsString('Source: remote', $output);
        $this->assertStringContainsString('Download: https://downloads.example/vendor-premium-pack-1.0.0.zip', $output);
    }

    public function test_render_list_handles_empty_and_skips_non_array_rows(): void
    {
        $command = new PackCommand();

        $empty = $this->invoke($command, 'renderList', [[]]);
        $this->assertSame('No packs installed.', $empty);

        $listed = $this->invoke($command, 'renderList', [[
            'invalid-row',
            [
                'name' => 'foundry/blog',
                'active_version' => null,
                'installed_versions' => ['1.0.0'],
                'source_kind' => 'local',
            ],
        ]]);

        $this->assertStringContainsString('Installed packs:', $listed);
        $this->assertStringContainsString('foundry/blog [inactive] source: local installed: 1.0.0', $listed);
        $this->assertStringNotContainsString('invalid-row', $listed);
    }

    public function test_render_search_handles_empty_and_skips_non_array_rows(): void
    {
        $command = new PackCommand();

        $empty = $this->invoke($command, 'renderSearch', [[
            'query' => 'premium',
            'packs' => [],
        ]]);
        $this->assertSame('No hosted packs matched `premium`.', $empty);

        $listed = $this->invoke($command, 'renderSearch', [[
            'query' => 'premium',
            'packs' => [
                'invalid-row',
                [
                    'name' => 'vendor/premium-pack',
                    'version' => '1.0.0',
                    'description' => 'Premium pack',
                ],
            ],
        ]]);

        $this->assertStringContainsString('Hosted pack results for `premium`:', $listed);
        $this->assertStringContainsString('vendor/premium-pack 1.0.0: Premium pack', $listed);
        $this->assertStringNotContainsString('invalid-row', $listed);
    }

    /**
     * @param list<mixed> $args
     */
    private function invoke(PackCommand $command, string $method, array $args): string
    {
        $reflection = new \ReflectionMethod(PackCommand::class, $method);
        /** @var string $result */
        $result = $reflection->invoke($command, ...$args);

        return $result;
    }
}
