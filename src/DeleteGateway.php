<?php

class DeleteGateway
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
    }

    public function __destruct()
    {
        $this->dbConnection = null;
    }


    public function deletewallet($id)
    {
        $deletDeposit = $this->connectToDataBase->deleteData($this->dbConnection, wallet, 'id', $id);
        if ($deletDeposit) {
            return $this->response->success('this wallet has been deleted successfully');
        } else {
            $this->response->unprocessableEntity('could not delete wallet');
        }

    }
}