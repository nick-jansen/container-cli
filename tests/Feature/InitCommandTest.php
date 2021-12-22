<?php

use Tests\TestCase;

test('init command', function () {
    $genFile = base_path("tests/Container/temp/service/test.conf");

    @unlink($genFile);

    /** @var TestCase $this */
    $this->artisan('init', [
        'init-file' => base_path('tests/Container/init.yml'),
        '--extend-file' => base_path('tests/Container/extend.yml'),
        '--config-file' => base_path('tests/Container/config.yml')
    ])
        ->assertExitCode(0);

    $this->assertFileEquals(base_path('tests/Container/templates/test.result'), $genFile);
});
