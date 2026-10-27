<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\Contact;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\License;

final class InfoTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Info::class;
    }

    public static function fields(): array
    {
        $contact = Contact::create()->name('API Support');
        $license = License::create()->name('MIT');

        return [
            'title' => [['Users API'], 'Users API'],
            'summary' => [['Manage users'], 'Manage users'],
            'description' => [['The users of the platform.'], 'The users of the platform.'],
            'termsOfService' => [['https://example.com/terms'], 'https://example.com/terms'],
            'contact' => [[$contact], $contact],
            'license' => [[$license], $license],
            'version' => [['1.0.0'], '1.0.0'],
        ];
    }
}
