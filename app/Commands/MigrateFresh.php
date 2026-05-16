<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MigrateFresh extends BaseCommand
{
    protected $group = 'Database';

    protected $name = 'migrate:fresh';

    protected $description = 'Drops all existing tables, reruns all migrations, and optionally seeds the database.';

    protected $usage = 'migrate:fresh [options]';

    protected $options = [
        '--seed' => 'Run DatabaseSeeder after refreshing migrations.',
        '-g' => 'Set database group.',
        '-n' => 'Set migration namespace.',
        '--all' => 'Run migrations for all namespaces.',
        '-f' => 'Force command in production environments.',
    ];

    public function run(array $params)
    {
        $this->call('migrate:refresh', $params);

        if (array_key_exists('seed', $params) || CLI::getOption('seed')) {
            $this->call('db:seed', ['DatabaseSeeder']);
        }
    }
}
