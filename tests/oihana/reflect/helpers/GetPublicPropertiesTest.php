<?php

namespace oihana\reflect\helpers;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

trait TestTraitA {
    public string $traitAProp;
    protected string $traitAProtected;
    private string $traitAPrivate;
}

trait TestTraitB {
    use TestTraitA;
    public int $traitBProp;
}

class ParentClass {
    public float $parentProp;
    protected float $parentProtected;
    private float $parentPrivate;
    public static string $parentStatic;
}

class ChildClass extends ParentClass {
    use TestTraitB;

    public bool $childProp;
    protected bool $childProtected;
    private bool $childPrivate;
    public static bool $childStatic;
}

final class GetPublicPropertiesTest extends TestCase
{
    public function testSimpleClassProperties()
    {
        $reflection = new ReflectionClass(ParentClass::class);
        $props = getPublicProperties($reflection);

        $this->assertArrayHasKey('parentProp', $props);
        $this->assertArrayNotHasKey('parentProtected', $props);
        $this->assertArrayNotHasKey('parentPrivate', $props);
        $this->assertArrayNotHasKey('parentStatic', $props);
        $this->assertCount(1, $props);
    }

    public function testChildClassWithTraits()
    {
        $reflection = new ReflectionClass(ChildClass::class);
        $props = getPublicProperties($reflection);

        $expectedKeys = [
            'childProp',    // own property
            'traitBProp',   // from trait
            'traitAProp',   // from nested trait
            'parentProp',   // from parent class
        ];

        foreach ($expectedKeys as $key)
        {
            $this->assertArrayHasKey($key, $props);
        }

        // Ensure private/protected/static are not included
        $this->assertArrayNotHasKey('childProtected', $props);
        $this->assertArrayNotHasKey('childPrivate', $props);
        $this->assertArrayNotHasKey('childStatic', $props);
        $this->assertArrayNotHasKey('parentProtected', $props);
        $this->assertArrayNotHasKey('parentPrivate', $props);
        $this->assertArrayNotHasKey('parentStatic', $props);

        $this->assertCount(count($expectedKeys), $props);
    }

    public function testCacheUsage()
    {
        $cache = [];

        $reflection = new ReflectionClass(ChildClass::class);

        // First call populates the cache
        $props1 = getPublicProperties($reflection, true, $cache);
        $this->assertNotEmpty($props1);
        $this->assertArrayHasKey('childProp', $cache[ChildClass::class]);

        // Modify cache externally
        $cache = [];
        $props2 = getPublicProperties($reflection, true, $cache);
        $this->assertNotEmpty($props2);
        $this->assertArrayHasKey('childProp', $cache[ChildClass::class]);
    }

    public function testNonRecursiveOption()
    {
        $reflection = new ReflectionClass(ChildClass::class);
        $props = getPublicProperties($reflection, false);

        // Only own public properties, traits and parents excluded
        $this->assertArrayHasKey('childProp', $props);
        $this->assertArrayNotHasKey('traitBProp', $props);
        $this->assertArrayNotHasKey('traitAProp', $props);
        $this->assertArrayNotHasKey('parentProp', $props);

        $this->assertCount(1, $props);
    }
}