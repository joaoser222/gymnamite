<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithPermissions;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithPermissions;

    protected function setUp(): void
    {
        parent::setUp();

        // Web form feature tests post without a CSRF token; disable the
        // request forgery middleware for the test environment only.
        $this->withoutMiddleware(PreventRequestForgery::class);
    }
}
