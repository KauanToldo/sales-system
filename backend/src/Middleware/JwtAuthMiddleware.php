<?php

namespace App\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuthMiddleware implements MiddlewareInterface
{
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
            'error' => 'Unauthorized'
        ]));

        return $response->withHeader('Content-Type', 'application/json')
                        ->withStatus(401);
    }
}