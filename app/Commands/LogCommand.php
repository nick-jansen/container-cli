<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class LogCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'log {message} {--type=}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Log a message';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        switch ($this->option('type')) {
            case 'error':
            case 'failure':
                $this->error($this->argument('message'));
                break;
            case 'warn':
            case 'warning':
                $this->warn($this->argument('message'));
                break;
            case 'info':
            case 'success':
                $this->info($this->argument('message'));
                break;
            default:
                $this->line($this->argument('message'));
                break;
        }
    }
}
