<?php
require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../EmailSender.php';
require_once __DIR__ . '/Generators/UserDataGenerator.php';
require_once __DIR__ . '/Generators/EmailDataGenerator.php';

use Services\Generators\UserDataGenerator;
use Services\Generators\EmailDataGenerator;

class AdminAuthService
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
    private $p2pTraderColumn;

    public function __construct($pdoConnection)
    {
        $this->dbConnection = $pdoConnection;
        $this->regUsercolumns = require __DIR__ . '/../Config/UserColumns.php';
        $this->EmailCoulmn = require __DIR__ . '/../Config/EmailCoulmn.php';
        $this->ForgotPasswordColumns = require __DIR__ . '/../Config/ForgotPasswordColumns.php';
        $this->p2pTraderColumn = require __DIR__ . '/../Config/P2PTraderColumn.php';
        $this->gateway = new TaskGatewayFunction($this->dbConnection);
        $this->userDataGenerator = new UserDataGenerator($this->gateway);
        $this->EmailDataGenerator = new EmailDataGenerator($this->gateway);
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


    
  
    public function adminLogin(array $data)
    {
        $adminMail = $data['email'];
        $adminPassword = $data['password'];
        $conditions = ['adminMal' => $adminMail];
        $fetchAdmin = $this->gateway->fetchData(admintable, $conditions);
        if ($fetchAdmin) {
            $password = $fetchAdmin['Password'];
            if ($adminPassword === $password) {3+
                $_SESSION['AdminSession'] = $fetchAdmin['id'];
                $response = ["message" => "your account has been logged in successful"];
                $this->response->created(array_merge(['id' => $fetchAdmin['id']], $response));
            } else {
                    $this->response->unprocessableEntity('incorrect password try again');
            }
        } else {
                $this->response->unprocessableEntity('email address is wrong try again with a correct enail');
        }
    }
    public function resendVerification(array $data)
    {
        $emailData = ['createdAt' => $data['createdAt'], 'email' => $data['email']];
        $EmailValData = $this->EmailDataGenerator->generateVerificationData($emailData);
        $fetchUserCondition = ['accToken' => $data['userId']];
        $fetchUser = $this->gateway->fetchData(RegTable, $fetchUserCondition); 
        $username = $fetchUser['name'];
        $bindingArrayforEmailVal = $this->gateway->generateRandomStrings($EmailValData);
        $createEmailVal = $this->createDbTables->createTableWithTypes(EmailValidation, $this->EmailCoulmn);
        if ($createEmailVal) {
            $createEmailValTable = $this->connectToDataBase->insertDataWithTypes($this->dbConnection, EmailValidation, $this->EmailCoulmn, $bindingArrayforEmailVal, $EmailValData);
            if ($createEmailValTable) {
            }
        }
    }

    public function addP2PTrader(array $data)
    {
        $createdAt = $data['createdAt'] ?? date('Y-m-d H:i:s');
        $p2pData = [
            'name' => $data['name'] ?? null,
            'username' => $data['username'] ?? null,
            'traderId' => $this->gateway->generateRandomCode(),
            'verified' => $data['verified'] ?? null,
            'emailVerified' => isset($data['emailVerified']) ? ($data['emailVerified'] ? 'true' : 'false') : null,
            'smsVerified' => isset($data['smsVerified']) ? ($data['smsVerified'] ? 'true' : 'false') : null,
            'idVerified' => isset($data['idVerified']) ? ($data['idVerified'] ? 'true' : 'false') : null,
            'topSeller' => isset($data['topSeller']) ? ($data['topSeller'] ? 'true' : 'false') : null,
            'completion' => $data['completion'] ?? null,
            'orders' => isset($data['orders']) ? (int) $data['orders'] : 0,
            'price' => $data['price'] ?? null,
            'limits' => $data['limits'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'avgRelease' => $data['avgRelease'] ?? null,
            'payment' => $data['payment'] ?? null,
            'country' => $data['country'] ?? null,
            'status' => $data['status'] ?? null,
            'lastActive' => $data['lastActive'] ?? null,
            'adType' => $data['adType'] ?? null,
            'createdAt' => $createdAt,
            'updatedAt' => null,
        ];

        $createTable = $this->createDbTables->createTableWithTypes(p2p_traders, $this->p2pTraderColumn);
        if (!$createTable) {
            return $this->response->unprocessableEntity('Could not create P2P trader table.');
        }

        $bindingArray = $this->gateway->generateRandomStrings($p2pData);
        $inserted = $this->connectToDataBase->insertDataWithTypes(
            $this->dbConnection,
            p2p_traders,
            $this->p2pTraderColumn,
            $bindingArray,
            $p2pData
        );

        if (!$inserted) {
            return $this->response->unprocessableEntity('Could not save P2P trader.');
        }

        $notifyUserId = isset($data['userId']) ? $data['userId'] : 0;
        $this->gateway->createNotificationMessage(
            $notifyUserId,
            'P2P Trader Added',
            'A new P2P trader has been added.',
            $createdAt
        );

        return $this->response->created('P2P trader added successfully.');
    }
    
  

}


