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

    public function updateUserBalances(array $data, string $accToken)
    {
        if (isset($data['balances_json']) && is_array($data['balances_json'])) {
            $data['balances_json'] = json_encode($data['balances_json'], JSON_UNESCAPED_SLASHES);
        }

        unset($data['accToken']);
        $keys = array_keys($data);

        $updated = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            RegTable,
            $keys,
            $data,
            'accToken',
            $accToken
        );

        if ($updated) {
            $this->response->created("User details updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating user details.');
        }
    }

    public function updateDeposit(array $data, string $accToken)
    {
        $depositRow = $this->gateway->fetchData(deposit, ['id' => $accToken]);
        if (!$depositRow || !is_array($depositRow)) {
            return $this->response->unprocessableEntity('Deposit not found.');
        }

        $user = null;
        if (!empty($depositRow['userId'])) {
            $user = $this->gateway->fetchData(RegTable, ['accToken' => $depositRow['userId']]);
        }

        $oldAmount = isset($depositRow['amount_usd']) ? (float) $depositRow['amount_usd'] : 0;
        $newAmount = isset($data['amount_usd']) ? (float) $data['amount_usd'] : $oldAmount;

        $oldNetwork = $depositRow['network'] ?? null;
        $newNetwork = $data['network'] ?? $oldNetwork;

        $oldCoinAmount = $depositRow['coin_amount'] ?? 0;
        $newCoinAmount = $data['coin_amount'] ?? $oldCoinAmount;

        // Update deposit record
        $keys = array_keys($data);
        $updatedDeposit = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            deposit,
            $keys,
            $data,
            'id',
            $accToken
        );

        if (!$updatedDeposit) {
            return $this->response->unprocessableEntity('Error updating deposit.');
        }

        // Adjust user balances if amount or network changed
        if ($user && isset($user['id'])) {
            $balances = [];
            if (!empty($user['balances_json'])) {
                $decodedBalances = json_decode($user['balances_json'], true);
                if (is_array($decodedBalances)) {
                    $balances = $decodedBalances;
                }
            }

            if ($oldNetwork !== $newNetwork) {
                if ($oldNetwork) {
                    foreach ($balances as $index => $item) {
                        if (isset($item['id']) && $item['id'] === $oldNetwork) {
                            $currentBalance = isset($item['balance']) ? (float) $item['balance'] : 0;
                            $balances[$index]['balance'] = $currentBalance - $oldAmount;
                            break;
                        }
                    }
                }
                if ($newNetwork) {
                    $found = false;
                    foreach ($balances as $index => $item) {
                        if (isset($item['id']) && $item['id'] === $newNetwork) {
                            $currentBalance = isset($item['balance']) ? (float) $item['balance'] : 0;
                            $balances[$index]['balance'] = $currentBalance + $newAmount;
                            $balances[$index]['price'] = $newCoinAmount;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $balances[] = [
                            'id' => $newNetwork,
                            'balance' => $newAmount,
                            'price' => $newCoinAmount,
                        ];
                    }
                }
            } else {
                $diffAmount = $newAmount - $oldAmount;
                if ($newNetwork) {
                    $found = false;
                    foreach ($balances as $index => $item) {
                        if (isset($item['id']) && $item['id'] === $newNetwork) {
                            $currentBalance = isset($item['balance']) ? (float) $item['balance'] : 0;
                            $balances[$index]['balance'] = $currentBalance + $diffAmount;
                            $balances[$index]['price'] = $newCoinAmount;
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $balances[] = [
                            'id' => $newNetwork,
                            'balance' => $newAmount,
                            'price' => $newCoinAmount,
                        ];
                    }
                }
            }

            $totalAsset = isset($user['totalAsset']) ? (float) $user['totalAsset'] : 0;
            $spotAccount = isset($user['spotAccount']) ? (float) $user['spotAccount'] : 0;

            $diffAmount = $newAmount - $oldAmount;
            $newTotalAsset = $totalAsset + $diffAmount;
            $newSpotAccount = $spotAccount + $diffAmount;

            $this->connectToDataBase->updateData(
                $this->dbConnection,
                RegTable,
                ['totalAsset', 'spotAccount', 'balances_json'],
                [$newTotalAsset, $newSpotAccount, json_encode($balances, JSON_UNESCAPED_SLASHES)],
                'id',
                $user['id']
            );
        }

        $this->response->created("Deposit updated successfully.");
    }

    public function updateP2PTrader(array $data, string $accToken)
    {
        unset($data['id']);
        $keys = array_keys($data);

        $updated = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            p2p_traders,
            $keys,
            $data,
            'id',
            $accToken
        );

        if ($updated) {
            $this->response->created("P2P trader updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating P2P trader.');
        }
    }



}


