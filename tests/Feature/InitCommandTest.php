<?php

test('init command', function () {
    $this->artisan('init')
         ->assertExitCode(0);
});
