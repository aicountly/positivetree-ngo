<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\DonationRepository;

class AuthController
{
    public function __construct(
        private readonly Auth $auth = new Auth(),
        private readonly DonationRepository $donations = new DonationRepository(),
    ) {
    }

    public function login(Request $request): void
    {
        $email = trim((string) ($request->body['email'] ?? ''));
        $password = (string) ($request->body['password'] ?? '');

        if ($email === '' || $password === '') {
            Response::error('Email and password are required', 422);
            return;
        }

        $user = $this->auth->attemptLogin($email, $password);
        if ($user === null) {
            Response::error('Invalid credentials', 401);
            return;
        }

        Response::json([
            'user' => $user,
            'token' => $this->auth->issueToken($user),
        ]);
    }

    public function me(Request $request): void
    {
        $user = $this->auth->requireUser($request);
        Response::json(['user' => $user]);
    }

    public function dashboard(Request $request): void
    {
        $this->auth->requireUser($request, ['superadmin', 'admin', 'viewer']);
        Response::json($this->donations->dashboardStats());
    }
}
