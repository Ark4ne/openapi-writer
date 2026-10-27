<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\License;

final class LicenseTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return License::class;
    }

    public static function fields(): array
    {
        return [
            'name' => [['Apache 2.0'], 'Apache 2.0'],
            'identifier' => [['Apache-2.0'], 'Apache-2.0'],
            'url' => [['https://www.apache.org/licenses/LICENSE-2.0.html'], 'https://www.apache.org/licenses/LICENSE-2.0.html'],
        ];
    }

    public function test_identifier_after_url_is_rejected(): void
    {
        $license = License::create()->name('MIT')->url('https://opensource.org/licenses/MIT');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('License::');

        $license->identifier('MIT');
    }

    public function test_url_after_identifier_is_rejected(): void
    {
        $license = License::create()->name('MIT')->identifier('MIT');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('License::');

        $license->url('https://opensource.org/licenses/MIT');
    }

    public function test_identifier_can_replace_a_url_removed_first(): void
    {
        $license = License::create()->name('MIT')->url('https://opensource.org/licenses/MIT');

        $replaced = $license->url(null)->identifier('MIT');

        self::assertSame('MIT', $replaced->identifier);
        self::assertFalse($replaced->has('url'));
    }
}
