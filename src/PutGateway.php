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
        $allowedFields = [
            'name', 'username', 'merchantType', 'verified', 'emailVerified', 'smsVerified', 'idVerified',
            'topSeller', 'asset', 'fiatCurrency', 'priceType', 'priceValue', 'priceMargin',
            'referencePriceSource', 'completion', 'orders', 'price', 'limits', 'minLimit', 'maxLimit',
            'quantity', 'availableQuantity', 'avgRelease', 'avgReleaseMinutes', 'orderTimeLimitMinutes',
            'payment', 'paymentMethods', 'paymentDetails', 'country', 'allowedRegions', 'blockedRegions', 'kycRequired',
            'kycTierRequired', 'complianceNote', 'status', 'isOnline', 'isHidden', 'isDeleted',
            'lastActive', 'adType', 'terms', 'paymentWindow', 'rating', 'ratingCount', 'thirtyDayVolume',
            'thirtyDayTrades', 'cancelRate', 'appealRate', 'approvedBy', 'approvedAt', 'updatedBy'
        ];

        unset($data['id']);
        $filtered = array_intersect_key($data, array_flip($allowedFields));
        if (isset($filtered['username'])) {
            $filtered['username'] = ltrim(trim((string) $filtered['username']), '@');
        }
        foreach (['emailVerified', 'smsVerified', 'idVerified', 'topSeller', 'isOnline', 'isHidden', 'isDeleted'] as $boolField) {
            if (array_key_exists($boolField, $filtered)) {
                $value = $filtered[$boolField];
                if (is_bool($value)) {
                    $filtered[$boolField] = $value ? 'true' : 'false';
                } else {
                    $normalized = strtolower(trim((string) $value));
                    $filtered[$boolField] = in_array($normalized, ['true', '1', 'yes', 'y'], true) ? 'true' : 'false';
                }
            }
        }
        foreach (['paymentMethods', 'allowedRegions', 'blockedRegions'] as $jsonField) {
            if (array_key_exists($jsonField, $filtered) && is_array($filtered[$jsonField])) {
                $filtered[$jsonField] = json_encode(array_values($filtered[$jsonField]), JSON_UNESCAPED_UNICODE);
            }
        }
        if (array_key_exists('paymentDetails', $filtered) && (is_array($filtered['paymentDetails']) || is_object($filtered['paymentDetails']))) {
            $filtered['paymentDetails'] = json_encode($filtered['paymentDetails'], JSON_UNESCAPED_UNICODE);
        }
        if (empty($filtered)) {
            return $this->response->unprocessableEntity('No valid fields to update.');
        }
        $keys = array_keys($filtered);

        $updated = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            p2p_traders,
            $keys,
            $filtered,
            'id',
            $accToken
        );

        if ($updated) {
            $this->response->created("P2P trader updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating P2P trader.');
        }
    }

    public function updateP2POrder(array $data, string $accToken)
    {
        $allowedFields = [
            'adType', 'coin', 'fiat', 'fiatAmount', 'cryptoAmount', 'price', 'paymentMethod',
            'paymentMethods', 'paymentDetails', 'merchant', 'orders', 'completion', 'limitRange',
            'quantity', 'status', 'userRelease', 'reservedAmount', 'confirmedAt', 'uploadedImages',
            'paymentTiming'
        ];

        unset($data['orderId']);
        $filtered = array_intersect_key($data, array_flip($allowedFields));

        foreach (['paymentMethods', 'uploadedImages'] as $jsonField) {
            if (array_key_exists($jsonField, $filtered) && is_array($filtered[$jsonField])) {
                $filtered[$jsonField] = json_encode(array_values($filtered[$jsonField]), JSON_UNESCAPED_UNICODE);
            }
        }
        foreach (['paymentDetails', 'paymentTiming'] as $jsonField) {
            if (array_key_exists($jsonField, $filtered) && (is_array($filtered[$jsonField]) || is_object($filtered[$jsonField]))) {
                $filtered[$jsonField] = json_encode($filtered[$jsonField], JSON_UNESCAPED_UNICODE);
            }
        }

        if (empty($filtered)) {
            return $this->response->unprocessableEntity('No valid fields to update.');
        }
        $filtered['updatedAt'] = date('Y-m-d H:i:s');
        $keys = array_keys($filtered);

        $updated = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            p2p_orders,
            $keys,
            $filtered,
            'orderId',
            $accToken
        );

        if ($updated) {
            $this->response->created("P2P order updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating P2P order.');
        }
    }

    public function updateCopyTrade(array $data, string $accToken, array $file = [])
    {
        $allowedFields = [
            'display_name', 'username', 'avatar', 'status', 'kyc_status',
            'copiers_count', 'aum_usd', 'total_return_pct', 'roi_30d_pct', 'roi_90d_pct',
            'profit_factor', 'total_trades', 'max_drawdown_pct', 'volatility_30d', 'sharpe_ratio',
            'avg_leverage', 'risk_score', 'liquidation_events', 'win_rate_pct', 'profit_share_pct',
            'management_fee_pct', 'min_copy_amount_usd', 'max_copiers', 'copy_mode',
            'slippage_limit_pct', 'markets', 'instruments', 'time_horizon',
            'strategy_description', 'tags', 'verified_track_record', 'exchange_linked',
            'warning_flags', 'terms_accepted_at', 'last_active_at', 'created_at', 'is_public'
        ];

        unset($data['id']);
        $filtered = array_intersect_key($data, array_flip($allowedFields));

        if (isset($file['avatar']) && is_array($file['avatar']) && !empty($file['avatar']['name'])) {
            $uploadedAvatar = $this->gateway->processImageWithgivenNameFiles($file['avatar']);
            if (is_string($uploadedAvatar) && $uploadedAvatar !== '') {
                $filtered['avatar'] = $uploadedAvatar;
            } else {
                return;
            }
        }

        if (array_key_exists('username', $filtered)) {
            $filtered['username'] = strtolower(preg_replace('/[^a-z0-9_]/i', '', trim((string) $filtered['username'])));
            if ($filtered['username'] === '') {
                unset($filtered['username']);
            }
        }

        foreach (['markets', 'instruments', 'tags', 'warning_flags'] as $jsonField) {
            if (!array_key_exists($jsonField, $filtered)) {
                continue;
            }
            if (is_array($filtered[$jsonField]) || is_object($filtered[$jsonField])) {
                $filtered[$jsonField] = json_encode($filtered[$jsonField], JSON_UNESCAPED_UNICODE);
                continue;
            }
            if (is_string($filtered[$jsonField])) {
                $trimmed = trim($filtered[$jsonField]);
                if ($trimmed === '') {
                    $filtered[$jsonField] = json_encode([]);
                    continue;
                }
                $decoded = json_decode($trimmed, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $filtered[$jsonField] = json_encode($decoded, JSON_UNESCAPED_UNICODE);
                } else {
                    $parts = array_values(array_filter(array_map('trim', explode(',', $trimmed)), function ($v) {
                        return $v !== '';
                    }));
                    $filtered[$jsonField] = json_encode($parts, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        foreach (['verified_track_record', 'exchange_linked', 'is_public'] as $boolField) {
            if (!array_key_exists($boolField, $filtered)) {
                continue;
            }
            $value = $filtered[$boolField];
            if (is_bool($value)) {
                $filtered[$boolField] = $value ? 1 : 0;
            } elseif (is_numeric($value)) {
                $filtered[$boolField] = ((int) $value) === 1 ? 1 : 0;
            } else {
                $normalized = strtolower(trim((string) $value));
                $filtered[$boolField] = in_array($normalized, ['true', '1', 'yes', 'y', 'on'], true) ? 1 : 0;
            }
        }

        foreach (['copiers_count', 'total_trades', 'risk_score', 'liquidation_events', 'max_copiers'] as $intField) {
            if (array_key_exists($intField, $filtered) && is_numeric($filtered[$intField])) {
                $filtered[$intField] = (int) $filtered[$intField];
            }
        }

        foreach ([
            'aum_usd', 'total_return_pct', 'roi_30d_pct', 'roi_90d_pct', 'profit_factor',
            'max_drawdown_pct', 'volatility_30d', 'sharpe_ratio', 'avg_leverage', 'win_rate_pct',
            'profit_share_pct', 'management_fee_pct', 'min_copy_amount_usd', 'slippage_limit_pct'
        ] as $floatField) {
            if (array_key_exists($floatField, $filtered) && is_numeric($filtered[$floatField])) {
                $filtered[$floatField] = (float) $filtered[$floatField];
            }
        }

        if (empty($filtered)) {
            return $this->response->unprocessableEntity('No valid fields to update.');
        }

        $keys = array_keys($filtered);
        $updated = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            copy_trade,
            $keys,
            $filtered,
            'id',
            $accToken
        );

        if ($updated) {
            $this->response->created("Copy trade profile updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating copy trade profile.');
        }
    }

    public function updateCopyTradeOrderSettings(array $data, string $accToken)
    {
        $copyTradeSettingsColumn = require __DIR__ . '/Config/CopyTradeSettingsColumn.php';
        $allowedFields = array_keys($copyTradeSettingsColumn);

        unset($data['id']);
        $filtered = array_intersect_key($data, array_flip($allowedFields));

        foreach (['traderId'] as $intField) {
            if (array_key_exists($intField, $filtered) && is_numeric($filtered[$intField])) {
                $filtered[$intField] = (int) $filtered[$intField];
            }
        }

        foreach (['amountPerOrder', 'amount', 'stopLoss', 'fixedLeverage', 'slippageRange'] as $floatField) {
            if (array_key_exists($floatField, $filtered) && is_numeric($filtered[$floatField])) {
                $filtered[$floatField] = (float) $filtered[$floatField];
            }
        }

        if (array_key_exists('agree', $filtered)) {
            $value = $filtered['agree'];
            if (is_bool($value)) {
                $filtered['agree'] = $value ? 1 : 0;
            } elseif (is_numeric($value)) {
                $filtered['agree'] = ((int) $value) === 1 ? 1 : 0;
            } else {
                $normalized = strtolower(trim((string) $value));
                $filtered['agree'] = in_array($normalized, ['true', '1', 'yes', 'y', 'on'], true) ? 1 : 0;
            }
        }

        if (empty($filtered)) {
            return $this->response->unprocessableEntity('No valid fields to update.');
        }

        $filtered['updatedAt'] = $data['updatedAt'] ?? date('Y-m-d H:i:s');
        $keys = array_keys($filtered);

        $created = $this->createDbTables->createTableWithTypes(copy_trade_settings, $copyTradeSettingsColumn);
        if (!$created) {
            return $this->response->unprocessableEntity('Could not prepare copy trade settings table.');
        }

        $updated = $this->connectToDataBase->updateDataWithArrayKey(
            $this->dbConnection,
            copy_trade_settings,
            $keys,
            $filtered,
            'id',
            $accToken
        );

        if ($updated) {
            $this->response->created("Copy trade settings updated successfully.");
        } else {
            $this->response->unprocessableEntity('Error updating copy trade settings.');
        }
    }

}


