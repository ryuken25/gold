<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class MigrateFresh extends BaseCommand
{
    protected $group = 'Database';

    protected $name = 'migrate:fresh';

    protected $description = 'Drops all existing tables, reruns all migrations, and optionally seeds the database.';

    protected $usage = 'migrate:fresh [options]';

    protected $options = [
        '--seed' => 'Run DatabaseSeeder after refreshing migrations.',
        '-g'     => 'Set database group.',
        '-n'     => 'Set migration namespace.',
        '--all'  => 'Run migrations for all namespaces.',
        '-f'     => 'Force command in production environments.',
    ];

    public function run(array $params)
    {
        $env = ENVIRONMENT;

        if ($env === 'production' && !CLI::getOption('f')) {
            CLI::error('Refusing to run migrate:fresh in production. Use -f to force.');
            return;
        }

        $dbGroup = CLI::getOption('g') ?? config('Database')->defaultGroup;
        $db = Database::connect($dbGroup);

        // 1. Disable FK checks
        CLI::write('Disabling foreign key checks...', 'yellow');
        $db->query('SET FOREIGN_KEY_CHECKS = 0');

        // 2. Drop all tables (including migrations)
        $tables = $db->listTables();
        $dropped = 0;
        foreach ($tables as $table) {
            $db->query("DROP TABLE IF EXISTS `{$table}`");
            $dropped++;
        }
        CLI::write("Dropped {$dropped} table(s).", 'green');

        // 3. Re-enable FK checks
        CLI::write('Re-enabling foreign key checks...', 'yellow');
        $db->query('SET FOREIGN_KEY_CHECKS = 1');

        // 4. Create migrations table if it doesn't exist (CI4 needs it before migrate)
        $hasMigrations = $db->query("SHOW TABLES LIKE 'migrations'")->getRowArray();
        if (!$hasMigrations) {
            $db->query("CREATE TABLE IF NOT EXISTS `migrations` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `version` varchar(255) NOT NULL,
                `class` varchar(255) NOT NULL,
                `group` varchar(255) NOT NULL,
                `namespace` varchar(255) NOT NULL,
                `time` int(11) NOT NULL,
                `batch` int(11) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            CLI::write('Created migrations table.', 'green');
        }

        // 5. Run migrations
        CLI::write('Running migrations...', 'yellow');
        $this->call('migrate', $params);

        // 5. Run seeder if --seed flag
        if (array_key_exists('seed', $params) || CLI::getOption('seed')) {
            CLI::write('Running seeders...', 'yellow');
            $this->call('db:seed', ['DatabaseSeeder']);
        }

        CLI::write('Done. Database has been refreshed.', 'green');
    }
}
