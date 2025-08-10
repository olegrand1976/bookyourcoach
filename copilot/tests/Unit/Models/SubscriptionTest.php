<?php

namespace Tests\Unit\Models;

use App\Models\Subscription;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $subscription = new Subscription();
        
        $this->assertInstanceOf(Subscription::class, $subscription);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $subscription = new Subscription();
        
        $this->assertEquals('subscriptions', $subscription->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $subscription = new Subscription();
        
        $this->assertTrue($subscription->timestamps);
    }
}
