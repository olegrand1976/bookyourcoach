<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;


class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    #[Test]
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }
}
