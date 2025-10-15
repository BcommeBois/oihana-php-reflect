<?php

namespace tests\oihana\reflect;

use InvalidArgumentException;

use ReflectionClassConstant;
use ReflectionException;
use ReflectionMethod;

use oihana\reflect\Reflection;


use tests\oihana\reflect\mocks\MockAddress;
use tests\oihana\reflect\mocks\MockCreativeWork;
use tests\oihana\reflect\mocks\MockEnum;
use tests\oihana\reflect\mocks\MockGeo;
use tests\oihana\reflect\mocks\MockOrganization;
use tests\oihana\reflect\mocks\MockPerson;
use tests\oihana\reflect\mocks\MockPolymorphicContainer;
use tests\oihana\reflect\mocks\MockUser;
use tests\oihana\reflect\mocks\MockWithRenameKey;

use PHPUnit\Framework\TestCase;

class ReflectionTest extends TestCase
{
    private Reflection $reflection;

    protected function setUp(): void
    {
        $this->reflection = new Reflection();
    }

    /**
     * @throws ReflectionException
     */
    public function testShortName()
    {
        $this->assertEquals('MockUser', $this->reflection->shortName(MockUser::class));
    }

    /**
     * @throws ReflectionException
     */
    public function testConstants()
    {
        $constants = $this->reflection->constants(MockEnum::class);
        $this->assertArrayHasKey('ACTIVE', $constants);
        $this->assertEquals('active', $constants['ACTIVE']);
    }

    /**
     * @throws ReflectionException
     */
    public function testMethods()
    {
        $methods = $this->reflection->methods(MockUser::class);
        $methodNames = array_map(fn($m) => $m->getName(), $methods);
        $this->assertContains('getName', $methodNames);
    }

    /**
     * @throws ReflectionException
     */
    public function testProperties()
    {
        $properties = $this->reflection->properties(MockUser::class);
        $propertyNames = array_map(fn($p) => $p->getName(), $properties);
        $this->assertContains('name', $propertyNames);
        $this->assertNotContains('id', $propertyNames); // private
    }

