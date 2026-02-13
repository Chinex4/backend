<?php 

class UserPutService{
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
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handleput(string $action): void
    {
        switch ($action) {
            case "updateNickname":
                // $this->put->updateNickname();
                break;
        }
    }

    public function handleUserPut(string $action, ?array $data, string $id, $file = null): void
    {
        switch ($action) {
            default:
                break;
        }
    }
}
