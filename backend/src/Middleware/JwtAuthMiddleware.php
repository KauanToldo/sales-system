<?php

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Repositories\UserRepository;

class JwtAuthMiddleware implements MiddlewareInterface
{
    public function __construct(private UserRepository $userRepository) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized();
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $secret = $_ENV['JWT_SECRET'];

            $decoded = JWT::decode($token, new Key($secret, 'HS256'));

            $userId = isset($decoded->sub) ? (int) $decoded->sub : 0;
            if ($userId <= 0) {
                return $this->unauthorized();
            }

            $user = $this->userRepository->findById($userId);
            if (!$user) {
                return $this->unauthorized();
            }

            // injeta user no request
            $request = $request->withAttribute('user', $decoded);

            return $handler->handle($request);

        } catch (\Throwable $e) {
            return $this->unauthorized();
        }
    }

    private function unauthorized(): Response
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'code' => 'unauthorized',
            'details' => [],
        ]));

        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(401);
    }
}