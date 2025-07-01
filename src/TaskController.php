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
            $gateway->handleAction($action, $data);

            return;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Invalid POST type']);
    }

    private function handleGet($gateway, $type, $action, $id): void
    {
        if ($type === 'user') {
            $gateway->handleFetch($action);
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
                    : (!empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true));
                $gateway->handleUserPut($action, $data, $id);
                return;
            }
            if ($type === 'admin') {
                $rawInput = file_get_contents('php://input');
                $jsonInput = json_decode($rawInput, true);
                $data = !empty($jsonInput)
                    ? $jsonInput
                    : (!empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true));
                $gateway->handleAdminPut($action, $data, $id);
                return;
            }
        }
        http_response_code(400);
        echo json_encode(['error' => 'Invalid put type']);
    }

    private function handlePatch($gateway, $type, $action, $id): void
    {

        if ($id) {
            if ($type === 'user') {
                $rawInput = file_get_contents('php://input');
                $jsonInput = json_decode($rawInput, true);
                $data = !empty($jsonInput)
                    ? $jsonInput
                    : (!empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true));
                $gateway->handleUserPatch($action, $data, $id);
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
                    : (!empty($_POST) ? $_POST : json_decode(file_get_contents('php://input'), true));
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
