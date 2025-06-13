<?php 
require_once __DIR__ . '/AdminAuthService.php';

class AdminPostService{
    private $pdovar;
    private $adminauthservice;
    private $columns;
 
    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection; 
        $this->adminauthservice = new AdminAuthService($this->pdovar);
        $this->columns = require __DIR__ . '/../Config/UserColumns.php'; 
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handlePost(string $action, $data): void
    {
        switch ($action) {
            case "login":
                $this->adminauthservice->adminLogin($data);
                break;
            case "resendVerification":
                $this->adminauthservice->resendVerification($data);
                break;
        }
    }
}