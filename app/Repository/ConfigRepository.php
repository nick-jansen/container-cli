<?php

namespace App\Repository;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class ConfigRepository
{
    private array $config = [];

    private array $rules = [
        'templates' => ['array'],
        'templates.*.name' => ['required', 'string'],
        'templates.*.destination' => ['required', 'string'],
        'variables' => ['array'],
        'rules' => ['array']
    ];

    public function __construct()
    {
        if (File::exists($this->getConfigFilePath())) {
            $this->config = Yaml::parseFile($this->getConfigFilePath());
        }

        if (File::exists($this->getCustomConfigFilePath())) {
            $custom = Yaml::parseFile($this->getCustomConfigFilePath());
            $this->config = array_replace_recursive($this->config, $custom);
        }

        $this->validate();
    }

    public function get(?string $key = null, $default = null)
    {
        $config = $this->config;

        foreach ($config['variables'] ?? [] as $varKey => $varValue) {
            $envKey = Str::upper(Str::snake($varKey));
            $config['variables'][$varKey] = env($envKey, $varValue);
        }

        if ($key) {
            return Arr::get($config, $key, $default);
        }

        return $config;
    }

    public function getPath(string $path = ''): string
    {
        $path = str_replace('~/', getenv("HOME") . '/', $path);

        return str_replace('./', config('container.workdir') . '/', $path);
    }

    public function getConfigFilePath(): string
    {
        return $this->getPath('./config.yml');
    }

    public function getCustomConfigFilePath(): string
    {
        return $this->getPath('./custom.yml');
    }

    public function validate(): void
    {
        $validator = Validator::make(
            $this->get(),
            $this->rules,
            config('validation')
        );

        foreach ($validator->errors()->all() as $error) {
            $validationErrors[] = $error;
        }

        if (count($validationErrors ?? [])) {
            abort(1, implode(PHP_EOL, $validationErrors));
        }

        $this->validateVariables();
    }

    public function validateVariables(): void
    {
        $rules = $this->get('rules', []);

        $validator = Validator::make(
            $this->get('variables', []),
            $rules,
            config('validation'),
            $attributes ?? []
        );

        foreach ($validator->errors()->all() as $error) {
            $validationErrors[] = $error;
        }

        if (count($validationErrors ?? [])) {
            abort(1, implode(PHP_EOL, $validationErrors));
        }
    }
}
