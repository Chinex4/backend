<?php 

class AdminDeleteService{
    private $pdovar;
    private $authService;
    private $encrypt;
    private $mailsender;
    private $conn;
    private $createDbTables;
    private $gateway;
    private $columns;
    private $userDataGenerator;
    private $delete;
    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection; 
        $this->delete = new DeleteGateway($this->pdovar);
        // $this->mailsender = new EmailSender();
        // $this->response = new JsonResponse();
        // $this->conn = new Database();
        // $this->createDbTables = new CreateDbTables($this->pdovar);
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handleDelete(string $action, $id): void
    {
        switch ($action) {
            case "deleteWallet": 
                $this->delete->deleteWallet($id);
                break;
            case "deleteKyc": 
                $this->delete->deleteKyc($id);
                break;
            case "deleteAdvancedKyc": 
                $this->delete->deleteAdvancedKyc($id);
                break;
            case "deleteInstitution": 
                $this->delete->deleteInstitution($id);
                break;
            case "deleteDeposit": 
                $this->delete->deleteDeposit($id);
                break;
            case "p2pOrders":
                $this->delete->deleteP2POrder($id);
                break;
            case "copytradeorderr":
            case "copytradeorder":
                $this->delete->deleteCopyTradeOrder($id);
                break;
        }
    }
}
