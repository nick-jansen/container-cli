<?php

use Tests\TestCase;
use Illuminate\Support\Arr;
use App\Repository\ConfigRepository;

beforeEach(function () {
    $this->config = new ConfigRepository;
});

it("can load the container configuration", function () {
    /** @var TestCase $this */
    $this->assertIsArray($this->config->get());
    $this->assertArrayHasKey('variables.php_timezone', Arr::dot($this->config->get()));
    $this->assertEquals('Europe/Amsterdam', $this->config->get()['variables']['php_timezone']);
});

it("can override configuration with custom.yml", function () {
    /** @var TestCase $this */
    $this->assertArrayHasKey('variables.php_upload_size', Arr::dot($this->config->get()));
    $this->assertEquals('32M', $this->config->get()['variables']['php_upload_size']);
});

it("can override configuration with environment variables", function () {
    /** @var TestCase $this */
    $this->assertArrayHasKey('variables.php_memory_limit', Arr::dot($this->config->get()));
    $this->assertEquals('256M', $this->config->get()['variables']['php_memory_limit']);
});
