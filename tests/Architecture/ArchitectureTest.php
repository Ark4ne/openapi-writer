<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Architecture;

use Ark4ne\OpenApiWriter\Objects\ExtensibleSpecObject;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Diagnostic;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Diagnostics;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\Rewriter;
use Ark4ne\OpenApiWriter\Validation\Rule;
use PHPat\Selector\ClassNamespace;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule as ArchitectureRule;
use PHPat\Test\PHPat;

/**
 * Architecture rules of docs/architecture.md, run by PHPStan through phpat (`composer stan`).
 */
final class ArchitectureTest
{
    private const ROOT = 'Ark4ne\OpenApiWriter';

    private const TESTS = 'Ark4ne\OpenApiWriter\Tests';

    public function test_support_depends_on_no_other_layer(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(self::layer('Support'))
            ->shouldNotDependOn()
            ->classes(...self::layers('Enums', 'Metadata', 'Objects', 'Contracts', 'Serialization', 'Validation'))
            ->because('Support is the leftmost layer.');
    }

    public function test_enums_depend_only_on_support(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(self::layer('Enums'))
            ->shouldNotDependOn()
            ->classes(...self::layers('Metadata', 'Objects', 'Contracts', 'Serialization', 'Validation'))
            ->because('a layer depends only on the layers to its left.');
    }

    public function test_metadata_does_not_depend_on_the_model_or_the_services(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(self::layer('Metadata'))
            ->shouldNotDependOn()
            ->classes(...self::layers('Objects', 'Contracts', 'Serialization', 'Validation'))
            ->because('a layer depends only on the layers to its left.');
    }

    public function test_the_model_does_not_depend_on_serialization_or_validation(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(...self::layers('Objects', 'Contracts'))
            ->excluding(Selector::classname(SpecObject::class))
            ->shouldNotDependOn()
            ->classes(...self::layers('Serialization', 'Validation'))
            ->because('the model describes, serialization decides; only the SpecObject facades call Serialization.');
    }

    public function test_spec_object_does_not_depend_on_validation(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(Selector::classname(SpecObject::class))
            ->shouldNotDependOn()
            ->classes(self::layer('Validation'))
            ->because('the facades of SpecObject only reach Serialization.');
    }

    public function test_serialization_does_not_depend_on_validation(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(self::layer('Serialization'))
            ->shouldNotDependOn()
            ->classes(self::layer('Validation'))
            ->because('a layer depends only on the layers to its left.');
    }

    public function test_exceptions_depend_only_on_support_enums_and_diagnostics(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(self::layer('Exceptions'))
            ->shouldNotDependOn()
            ->classes(...self::layers('Metadata', 'Objects', 'Contracts', 'Serialization', 'Validation'))
            ->excluding(
                Selector::classname(Diagnostics::class),
                Selector::classname(Diagnostic::class),
                Selector::classname(DiagnosticLevel::class),
            )
            ->because('exceptions are usable by every layer; UnsupportedFeatureException carries the diagnostics.');
    }

    public function test_classes_are_final(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT))
            ->excluding(
                Selector::inNamespace(self::TESTS),
                Selector::isInterface(),
                Selector::isEnum(),
                Selector::classname(SpecObject::class),
                Selector::classname(ExtensibleSpecObject::class),
            )
            ->shouldBeFinal()
            ->because('objects are extended by composition and x-* extensions, never by inheritance.');
    }

    public function test_only_the_model_base_classes_are_abstract(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT))
            ->excluding(
                Selector::inNamespace(self::TESTS),
                Selector::isInterface(),
                Selector::classname(SpecObject::class),
                Selector::classname(ExtensibleSpecObject::class),
            )
            ->shouldNotBeAbstract()
            ->because('abstract classes are reserved for the base classes of the model.');
    }

    public function test_src_uses_no_trait(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::ROOT))
            ->excluding(Selector::inNamespace(self::TESTS))
            ->shouldNotInclude()
            ->classes(Selector::all())
            ->because('traits are forbidden in src/: use a base class or composition.');
    }

    public function test_interfaces_have_no_interface_suffix(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(Selector::inNamespace(self::ROOT), Selector::isInterface()))
            ->shouldBeNamed('/^(?!.*Interface$).*$/', true)
            ->because('interfaces are named after a role (Rewriter, Encoder) or a capability (Extensible).');
    }

    public function test_rewriters_are_named_after_their_feature(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(Selector::implements(Rewriter::class))
            ->shouldBeNamed('/Rewriter$/', true)
            ->because('a rewriter is named after the version difference it handles: NullableRewriter.');
    }

    public function test_rules_are_named_after_their_check(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(Selector::implements(Rule::class))
            ->shouldBeNamed('/Rule$/', true)
            ->because('a validation rule is named after what it checks: UniqueOperationIdRule.');
    }

    public function test_union_contracts_are_suffixed_contract(): ArchitectureRule
    {
        return PHPat::rule()
            ->classes(Selector::AllOf(Selector::inNamespace(self::ROOT . '\Contracts'), Selector::isInterface()))
            ->excluding(Selector::classname(self::ROOT . '\Contracts\Extensible'))
            ->shouldBeNamed('/Contract$/', true)
            ->because('"X or Reference" unions are named <X>Contract.');
    }

    private static function layer(string $layer): ClassNamespace
    {
        return Selector::inNamespace(self::ROOT . '\\' . $layer);
    }

    /**
     * @return list<ClassNamespace>
     */
    private static function layers(string ...$layers): array
    {
        return array_values(array_map(self::layer(...), $layers));
    }
}
