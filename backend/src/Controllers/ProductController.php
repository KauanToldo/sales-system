<?php

namespace App\Controllers;

use App\Services\ProductService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ProductController
{
    public function __construct(private ProductService $productService) {}

    public function index(Request $request, Response $response): Response
    {
        try {
            $result = $this->productService->list();

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to list products', 'product_list_error', 500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        try {
            $result = $this->productService->findById($id);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to load product', 'product_get_error', 500);
        }
    }

    public function store(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            $result = $this->productService->create($data);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'validation_error', 422);
        } catch (\DomainException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'conflict', 409);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to create product', 'product_create_error', 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            $result = $this->productService->update($id, $data);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'validation_error', 422);
        } catch (\DomainException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'conflict', 409);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to update product', 'product_update_error', 500);
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        try {
            $this->productService->delete($id);

            return $response->withStatus(204);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to delete product', 'product_delete_error', 500);
        }
    }

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
}
