<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_be_instantiated()
    {
        $auditLog = new AuditLog();

        $this->assertInstanceOf(AuditLog::class, $auditLog);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $auditLog = new AuditLog();

        $this->assertEquals('audit_logs', $auditLog->getTable());
    }

    /** @test */
    public function it_uses_timestamps()
    {
        $auditLog = new AuditLog();

        $this->assertTrue($auditLog->timestamps);
    }
}
