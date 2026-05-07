<?php

declare(strict_types=1);

namespace Foundry\Packs;

use Foundry\Support\FoundryError;

final readonly class HostedPackRegistryEntry
{
    public function __construct(
        public string $name,
        public string $version,
        public string $description,
        public string $downloadUrl,
        public string $checksum,
        public ?string $signature,
        public bool $verified,
        public string $distribution,
        public bool $entitlementRequired,
        public ?string $priceCurrency,
        public ?string $priceAmount,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'download_url' => $this->downloadUrl,
            'checksum' => $this->checksum,
            'signature' => $this->signature,
            'verified' => $this->verified,
            'distribution' => $this->distribution,
            'entitlement_required' => $this->entitlementRequired,
            'price' => ($this->priceCurrency === null || $this->priceAmount === null)
                ? null
                : [
                    'currency' => $this->priceCurrency,
                    'amount' => $this->priceAmount,
                ],
        ];
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function fromArray(array $payload, int $index = 0): self
    {
        $name = trim((string) ($payload['name'] ?? ''));
        $version = trim((string) ($payload['version'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $downloadUrl = trim((string) ($payload['download_url'] ?? ''));
        $checksum = strtolower(trim((string) ($payload['checksum'] ?? '')));
        $signature = $payload['signature'] ?? null;
        $verified = $payload['verified'] ?? null;
        $distribution = strtolower(trim((string) ($payload['distribution'] ?? 'free')));
        $entitlementRequired = $payload['entitlement_required'] ?? ($distribution !== 'free');
        $price = $payload['price'] ?? null;

        $errors = [];

        if (!PackManifest::isValidName($name)) {
            $errors['name'] = 'name must match vendor/pack-name format.';
        }

        if (!PackManifest::isValidVersion($version)) {
            $errors['version'] = 'version must be a semantic version.';
        }

        if ($description === '') {
            $errors['description'] = 'description must be non-empty.';
        }

        if (!self::isValidDownloadUrl($downloadUrl)) {
            $errors['download_url'] = 'download_url must be an HTTPS URL.';
        }

        if (!array_key_exists('checksum', $payload) || !PackManifest::isValidChecksum($checksum)) {
            $errors['checksum'] = 'checksum must be a 64-character SHA-256 hex string.';
        }

        if (!array_key_exists('signature', $payload) || !PackManifest::isValidSignature($signature)) {
            $errors['signature'] = 'signature must be a non-empty string or null.';
        }

        if (!is_bool($verified)) {
            $errors['verified'] = 'verified must be a boolean.';
        }

        if (!in_array($distribution, ['free', 'licensed', 'premium'], true)) {
            $errors['distribution'] = 'distribution must be one of free|licensed|premium.';
        }

        if (!is_bool($entitlementRequired)) {
            $errors['entitlement_required'] = 'entitlement_required must be a boolean.';
        }

        if (is_bool($entitlementRequired) && $distribution === 'free' && $entitlementRequired) {
            $errors['entitlement_required'] = 'free packs cannot require entitlement.';
        }

        if (is_bool($entitlementRequired) && $distribution === 'premium' && !$entitlementRequired) {
            $errors['entitlement_required'] = 'premium packs must require entitlement.';
        }

        if ($price !== null) {
            if (!is_array($price)) {
                $errors['price'] = 'price must be null or an object.';
            } else {
                $currency = strtoupper(trim((string) ($price['currency'] ?? '')));
                $amount = trim((string) ($price['amount'] ?? ''));
                if (preg_match('/^[A-Z]{3}$/', $currency) !== 1 || preg_match('/^\d+\.\d{2}$/', $amount) !== 1) {
                    $errors['price'] = 'price must include currency (ISO-4217) and amount (decimal with 2 digits).';
                }
            }
        }

        if ($errors !== []) {
            throw new FoundryError(
                'PACK_REGISTRY_ENTRY_INVALID',
                'validation',
                [
                    'index' => $index,
                    'errors' => $errors,
                    'entry' => $payload,
                ],
                'Hosted pack registry entry is invalid.',
            );
        }

        return new self(
            name: $name,
            version: $version,
            description: $description,
            downloadUrl: $downloadUrl,
            checksum: $checksum,
            signature: is_string($signature) ? trim($signature) : null,
            verified: $verified,
            distribution: $distribution,
            entitlementRequired: $entitlementRequired,
            priceCurrency: is_array($price) ? strtoupper(trim((string) ($price['currency'] ?? ''))) : null,
            priceAmount: is_array($price) ? trim((string) ($price['amount'] ?? '')) : null,
        );
    }

    public static function isValidDownloadUrl(string $value): bool
    {
        $parts = parse_url($value);
        if (!is_array($parts)) {
            return false;
        }

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && trim((string) ($parts['host'] ?? '')) !== '';
    }
}
