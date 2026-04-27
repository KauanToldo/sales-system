<?php

namespace App\Controllers;

use App\Services\SaleService;
use App\Services\SalePdfService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class SaleController
{
    public function __construct(
        private SaleService $saleService,
        private SalePdfService $salePdfService
    ) {}

    public function index(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 50;
        $offset = isset($queryParams['offset']) ? (int) $queryParams['offset'] : 0;

        try {
            $result = $this->saleService->list($limit, $offset);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to list sales', 'sale_list_error', 500);
        }
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        try {
            $result = $this->saleService->findById($id);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to load sale', 'sale_get_error', 500);
        }
    }

    public function store(Request $request, Response $response): Response
    {
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];

        $jwtUser = $request->getAttribute('user');
        $userId = is_object($jwtUser) && isset($jwtUser->sub) ? (int) $jwtUser->sub : 0;

        try {
            $result = $this->saleService->createOpen($data, $userId);

            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'validation_error', 422);
        } catch (\DomainException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'business_rule_error', 422);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'auth_user_error', 401);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to create sale', 'sale_create_error', 500);
        }
    }

    public function addPayment(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);
        $parsedBody = $request->getParsedBody();
        $data = is_array($parsedBody) ? $parsedBody : [];

        try {
            $result = $this->saleService->addPayment($id, $data);
            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'validation_error', 422);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\DomainException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'business_rule_error', 422);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to add payment', 'sale_payment_add_error', 500);
        }
    }

    public function finalize(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        try {
            $result = $this->saleService->finalize($id);
            $response->getBody()->write(json_encode($result));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\DomainException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'business_rule_error', 422);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to finalize sale', 'sale_finalize_error', 500);
        }
    }

    public function pdf(Request $request, Response $response, array $args): Response
    {
        $id = (int) ($args['id'] ?? 0);

        try {
            $pdfBinary = $this->salePdfService->generate($id);
            $response->getBody()->write($pdfBinary);

            return $response
                ->withHeader('Content-Type', 'application/pdf')
                ->withHeader('Content-Disposition', 'inline; filename="sale-' . $id . '.pdf"')
                ->withStatus(200);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'not_found', 404);
        } catch (\DomainException $e) {
            return $this->errorResponse($response, $e->getMessage(), 'business_rule_error', 422);
        } catch (\Throwable $e) {
            return $this->errorResponse($response, 'Unable to generate sale pdf', 'sale_pdf_error', 500);
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

