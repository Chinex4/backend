<?php
require_once __DIR__ . '/Services/AuthService.php';
class FetchGateway
{
    private $pdovar;
    private $authService;
    private $encrypt;
    private $mailsender;
    private $conn;
    private $createDbTables;
    private $gateway;
    private $columns;
    private $userDataGenerator;
    private $response;
    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection;
        $this->authService = new AuthService($this->pdovar);
        $this->columns = require __DIR__ . '/Config/UserColumns.php';
        $this->response = new JsonResponse();
        $this->gateway = new TaskGatewayFunction($this->pdovar);
        // $this->mailsender = new EmailSender();
        // $this->response = new JsonResponse();
        // $this->conn = new Database();
        // $this->createDbTables = new CreateDbTables($this->pdovar);
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function fetchuser($id)
    {
        $condForfetch = ['id' => $id];
        $fetchuser = $this->gateway->fetchData(RegTable, $condForfetch);
        return $this->response->success(['userDetails' => $fetchuser]);


    }

    public function fetchAlluser()
    {
        $fetchuser = $this->gateway->fetchAllData(RegTable);
        return $this->response->success(['userDetails' => $fetchuser]);
    }


}
