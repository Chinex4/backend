<?php 

class AdminfetchService{
    private $pdovar;
    private $authService;
    private $encrypt;
    private $mailsender;
    private $conn;
    private $createDbTables;
    private $gateway;
    private $columns;
    private $userDataGenerator;
    private $fetch;
    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection; 
        $this->fetch = new FetchGateway($this->pdovar);
        // $this->mailsender = new EmailSender();
        // $this->response = new JsonResponse();
        // $this->conn = new Database();
        // $this->createDbTables = new CreateDbTables($this->pdovar);
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handleFetch(string $action): void
    {
        switch ($action) {
            case "fetchAlluser": 
                $this->fetch->fetchAlluser();
                break;
            case "fetchWallets": 
                $this->fetch->fetchWallets();
                break;
        }
    }
}