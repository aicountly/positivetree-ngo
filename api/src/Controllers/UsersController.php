<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;

class UsersController
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly Auth $auth = new Auth(),
    ) {
    }

    public function index(Request $request): void
    {
        $current = $this->auth->requireUser($request);
        $this->auth->requireSuperadmin($current);

        $items = array_map(
            fn (array $user) => $this->auth->publicUser($user),
            $this->users->all()
        );

        Response::json(['items' => $items]);
    }

    public function create(Request $request): void
    {
        $current = $this->auth->requireUser($request);
        $this->auth->requireSuperadmin($current);

        $body = $request->body;
        $email = trim((string) ($body['email'] ?? ''));
        $name = trim((string) ($body['name'] ?? ''));
        $password = (string) ($body['password'] ?? '');
        $role = (string) ($body['role'] ?? 'admin');

        if ($email === '' || $name === '' || $password === '') {
            Response::error('Name, email, and password are required', 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email address', 422);
            return;
        }

        if (!in_array($role, Auth::ROLES, true)) {
            Response::error('Invalid role', 422);
            return;
        }

        $passwordError = Auth::validatePassword($password);
        if ($passwordError !== null) {
            Response::error($passwordError, 422);
            return;
        }

        if ($this->users->findByEmail($email) !== null) {
            Response::error('Email already in use', 409);
            return;
        }

        $user = $this->users->create([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'role' => $role,
            'is_active' => $body['is_active'] ?? true,
        ]);

        Response::json(['user' => $this->auth->publicUser($user)], 201);
    }

    public function update(Request $request, array $params): void
    {
        $current = $this->auth->requireUser($request);
        $this->auth->requireSuperadmin($current);

        $id = (int) ($params['id'] ?? 0);
        $existing = $this->users->findById($id);
        if ($existing === null) {
            Response::error('User not found', 404);
            return;
        }

        $body = $request->body;

        if (isset($body['role']) && !in_array($body['role'], Auth::ROLES, true)) {
            Response::error('Invalid role', 422);
            return;
        }

        if (!empty($body['password'])) {
            $passwordError = Auth::validatePassword((string) $body['password']);
            if ($passwordError !== null) {
                Response::error($passwordError, 422);
                return;
            }
        }

        if (isset($body['email'])) {
            $email = trim((string) $body['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::error('Invalid email address', 422);
                return;
            }
            $other = $this->users->findByEmail($email);
            if ($other !== null && (int) $other['id'] !== $id) {
                Response::error('Email already in use', 409);
                return;
            }
        }

        if ($existing['role'] === 'superadmin' && isset($body['is_active']) && !$body['is_active']) {
            Response::error('Cannot deactivate superadmin account', 422);
            return;
        }

        $updated = $this->users->update($id, $body);
        Response::json(['user' => $this->auth->publicUser($updated)]);
    }
}
