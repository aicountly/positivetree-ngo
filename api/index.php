<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'PHP 8.1 or newer is required']);
    exit;
}

require __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DocumentSettingsController;
use App\Controllers\DonationsController;
use App\Controllers\PublicReceiptController;
use App\Controllers\SetupController;
use App\Controllers\UsersController;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Router;

$corsOrigin = config('CORS_ORIGIN');
if ($corsOrigin) {
    header('Access-Control-Allow-Origin: ' . $corsOrigin);
} elseif (config('APP_ENV') !== 'production') {
    header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    Database::connection();

    $request = Request::fromGlobals();
    $path = $request->path;

    if (str_starts_with($path, '/api')) {
        $path = substr($path, 4) ?: '/';
    }

    $request = new Request(
        $request->method,
        $path,
        $request->query,
        $request->body,
        $request->headers,
        $request->rawBody,
    );

    $setup = new SetupController();
    $auth = new AuthController();
    $users = new UsersController();
    $donations = new DonationsController();
    $documentSettings = new DocumentSettingsController();
    $publicReceipt = new PublicReceiptController();

    $router = new Router();
    $router->get('/setup/status', fn ($req) => $setup->status($req));
    $router->post('/setup', fn ($req) => $setup->create($req));
    $router->post('/auth/login', fn ($req) => $auth->login($req));
    $router->get('/auth/me', fn ($req) => $auth->me($req));
    $router->get('/dashboard', fn ($req) => $auth->dashboard($req));
    $router->get('/users', fn ($req) => $users->index($req));
    $router->get('/users/{id}', fn ($req, $params) => $users->show($req, $params));
    $router->post('/users', fn ($req) => $users->create($req));
    $router->put('/users/{id}', fn ($req, $params) => $users->update($req, $params));
    $router->patch('/users/{id}', fn ($req, $params) => $users->update($req, $params));
    $router->get('/settings/documents', fn ($req) => $documentSettings->show($req));
    $router->put('/settings/documents', fn ($req) => $documentSettings->update($req));
    $router->post('/settings/documents/logo', fn ($req) => $documentSettings->uploadLogo($req));
    $router->post('/settings/documents/signature', fn ($req) => $documentSettings->uploadSignature($req));
    $router->get('/settings/documents/signature', fn ($req) => $documentSettings->signatureImage($req));
    $router->get('/settings/documents/preview/receipt', fn ($req) => $documentSettings->previewReceipt($req));
    $router->get('/settings/documents/preview/certificate', fn ($req) => $documentSettings->previewCertificate($req));
    $router->get('/public/receipt/{token}', fn ($req, $params) => $publicReceipt->show($req, $params));
    $router->get('/donations/causes', fn ($req) => $donations->causes($req));
    $router->get('/donations', fn ($req) => $donations->index($req));
    $router->post('/donations', fn ($req) => $donations->create($req));
    $router->get('/donations/{id}', fn ($req, $params) => $donations->show($req, $params));
    $router->put('/donations/{id}', fn ($req, $params) => $donations->update($req, $params));
    $router->get('/donations/{id}/receipt', fn ($req, $params) => $donations->receipt($req, $params));
    $router->get('/donations/{id}/certificate', fn ($req, $params) => $donations->certificate($req, $params));
    $router->post('/donations/{id}/approve-certificate', fn ($req, $params) => $donations->approveCertificate($req, $params));
    $router->post('/donations/{id}/revoke-certificate', fn ($req, $params) => $donations->revokeCertificate($req, $params));
    // Online donations (Razorpay) are handled by https://sispl.org/api — see public_html/js/donate-checkout.js.
    // The admin API only manages offline donations (cash, cheque, UPI, bank transfer) recorded by staff.

    $router->dispatch($request);
} catch (Throwable $e) {
    $message = config('APP_ENV') === 'production'
        ? 'Internal server error'
        : $e->getMessage();
    error_log('[donation-api] ' . $e->getMessage());
    Response::error($message, 500);
}
