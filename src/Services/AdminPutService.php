<?php 

class AdminPutService{
    private $pdovar;
    private $authService;
    private $encrypt;
    private $mailsender;
    private $conn;
    private $createDbTables;
    private $gateway;
    private $columns;
    private $userDataGenerator;
    private $put;
    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection; 
        $this->put = new PutGateway($this->pdovar);
        // $this->mailsender = new EmailSender();
        // $this->response = new JsonResponse();
        // $this->conn = new Database();
        // $this->createDbTables = new CreateDbTables($this->pdovar);
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handleAdminPut(string $action,  ?array $data, string $accToken): void
    {
        switch ($action) {
            case "updateUser": 
                $this->put->updateUser( $data,  $accToken);
                break;
            case "updateWallet": 
                $this->put->updateWallet( $data,  $accToken);
                break;
        }
    }
}