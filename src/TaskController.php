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
        $this->pdo = $this->Database->check_Database($_ENV["DB_NAME"]);

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
                $this->handlePut($gateway, $id);
                break;
            case 'PATCH':
                $this->handlePatch($gateway, $id);
                break;
            case 'DELETE':
                $this->handleDelete($gateway, $id);
                break;
            default:
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                break;
        }
    }

    private function handlePost($gateway, $type, $action): void
    {
        if ($type === "user") {
            $rawInput = file_get_contents("php://input");
            $jsonInput = json_decode($rawInput, true);

            // Use ternary to determine source of data
            $data = !empty($jsonInput)
                ? $jsonInput
                : (!empty($_POST) ? $_POST : json_decode(file_get_contents("php://input"), true));
            $gateway->handleAction($action, $data);

            return;
        }
        if ($type === "admin") {
            $rawInput = file_get_contents("php://input");
            $jsonInput = json_decode($rawInput, true);

            // Use ternary to determine source of data
            $data = !empty($jsonInput)
                ? $jsonInput
                : (!empty($_POST) ? $_POST : json_decode(file_get_contents("php://input"), true));
            $gateway->handleAction($action, $data);

            return;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Invalid POST type']);
    }

    private function handleGet($gateway, $type, $action, $id): void
    {

        if ($type === "user") {
            if ($id) {
                  $gateway->handleFetch($action, $id);
            } else {
                 $gateway->handleFetchAll($action);
            }
        }
        if ($type === "admin") {
            if ($id) {
                  $gateway->handleFetch($action, $id);
            } else {
                 $gateway->handleFetchAll($action);
            }
        }
    }

    private function handlePut($gateway, ?string $id): void
    {
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'PUT requests require an ID']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $result = $gateway->update($id, $data);
        echo json_encode($result);
    }

    private function handlePatch($gateway, ?string $id): void
    {
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'PATCH requests require an ID']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $result = $gateway->partialUpdate($id, $data);
        echo json_encode($result);
    }

    private function handleDelete($gateway, ?string $id): void
    {
        if ($id === null) {
            http_response_code(400);
            echo json_encode(['error' => 'DELETE requests require an ID']);
            return;
        }

        $result = $gateway->delete($id);
        echo json_encode($result);
    }
}
