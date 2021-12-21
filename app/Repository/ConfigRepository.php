<?php

namespace App\Repository;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Validator;

class ConfigRepository
{
    private array $config = [
        'templates' => [],
        'variables' => [],
        'rules' => []
    ];

    private array $rules = [
        'templates' => ['array'],
        'templates.*.name' => ['required', 'string'],
        'templates.*.destination' => ['required', 'string'],
        'variables' => ['array'],
        'rules' => ['array']
    ];

    public function __construct()
    {
        foreach ($this->getCustomConfigFiles() as $file) {
            $this->mergeConfig(Yaml::parseFile($file));
        }

        $this->validate();
    }

    public function mergeConfig($config): void
    {
        if (is_array($config)) {
            if (array_intersect_key(array_flip(array_keys($this->config)), $config)) {
                foreach ($config as $key => $value) {
                    if (array_key_exists($key, $this->config)) {
                        if (is_array($value) && Arr::isAssoc($value)) {
                            $this->config[$key] = array_replace_recursive($this->config[$key] ?? [], $value);
                        } elseif (is_array($value)) {
                            $this->config[$key] = array_merge($this->config[$key] ?? [], $value);
                        } else {
                            $this->config[$key] = $value;
                        }
                    }
                }
            } else {
                $this->config['variables'] = array_replace_recursive($this->config['variables'] ?? [], $config);
            }
        }
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

    public function getCustomConfigFiles(): array
    {
        $files = [];

        foreach (glob($this->getPath("./*.{yml,yaml}"), GLOB_BRACE) as $file) {
            $files[] = $file;
        }

        return $files;
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
