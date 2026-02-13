<?php
require_once __DIR__ . '/constants.php';

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
    public function deleteKyc($id)
    {
        $deletDeposit = $this->connectToDataBase->deleteData($this->dbConnection, idVer, 'id', $id);
        if ($deletDeposit) {
            return $this->response->success('this basic verification details has been has been deleted successfully');
        } else {
            $this->response->unprocessableEntity('could not delete basic verification details');
        }

    }
    public function deleteAdvancedKyc($id)
    {
        $deletDeposit = $this->connectToDataBase->deleteData($this->dbConnection, advancedVerification, 'id', $id);
        if ($deletDeposit) {
            return $this->response->success('this Advanced verification details has been has been deleted successfully');
        } else {
            $this->response->unprocessableEntity('could not delete Advanced verification details');
        }

    }
    public function deleteInstitution($id)
    {
        $deletDeposit = $this->connectToDataBase->deleteData($this->dbConnection, institutionalVerification, 'id', $id);
        if ($deletDeposit) {
            return $this->response->success('this Institutional Verification details has been has been deleted successfully');
        } else {
            $this->response->unprocessableEntity('could not delete Institutional Verification details');
        }

    }
    public function deleteDeposit($id)
    {
        $deletDeposit = $this->connectToDataBase->deleteData($this->dbConnection, deposit, 'id', $id);
        if ($deletDeposit) {
            return $this->response->success('this deposit has been deleted successfully');
        } else {
            $this->response->unprocessableEntity('could not delete deposit');
        }

    }

    public function deleteP2POrder($id)
    {
        $deleted = $this->connectToDataBase->deleteData($this->dbConnection, p2p_orders, 'orderId', $id);
        if ($deleted) {
            return $this->response->success('this p2p order has been deleted successfully');
        } else {
            $this->response->unprocessableEntity('could not delete p2p order');
        }
    }
}
