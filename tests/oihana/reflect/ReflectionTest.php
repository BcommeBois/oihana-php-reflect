<?php

namespace tests\oihana\reflect;

use DateTime;
use DateTimeImmutable;
use InvalidArgumentException;

use ReflectionClassConstant;
use ReflectionException;
use ReflectionMethod;
use TypeError;
use ValueError;

use oihana\reflect\Reflection;


use tests\oihana\reflect\mocks\MockAddress;
use tests\oihana\reflect\mocks\MockCreativeWork;
use tests\oihana\reflect\mocks\MockDocCommentArray;
use tests\oihana\reflect\mocks\MockHydrateAs;
use tests\oihana\reflect\mocks\MockHydrateWithBogus;
use tests\oihana\reflect\mocks\MockHydrateWithEmpty;
use tests\oihana\reflect\mocks\MockHydrateWithRenameKey;
use tests\oihana\reflect\mocks\MockEnum;
use tests\oihana\reflect\mocks\MockGeo;
use tests\oihana\reflect\mocks\MockOrganization;
use tests\oihana\reflect\mocks\MockOptionalCtor;
use tests\oihana\reflect\mocks\MockPerson;
use tests\oihana\reflect\mocks\MockPolymorphicContainer;
use tests\oihana\reflect\mocks\MockRequiredCtor;
use tests\oihana\reflect\mocks\MockScalarCoercion;
use tests\oihana\reflect\mocks\MockPriority;
use tests\oihana\reflect\mocks\MockStatus;
use tests\oihana\reflect\mocks\MockWithDate;
use tests\oihana\reflect\mocks\MockWithReadonly;
use tests\oihana\reflect\mocks\MockWithEnum;
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
     * Regression: an `array` property annotated with `@var Class[]` (no
     * HydrateWith attribute) hydrates each element into the documented class.
     *
     * @throws ReflectionException
     */
    public function testHydrateArrayFromVarDocComment()
    {
        $data = [
            'addresses' => [
                [ 'city' => 'Nice' ],
                [ 'city' => 'Paris' ],
            ],
        ];

        $result = $this->reflection->hydrate($data, MockDocCommentArray::class);

        $this->assertCount      ( 2 , $result->addresses ) ;
        $this->assertInstanceOf ( MockAddress::class , $result->addresses[0] ) ;
        $this->assertEquals     ( 'Nice' , $result->addresses[0]->city ) ;
        $this->assertInstanceOf ( MockAddress::class , $result->addresses[1] );
        $this->assertEquals     ( 'Paris' , $result->addresses[1]->city ) ;
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

    /**
     * @throws ReflectionException
     */
    public function testDescribeCallableParametersFromObjectMethodArray()
    {
        $params = $this->reflection->describeCallableParameters([ new MockUser(), 'setName' ]);

        $this->assertEquals('name', $params[0]['name']);
    }

    /**
     * @throws ReflectionException
     */
    public function testDescribeCallableParametersFromInvokableObject()
    {
        $invokable = new class
        {
            public function __invoke(string $label): string { return $label; }
        };

        $params = $this->reflection->describeCallableParameters($invokable);

        $this->assertCount(1, $params);
        $this->assertEquals('label', $params[0]['name']);
    }

    /**
     * @throws ReflectionException
     */
    public function testDescribeCallableParametersWithUnionType()
    {
        $fn = fn(int|string|null $value) => $value;

        $params = $this->reflection->describeCallableParameters($fn);

        // PHP may reorder union members, so assert membership rather than order.
        $this->assertStringContainsString('int', $params[0]['type']);
        $this->assertStringContainsString('string', $params[0]['type']);
        $this->assertStringContainsString('null', $params[0]['type']);
        $this->assertTrue($params[0]['nullable']);
    }

    /**
     * @throws ReflectionException
     */
    public function testDescribeCallableParametersThrowsOnUnresolvableCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->reflection->describeCallableParameters('Unknown\\Class::nope');
    }

    /**
     * @throws ReflectionException
     */
    public function testParametersThrowsWhenMethodIsMissing()
    {
        $this->expectException(ReflectionException::class);
        $this->reflection->parameters(MockUser::class, 'ghostMethod');
    }

    /**
     * @throws ReflectionException
     */
    public function testParameterTypeReturnsNullWhenParamMissing()
    {
        $this->assertNull($this->reflection->parameterType(MockUser::class, 'setName', 'ghost'));
    }

    /**
     * @throws ReflectionException
     */
    public function testParameterDefaultValueReturnsNullWhenParamMissing()
    {
        $this->assertNull($this->reflection->parameterDefaultValue(MockUser::class, 'setName', 'ghost'));
    }

    /**
     * @throws ReflectionException
     */
    public function testIsParameterNullableReturnsFalseWhenParamMissing()
    {
        $this->assertFalse($this->reflection->isParameterNullable(MockUser::class, 'setName', 'ghost'));
    }

    /**
     * @throws ReflectionException
     */
    public function testIsParameterOptionalReturnsFalseWhenParamMissing()
    {
        $this->assertFalse($this->reflection->isParameterOptional(MockUser::class, 'setName', 'ghost'));
    }

    /**
     * @throws ReflectionException
     */
    public function testIsParameterVariadicReturnsFalseWhenParamMissing()
    {
        $this->assertFalse($this->reflection->isParameterVariadic(MockUser::class, 'setName', 'ghost'));
    }

    /**
     * #[HydrateAs] forces the target class for an otherwise loosely-typed property.
     *
     * @throws ReflectionException
     */
    public function testHydrateWithHydrateAsAttribute()
    {
        // Associative value -> single object.
        $single = $this->reflection->hydrate([ 'payload' => [ 'city' => 'Nice' ] ], MockHydrateAs::class);
        $this->assertInstanceOf(MockAddress::class, $single->payload);
        $this->assertEquals('Nice', $single->payload->city);

        // List value -> array of objects (array_map branch).
        $list = $this->reflection->hydrate(
            [ 'payload' => [ [ 'city' => 'A' ], [ 'city' => 'B' ] ] ],
            MockHydrateAs::class
        );
        $this->assertCount(2, $list->payload);
        $this->assertInstanceOf(MockAddress::class, $list->payload[0]);
        $this->assertEquals('B', $list->payload[1]->city);
    }

    /**
     * #[HydrateWith] scores candidate classes by their HydrateKey alternative key.
     *
     * @throws ReflectionException
     */
    public function testHydrateWithSelectsClassByHydrateKeyScore()
    {
        $data   = [ 'items' => [ [ 'user_name' => 'Bob' ] ] ];
        $result = $this->reflection->hydrate($data, MockHydrateWithRenameKey::class);

        $this->assertInstanceOf(MockWithRenameKey::class, $result->items[0]);
        $this->assertEquals('Bob', $result->items[0]->name);
    }

    /**
     * #[HydrateWith] containing only a non-existent class leaves array items untouched.
     *
     * @throws ReflectionException
     */
    public function testHydrateWithUnknownClassLeavesItemsAsArrays()
    {
        $data   = [ 'items' => [ [ 'x' => 1 ] ] ];
        $result = $this->reflection->hydrate($data, MockHydrateWithBogus::class);

        $this->assertSame([ 'x' => 1 ], $result->items[0]);
    }

    /**
     * An empty #[HydrateWith()] cannot guess a type, so array items stay raw.
     *
     * @throws ReflectionException
     */
    public function testHydrateWithEmptyAttributeLeavesItemsAsArrays()
    {
        $data   = [ 'items' => [ [ 'x' => 1 ] ] ];
        $result = $this->reflection->hydrate($data, MockHydrateWithEmpty::class);

        $this->assertSame([ 'x' => 1 ], $result->items[0]);
    }

    /**
     * Non-array elements in a #[HydrateWith] array are passed through unchanged.
     *
     * @throws ReflectionException
     */
    public function testHydrateWithPassesThroughNonArrayItems()
    {
        $data = [
            'items' => [
                42,
                [ '@type' => 'MockAddress', 'city' => 'X' ],
            ],
        ];
        $result = $this->reflection->hydrate($data, MockPolymorphicContainer::class);

        $this->assertSame(42, $result->items[0]);
        $this->assertInstanceOf(MockAddress::class, $result->items[1]);
    }

    // ------------------------------------------------------------------ Enums

    /**
     * @throws ReflectionException
     */
    public function testHydrateStringBackedEnumFromScalar()
    {
        $object = $this->reflection->hydrate( [ 'status' => 'inactive' ] , MockWithEnum::class );
        $this->assertSame( MockStatus::Inactive , $object->status );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateIntBackedEnumFromScalar()
    {
        $object = $this->reflection->hydrate( [ 'status' => 'active' , 'priority' => 10 ] , MockWithEnum::class );
        $this->assertSame( MockPriority::High , $object->priority );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateEnumKeepsExistingInstance()
    {
        $object = $this->reflection->hydrate( [ 'status' => MockStatus::Active ] , MockWithEnum::class );
        $this->assertSame( MockStatus::Active , $object->status );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateNullableEnumWithNullStaysNull()
    {
        $object = $this->reflection->hydrate( [ 'status' => 'active' , 'priority' => null ] , MockWithEnum::class );
        $this->assertNull( $object->priority );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateInvalidEnumValueThrows()
    {
        $this->expectException( ValueError::class );
        $this->reflection->hydrate( [ 'status' => 'unknown' ] , MockWithEnum::class );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateArrayOfEnumsViaHydrateWith()
    {
        $object = $this->reflection->hydrate( [ 'status' => 'active' , 'history' => [ 'active' , 'inactive' ] ] , MockWithEnum::class );
        $this->assertSame( [ MockStatus::Active , MockStatus::Inactive ] , $object->history );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateArrayOfEnumsViaVarDocComment()
    {
        $object = $this->reflection->hydrate( [ 'status' => 'active' , 'levels' => [ 1 , 10 ] ] , MockWithEnum::class );
        $this->assertSame( [ MockPriority::Low , MockPriority::High ] , $object->levels );
    }

    // -------------------------------------------------------------- DateTime

    /**
     * @throws ReflectionException
     */
    public function testHydrateImmutableDateFromIsoString()
    {
        $object = $this->reflection->hydrate( [ 'createdAt' => '2024-01-02T03:04:05+00:00' ] , MockWithDate::class );
        $this->assertInstanceOf( DateTimeImmutable::class , $object->createdAt );
        $this->assertSame( '2024-01-02T03:04:05+00:00' , $object->createdAt->format( 'c' ) );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateMutableDateKeepsConcreteClass()
    {
        $object = $this->reflection->hydrate( [ 'updatedAt' => '2024-05-06' ] , MockWithDate::class );
        $this->assertInstanceOf( DateTime::class , $object->updatedAt );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateDateInterfaceDefaultsToImmutable()
    {
        $object = $this->reflection->hydrate( [ 'publishedAt' => '2024-05-06' ] , MockWithDate::class );
        $this->assertInstanceOf( DateTimeImmutable::class , $object->publishedAt );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateDateFromIntTimestamp()
    {
        $object = $this->reflection->hydrate( [ 'createdAt' => 1704067200 ] , MockWithDate::class );
        $this->assertInstanceOf( DateTimeImmutable::class , $object->createdAt );
        $this->assertSame( 1704067200 , $object->createdAt->getTimestamp() );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateDateKeepsExistingInstance()
    {
        $date   = new DateTimeImmutable( '2020-01-01' );
        $object = $this->reflection->hydrate( [ 'createdAt' => $date ] , MockWithDate::class );
        $this->assertSame( $date , $object->createdAt );
    }

    /**
     * Scalar wins : a union containing string keeps the raw string (schema.org value object style).
     *
     * @throws ReflectionException
     */
    public function testHydrateUnionWithStringKeepsRawString()
    {
        $object = $this->reflection->hydrate( [ 'endDate' => '2024-12-31' ] , MockWithDate::class );
        $this->assertSame( '2024-12-31' , $object->endDate );
    }

    /**
     * Real schema.org pattern (null|string|int) : never converted.
     *
     * @throws ReflectionException
     */
    public function testHydrateNonDateUnionIsUntouched()
    {
        $asString = $this->reflection->hydrate( [ 'startDate' => '2024-12-31' ] , MockWithDate::class );
        $this->assertSame( '2024-12-31' , $asString->startDate );

        $asInt = $this->reflection->hydrate( [ 'startDate' => 1704067200 ] , MockWithDate::class );
        $this->assertSame( 1704067200 , $asInt->startDate );
    }

    /**
     * Explicit #[HydrateAs] forces date parsing even when the union accepts a string.
     *
     * @throws ReflectionException
     */
    public function testHydrateForcedDateViaHydrateAs()
    {
        $object = $this->reflection->hydrate( [ 'forcedDate' => '2024-12-31' ] , MockWithDate::class );
        $this->assertInstanceOf( DateTimeImmutable::class , $object->forcedDate );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateNullableDateWithNullStaysNull()
    {
        $object = $this->reflection->hydrate( [ 'createdAt' => null ] , MockWithDate::class );
        $this->assertNull( $object->createdAt );
    }

    /**
     * @throws ReflectionException
     */
    public function testHydrateArrayOfDatesViaVarDocComment()
    {
        $object = $this->reflection->hydrate( [ 'milestones' => [ '2024-01-01' , '2024-06-01' ] ] , MockWithDate::class );
        $this->assertCount( 2 , $object->milestones );
        $this->assertContainsOnlyInstancesOf( DateTimeImmutable::class , $object->milestones );
    }

    // ----------------------------------------------------------- Constructors

    /**
     * A class whose constructor requires arguments is hydrated without invoking it.
     *
     * @throws ReflectionException
     */
    public function testHydrateClassWithRequiredConstructor()
    {
        $object = $this->reflection->hydrate( [ 'currency' => 'EUR' , 'amount' => 100 ] , MockRequiredCtor::class );
        $this->assertInstanceOf( MockRequiredCtor::class , $object );
        $this->assertSame( 'EUR' , $object->currency );
        $this->assertSame( 100 , $object->amount );
    }

    /**
     * Declared property defaults still apply when bypassing the constructor.
     *
     * @throws ReflectionException
     */
    public function testHydrateRequiredConstructorKeepsDeclaredDefault()
    {
        $object = $this->reflection->hydrate( [ 'currency' => 'USD' ] , MockRequiredCtor::class );
        $this->assertSame( 0 , $object->amount ); // declared default preserved
    }

    /**
     * A required property absent from the data stays uninitialized (no constructor ran).
     *
     * @throws ReflectionException
     */
    public function testHydrateRequiredConstructorLeavesMissingPropertyUninitialized()
    {
        $object = $this->reflection->hydrate( [ 'amount' => 5 ] , MockRequiredCtor::class );

        $property = new \ReflectionProperty( MockRequiredCtor::class , 'currency' );
        $this->assertFalse( $property->isInitialized( $object ) );
    }

    /**
     * Non-regression: a constructor callable with no arguments is still invoked.
     *
     * @throws ReflectionException
     */
    public function testHydrateOptionalConstructorStillRuns()
    {
        $object = $this->reflection->hydrate( [ 'name' => 'Bob' ] , MockOptionalCtor::class );
        $this->assertTrue( $object->constructed ); // constructor body executed
        $this->assertSame( 'Bob' , $object->name ); // then overwritten by hydration
    }

    /**
     * Non-regression: the no-argument constructor runs even with empty data.
     *
     * @throws ReflectionException
     */
    public function testHydrateOptionalConstructorRunsWithEmptyData()
    {
        $object = $this->reflection->hydrate( [] , MockOptionalCtor::class );
        $this->assertTrue( $object->constructed );
        $this->assertSame( 'fallback' , $object->name ); // constructor default applied
    }

    // -------------------------------------- Readonly / asymmetric visibility

    /**
     * A readonly property is initialized through reflection.
     *
     * @throws ReflectionException
     */
    public function testHydrateReadonlyProperty()
    {
        $object = $this->reflection->hydrate( [ 'id' => 'abc-123' ] , MockWithReadonly::class );
        $this->assertSame( 'abc-123' , $object->id );
    }

    /**
     * A public-read / private-write property is written through reflection.
     *
     * @throws ReflectionException
     */
    public function testHydratePrivateSetProperty()
    {
        $object = $this->reflection->hydrate( [ 'score' => 5 ] , MockWithReadonly::class );
        $this->assertSame( 5 , $object->score );
    }

    /**
     * A public-read / protected-write property is written through reflection.
     *
     * @throws ReflectionException
     */
    public function testHydrateProtectedSetProperty()
    {
        $object = $this->reflection->hydrate( [ 'tag' => 'vip' ] , MockWithReadonly::class );
        $this->assertSame( 'vip' , $object->tag );
    }

    /**
     * Non-regression: plain mutable property still works, with scalar coercion preserved.
     *
     * @throws ReflectionException
     */
    public function testHydratePlainPropertyWithScalarCoercionViaSetValue()
    {
        $object = $this->reflection->hydrate( [ 'count' => '42' ] , MockWithReadonly::class );
        $this->assertSame( 42 , $object->count ); // string '42' coerced to int 42
    }

    // ------------------------------------------ Scalar coercion contract (lock-in)

    /**
     * Numeric string is coerced to int.
     *
     * @throws ReflectionException
     */
    public function testHydrateCoercesStringToInt()
    {
        $object = $this->reflection->hydrate( [ 'count' => '42' ] , MockScalarCoercion::class );
        $this->assertSame( 42 , $object->count );
    }

    /**
     * Numeric string is coerced to float.
     *
     * @throws ReflectionException
     */
    public function testHydrateCoercesStringToFloat()
    {
        $object = $this->reflection->hydrate( [ 'ratio' => '3.14' ] , MockScalarCoercion::class );
        $this->assertSame( 3.14 , $object->ratio );
    }

    /**
     * '1' / '0' strings are coerced to bool.
     *
     * @throws ReflectionException
     */
    public function testHydrateCoercesStringToBool()
    {
        $true  = $this->reflection->hydrate( [ 'enabled' => '1' ] , MockScalarCoercion::class );
        $false = $this->reflection->hydrate( [ 'enabled' => '0' ] , MockScalarCoercion::class );
        $this->assertTrue( $true->enabled );
        $this->assertFalse( $false->enabled );
    }

    /**
     * int is coerced to string.
     *
     * @throws ReflectionException
     */
    public function testHydrateCoercesIntToString()
    {
        $object = $this->reflection->hydrate( [ 'label' => 7 ] , MockScalarCoercion::class );
        $this->assertSame( '7' , $object->label );
    }

    /**
     * Fail loud: a value that cannot be coerced to the declared scalar type throws TypeError.
     *
     * @throws ReflectionException
     */
    public function testHydrateNonCoercibleScalarThrows()
    {
        $this->expectException( TypeError::class );
        $this->reflection->hydrate( [ 'count' => 'not-a-number' ] , MockScalarCoercion::class );
    }

    // ------------------------------------------------ Hydration plan cache

    /**
     * Hydrating the same class several times (cached plan) yields independent, correct objects.
     *
     * @throws ReflectionException
     */
    public function testHydratePlanCacheReuse()
    {
        $first  = $this->reflection->hydrate( [ 'name' => 'Alice' , 'address' => [ 'city' => 'Paris'  ] ] , MockUser::class );
        $second = $this->reflection->hydrate( [ 'name' => 'Bob'   , 'address' => [ 'city' => 'Berlin' ] ] , MockUser::class );

        $this->assertNotSame( $first , $second );
        $this->assertSame( 'Alice'  , $first->name );
        $this->assertSame( 'Paris'  , $first->address->city );
        $this->assertSame( 'Bob'    , $second->name );
        $this->assertSame( 'Berlin' , $second->address->city );
    }
}