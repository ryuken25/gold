<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class AuditRoutes extends BaseCommand
{
    protected $group = 'Audit';

    protected $name = 'audit:routes';

    protected $description = 'Audit route integrity — checks expected routes exist.';

    protected $usage = 'audit:routes';

    public function run(array $params)
    {
        $errors   = [];
        $warnings = [];

        CLI::write('Route Audit', 'cyan');
        CLI::write(str_repeat('=', 60), 'cyan');

        // Read Routes.php source to find route definitions
        $routesFile = APPPATH . 'Config/Routes.php';
        $source = file_get_contents($routesFile);
        if ($source === false) {
            CLI::error('Cannot read Routes.php');
            return;
        }

        // Extract all route patterns
        $routes = [];
        // Match: $routes->get('path', ...), $routes->post('path', ...), etc.
        preg_match_all('/\$routes->(get|post|put|delete|match)\s*\(\s*[\'"]([^\'"]+)[\'"]/', $source, $matches);
        foreach ($matches[1] as $i => $method) {
            $route = $matches[2][$i];
            $routes[strtoupper($method)][$route] = true;
        }

        CLI::write("  Found " . count($routes) . " HTTP methods in Routes.php", 'cyan');
        $totalRoutes = array_sum(array_map('count', $routes));
        CLI::write("  Total route definitions: {$totalRoutes}", 'cyan');
        CLI::newLine();

        // Check expected GET routes
        $expectedGet = [
            '/'                 => ['/'],
            'login'             => ['login'],
            'register'          => ['register'],
            'akun'              => ['akun', '/'],
            'admin'             => ['admin', '/'],
            'admin/dashboard'   => ['dashboard'],
            'admin/pengajuan'   => ['pengajuan'],
            'admin/pembayaran'  => ['pembayaran'],
            'admin/kredit'      => ['kredit'],
            'admin/produk'      => ['produk'],
            'admin/nasabah'     => ['nasabah'],
        ];
        foreach ($expectedGet as $label => $patterns) {
            $found = false;
            foreach ($patterns as $pattern) {
                if (!empty($routes['GET'][$pattern])) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                CLI::write("  ✓ GET /{$label}", 'green');
            } else {
                $warnings[] = "GET /{$label} — not found in routes";
            }
        }

        // Check expected POST routes
        // Note: admin/logout is defined as 'logout' inside group('admin'), so the raw pattern is just 'logout'
        $expectedPost = [
            'login'        => ['login'],
            'register'     => ['register'],
            'logout'       => ['logout'],
            'admin/logout' => ['logout'], // inside group('admin')
        ];
        foreach ($expectedPost as $label => $patterns) {
            $found = false;
            foreach ($patterns as $pattern) {
                if (!empty($routes['POST'][$pattern])) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                CLI::write("  ✓ POST /{$label}", 'green');
            } else {
                $errors[] = "POST /{$label} — MISSING (critical)";
            }
        }

        // Check development-only routes
        if (ENVIRONMENT !== 'development') {
            if (!empty($routes['GET']['admin/test-email'])) {
                $warnings[] = "Development route GET /admin/test-email accessible in " . ENVIRONMENT;
            }
        }

        if (!empty($warnings)) {
            CLI::write("  Warnings: " . count($warnings), 'yellow');
            foreach ($warnings as $w) {
                CLI::write("    ⚠ {$w}", 'yellow');
            }
        }
        if (!empty($errors)) {
            CLI::write("  Errors: " . count($errors), 'red');
            foreach ($errors as $e) {
                CLI::write("    ✗ {$e}", 'red');
            }
        }

        CLI::newLine();

        if (!empty($errors)) {
            CLI::error('Route audit FAILED — ' . count($errors) . ' error(s).');
            return;
        }

        CLI::write('Route audit PASSED.', 'green');
    }
}
