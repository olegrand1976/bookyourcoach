<?php

namespace Tests\Unit\Support;

use App\Support\FrontendUrl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FrontendUrlTest extends TestCase
{
    #[Test]
    public function sanitize_redirect_rejects_open_redirects(): void
    {
        $this->assertSame('', FrontendUrl::sanitizeRedirectPath(''));
        $this->assertSame('', FrontendUrl::sanitizeRedirectPath('https://evil.test/path'));
        $this->assertSame('', FrontendUrl::sanitizeRedirectPath('//evil.test'));
        $this->assertSame('', FrontendUrl::sanitizeRedirectPath('not-a-path'));
    }

    #[Test]
    public function sanitize_redirect_accepts_internal_paths(): void
    {
        $this->assertSame('/student/dashboard', FrontendUrl::sanitizeRedirectPath('/student/dashboard'));
        $this->assertSame('/club/planning?x=1', FrontendUrl::sanitizeRedirectPath('/club/planning?x=1'));
    }

    #[Test]
    public function login_appends_encoded_redirect(): void
    {
        config(['app.frontend_url' => 'https://app.example.com']);

        $this->assertSame('https://app.example.com/login', FrontendUrl::login());
        $this->assertSame(
            'https://app.example.com/login?redirect=%2Fteacher%2Fdashboard',
            FrontendUrl::login('/teacher/dashboard')
        );
    }
}
