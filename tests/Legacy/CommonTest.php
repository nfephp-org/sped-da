<?php

namespace NFePHP\DA\Tests\Legacy;

use NFePHP\DA\Legacy\Common;
use PHPUnit\Framework\TestCase;

class CommonTest extends TestCase
{
    private $common;

    protected function setUp(): void
    {
        $this->common = new Common();
    }

    public function testToTimestamp()
    {
        $expected = (new \DateTime('2009-11-02T00:00:00-03:00'))->getTimestamp();
        $this->assertEquals($expected, $this->common->toTimestamp('2009-11-02T00:00:00-03:00'));
        $this->assertEquals(0, $this->common->toTimestamp('invalid-date'));
    }

    public function testToDateTime()
    {
        $date = $this->common->toDateTime('2009-11-02T00:00:00-03:00');
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2009-11-02', $date->format('Y-m-d'));

        $this->assertFalse($this->common->toDateTime('invalid-date'));
    }

    public function testToDateTimeWithoutTimezone()
    {
        $date = $this->common->toDateTime('2009-11-02T00:00:00');
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2009-11-02', $date->format('Y-m-d'));

        $this->assertFalse($this->common->toDateTime('invalid-date'));
    }

    public function testToDateTimeLegacy()
    {
        $reflection = new \ReflectionClass($this->common);
        $method = $reflection->getMethod('toDateTimeLegacy');
        $method->setAccessible(true);
        $date = $method->invoke($this->common, '2009-11-02T00:00:00');
        $this->assertInstanceOf(\DateTime::class, $date);
        $this->assertEquals('2009-11-02', $date->format('Y-m-d'));

        $date = $method->invoke($this->common, '2009-11-02T00:00:00-03:00');
        $this->assertEquals('2009-11-02', $date->format('Y-m-d'));

        $date = $method->invoke($this->common, 'invalid-date');
        $this->assertFalse($date);
    }

}
