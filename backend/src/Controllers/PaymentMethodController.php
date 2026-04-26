<?php

namespace App\Controllers;

use App\Services\PaymentMethodService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PaymentMethodController
{
    public function __construct(private PaymentMethodService $paymentMethodService) {}

    public function index(Request $request, Response $response): Response
    {
        try {
            $result = $this->paymentMethodService->list();

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to list payment methods', 'payment_method_list_error', 500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        try {
            $result = $this->paymentMethodService->findById($id);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to load payment method', 'payment_method_get_error', 500);
        }
    }

    public function store(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            $result = $this->paymentMethodService->create($data);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'validation_error', 422);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to create payment method', 'payment_method_create_error', 500);
        }
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            $result = $this->paymentMethodService->update($id, $data);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'validation_error', 422);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to update payment method', 'payment_method_update_error', 500);
        }
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        try {
            $this->paymentMethodService->delete($id);

            return $response->withStatus(204);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to delete payment method', 'payment_method_delete_error', 500);
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
