<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\DonationsController;
use App\Controllers\PaymentsController;
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
    $payments = new PaymentsController();

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
    $router->get('/donations/causes', fn ($req) => $donations->causes($req));
    $router->get('/donations', fn ($req) => $donations->index($req));
    $router->post('/donations', fn ($req) => $donations->create($req));
    $router->get('/donations/{id}', fn ($req, $params) => $donations->show($req, $params));
    $router->put('/donations/{id}', fn ($req, $params) => $donations->update($req, $params));
    $router->get('/donations/{id}/receipt', fn ($req, $params) => $donations->receipt($req, $params));
    $router->get('/payments/razorpay/config', fn ($req) => $payments->config($req));
    $router->post('/payments/razorpay/order', fn ($req) => $payments->createOrder($req));
    $router->post('/payments/razorpay/verify', fn ($req) => $payments->verify($req));
    $router->post('/webhooks/razorpay', fn ($req) => $payments->webhook($req));

    $router->dispatch($request);
} catch (Throwable $e) {
    $message = config('APP_ENV') === 'production'
        ? 'Internal server error'
        : $e->getMessage();
    Response::error($message, 500);
}
