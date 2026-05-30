<?php

declare(strict_types=1);

namespace Foundry\Support;

final class FeatureNaming
{
    public static function canonical(string $feature): string
    {
        return str_replace('_', '-', trim($feature));
    }

    public static function codeSafe(string $feature): string
    {
        return Str::toSnakeCase(self::canonical($feature));
    }

    public static function pascal(string $feature): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', self::canonical($feature))));
    }

    public static function directory(string $feature): string
    {
        return 'Features/' . self::pascal($feature);
    }

    public static function fromDirectoryName(string $directory): string
    {
        $slug = preg_replace('/(?<!^)[A-Z]/', '-$0', $directory) ?? $directory;

        return strtolower(self::canonical($slug));
    }
}
