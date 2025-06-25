<?php
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/EmailSender.php';


class PutGateway
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

    public function updateUser(array $data, string $accToken)
    {
        unset($data['accToken']);
        $keys = array_keys($data);
        $updated = $this->connectToDataBase->updateDataWithArrayKey($this->dbConnection, RegTable, $keys, $data, 'accToken', $accToken);
        if ($updated) {
            $this->response->created("User details updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating user details.');
        }

    }
    public function updateWallet(array $data, string $accToken)
    {
        // Handle nested network array → flatten into JSON fields
        if (isset($data['network']) && is_array($data['network'])) {
            $networks = $data['network'];

            $networkNames = [];
            $depositAddresses = [];
            $minDeposits = [];
            $confirmations = [];

            foreach ($networks as $net) {
                $networkNames[] = $net['name'];
                $depositAddresses[] = $net['deposit_address'];
                $minDeposits[] = $net['min_deposit'];
                $confirmations[] = $net['confirmations_required'];
            }

            $data['network'] = json_encode($networkNames, JSON_UNESCAPED_UNICODE);
            $data['deposit_address'] = json_encode($depositAddresses, JSON_UNESCAPED_UNICODE);
            $data['min_deposit'] = json_encode($minDeposits, JSON_UNESCAPED_UNICODE);
            $data['confirmations_required'] = json_encode($confirmations, JSON_UNESCAPED_UNICODE);
        }

        // Remove token
        unset($data['accToken']);

        $keys = array_keys($data);

        $updated = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            wallet,
            $keys,
            $data,
            'id',
            $accToken
        );

        if ($updated) {
            $this->response->created("User details updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating user details.');
        }
    }



}


