<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PackageBoundaryTest extends TestCase
{
    #[Test]
    public function runtime_dependencies_are_framework_neutral_contracts(): void
    {
        $manifest = json_decode(
            file_get_contents(dirname(__DIR__).'/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame([
            'php' => '^8.3',
            'sifrious/elwin' => 'dev-main',
            'sifrious/reference-contract' => '^1.0',
        ], $manifest['require']);
        self::assertSame('sifrious/titan', $manifest['name']);
        self::assertSame('Sifrious\\Titan\\', array_key_first($manifest['autoload']['psr-4']));
    }

    #[Test]
    public function every_source_type_is_listed_in_the_public_api(): void
    {
        $api = file_get_contents(dirname(__DIR__).'/PUBLIC-API.md');
        $sourceFiles = glob(dirname(__DIR__).'/src/*.php');

        self::assertIsArray($sourceFiles);

        foreach ($sourceFiles as $sourceFile) {
            $name = pathinfo($sourceFile, PATHINFO_FILENAME);
            self::assertStringContainsString("`{$name}`", $api, "{$name} is missing from PUBLIC-API.md.");
        }
    }

    #[Test]
    public function source_does_not_import_host_or_provider_surfaces(): void
    {
        $sourceFiles = glob(dirname(__DIR__).'/src/*.php');
        self::assertIsArray($sourceFiles);

        foreach ($sourceFiles as $sourceFile) {
            $contents = file_get_contents($sourceFile);
            self::assertIsString($contents);
            self::assertDoesNotMatchRegularExpression(
                '/\\\\Illuminate\\\\|Eloquent|Laravel|Livewire|Blade|NativePHP|Guzzle|Octane|ShouldQueue/',
                $contents,
                basename($sourceFile).' imports a host or provider surface.',
            );
        }
    }
}
