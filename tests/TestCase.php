<?php

namespace Tests;

use LaravelZero\Framework\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        $_ENV['CONTAINER_WORKDIR'] = getcwd() . '/tests/Container';

        parent::setUp();
    }
}
