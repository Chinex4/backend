<?php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/EmailSender.php';


class PatchGateway
{
    private $dbConnection;
    private $regUsercolumns;
    private $EmailCoulmn;
    private $gateway;
    private $userDataGenerator;
    private $EmailDataGenerator;
    private $createDbTables;
    private $response;
    private $connectToDataBase;
    private $mailsender;
    private $jwtCodec;
    private $refreshTokenGateway;
    private $ForgotPasswordColumns;

    public function __construct($pdoConnection)
    {
        $this->dbConnection = $pdoConnection;
        $this->gateway = new TaskGatewayFunction($this->dbConnection);
        $this->createDbTables = new CreateDbTables($this->dbConnection);
        $this->response = new JsonResponse();
        $this->connectToDataBase = new Database();
        $this->mailsender = new EmailSender();
        $key = $_ENV['SECRET_KEY'];
        $this->jwtCodec = new JWTCodec($key);
        $this->refreshTokenGateway = new RefreshTokenGateway($pdoConnection, $key);
    }

    public function __destruct()
    {
        $this->dbConnection = null;
    }

    public function enableOtp(string $accToken)
    {
        $createColumn = $this->createDbTables->createTable(RegTable, ['allowOtp']);
        if ($createColumn) {
        $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['allowOtp'], ['true'], 'accToken', $accToken);
        if ($updated) {
            $this->response->created("OTP login has been enabled for this user.");
        } else {
            $this->response->unprocessableEntity('error disabling user login');
        }
        }
    }
    public function disableOtp(string $accToken)
    {
        $createColumn = $this->createDbTables->createTable(RegTable, ['allowOtp']);
        if ($createColumn) {
        $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['allowOtp'], ['false'], 'accToken', $accToken);
        if ($updated) {
            $this->response->created("OTP login has been disabled for this user.");
        } else {
            $this->response->unprocessableEntity('error disabling user login');
        }
        }
    }
    public function disableLogin(string $accToken)
    {
        // $createColumn = $this->createDbTables->createTable(RegTable, ['chinex']);
        // if ($createColumn) {
        $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['AllowLogin'], ['false'], 'accToken', $accToken);
        if ($updated) {
            $this->response->created("user login disabled successfully, this user Can not login again except you enable it");
        } else {
            $this->response->unprocessableEntity('error disabling user login');
        }
        // }
    }
    public function disableAlert(string $accToken)
    {
        // $createColumn = $this->createDbTables->createTable(RegTable, ['chinex']);
        // if ($createColumn) {
        $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['allowMessage'], ['false'], 'accToken', $accToken);
    
        if ($updated) {
            $this->response->created("Alert messages disabled successfully for this user.");
        } else {
            $this->response->unprocessableEntity("Error disabling alert messages for this user.");
        }
        // }
    }
    
    public function enableAlert(string $accToken)
{
    $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['allowMessage'], ['true'], 'accToken', $accToken);

    if ($updated) {
        $this->response->created("Alert messages enabled successfully for this user.");
    } else {
        $this->response->unprocessableEntity("Error enabling alert messages for this user.");
    }
}
 

    public function enableLogin(string $accToken)
    {
        $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['AllowLogin'], ['true'], 'accToken', $accToken);
        if ($updated) {
            $this->response->created("user login enabled successfully, this user Can now login again except you disable it");
        } else {
            $this->response->unprocessableEntity('error disabling user login');
        }
    }


}


