<?php

use Tests\TestCase;

test('init command', function () {
    /** @var TestCase $this */
    $this->artisan('init')
         ->assertExitCode(0);
});
