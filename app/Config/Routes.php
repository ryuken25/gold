<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setAutoRoute(false);

$routes->get('/', 'PublicController::index');
$routes->get('katalog', 'PublicController::katalog');
$routes->get('produk/(:segment)', 'PublicController::detail/$1');
$routes->get('simulasi', 'PublicController::simulasi');
$routes->post('wa/pengajuan', 'PublicController::waPengajuan');

// Customer Auth
$routes->get('login', 'Customer\AuthController::login', ['namespace' => 'App\Controllers']);
$routes->post('login', 'Customer\AuthController::attempt', ['namespace' => 'App\Controllers']);
$routes->get('register', 'Customer\AuthController::register', ['namespace' => 'App\Controllers']);
$routes->post('register', 'Customer\AuthController::store', ['namespace' => 'App\Controllers']);
$routes->post('logout', 'Customer\AuthController::logout', ['namespace' => 'App\Controllers']);

// Area akun pelanggan (butuh login)
$routes->group('akun', ['namespace' => 'App\Controllers', 'filter' => 'customerauth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Customer\AkunController::index');
});

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function (RouteCollection $routes) {
    $routes->match(['get', 'post'], 'kredit/preview', 'KreditApiController::preview');
    $routes->get('produk/(:num)/simulasi', 'KreditApiController::produkSimulasi/$1');
    $routes->get('referensi-harga-emas', 'KreditApiController::referensiHarga');
});

$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes) {
    $routes->get('/', 'DashboardController::index', ['filter' => 'adminauth']);
    $routes->get('login', 'AuthController::login');
    $routes->post('login', 'AuthController::attempt');
    $routes->post('logout', 'AuthController::logout', ['filter' => 'adminauth']);

    $routes->group('', ['filter' => 'adminauth'], static function (RouteCollection $routes) {
        $routes->get('dashboard', 'DashboardController::index');

        $routes->get('produk', 'ProdukController::index');
        $routes->get('produk/create', 'ProdukController::create');
        $routes->post('produk', 'ProdukController::store');
        $routes->get('produk/(:num)/edit', 'ProdukController::edit/$1');
        $routes->post('produk/(:num)', 'ProdukController::update/$1');
        $routes->post('produk/(:num)/delete', 'ProdukController::delete/$1');

        $routes->get('nasabah', 'NasabahController::index');
        $routes->get('nasabah/create', 'NasabahController::create');
        $routes->post('nasabah', 'NasabahController::store');
        $routes->get('nasabah/(:num)/edit', 'NasabahController::edit/$1');
        $routes->post('nasabah/(:num)', 'NasabahController::update/$1');
        $routes->post('nasabah/(:num)/delete', 'NasabahController::delete/$1');
        $routes->get('nasabah/(:num)/kartu-piutang', 'NasabahController::kartuPiutang/$1');

        $routes->get('kredit', 'KreditController::index');
        $routes->get('kredit/create', 'KreditController::create');
        $routes->post('kredit', 'KreditController::store');
        $routes->get('kredit/(:num)', 'KreditController::show/$1');
        $routes->post('kredit/(:num)/batalkan', 'KreditController::cancel/$1');
        $routes->get('kredit/(:num)/wa-info', 'KreditController::waInfo/$1');
        $routes->get('kredit/(:num)/wa-lunas', 'KreditController::waLunas/$1');
        $routes->get('kredit/(:num)/wa-pengingat/(:num)', 'KreditController::waPengingat/$1/$2');

        $routes->get('pembayaran', 'PembayaranController::index');
        $routes->get('pembayaran/create', 'PembayaranController::create');
        $routes->post('pembayaran', 'PembayaranController::store');
        $routes->get('pembayaran/(:num)/wa-konfirmasi', 'PembayaranController::waKonfirmasi/$1');

        $routes->get('piutang', 'PiutangController::index');

        $routes->get('laporan/kredit', 'LaporanController::kredit');
        $routes->get('laporan/pembayaran', 'LaporanController::pembayaran');
        $routes->get('laporan/piutang', 'LaporanController::piutang');

        $routes->get('whatsapp-logs', 'WhatsAppLogController::index');

        $routes->get('pengaturan', 'PengaturanController::index');
        $routes->post('pengaturan', 'PengaturanController::update');
    });
});
