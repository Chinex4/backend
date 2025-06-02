<?php
require_once __DIR__ . '/Services/AuthService.php';
class UserGateway
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

    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection;
        $this->authService = new AuthService($this->pdovar);
        $this->columns = require __DIR__ . '/Config/UserColumns.php';
      
        // $this->mailsender = new EmailSender();
        // $this->response = new JsonResponse();
        // $this->conn = new Database();
        // $this->createDbTables = new CreateDbTables($this->pdovar);
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handleAction(string $action, array $data):void
    {
        switch($action){
            case "registerUser":
                $this->authService->registerUser($data);
                break;
            case "verify-email":
                $this->authService->verifyEmail($data);
                break;
            case "resend-otp":
                $this->authService->resendOtp($data);
                break;
            case "login":
                $this->authService->login($data);
                break;
            case "forgot-password":
                $this->authService->forgotPassword($data);
                break;
            case "verifyResetPassword":
                $this->authService->verifyResetPassword($data);
                break;
            case "changePassword":
                $this->authService->changePassword($data);
                break;
        }
    }

   
}
