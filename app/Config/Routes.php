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
$routes->post('pesanan', 'PublicController::ajukanPesanan');

// Customer Auth
$routes->get('login', 'Customer\AuthController::login', ['namespace' => 'App\Controllers']);
$routes->post('login', 'Customer\AuthController::attempt', ['namespace' => 'App\Controllers']);
$routes->get('register', 'Customer\AuthController::register', ['namespace' => 'App\Controllers']);
$routes->post('register', 'Customer\AuthController::store', ['namespace' => 'App\Controllers']);
$routes->post('logout', 'Customer\AuthController::logout', ['namespace' => 'App\Controllers']);

// Area akun pelanggan (butuh login)
$routes->group('akun', ['namespace' => 'App\Controllers', 'filter' => 'customerauth'], static function (RouteCollection $routes) {
    $routes->get('/', 'Customer\AkunController::index');
    $routes->get('profil', 'Customer\AkunController::profil');
    $routes->post('profil', 'Customer\AkunController::updateProfil');
    $routes->post('password', 'Customer\AkunController::updatePassword');
    $routes->get('pesanan', 'Customer\AkunController::pesanan');
    $routes->get('pesanan/(:num)/ktp', 'Customer\AkunController::ktp/$1');
    $routes->post('pesanan/(:num)/bukti', 'Customer\AkunController::uploadBuktiCash/$1');
    $routes->post('pesanan/(:num)/bukti-dp', 'Customer\AkunController::uploadBuktiDP/$1');
    $routes->get('pesanan/(:num)', 'Customer\AkunController::pesananDetail/$1');
    $routes->get('kredit/(:num)', 'Customer\AkunController::kreditDetail/$1');
    $routes->post('kredit/(:num)/bukti/(:num)', 'Customer\AkunController::uploadBuktiAngsuran/$1/$2');
    $routes->get('bukti/(:num)', 'Customer\AkunController::bukti/$1');
});

$routes->group('api', ['namespace' => 'App\Controllers\Api'], static function (RouteCollection $routes) {
    $routes->match(['get', 'post'], 'kredit/preview', 'KreditApiController::preview');
    $routes->get('produk/(:num)/simulasi', 'KreditApiController::produkSimulasi/$1');
    $routes->get('referensi-harga-emas', 'KreditApiController::referensiHarga');
});

$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], static function (RouteCollection $routes) {
    $routes->get('/', 'DashboardController::index', ['filter' => 'adminauth']);
    $routes->get('login', 'AuthController::login'); // redirect ke /login (login disatukan)
    $routes->post('logout', 'AuthController::logout', ['filter' => 'adminauth']);

    $routes->group('', ['filter' => 'adminauth'], static function (RouteCollection $routes) {
        $routes->get('dashboard', 'DashboardController::index');

        $routes->get('pengajuan', 'PengajuanController::index');
        $routes->get('pengajuan/(:num)', 'PengajuanController::show/$1');
        $routes->post('pengajuan/(:num)/status', 'PengajuanController::updateStatus/$1');
        $routes->post('pengajuan/(:num)/verifikasi', 'PengajuanController::verifikasi/$1');
        $routes->post('pengajuan/(:num)/tolak', 'PengajuanController::tolak/$1');
        $routes->post('pengajuan/(:num)/batalkan', 'PengajuanController::batalkan/$1');
        // UPDATED: WA manual route dihapus — notifikasi hanya via email
        $routes->get('pengajuan/(:num)/ktp', 'PengajuanController::ktp/$1');

        $routes->get('produk', 'ProdukController::index');
        $routes->get('produk/create', 'ProdukController::create');
        $routes->post('produk', 'ProdukController::store');
        $routes->get('produk/(:num)/edit', 'ProdukController::edit/$1');
        $routes->post('produk/(:num)', 'ProdukController::update/$1');
        $routes->post('produk/(:num)/delete', 'ProdukController::delete/$1');

        $routes->get('pelanggan', 'PelangganController::index');

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
        // UPDATED: WA manual routes dihapus — notifikasi hanya via email

        $routes->get('pembayaran', 'PembayaranController::index');
        $routes->post('pembayaran/(:num)/verifikasi', 'PembayaranController::verifikasi/$1');
        $routes->post('pembayaran/(:num)/tolak', 'PembayaranController::tolak/$1');
        $routes->get('pembayaran/(:num)/bukti', 'PembayaranController::bukti/$1');
        // UPDATED: WA payment route dihapus

        $routes->get('laporan/kredit', 'LaporanController::kredit');
        $routes->get('laporan/pembayaran', 'LaporanController::pembayaran');
        $routes->get('laporan/piutang', 'LaporanController::piutang');
    });
});
