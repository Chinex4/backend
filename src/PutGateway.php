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
 

        // Handle nested networks array → flatten into JSON fields
        if (isset($data['networks']) && is_array($data['networks'])) {
            $networks = $data['networks'];

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

            // Encode as JSON for MySQL JSON columns with CHECK constraint
            $data['network'] = json_encode($networkNames);
            $data['deposit_address'] = json_encode($depositAddresses);
            $data['min_deposit'] = json_encode($minDeposits);
            $data['confirmations_required'] = json_encode($confirmations);

            unset($data['networks']);  
        }

        // Remove unused token before DB update
        unset($data['accToken']);

        // Prepare keys and update
        $keys = array_keys($data);

        $updated = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            wallet,  
            $keys,
            $data,
            'id',
            $accToken
        );

        // Send response
        if ($updated) {
            $this->response->created("User details updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating user details.');
        }



    }



}


