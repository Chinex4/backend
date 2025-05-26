<?php
require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../EmailSender.php';
require_once __DIR__ . '/Generators/UserDataGenerator.php';
require_once __DIR__ . '/Generators/EmailDataGenerator.php';

use Services\Generators\UserDataGenerator;
use Services\Generators\EmailDataGenerator;

class AuthService
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
    public function __construct($pdoConnection)
    {
        $this->dbConnection = $pdoConnection;
        $this->regUsercolumns = require __DIR__ . '/../Config/UserColumns.php';
        $this->EmailCoulmn = require __DIR__ . '/../Config/EmailCoulmn.php';
        $this->gateway = new TaskGatewayFunction($this->dbConnection);
        $this->userDataGenerator = new UserDataGenerator($this->gateway);
        $this->EmailDataGenerator = new EmailDataGenerator($this->gateway);
        $this->createDbTables = new CreateDbTables($this->dbConnection);
        $this->response = new JsonResponse();
        $this->connectToDataBase = new Database();
        $this->mailsender = new EmailSender();
    }
    public function __destruct()
    {
        $this->dbConnection = null;
    }


    public function registerUser(array $data)
    {
        $defaultData = $this->userDataGenerator->generateDefaultData($data);
        $regdata = array_merge($data, $defaultData);
        $emailData = ['createdAt' => $data['createdAt'], 'email' => $data['email']];
        $EmailValData = $this->EmailDataGenerator->generateVerificationData($emailData);


        $checkMailCond = ['email' => $regdata['email']];
        $result = $this->createDbTables->createTableWithTypes(RegTable, $this->regUsercolumns);
        if ($result) {
            if ($this->gateway->checkEmailExistence($this->dbConnection, RegTable, $checkMailCond)) {
                $this->response->badRequest('This email already exists. try another.');
            } else {
                $bindingArrayforRegUser = $this->gateway->generateRandomStrings($regdata);
                $id = $this->gateway->createForUserWithTypes($this->dbConnection, RegTable, $this->regUsercolumns, $bindingArrayforRegUser, $regdata);
                if ($id) {
                    $fetchUser = $this->gateway->fetchDataWithId(RegTable, $id);
                    $username = $fetchUser['name'];
                    $bindingArrayforEmailVal = $this->gateway->generateRandomStrings($EmailValData);
 
                    $createEmailVal = $this->createDbTables->createTableWithTypes(EmailValidation, $this->EmailCoulmn);
                    if ($createEmailVal ) {
                        $createEmailValTable = $this->connectToDataBase->insertDataWithTypes($this->dbConnection, EmailValidation, $this->EmailCoulmn, $bindingArrayforEmailVal, $EmailValData);
                        if ($createEmailValTable) {
                            if ($data['referral'] === "") {
                                $h = 'Successful Registration';
                                $c = 'Congratulations! Your registration was successful.';
                                $message = $this->gateway->createNotificationMessage($id, $h, $c);
                                if ($message) {
                                    $sent = $this->mailsender->sendOtpEmail($fetchUser['email'], $username, $EmailValData['verificationToken']);
                                    if ($sent === true) {
                                        $response = ['status' => 'true', 'email' => $fetchUser['email']];
                                        $this->response->success($response);
                                    } else {
    
                                        $this->response->unprocessableEntity('could not send mail to user');
    
                                    }
                                }
                            } else {
    
                                $this->response->unprocessableEntity('could not insert message');
    
                            }
    
                        }
                    }
                }
            }
        }
    }
}


