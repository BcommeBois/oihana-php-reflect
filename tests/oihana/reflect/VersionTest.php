<?php

namespace tests\oihana\reflect;

use PHPUnit\Framework\TestCase;

use oihana\reflect\Version;

class VersionTest extends TestCase
{
    public function testConstruct()
    {
        $version = new Version(2, 1, 1, 110);

        $this->assertEquals(2, $version->major);
        $this->assertEquals(1, $version->minor);
        $this->assertEquals(1, $version->build);
        $this->assertEquals(110, $version->revision);
    }

    public function testSetProperties()
    {
        $version = new Version();
        $version->major = 3;
        $version->minor = 2;
        $version->build = 1;
        $version->revision = 100;

        $this->assertEquals(3   , $version->major );
        $this->assertEquals(2   , $version->minor ) ;
        $this->assertEquals(1   , $version->build );
        $this->assertEquals(100 , $version->revision ) ;
    }

    public function testEquals()
    {
        $version1 = new Version(2, 1, 1, 110);
        $version2 = new Version(2, 1, 1, 110);
        $version3 = new Version(3, 1, 1, 110);

        $this->assertTrue($version1->equals($version2));
        $this->assertFalse($version1->equals($version3));
        $this->assertFalse($version1->equals('toto'));
    }

    public function testFromString()
    {
        $versionStr1 = Version::fromString('2.1.1.110');
        $version1 = new Version(2, 1, 1, 110);
        $this->assertEquals((string)$version1, $versionStr1);

        $versionStr2 = Version::fromString('2');
        $version2 = new Version(2, 0, 0, 0);
        $this->assertEquals((string)$version2, $versionStr2);

        $this->assertNull(Version::fromString(''));
    }

    public function testToString()
    {
        $version = new Version(2, 1, 1, 110);
        $this->assertEquals('2.1.1.110', (string) $version ) ;

        $version->fields = 2;
        $this->assertEquals('2.1', $version ) ;
    }

    public function testValueOf()
    {
        $version = new Version(2, 1, 1, 110);
        $expectedValue = (2 << 28) | (1 << 24) | (1 << 16) | 110;
        $this->assertEquals($expectedValue, $version->valueOf());
    }
}