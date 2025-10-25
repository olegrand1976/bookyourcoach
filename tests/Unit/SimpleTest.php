<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function test_basic_phpunit_functionality()
    {
        $this->assertTrue(true);
        $this->assertEquals(2, 1 + 1);
        $this->assertStringContainsString('test', 'This is a test');
    }

    public function test_array_operations()
    {
        $array = [1, 2, 3];
        $this->assertCount(3, $array);
        $this->assertContains(2, $array);
    }
}
