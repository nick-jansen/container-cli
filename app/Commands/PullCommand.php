<?php

namespace App\Commands;

use Exception;
use Symfony\Component\Yaml\Yaml;
use App\Repository\ConfigRepository;
use Symfony\Component\Process\Process;
use LaravelZero\Framework\Commands\Command;

class PullCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'pull {--f|file=config.yml : Pull configuration file} {--wp}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Pull remote files';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = new ConfigRepository(
            $this->option('file'),
            true
        );

        $config->validate(array_merge([
            'pull.exclude' => ['array'],
            'remote.path' => ['required', 'string'],
            'remote.host' => ['required', 'string'],
            'remote.port' => ['required', 'numeric'],
            'remote.user' => ['required', 'string'],
            'remote.url' => ['nullable', 'url'],
            'local.path' => ['required', 'string'],
            'local.url' => ['nullable', 'url']
        ], $this->option('wp') ? [
            'remote.wordpress.cli_path' => ['required', 'string'],
            'local.database.host' => ['required', 'string'],
            'local.database.name' => ['required', 'string'],
            'local.database.user' => ['required', 'string'],
            'local.database.password' => ['required', 'string'],
            'local.wordpress.cli_path' => ['required', 'string']
        ] : []));

        $this->newLine();
        $this->info('[- Local -]');
        $this->line(Yaml::dump($config->get('local'), 99, 2));
        $this->info('[- Remote -]');
        $this->line(Yaml::dump($config->get('remote'), 99, 2));
        $this->info('[- Config -]');
        $this->line(Yaml::dump($config->get('pull'), 99, 2));

        $local = (object) $config->get('local');
        $remote = (object) $config->get('remote');
        $config = (object) $config->get('pull');

        if ($this->ask("Are you sure you want to pull the remote files?")) {
            try {
                if ($this->option('wp')) {
                    $this->task("Export remote WordPress database", function () use ($remote) {
                        (new Process(
                            ["ssh", "-t", "-p", $remote->port, "{$remote->user}@{$remote->host}", "cd {$remote->path} ; {$remote->wordpress['cli_path']} db export export.sql --allow-root"]
                        ))->setTimeout(900)->mustRun();
                    });
                }

                $this->task("Pull remote files", function () use ($local, $remote, $config) {
                    $excludes = [];

                    foreach ($config->exclude as $file) {
                        $excludes[] = "--exclude=$file";
                    }

                    (new Process(
                        array_merge(["rsync", "-a", "--delete", "--info=progress2"], $excludes, ["-e", "ssh -p {$remote->port}", "{$remote->user}@{$remote->host}:{$remote->path}/", $local->path])
                    ))->setTimeout(900)->mustRun();
                });

                if ($this->option('wp')) {
                    $this->task("Update WordPress config", function () use ($local) {
                        (new Process([$local->wordpress['cli_path'], 'config', 'set', 'DB_HOST', $local->database['host'], "--path={$local->path}"]))->mustRun();
                        (new Process([$local->wordpress['cli_path'], 'config', 'set', 'DB_NAME', $local->database['name'], "--path={$local->path}"]))->mustRun();
                        (new Process([$local->wordpress['cli_path'], 'config', 'set', 'DB_USER', $local->database['user'], "--path={$local->path}"]))->mustRun();
                        (new Process([$local->wordpress['cli_path'], 'config', 'set', 'DB_PASSWORD', $local->database['password'], "--path={$local->path}"]))->mustRun();
                    });

                    $this->task("Import WordPress database", function () use ($local) {
                        (new Process([$local->wordpress['cli_path'], 'db', 'import', $local->path . '/export.sql', "--path={$local->path}"]))->setTimeout(900)->mustRun();
                    });

                    if ($remote->url && $local->url) {
                        $this->task("Update WordPress url", function () use ($local, $remote) {
                            (new Process([$local->wordpress['cli_path'], 'search-replace', $remote->url, $local->url, "--path={$local->path}"]))->mustRun();
                        });
                    }
                }
            } catch (Exception $e) {
                abort(1, $e->getMessage());
            }
        }
    }
}
