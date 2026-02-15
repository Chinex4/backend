<?php
session_start();

class TaskController
{
    private array $gateways = [];
    private $pdo;
    private $Database;

    public function __construct()
    {
        $this->Database = new Database();
        $this->pdo = $this->Database->check_Database($_ENV['DB_NAME']);
        $this->gateways = [
            'user' => new UserGateway($this->pdo),
            'admin' => new AdminGateway($this->pdo)
        ];
    }

    public function processRequest(string $method, string $type, string $action, ?string $id): void
    {
        if (!isset($this->gateways[$type])) {
            http_response_code(404);
            echo json_encode(['error' => 'Invalid request type']);
            return;
        }
// var_dump($method, $type, $action, $id);
        $gateway = $this->gateways[$type];

        switch ($method) {
            case 'POST':
                $this->handlePost($gateway, $type, $action);
                break;
            case 'GET':
                $this->handleGet($gateway, $type, $action, $id);
                break;
            case 'PUT':
                $this->handlePut($gateway, $type, $action, $id);
                break;
            case 'PATCH':
                $this->handlePatch($gateway, $type, $action, $id);
                break;
            case 'DELETE':
                $this->handleDelete($gateway, $type, $action, $id);
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
        }
    }

    private function handlePost($gateway, $type, $action): void
    {
        if ($type === 'user') {
            $rawInput = file_get_contents('php://input');
            $jsonInput = json_decode($rawInput, true);

            // Use ternary to determine source of data
            $data = !empty($jsonInput)
                ? $jsonInput
                : (!empty($_POST)
                    ? $_POST
                    : (json_decode(file_get_contents('php://input'), true) ?? []));
            $gateway->handleAction($action, $data ?? [], $_FILES ?? null);
            return;
        }
        if ($type === 'admin') {
            $rawInput = file_get_contents('php://input');
            $jsonInput = json_decode($rawInput, true);

            // Use ternary to determine source of data
            $data = !empty($jsonInput)
                ? $jsonInput
                : (!empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true));
            $gateway->handleAction($action, $data ?? [], $_FILES ?? null);

            return;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Invalid POST type']);
    }

    private function handleGet($gateway, $type, $action, ?string $id): void
    {
        if ($type === 'user') {
            $gateway->handleFetch($action, $id);
            return;
        }
        if ($type === 'admin') {
            // if ($id) {
            //     $gateway->handleAdminFetch($action, $id);
            // } else {
            $gateway->handleAdminFetchAll($action);
            return;
            // }
        }
        http_response_code(400);
        echo json_encode(['error' => 'Invalid get type']);
    }

    private function handlePut($gateway, $type, $action, $id): void
    {
        if ($id) {
            if ($type === 'user') {
                $rawInput = file_get_contents('php://input');
                $jsonInput = json_decode($rawInput, true);
                $data = !empty($jsonInput)
                    ? $jsonInput
                    : (!empty($_POST)
                        ? $_POST
                        : (json_decode(file_get_contents('php://input'), true) ?? []));
                $gateway->handleUserPut($action, $data ?? [], $id, $_FILES ?? null);
                return;
            }
            if ($type === 'admin') {
                $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
                if (stripos($contentType, 'multipart/form-data') !== false) {
                    [$formData, $files] = $this->parseMultipartPutRequest();
                    $data = !empty($formData) ? $formData : [];
                    $gateway->handleAdminPut($action, $data, $id, $files);
                    return;
                }

                $rawInput = file_get_contents('php://input');
                $jsonInput = json_decode($rawInput, true);
                $data = !empty($jsonInput)
                    ? $jsonInput
                    : (!empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true));
                $gateway->handleAdminPut($action, $data, $id, $_FILES ?? null);
                return;
            }
        }
        http_response_code(400);
        echo json_encode(['error' => 'Invalid put type']);
    }

    private function parseMultipartPutRequest(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
        if (!preg_match('/boundary=(.*)$/', $contentType, $matches)) {
            return [[], []];
        }

        $boundary = '--' . trim($matches[1], "\" \r\n");
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') {
            return [[], []];
        }

        $parts = explode($boundary, $raw);
        $data = [];
        $files = [];

        foreach ($parts as $part) {
            $part = ltrim($part, "\r\n");
            $part = rtrim($part, "\r\n");
            if ($part === '' || $part === '--') {
                continue;
            }

            $sections = explode("\r\n\r\n", $part, 2);
            if (count($sections) !== 2) {
                continue;
            }

            [$rawHeaders, $body] = $sections;
            $headers = [];
            foreach (explode("\r\n", $rawHeaders) as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, ':') === false) {
                    continue;
                }
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }

            $disposition = $headers['content-disposition'] ?? '';
            if (!preg_match('/name=\"([^\"]+)\"/', $disposition, $nameMatch)) {
                continue;
            }
            $fieldName = $nameMatch[1];
            $body = preg_replace("/\r\n$/", '', $body);

            if (preg_match('/filename=\"([^\"]*)\"/', $disposition, $fileMatch)) {
                $filename = $fileMatch[1];
                if ($filename === '') {
                    continue;
                }
                $mime = $headers['content-type'] ?? 'application/octet-stream';
                $tmpFile = tempnam(sys_get_temp_dir(), 'put_');
                if ($tmpFile === false) {
                    continue;
                }
                file_put_contents($tmpFile, $body);
                $files[$fieldName] = [
                    'name' => $filename,
                    'type' => $mime,
                    'tmp_name' => $tmpFile,
                    'error' => 0,
                    'size' => strlen($body),
                ];
            } else {
                $data[$fieldName] = $body;
            }
        }

        return [$data, $files];
    }

    private function handlePatch($gateway, $type, $action, $id): void
    {

        if ($id) {
            if ($type === 'user') {
                $rawInput = file_get_contents('php://input');
                $jsonInput = json_decode($rawInput, true);
                $data = !empty($jsonInput)
                    ? $jsonInput
                    : (!empty($_POST)
                        ? $_POST
                        : (json_decode(file_get_contents('php://input'), true) ?? []));
                $gateway->handlePatch($action, $data);
                return;
            }
            if ($type === 'admin') {
                $rawInput = file_get_contents('php://input');
                $jsonInput = json_decode($rawInput, true);
                $data = !empty($jsonInput)
                    ? $jsonInput
                    : (!empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), associative: true));
                $gateway->handleAdminPatch($action, $data, $id);
                return;
            }
        } else {
            if ($type === 'user') {
                $rawInput = file_get_contents('php://input');
                $jsonInput = json_decode($rawInput, true);
                $data = !empty($jsonInput)
                    ? $jsonInput
                    : (!empty($_POST)
                        ? $_POST
                        : (json_decode(file_get_contents('php://input'), true) ?? []));
                $gateway->handlePatch($action, $data);
                return;
            }
            if ($type === 'admin') {
                $rawInput = file_get_contents('php://input');
                $jsonInput = json_decode($rawInput, true);
                $data = !empty($jsonInput)
                    ? $jsonInput
                    : (!empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), associative: true));
                $gateway->handleAdminPatch($action, $data);
                return;
            }
        }
        http_response_code(400);
        echo json_encode(['error' => 'Invalid patch type']);
    }

    private function handleDelete($gateway, $type, $action, $id): void
    {
        if ($type === 'admin') {
            if ($id) {
                $gateway->handleAdminDelete($action, $id);
            }
            return;
        }
        if ($type === 'user') {
            if ($id) {
                $gateway->handleUserDelete($action, $id);
            }
            return;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Invalid delete type']);
    }
}
