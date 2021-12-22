<?php

use Tests\TestCase;
use Illuminate\Support\Arr;
use App\Repository\ConfigRepository;

beforeEach(function () {
    $this->config = new ConfigRepository(
        base_path('tests/Container/init.yml')
    );
});

it("can load the container configuration", function () {
    /** @var TestCase $this */
    $this->assertIsArray($this->config->get());
    $this->assertArrayHasKey('variables.php_timezone', Arr::dot($this->config->get()));
    $this->assertEquals('Europe/Amsterdam', $this->config->get()['variables']['php_timezone']);
});
