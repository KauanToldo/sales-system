<?php

namespace App\Controllers;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    public function __construct(private AuthService $authService) {}

    private function errorResponse(
        Response $response,
        string $message,
        string $code,
        int $status,
        array $details = []
    ): Response {
        $response->getBody()->write(json_encode([
            'error' => $message,
            'code' => $code,
            'details' => $details,
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function register(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            $result = $this->authService->register($data);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $response,
                $e->getMessage(),
                'validation_error',
                422
            );
        } catch (\DomainException $e) {
            return $this->errorResponse(
                $response,
                $e->getMessage(),
                'email_already_exists',
                409
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                $e->getMessage(),
                'auth_error',
                400
            );
        }
    }

    public function login(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            $result = $this->authService->login($data);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);

        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse(
                $response,
                $e->getMessage(),
                'validation_error',
                422
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $response,
                $e->getMessage(),
                'invalid_credentials',
                401
            );
        }
    }

    public function me(Request $request, Response $response): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->errorResponse(
                $response,
                'Unauthorized',
                'unauthorized',
                401
            );
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $user = $this->authService->validateToken($token);
        } catch (\Throwable $e) {
            return $this->errorResponse(
                $response,
                'Unauthorized',
                'unauthorized',
                401
            );
        }

        $response->getBody()->write(json_encode([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);
    }
}