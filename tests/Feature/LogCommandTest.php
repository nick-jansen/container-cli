<?php

use Tests\TestCase;

test('log command', function () {
    /** @var TestCase $this */
    $this->artisan('log "hello world"')
        ->assertExitCode(0)
        ->expectsOutput("hello world");
});
