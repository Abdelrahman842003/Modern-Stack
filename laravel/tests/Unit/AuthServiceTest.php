<?php

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

describe('AuthService', function () {
    beforeEach(function () {
        $this->authService = new AuthService();
    });

    describe('register', function () {
        test('creates new user with hashed password', function () {
            $userData = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'password123',
            ];

            $result = $this->authService->register($userData);

            expect($result)->toHaveKeys(['user', 'token'])
                ->and($result['user'])->toBeInstanceOf(User::class)
                ->and($result['user']->name)->toBe('Test User')
                ->and($result['user']->email)->toBe('test@example.com')
                ->and(Hash::check('password123', $result['user']->password))->toBeTrue()
                ->and($result['token'])->toBeString();
        });

        test('generates authentication token for new user', function () {
            $userData = [
                'name' => 'Token User',
                'email' => 'token@example.com',
                'password' => 'password123',
            ];

            $result = $this->authService->register($userData);

            expect($result['token'])->not->toBeEmpty()
                ->and($result['user']->tokens()->count())->toBe(1);
        });
    });

    describe('login', function () {
        test('authenticates user with valid credentials', function () {
            $user = User::factory()->create([
                'email' => 'login@example.com',
                'password' => Hash::make('password123'),
            ]);

            $result = $this->authService->login('login@example.com', 'password123');

            expect($result)->toHaveKeys(['user', 'token'])
                ->and($result['user']->id)->toBe($user->id)
                ->and($result['token'])->toBeString();
        });

        test('revokes previous tokens on new login', function () {
            $user = User::factory()->create([
                'email' => 'revoke@example.com',
                'password' => Hash::make('password123'),
            ]);

            // Create some old tokens
            $user->createToken('old-token-1');
            $user->createToken('old-token-2');

            expect($user->tokens()->count())->toBe(2);

            $this->authService->login('revoke@example.com', 'password123');

            // Old tokens should be revoked, only new token should exist
            expect($user->fresh()->tokens()->count())->toBe(1);
        });

        test('throws validation exception for invalid email', function () {
            $this->authService->login('nonexistent@example.com', 'password123');
        })->throws(ValidationException::class, 'The provided credentials are incorrect.');

        test('throws validation exception for wrong password', function () {
            User::factory()->create([
                'email' => 'wrong@example.com',
                'password' => Hash::make('correctpassword'),
            ]);

            $this->authService->login('wrong@example.com', 'wrongpassword');
        })->throws(ValidationException::class);
    });

    describe('logout', function () {
        test('revokes current access token', function () {
            $user = User::factory()->create();
            $token = $user->createToken('auth-token');
            $tokenId = $token->accessToken->id;

            expect($user->tokens()->count())->toBe(1);

            $this->authService->logout($user, $tokenId);

            expect($user->fresh()->tokens()->count())->toBe(0);
        });

        test('does not affect other user tokens when using specific token id', function () {
            $user = User::factory()->create();
            $token1 = $user->createToken('token-1');
            $token2 = $user->createToken('token-2');

            expect($user->tokens()->count())->toBe(2);

            $this->authService->logout($user, $token1->accessToken->id);

            $user = $user->fresh();
            expect($user->tokens()->count())->toBe(1)
                ->and($user->tokens()->first()->id)->toBe($token2->accessToken->id);
        });
    });
});
