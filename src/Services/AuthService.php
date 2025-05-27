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
        $arrayRecord = ['uid' => $data['referral']];
        $result = $this->createDbTables->createTableWithTypes(RegTable, $this->regUsercolumns);
        if ($result) {
            if ($this->gateway->checkEmailExistence($this->dbConnection, RegTable, $checkMailCond)) {
                $this->response->unprocessableEntity('This email already exists. try another.');
            } else {
                $bindingArrayforRegUser = $this->gateway->generateRandomStrings($regdata);
                $id = $this->gateway->createForUserWithTypes($this->dbConnection, RegTable, $this->regUsercolumns, $bindingArrayforRegUser, $regdata);
                if ($id) {
                    $fetchUser = $this->gateway->fetchDataWithId(RegTable, $id);
                    $username = $fetchUser['name'];
                    $bindingArrayforEmailVal = $this->gateway->generateRandomStrings($EmailValData);

                    $createEmailVal = $this->createDbTables->createTableWithTypes(EmailValidation, $this->EmailCoulmn);
                    if ($createEmailVal) {
                        $createEmailValTable = $this->connectToDataBase->insertDataWithTypes($this->dbConnection, EmailValidation, $this->EmailCoulmn, $bindingArrayforEmailVal, $EmailValData);
                        if ($createEmailValTable) {
                            if ($data['referral'] === "") {
                               
                                $message = $this->gateway->createNotificationMessage(
                                    $id,
                                    '🎉 Welcome to ' . sitename,
                                    'Your registration was successful! We\'re excited to have you on board. Explore your dashboard and get started today.',
                                    $data['createdAt']
                                );
                                if ($message) {
                                    $sent = $this->mailsender->sendOtpEmail($fetchUser['email'], $username, $EmailValData['verificationToken']);
                                    if ($sent === true) {
                                        $response = ['status' => 'true', 'email' => $fetchUser['email']];
                                        $this->response->created($response);
                                    } else {

                                        $this->response->unprocessableEntity('could not send mail to user');

                                    }
                                }
                            }
                            if ($data['referral'] !== "") {

                                if ($this->gateway->recordExists(RegTable, $arrayRecord)) {
                                    $referralID = $this->gateway->genRandomAlphanumericstrings(10);
                                    $referralColumns = ['nameOfRefers', 'referrerId', 'referredId', 'referralId', 'dateOfReferral', 'amtEarned', 'status'];

                                    $date = $data['createdAt'];
                                    $referralData = [$fetchUser['name'], $data['referral'], $fetchUser['uid'], $referralID, $date, '0.00', 'Pending'];

                                    if (!$this->createDbTables->createTable(referralTable, $referralColumns)) {
                                        $this->response->unprocessableEntity(['Failed to create table: ' . referralTable]);
                                        return;
                                    }

                                    $referralBindingArray = $this->gateway->generateRandomStrings($referralColumns);
                                    $inserted = $this->connectToDataBase->insertData($this->dbConnection, referralTable, $referralColumns, $referralBindingArray, $referralData);

                                    if (!$inserted) {
                                        $this->response->unprocessableEntity(['Failed to insert referral record.']);
                                        return;
                                    }

                                    $notificationSent = $this->gateway->createNotificationMessage(
                                        $id,
                                        '🎉 Welcome to ' . sitename,
                                        'Your registration was successful! We\'re excited to have you on board. Explore your dashboard and get started today.',
                                        $data['createdAt']
                                    );


                                    if (!$notificationSent) {
                                        $this->response->unprocessableEntity(['Failed to send registration notification.']);
                                        return;
                                    }

                                    $emailSent = $this->mailsender->sendOtpEmail($fetchUser['email'], $username, $EmailValData['verificationToken']);

                                    if (!$emailSent) {
                                        $this->response->unprocessableEntity(['Failed to send registration email.']);
                                        return;
                                    }

                                    $this->response->created(['status' => 'true', 'email' => $fetchUser['email']]);
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


