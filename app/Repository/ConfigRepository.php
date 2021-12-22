<?php

namespace App\Repository;

use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Validator;

class ConfigRepository
{
    public string $file;

    private array $config = [];

    private array $defaults = [
        'local' => [
            'path' => '',
            'url' => '',
            'database' => [
                'host' => '',
                'name' => '',
                'user' => '',
                'password' => '',
            ],
            'wordpress' => [
                'cli_path' => 'wp'
            ]
        ],
        'remote' => [
            'path' => '',
            'host' => '',
            'port' => 22,
            'user' => 'root',
            'url' => '',
            'wordpress' => [
                'cli_path' => 'wp'
            ]
        ],
        'pull' => [
            'exclude' => []
        ]
    ];

    public function __construct(string $configFile, bool $mergeDefaults = false)
    {
        $this->file = realpath($configFile) ?: $configFile;

        if (!file_exists($this->file)) {
            abort(1, "Could not load configuration file [{$this->file}]");
        }

        $this->config = Yaml::parseFile($this->file);

        if ($mergeDefaults) {
            $this->config = array_replace_recursive(
                $this->defaults,
                $this->config
            );

            $this->config['local']['path'] = getcwd() . '/public';
        }
    }

    public function get(?string $key = null, $default = null)
    {
        $config = $this->config;

        if ($key) {
            return Arr::get($config, $key, $default);
        }

        return $config;
    }

    public function getPath(string $path = ''): string
    {
        return str_replace('./', dirname($this->file) . '/', $path);
    }

    public function validate(array $rules, string $key = null)
    {
        $validator = Validator::make(
            $this->get($key, []),
            $rules,
            config('validation')
        );

        foreach ($validator->errors()->all() as $error) {
            $validationErrors[] = $error;
        }

        if (count($validationErrors ?? [])) {
            abort(1, implode(PHP_EOL, $validationErrors));
        }
    }

    public function merge($config): void
    {
        if (is_array($config)) {
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
        }
    }
}
