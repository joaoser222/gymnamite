<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\InteractsWithPermissions;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithPermissions;
}
