<?php
function sendJson(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function successResponse(array $data = [], string $message = 'Thành công', int $statusCode = 200): void
{
    sendJson($statusCode, [
        'success' => true,
        'message' => $message,
        'data' => $data,
    ]);
}

function errorResponse(string $message, int $statusCode = 400, array $data = []): void
{
    sendJson($statusCode, [
        'success' => false,
        'message' => $message,
        'data' => $data,
    ]);
}

function readJsonBody(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new InvalidArgumentException('Dữ liệu JSON không hợp lệ');
    }

    return $decoded;
}
