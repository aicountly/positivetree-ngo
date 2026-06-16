<?php

declare(strict_types=1);

namespace App;

use App\Http\Request;
use App\Http\Response;
use App\Repositories\UserRepository;

class Auth
{
    public const ROLES = ['superadmin', 'admin', 'viewer'];

    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
    ) {
    }

    public function attemptLogin(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !(bool) $user['is_active']) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $this->publicUser($user);
    }

    public function userFromRequest(Request $request): ?array
    {
        $token = $request->bearerToken();
        if ($token === null) {
            return null;
        }

        $secret = config('JWT_SECRET');
        if ($secret === null) {
            return null;
        }

        $payload = Jwt::decode($token, $secret);
        if ($payload === null || !isset($payload['sub'])) {
            return null;
        }

        $user = $this->users->findById((int) $payload['sub']);
        if ($user === null || !(bool) $user['is_active']) {
            return null;
        }

        return $this->publicUser($user);
    }

    public function issueToken(array $user): string
    {
        $secret = config('JWT_SECRET');
        if ($secret === null) {
            throw new \RuntimeException('JWT_SECRET is not configured');
        }

        return Jwt::encode([
            'sub' => (int) $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
        ], $secret);
    }

    public function requireUser(Request $request, ?array $roles = null): array
    {
        $user = $this->userFromRequest($request);
        if ($user === null) {
            Response::error('Unauthorized', 401);
            exit;
        }

        if ($roles !== null && !in_array($user['role'], $roles, true)) {
            Response::error('Forbidden', 403);
            exit;
        }

        return $user;
    }

    public function requireWriteAccess(array $user): void
    {
        if ($user['role'] === 'viewer') {
            Response::error('Forbidden', 403);
            exit;
        }
    }

    public function requireSuperadmin(array $user): void
    {
        if ($user['role'] !== 'superadmin') {
            Response::error('Forbidden', 403);
            exit;
        }
    }

    public function publicUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'is_active' => (bool) $user['is_active'],
            'created_at' => $user['created_at'],
            'updated_at' => $user['updated_at'],
        ];
    }

    public static function validatePassword(string $password): ?string
    {
        if (strlen($password) < 8) {
            return 'Password must be at least 8 characters';
        }

        return null;
    }
}
