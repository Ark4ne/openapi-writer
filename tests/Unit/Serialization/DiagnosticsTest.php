<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Serialization;

use Ark4ne\OpenApiWriter\Serialization\Diagnostic;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Diagnostics;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Tests\TestCase;

final class DiagnosticsTest extends TestCase
{
    public function test_a_new_collection_is_empty(): void
    {
        $diagnostics = new Diagnostics();

        self::assertTrue($diagnostics->isEmpty());
        self::assertCount(0, $diagnostics);
        self::assertSame([], $diagnostics->all());
    }

    public function test_it_keeps_every_diagnostic_in_order(): void
    {
        $converted = new Diagnostic(DiagnosticLevel::Converted, JsonPointer::fromString('/components/schemas/User'), 'nullable written as type: [string, "null"]');
        $unsupported = new Diagnostic(DiagnosticLevel::Unsupported, JsonPointer::fromString('/webhooks'), 'webhooks needs 3.1.0');
        $diagnostics = new Diagnostics();

        $diagnostics->add($converted);
        $diagnostics->add($unsupported);

        self::assertFalse($diagnostics->isEmpty());
        self::assertCount(2, $diagnostics);
        self::assertSame([$converted, $unsupported], $diagnostics->all());
        self::assertSame([$converted, $unsupported], iterator_to_array($diagnostics));
    }

    public function test_of_level_filters_by_level(): void
    {
        $converted = new Diagnostic(DiagnosticLevel::Converted, JsonPointer::fromString('/a'), 'a');
        $dropped = new Diagnostic(DiagnosticLevel::Dropped, JsonPointer::fromString('/b'), 'b');
        $alsoConverted = new Diagnostic(DiagnosticLevel::Converted, JsonPointer::fromString('/c'), 'c');
        $diagnostics = new Diagnostics();
        $diagnostics->add($converted);
        $diagnostics->add($dropped);
        $diagnostics->add($alsoConverted);

        self::assertSame([$converted, $alsoConverted], $diagnostics->ofLevel(DiagnosticLevel::Converted));
        self::assertSame([$dropped], $diagnostics->ofLevel(DiagnosticLevel::Dropped));
        self::assertSame([], $diagnostics->ofLevel(DiagnosticLevel::Unsupported));
    }

    public function test_a_diagnostic_reads_as_level_pointer_and_message(): void
    {
        $diagnostic = new Diagnostic(DiagnosticLevel::Dropped, JsonPointer::fromString('/info/summary'), 'summary needs 3.1.0');

        self::assertSame('[dropped] /info/summary: summary needs 3.1.0', (string) $diagnostic);
    }
}
