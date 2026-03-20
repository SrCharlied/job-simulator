<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/Resource.php';

class ResourceController
{
    private Resource $resource;

    private array $rules = [
        'campo1' => 'string',
        'campo2' => 'string',
        'campo3' => 'string',
        'campo4' => 'integer',
        'campo5' => 'float',
        'campo6' => 'boolean',
    ];

    public function __construct(?Resource $resource = null)
    {
        $this->resource = $resource ?? new Resource();
    }

    public function handle(string $method, ?int $id = null): void
    {
        try {
            switch ($method) {
                case 'GET':
                    $id === null ? $this->index() : $this->show($id);
                    return;

                case 'POST':
                    $this->store($this->decodeJsonBody());
                    return;

                case 'PUT':
                    if ($id === null) {
                        $this->respond(['error' => 'Resource id is required.'], 400);
                        return;
                    }

                    $this->update($id, $this->decodeJsonBody());
                    return;

                case 'PATCH':
                    if ($id === null) {
                        $this->respond(['error' => 'Resource id is required.'], 400);
                        return;
                    }

                    $this->patch($id, $this->decodeJsonBody());
                    return;

                case 'DELETE':
                    if ($id === null) {
                        $this->respond(['error' => 'Resource id is required.'], 400);
                        return;
                    }

                    $this->destroy($id);
                    return;

                default:
                    $this->respond(['error' => 'Method not allowed.'], 405);
            }
        } catch (InvalidArgumentException $exception) {
            $this->respond(['error' => $exception->getMessage()], 400);
        } catch (RuntimeException $exception) {
            $this->respond(['error' => $exception->getMessage()], 422);
        } catch (PDOException) {
            $this->respond(['error' => 'Database operation failed.'], 500);
        }
    }

    public function index(): void
    {
        $this->respond($this->resource->all(), 200);
    }

    public function show(int $id): void
    {
        $resource = $this->resource->find($id);

        if ($resource === null) {
            $this->respond(['error' => 'Resource not found.'], 404);
            return;
        }

        $this->respond($resource, 200);
    }

    public function store(array $input): void
    {
        $data = $this->validate($input, false);
        $resource = $this->resource->create($data);

        $this->respond($resource, 201);
    }

    public function update(int $id, array $input): void
    {
        $data = $this->validate($input, false);
        $resource = $this->resource->update($id, $data);

        if ($resource === null) {
            $this->respond(['error' => 'Resource not found.'], 404);
            return;
        }

        $this->respond($resource, 200);
    }

    public function patch(int $id, array $input): void
    {
        $data = $this->validate($input, true);
        $resource = $this->resource->patch($id, $data);

        if ($resource === null) {
            $this->respond(['error' => 'Resource not found.'], 404);
            return;
        }

        $this->respond($resource, 200);
    }

    public function destroy(int $id): void
    {
        $deleted = $this->resource->delete($id);

        if (!$deleted) {
            $this->respond(['error' => 'Resource not found.'], 404);
            return;
        }

        $this->respond(['message' => 'Resource deleted successfully.'], 200);
    }

    private function decodeJsonBody(): array
    {
        $rawBody = file_get_contents('php://input');

        if ($rawBody === false || trim($rawBody) === '') {
            throw new InvalidArgumentException('Request body must be valid JSON.');
        }

        $data = json_decode($rawBody, true);

        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Request body must be valid JSON.');
        }

        return $data;
    }

    private function validate(array $input, bool $partial): array
    {
        if (!$partial) {
            $missing = array_diff(array_keys($this->rules), array_keys($input));

            if ($missing !== []) {
                throw new RuntimeException(
                    'Missing required fields: ' . implode(', ', $missing)
                );
            }
        }

        $validated = [];

        foreach ($input as $field => $value) {
            if (!array_key_exists($field, $this->rules)) {
                throw new RuntimeException("Unknown field: {$field}");
            }

            $validated[$field] = $this->validateField($field, $value);
        }

        if ($partial && $validated === []) {
            throw new RuntimeException('At least one field is required.');
        }

        return $validated;
    }

    private function validateField(string $field, mixed $value): mixed
    {
        if ($value === null) {
            throw new RuntimeException("Field {$field} is required.");
        }

        return match ($this->rules[$field]) {
            'string' => $this->validateString($field, $value),
            'integer' => $this->validateInteger($field, $value),
            'float' => $this->validateFloat($field, $value),
            'boolean' => $this->validateBoolean($field, $value),
            default => throw new RuntimeException("Unsupported field rule for {$field}."),
        };
    }

    private function validateString(string $field, mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException("Field {$field} must be a non-empty string.");
        }

        return trim($value);
    }

    private function validateInteger(string $field, mixed $value): int
    {
        if (!is_int($value)) {
            throw new RuntimeException("Field {$field} must be an integer.");
        }

        return $value;
    }

    private function validateFloat(string $field, mixed $value): float
    {
        if (!is_float($value)) {
            throw new RuntimeException("Field {$field} must be a float.");
        }

        return $value;
    }

    private function validateBoolean(string $field, mixed $value): bool
    {
        if (!is_bool($value)) {
            throw new RuntimeException("Field {$field} must be a boolean.");
        }

        return $value;
    }

    private function respond(array $payload, int $statusCode): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