    /**
     * @throws ReflectionException
     */
    public function testReflectionCaching()
    {
        $ref1 = $this->reflection->reflection(MockUser::class);
        $ref2 = $this->reflection->reflection(MockUser::class);
        $this->assertSame($ref1, $ref2);
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateFlat()
    {
        $data = ['name' => 'Alice'];
        $user = $this->reflection->hydrate($data, MockUser::class);
        $this->assertInstanceOf(MockUser::class, $user);
        $this->assertEquals('Alice', $user->name);
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateNested()
    {
        $data = [
            'name' => 'Bob',
            'address' => [
                'city' => 'Lyon'
            ]
        ];
        $user = $this->reflection->hydrate($data, MockUser::class);
        $this->assertInstanceOf(MockAddress::class, $user->address);
        $this->assertEquals('Lyon', $user->address->city);
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateArrayOfObjects()
    {
        $data =
        [
            'locations' =>
            [
                [ 'city' => 'Paris'  ],
                [ 'city' => 'Berlin' ],
            ]
        ];
        $geo = $this->reflection->hydrate($data, MockGeo::class);
        $this->assertCount(2, $geo->locations);
        $this->assertEquals('Berlin', $geo->locations[1]->city ) ;
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateArrayOfPolymorphicObjectsWithHydrateWith()
    {
        $data = [
            'items' => [
                [ '@type' => 'MockAddress', 'city' => 'Nice' ],
                [ 'type' => 'MockUser', 'name' => 'Zoe' ],
                [ 'city' => 'Unknown' ], // Should fallback to first class
            ]
        ];

        $result = $this->reflection->hydrate($data, MockPolymorphicContainer::class);

        $this->assertCount(3, $result->items);
        $this->assertInstanceOf(MockAddress::class, $result->items[0]);
        $this->assertEquals('Nice', $result->items[0]->city);
        $this->assertInstanceOf(MockUser::class, $result->items[1]);
        $this->assertEquals('Zoe', $result->items[1]->name);
        $this->assertInstanceOf(MockAddress::class, $result->items[2]); // fallback to first class
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateInvalidClassThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->reflection->hydrate([], 'NonExistentClass');
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateNonNullablePropertyWithNullThrows()
    {
        $this->expectException(InvalidArgumentException::class);

        $data = ['name' => null]; // Supposons que 'name' est non nullable
        $this->reflection->hydrate($data, MockUser::class);
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateWithHydrateKeyAttribute()
    {
        $data = ['user_name' => 'Charlie'];
        $user = $this->reflection->hydrate( $data , MockWithRenameKey::class ) ;
        $this->assertEquals('Charlie' , $user->name ) ;
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateWithArrayOfObjects()
    {
        // Case 1: Array of objects - should hydrate to [MockPerson, MockOrganization]
        $data1 =
        [
            'sponsor' =>
            [
                [ 'atType' => 'MockPerson'       , 'name' => 'Alice'],
                [ '@type'  => 'MockOrganization' , 'name' => 'Acme Corp']
            ]
        ];
        $work1 = $this->reflection->hydrate($data1, MockCreativeWork::class);

        $this->assertIsArray($work1->sponsor);
        $this->assertCount(2 , $work1->sponsor ) ;

        $this->assertInstanceOf(MockPerson::class       , $work1->sponsor[0] ) ;
        $this->assertInstanceOf(MockOrganization::class , $work1->sponsor[1] ) ;

        $this->assertEquals('Alice'     , $work1->sponsor[0]->name ) ;
        $this->assertEquals('Acme Corp' , $work1->sponsor[1]->name ) ;

        // Case 2: Unique object - should hydrate to MockPerson
        $data2 =
        [
            'sponsor' => ['type' => 'Person', 'name' => 'Bob']
        ];
        $work2 = $this->reflection->hydrate($data2, MockCreativeWork::class);

        $this->assertInstanceOf(MockPerson::class, $work2->sponsor);
        $this->assertEquals('Bob', $work2->sponsor->name);

        // Case 3: String (no hydration) - should preserve the string value
        $data3 =
        [
            'sponsor' => 'https://example.com/sponsor'
        ];
        $work3 = $this->reflection->hydrate($data3, MockCreativeWork::class);

        $this->assertIsString($work3->sponsor);
        $this->assertEquals('https://example.com/sponsor', $work3->sponsor);

        // Case 4: Null - should preserve null value
        $data4 =
        [
            'sponsor' => null
        ];
        $work4 = $this->reflection->hydrate($data4, MockCreativeWork::class);

        $this->assertNull($work4->sponsor);
    }

    /**
     * @throws ReflectionException
     */
    public function testClassNameAnonymousClass()
    {
        $anon = new class {};
        $name = $this->reflection->shortName($anon);
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    /**
     * @throws ReflectionException
     */
    public function testConstantsWithPrivateFilter()
    {
        $constants = $this->reflection->constants(MockEnum::class, ReflectionClassConstant::IS_PRIVATE);
        $this->assertArrayHasKey('HIDDEN', $constants);
        $this->assertEquals('secret', $constants['HIDDEN']);
    }

    /**
     * @throws ReflectionException
     */
    public function testMethodsWithProtectedFilter()
    {
        $methods = $this->reflection->methods(MockUser::class, ReflectionMethod::IS_PROTECTED);
        $methodNames = array_map(fn($m) => $m->getName(), $methods);
        $this->assertContains('someProtectedMethod', $methodNames);
    }

    public function testShortNameWithInvalidClassThrows()
    {
        $this->expectException(ReflectionException::class);
        $this->reflection->shortName('NonExistentClass');
    }

    public function testDescribeCallableParametersFromClosure()
    {
        $fn = fn(string $name, int $age = 42, ...$tags) => null;

        $params = $this->reflection->describeCallableParameters($fn);

        $this->assertCount(3, $params);
        $this->assertEquals('name', $params[0]['name']);
        $this->assertEquals('string', $params[0]['type']);
        $this->assertFalse($params[0]['optional']);
        $this->assertTrue($params[2]['variadic']);
    }

    /**
     * @throws ReflectionException
     */
    public function testHasParameterReturnsTrue()
    {
        $this->assertTrue
        (
            $this->reflection->hasParameter(MockUser::class, 'setName', 'name')
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testHasParameterReturnsFalse()
    {
        $this->assertFalse
        (
            $this->reflection->hasParameter(MockUser::class, 'setName', 'unknown')
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testParameterType()
    {
        $type = $this->reflection->parameterType(MockUser::class, 'setName', 'name');
        $this->assertEquals('string', $type);
    }

    /**
     * @throws ReflectionException
     */
    public function testParameterDefaultValue()
    {
        $default = $this->reflection->parameterDefaultValue(MockUser::class, 'setAge', 'age');
        $this->assertEquals(30, $default); // supposons que setAge($age = 30)
    }

    /**
     * @throws ReflectionException
     */
    public function testIsParameterNullable()
    {
        $nullable = $this->reflection->isParameterNullable(MockUser::class, 'setNickname', 'nickname');
        $this->assertTrue($nullable); // supposons ?string $nickname
    }

    /**
     * @throws ReflectionException
     */
    public function testIsParameterOptional()
    {
        $optional = $this->reflection->isParameterOptional(MockUser::class, 'setAge', 'age');
        $this->assertTrue($optional);
    }

    /**
     * @throws ReflectionException
     */
    public function testIsParameterVariadic()
    {
        $variadic = $this->reflection->isParameterVariadic(MockUser::class, 'addTags', 'tags');
        $this->assertTrue($variadic); // supposons addTags(...$tags)
    }
}