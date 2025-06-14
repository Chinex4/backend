<?php 

class AdminPatchService{
    private $pdovar;
    private $authService;
    private $encrypt;
    private $mailsender;
    private $conn;
    private $createDbTables;
    private $gateway;
    private $columns;
    private $userDataGenerator;
    private $patch;
    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection; 
        $this->patch = new PatchGateway($this->pdovar);
        // $this->mailsender = new EmailSender();
        // $this->response = new JsonResponse();
        // $this->conn = new Database();
        // $this->createDbTables = new CreateDbTables($this->pdovar);
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handlePatch(string $action, ?array $data, string $accToken): void
    {
        switch ($action) {
            case "disableLogin": 
                $this->patch->disableLogin($accToken);
                break;
            case "enableLogin": 
                $this->patch->enableLogin($accToken);
                break;
            case "disableAlert": 
                $this->patch->disableAlert($accToken);
                break;
            case "enableAlert": 
                $this->patch->enableAlert($accToken);
                break;
            case "enableOtp": 
                $this->patch->enableOtp($accToken);
                break;
            case "disableOtp": 
                $this->patch->disableOtp($accToken);
                break;
            
        }
    }
}