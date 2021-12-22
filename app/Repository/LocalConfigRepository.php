<?php

namespace App\Repository;

use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Validator;

class LocalConfigRepository
{
    private array $config = [
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

    public function __construct()
    {
        $configFile = (glob(getcwd() . '/{sitepilot,cli}.{yml,yaml}', GLOB_BRACE))[0] ?? null;

        if (!file_exists($configFile)) {
            abort(1, "Could not locate local configuration file.");
        }

        $this->config['local']['path'] = getcwd() . '/public';

        $this->config = array_replace_recursive(
            $this->config,
            Yaml::parseFile($configFile) ? Yaml::parseFile($configFile) : []
        );
    }

    public function get(?string $key = null, $default = null)
    {
        $config = $this->config;

        if ($key) {
            return Arr::get($config, $key, $default);
        }

        return $config;
    }

    public function validate(array $rules)
    {
        $validator = Validator::make(
            $this->get(),
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
}
