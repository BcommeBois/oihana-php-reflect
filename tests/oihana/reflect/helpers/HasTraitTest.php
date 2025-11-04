<?php

namespace oihana\reflect\helpers;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

trait DirectTrait {}

trait BaseTrait {}

trait ComposedTrait 
{
    use BaseTrait;
}

/**
 * Parent class with direct trait
 */
class ParentClazz 
{
    use DirectTrait;
}

/**
 * Child class
 */
class ChildClazz extends ParentClazz {}

/**
 * A class with an internal trait.
 */
class NestedClazz
{
    use ComposedTrait;
}


final class HasTraitTest extends TestCase
{


    public function testDirectTrait(): void
    {
        $reflection = new ReflectionClass(ParentClazz::class);
        $this->assertTrue(hasTrait($reflection, DirectTrait::class));
    }
    
    public function testTraitInParent(): void
    {
        $reflection = new ReflectionClass(ChildClazz::class);
        $this->assertTrue(hasTrait($reflection, DirectTrait::class));
    }
    
    public function testNestedTrait(): void
    {
        $reflection = new ReflectionClass(NestedClazz::class);
        $this->assertTrue(hasTrait($reflection, BaseTrait::class));
        $this->assertTrue(hasTrait($reflection, ComposedTrait::class));
    }
    
    public function testNonRecursive(): void
    {
        $reflection = new ReflectionClass(ChildClazz::class);
        $this->assertFalse(hasTrait($reflection, DirectTrait::class, false));
    
        $reflectionNested = new ReflectionClass(NestedClazz::class);
        $this->assertFalse(hasTrait($reflectionNested, BaseTrait::class, false));
    }
    
    public function testCacheUsage(): void
    {
        $cache = [];
    
        $reflection1 = new ReflectionClass(ParentClazz::class);
        $reflection2 = new ReflectionClass(ChildClazz::class);
    
        // Première utilisation remplit le cache
        $this->assertTrue(hasTrait($reflection1, DirectTrait::class, true, $cache));
    
        // Deuxième utilisation doit utiliser le cache
        $this->assertTrue(hasTrait($reflection2, DirectTrait::class, true, $cache));
    
        // Le cache doit contenir une clé pour chaque combinaison
        $expectedKey1 = ParentClazz::class . '::' . DirectTrait::class . '::1';
        $expectedKey2 = ChildClazz::class . '::' . DirectTrait::class . '::1';
    
        $this->assertArrayHasKey($expectedKey1, $cache);
        $this->assertArrayHasKey($expectedKey2, $cache);
    }
}