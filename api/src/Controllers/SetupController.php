<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;
use App\SetupLock;

class SetupController
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly Auth $auth = new Auth(),
    ) {
    }

    public function status(Request $request): void
    {
        $hasSuperadmin = $this->users->hasSuperadmin();
        if ($hasSuperadmin) {
            SetupLock::markCompleted();
        }

        $setupLocked = SetupLock::exists() && !$hasSuperadmin;

        Response::json([
            'setup_required' => !$hasSuperadmin && !SetupLock::exists(),
            'setup_locked' => $setupLocked,
        ]);
    }

    public function create(Request $request): void
    {
        if ($this->users->hasSuperadmin() || SetupLock::exists()) {
            Response::error('Setup already completed', 409);
            return;
        }

        $body = $request->body;
        $email = trim((string) ($body['email'] ?? ''));
        $name = trim((string) ($body['name'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($email === '' || $name === '' || $password === '') {
            Response::error('Name, email, and password are required', 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address', 422);
            return;
        }

        $passwordError = Auth::validatePassword($password);
        if ($passwordError !== null) {
            Response::error($passwordError, 422);
            return;
        }

        $user = $this->users->create([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        $publicUser = $this->auth->publicUser($user);
        SetupLock::markCompleted();
        Response::json([
            'user' => $publicUser,
            'token' => $this->auth->issueToken($user),
        ], 201);
    }
}
