<?php

namespace oihana\reflect\helpers;

use oihana\reflect\traits\ConstantsTrait;
use PHPUnit\Framework\TestCase;

trait UseConstantsTraitTestTrait { use ConstantsTrait; }

class UseConstantsTraitDirectClass { use UseConstantsTraitTestTrait; }
class UseConstantsTraitParentClass { use UseConstantsTraitTestTrait; }
class UseConstantsTraitChildClass extends UseConstantsTraitParentClass {}
class UseConstantsTraitNoTraitClass {}

final class UseConstantsTrait extends TestCase
{
    public function testDirectTrait(): void
    {
        $this->assertTrue(useConstantsTrait(UseConstantsTraitDirectClass::class));
    }

    public function testTraitInParent(): void
    {
        $this->assertTrue(useConstantsTrait(UseConstantsTraitChildClass::class));
    }

    public function testClassWithoutTrait(): void
    {
        $this->assertFalse(useConstantsTrait(UseConstantsTraitNoTraitClass::class));
    }

    public function testNonExistentClass(): void
    {
        $this->assertFalse(useConstantsTrait('NonExistentClass'));
    }
}