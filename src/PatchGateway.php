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

    public function approveAdvancedKyc(string $accToken, array $data)
    {
        $createColumn = $this->createDbTables->createTable(advancedVerification, ['status']);
        if ($createColumn) {
            $updated = $this->connectToDataBase->updateData($this->dbConnection, advancedVerification, ['status', 'updatedAt'], ['Verified', $data['createdAt']], 'id', $accToken);
            if ($updated) {
                $advancedVerificationData = $this->gateway->fetchData(advancedVerification, ['id' => $accToken]);
                $updateUSerKyc = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['AdvancedVerification'], ['Verified'], 'accToken', $advancedVerificationData['userId']);
                if ($updateUSerKyc) {
                    $this->response->created("Advanced KYC has been approved for this user.");
                } else {
                    $this->response->unprocessableEntity('Advanced KYC has been disapproved');
                }
            } else {
                $this->response->unprocessableEntity('Advanced KYC has been disapproved');
            }
        }
    }
    public function approveInstitution(string $accToken, array $data)
    {
        $createColumn = $this->createDbTables->createTable(institutionalVerification, ['status']);
        if ($createColumn) {
            $updated = $this->connectToDataBase->updateData($this->dbConnection, institutionalVerification, ['status', 'updatedAt'], ['Verified', $data['createdAt']], 'id', $accToken);
            if ($updated) {
                $institutionalVerificationData = $this->gateway->fetchData(institutionalVerification, ['id' => $accToken]);
                $updateUSerKyc = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['InstitutionalVerification'], ['Verified'], 'accToken', $institutionalVerificationData['UserId']);
                if ($updateUSerKyc) {
                    $this->response->created("Institutional Verification has been approved for this user.");
                } else {
                    $this->response->unprocessableEntity('Institutional Verification has been disapproved');
                }
            } else {
                $this->response->unprocessableEntity('Institutional Verification has been disapproved');
            }
        }
    }
    public function approveKyc(string $accToken, array $data)
    {
        $createColumn = $this->createDbTables->createTable(idVer, ['status']);
        if ($createColumn) {
            $updated = $this->connectToDataBase->updateData($this->dbConnection, idVer, ['status', 'updatedAt'], ['Verified', $data['createdAt']], 'id', $accToken);
            if ($updated) {
                $idVerificationData = $this->gateway->fetchData(idVer, ['id' => $accToken]);
                $updateUSerKyc = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['BasicVerification'], ['Verified'], 'accToken', $idVerificationData['userId']);
                if ($updateUSerKyc) {
                    $this->response->created("KYC has been approved for this user.");
                } else {
                    $this->response->unprocessableEntity('KYC has been disapproved');
                }
            } else {
                $this->response->unprocessableEntity('KYC has been disapproved');
            }

        }
    }
    public function disapproveAdvancedKyc(string $accToken, array $data)
    {
        $createColumn = $this->createDbTables->createTable(advancedVerification, ['status']);
        if ($createColumn) {
            $updated = $this->connectToDataBase->updateData($this->dbConnection, advancedVerification, ['status', 'updatedAt'], ['Disapproved', $data['createdAt']], 'id', $accToken);
            if ($updated) {
                $advancedVerificationData = $this->gateway->fetchData(advancedVerification, ['id' => $accToken]);
                
                $updateUSerKyc = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['AdvancedVerification'], ['Disapproved'], 'accToken', $advancedVerificationData['userId']);
                if ($updateUSerKyc) {
                    $this->response->created("Advanced KYC has been disapproved for this user.");
                }
            } else {
                $this->response->unprocessableEntity('Advanced KYC has been approved');
            }
        }
    }
    public function rejectInstitution(string $accToken, array $data)
    {
        $createColumn = $this->createDbTables->createTable(institutionalVerification, ['status']);
        if ($createColumn) {
            $updated = $this->connectToDataBase->updateData($this->dbConnection, institutionalVerification, ['status', 'updatedAt'], ['Disapproved', $data['createdAt']], 'id', $accToken);
            if ($updated) {
                $institutionalVerificationData = $this->gateway->fetchData(institutionalVerification, ['id' => $accToken]);
                
                $updateUSerKyc = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['institutionalVerification'], ['Disapproved'], 'accToken', $institutionalVerificationData['UserId']);
                if ($updateUSerKyc) {
                    $this->response->created("Institutional Verification has been disapproved for this user.");
                }
            } else {
                $this->response->unprocessableEntity('Institutional Verification has been approved');
            }
        }
    }
    public function disapproveKyc(string $accToken, array $data)
    {
        $createColumn = $this->createDbTables->createTable(idVer, ['status']);
        if ($createColumn) {
            $updated = $this->connectToDataBase->updateData($this->dbConnection, idVer, ['status', 'updatedAt'], ['Disapproved', $data['createdAt']], 'id', $accToken);
            if ($updated) {
                $idVerificationData = $this->gateway->fetchData(idVer, ['id' => $accToken]);
                $updateUSerKyc = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['BasicVerification'], ['Disapproved'], 'accToken', $idVerificationData['userId']);
                if ($updateUSerKyc) {
                    $this->response->created("KYC has been disapproved for this user.");
                }
            } else {
                $this->response->unprocessableEntity('KYC has been approved');
            }

        }
    }
    public function disapproveDeposit(string $accToken, array $data)
    {
        $createColumn = $this->createDbTables->createTable(deposit, ['status', 'updatedAt', 'reviewedAt']);
        if ($createColumn) {
            $date = $data['actionDate'] ?? date('Y-m-d H:i:s');
            $updated = $this->connectToDataBase->updateData(
                $this->dbConnection,
                deposit,
                ['status', 'updatedAt', 'reviewedAt'],
                ['Disapproved', $date, $date],
                'id',
                $accToken
            );
            if ($updated) {
                $depositData = $this->gateway->fetchData(deposit, ['id' => $accToken]);
                if ($depositData && isset($depositData['userId'])) {
                    $user = $this->gateway->fetchData(RegTable, ['accToken' => $depositData['userId']]);
                    if ($user && isset($user['id'])) {
                        $this->gateway->createNotificationMessage(
                            $user['id'],
                            'Deposit Disapproved',
                            'Your deposit has been disapproved. Please contact support for more information.',
                            $date
                        );
                    }
                }
                $this->response->created("Deposit has been disapproved for this user.");
            } else {
                $this->response->unprocessableEntity('Deposit has been approved');
            }
        }
    }
    public function approveDeposit(string $accToken, array $data)
    {
        $createColumn = $this->createDbTables->createTable(deposit, ['status', 'updatedAt', 'reviewedAt', 'confirmedAt']);
        if ($createColumn) {
            $date = $data['actionDate'] ?? date('Y-m-d H:i:s');
            $updated = $this->connectToDataBase->updateData(
                $this->dbConnection,
                deposit,
                ['status', 'updatedAt', 'reviewedAt', 'confirmedAt'],
                ['Approved', $date, $date, $date],
                'id',
                $accToken
            );
            if ($updated) {
                $depositData = $this->gateway->fetchData(deposit, ['id' => $accToken]);
                if ($depositData && isset($depositData['userId'])) {
                    $user = $this->gateway->fetchData(RegTable, ['accToken' => $depositData['userId']]);
                    if ($user && isset($user['id'])) {
                        $balances = [];
                        if (!empty($user['balances_json'])) {
                            $decodedBalances = json_decode($user['balances_json'], true);
                            if (is_array($decodedBalances)) {
                                $balances = $decodedBalances;
                            }
                        }

                        $network = $depositData['network'] ?? null;
                        $amountUsd = isset($depositData['amount_usd']) ? (float) $depositData['amount_usd'] : 0;
                        $coinAmount = $depositData['coin_amount'] ?? 0;

                        if ($network) {
                            $found = false;
                            foreach ($balances as $index => $item) {
                                if (isset($item['id']) && $item['id'] === $network) {
                                    $currentBalance = isset($item['balance']) ? (float) $item['balance'] : 0;
                                    $balances[$index]['balance'] = $currentBalance + $amountUsd;
                                    $balances[$index]['price'] = $coinAmount;
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                $balances[] = [
                                    'id' => $network,
                                    'balance' => $amountUsd,
                                    'price' => $coinAmount,
                                ];
                            }
                        }

                        $totalAsset = isset($user['totalAsset']) ? (float) $user['totalAsset'] : 0;
                        $spotAccount = isset($user['spotAccount']) ? (float) $user['spotAccount'] : 0;

                        $newTotalAsset = $totalAsset + $amountUsd;
                        $newSpotAccount = $spotAccount + $amountUsd;

                        $this->connectToDataBase->updateData(
                            $this->dbConnection,
                            RegTable,
                            ['totalAsset', 'spotAccount', 'balances_json'],
                            [$newTotalAsset, $newSpotAccount, json_encode($balances, JSON_UNESCAPED_SLASHES)],
                            'id',
                            $user['id']
                        );

                        $this->gateway->createNotificationMessage(
                            $user['id'],
                            'Deposit Approved',
                            'Your deposit has been approved and credited to your account.',
                            $date
                        );
                    }
                }
                $this->response->created("Deposit has been approved for this user.");
            } else {
                $this->response->unprocessableEntity('Deposit has been disapproved');
            }
        }
    }

    public function enableOtp(string $accToken)
    {
        $createColumn = $this->createDbTables->createTable(RegTable, ['allowOtp']);
        if ($createColumn) {
            $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['allowOtp'], ['true'], 'accToken', $accToken);
            if ($updated) {
                $this->response->created("OTP login has been enabled for this user.");
            } else {
                $this->response->unprocessableEntity('error enabling OTP login');
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
                $this->response->unprocessableEntity('error disabling OTP login');
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
            $this->response->unprocessableEntity('error enabling user login');
        }
    }
    public function updateNickname(array $data)
    {

        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }
        $token = $matches[1];
        try {
            $decodedPayload = $this->jwtCodec->decode($token);
            $userid = $decodedPayload['sub'];
            $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['username'], [$data['nickname']], 'id', $userid);
            if ($updated) {
                $this->response->created("Username updated successfully");
            } else {
                $this->response->unprocessableEntity('Failed to update username. Please try again.');
            }
            // return $this->response->success(['userDetails' => $user]);
        } catch (InvalidArgumentException $e) {
            return $this->response->unauthorized("Invalid token format.");
        } catch (InvalidSignatureException $e) {
            return $this->response->unauthorized("Invalid token signature.");
        } catch (TokenExpiredException $e) {
            return $this->response->unauthorized("Token has expired.");
        } catch (Exception $e) {
            return $this->response->unauthorized("Token decode error: " . $e->getMessage());
        }



    }
    public function updateLanguage(array $data)
    {

        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }
        $token = $matches[1];
        try {
            $decodedPayload = $this->jwtCodec->decode($token);
            $userid = $decodedPayload['sub'];
            $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['language'], [$data['language']], 'id', $userid);
            if ($updated) {
                $this->response->created("Username updated successfully");
            } else {
                $this->response->unprocessableEntity('Failed to update username. Please try again.');
            }
            // return $this->response->success(['userDetails' => $user]);
        } catch (InvalidArgumentException $e) {
            return $this->response->unauthorized("Invalid token format.");
        } catch (InvalidSignatureException $e) {
            return $this->response->unauthorized("Invalid token signature.");
        } catch (TokenExpiredException $e) {
            return $this->response->unauthorized("Token has expired.");
        } catch (Exception $e) {
            return $this->response->unauthorized("Token decode error: " . $e->getMessage());
        }



    }
    public function disableGoogleAuth()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }
        $token = $matches[1];
        try {
            $decodedPayload = $this->jwtCodec->decode($token);
            $userid = $decodedPayload['sub'];
            $updated = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['isGoogleAUthEnabled'], [null], 'id', $userid);
            if ($updated) {
                $this->response->created("true");
            } else {
                $this->response->unprocessableEntity('Failed to update.');
            }
            // return $this->response->success(['userDetails' => $user]);
        } catch (InvalidArgumentException $e) {
            return $this->response->unauthorized("Invalid token format.");
        } catch (InvalidSignatureException $e) {
            return $this->response->unauthorized("Invalid token signature.");
        } catch (TokenExpiredException $e) {
            return $this->response->unauthorized("Token has expired.");
        } catch (Exception $e) {
            return $this->response->unauthorized("Token decode error: " . $e->getMessage());
        }



    }

    public function confirmP2POrder(array $data, string $orderId, array $file = []): void
    {
        
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->response->unauthorized("Authorization header missing or invalid");
            return;
        }

        $orderId = trim($orderId ?: ($data['orderId'] ?? ''));
        if ($orderId === '') {
            $this->response->unprocessableEntity('orderId is required.');
            return;
        }

        try {
            $decodedPayload = $this->jwtCodec->decode($matches[1]);
            $userId = (string) ($decodedPayload['sub'] ?? '');
            if ($userId === '') {
                $this->response->unauthorized("Invalid token payload.");
                return;
            }

            $order = $this->gateway->fetchData(p2p_orders, ['orderId' => $orderId]);
            if (!$order || !is_array($order)) {
                $this->response->unprocessableEntity('Order not found.');
                return;
            }

            if ((string) ($order['userId'] ?? '') !== $userId) {
                $this->response->forbidden('You are not allowed to confirm this order.');
                return;
            }

            $currentStatus = (string) ($order['status'] ?? '');
            if (in_array($currentStatus, ['Released', 'Completed'], true)) {
                $this->response->unprocessableEntity('This order can no longer be confirmed.');
                return;
            }

            $confirmedAt = $data['confirmedAt'] ?? date('Y-m-d H:i:s');
            $updatedAt = $data['updatedAt'] ?? $confirmedAt;

            $uploadedImages = [];
            foreach ($file as $fileKey => $fileValue) {
                if (!is_array($fileValue) || !isset($fileValue['name'])) {
                    continue;
                }

                // Handles multi-file format: $_FILES['documents']['name'][0...]
                if (is_array($fileValue['name'])) {
                    foreach ($fileValue['name'] as $index => $name) {
                        $singleFile = [
                            'name' => $name,
                            'type' => $fileValue['type'][$index] ?? '',
                            'tmp_name' => $fileValue['tmp_name'][$index] ?? '',
                            'error' => $fileValue['error'][$index] ?? 4,
                            'size' => $fileValue['size'][$index] ?? 0,
                        ];
                        $uploaded = $this->gateway->processImageWithgivenNameFiles($singleFile);
                        if (is_string($uploaded) && $uploaded !== '') {
                            $uploadedImages[] = $uploaded;
                        }
                    }
                    continue;
                }

                // Handles top-level keyed files: image_0, image_1, paymentProof, etc.
                $uploaded = $this->gateway->processImageWithgivenNameFiles($fileValue);
                if (is_string($uploaded) && $uploaded !== '') {
                    $uploadedImages[] = $uploaded;
                }
            }
            $paymentTiming = null;
            if (isset($data['paymentTiming'])) {
                if (is_array($data['paymentTiming']) || is_object($data['paymentTiming'])) {
                    $paymentTiming = json_encode($data['paymentTiming'], JSON_UNESCAPED_SLASHES);
                } elseif (is_string($data['paymentTiming']) && trim($data['paymentTiming']) !== '') {
                    $paymentTiming = $data['paymentTiming'];
                }
            } elseif (isset($data['paidAt']) || isset($data['paidTime']) || isset($data['paymentAt'])) {
                $paymentTiming = json_encode(
                    [
                        'paidAt' => $data['paidAt'] ?? $data['paidTime'] ?? $data['paymentAt'],
                        'confirmedAt' => $confirmedAt,
                    ],
                    JSON_UNESCAPED_SLASHES
                );
            }

            $columns = ['status', 'confirmedAt', 'updatedAt'];
            $values = ['Confirmed', $confirmedAt, $updatedAt];

            if (!empty($uploadedImages)) {
                $columns[] = 'uploadedImages';
                $values[] = json_encode(array_values($uploadedImages), JSON_UNESCAPED_SLASHES);
            } elseif (isset($data['uploadedImages'])) {
                $columns[] = 'uploadedImages';
                if (is_array($data['uploadedImages']) || is_object($data['uploadedImages'])) {
                    $values[] = json_encode($data['uploadedImages'], JSON_UNESCAPED_SLASHES);
                } else {
                    $values[] = $data['uploadedImages'];
                }
            }

            if ($paymentTiming !== null) {
                $columns[] = 'paymentTiming';
                $values[] = $paymentTiming;
            }

            $createColumn = $this->createDbTables->createTable(p2p_orders, $columns);
            if (!$createColumn) {
                $this->response->unprocessableEntity('Could not prepare P2P order table.');
                return;
            }

            $updated = $this->connectToDataBase->updateData(
                $this->dbConnection,
                p2p_orders,
                $columns,
                $values,
                'orderId',
                $orderId
            );

            if (!$updated) {
                $this->response->unprocessableEntity('Could not confirm this order.');
                return;
            }

            $this->gateway->createNotificationMessage(
                (int) $userId,
                'P2P Order Confirmed',
                'Your payment confirmation has been submitted. Please wait for release.',
                $confirmedAt
            );

            $this->response->created([
                'message' => 'Order confirmed successfully.',
                'orderId' => $orderId,
                'status' => 'Confirmed',
            ]);
        } catch (InvalidArgumentException $e) {
            $this->response->unauthorized("Invalid token format.");
        } catch (InvalidSignatureException $e) {
            $this->response->unauthorized("Invalid token signature.");
        } catch (TokenExpiredException $e) {
            $this->response->unauthorized("Token has expired.");
        } catch (Exception $e) {
            $this->response->unprocessableEntity("Token decode error: " . $e->getMessage());
        }
    }





    

    public function releaseP2POrder(string $orderId, ?array $data = null): void
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            $this->response->unprocessableEntity('orderId is required.');
            return;
        }

        $payload = is_array($data) ? $data : [];
        $releasedAt = $payload['actionDate'] ?? $payload['releasedAt'] ?? date('Y-m-d H:i:s');

        $order = $this->gateway->fetchData(p2p_orders, ['orderId' => $orderId]);
        if (!$order || !is_array($order)) {
            $this->response->unprocessableEntity('Order not found.');
            return;
        }

        $currentStatus = (string) ($order['status'] ?? '');
        if ($currentStatus === 'Released') {
            $this->response->created('P2P order is already released.');
            return;
        }
        if ($currentStatus === 'Cancelled') {
            $this->response->unprocessableEntity('Cancelled order cannot be released.');
            return;
        }

        $targetUserId = (string) ($order['userId'] ?? '');
        if ($targetUserId === '') {
            $this->response->unprocessableEntity('Invalid order owner.');
            return;
        }

        $targetUser = $this->gateway->fetchData(RegTable, ['id' => $targetUserId]);
        if (!$targetUser || !is_array($targetUser)) {
            $this->response->unprocessableEntity('Order owner not found.');
            return;
        }

        $orderPrice = isset($order['price']) ? (float) $order['price'] : 0.0;
        $cryptoAmount = isset($order['cryptoAmount']) ? (float) $order['cryptoAmount'] : 0.0;
        $orderValue = $orderPrice * $cryptoAmount;
        if ($orderValue <= 0 && isset($order['fiatAmount'])) {
            $orderValue = (float) $order['fiatAmount'];
        }

        $coinId = $this->resolveCoinIdForP2P((string) ($order['coin'] ?? ''));
        $balances = [];
        if (!empty($targetUser['balances_json'])) {
            $decodedBalances = json_decode($targetUser['balances_json'], true);
            if (is_array($decodedBalances)) {
                $balances = $decodedBalances;
            }
        }

        if ($coinId !== null) {
            $found = false;
            foreach ($balances as $index => $item) {
                if (($item['id'] ?? null) === $coinId) {
                    $currentBalance = isset($item['balance']) ? (float) $item['balance'] : 0.0;
                    $balances[$index]['balance'] = $currentBalance + $orderValue;
                    $balances[$index]['price'] = $cryptoAmount;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $balances[] = [
                    'id' => $coinId,
                    'balance' => $orderValue,
                    'price' => $cryptoAmount,
                ];
            }
        }

        $createUserColumns = $this->createDbTables->createTable(RegTable, ['totalAsset', 'spotAccount', 'balances_json']);
        if (!$createUserColumns) {
            $this->response->unprocessableEntity('Could not prepare user table.');
            return;
        }

        $currentTotalAsset = isset($targetUser['totalAsset']) ? (float) $targetUser['totalAsset'] : 0.0;
        $currentSpotAccount = isset($targetUser['spotAccount']) ? (float) $targetUser['spotAccount'] : 0.0;
        $newTotalAsset = $currentTotalAsset + $orderValue;
        $newSpotAccount = $currentSpotAccount + $orderValue;

        $updatedUser = $this->connectToDataBase->updateData(
            $this->dbConnection,
            RegTable,
            ['totalAsset', 'spotAccount', 'balances_json'],
            [$newTotalAsset, $newSpotAccount, json_encode($balances, JSON_UNESCAPED_SLASHES)],
            'id',
            $targetUserId
        );

        if (!$updatedUser) {
            $this->response->unprocessableEntity('Could not update user balances.');
            return;
        }

        $columns = ['status', 'userRelease', 'releasedAt', 'updatedAt'];
        $values = ['Released', $payload['userRelease'] ?? 'true', $releasedAt, $releasedAt];

        $createColumn = $this->createDbTables->createTable(p2p_orders, $columns);
        if (!$createColumn) {
            $this->response->unprocessableEntity('Could not prepare P2P order table.');
            return;
        }

        $updated = $this->connectToDataBase->updateData(
            $this->dbConnection,
            p2p_orders,
            $columns,
            $values,
            'orderId',
            $orderId
        );

        if (!$updated) {
            $this->response->unprocessableEntity('Error releasing P2P order.');
            return;
        }

        $ownerId = isset($order['userId']) ? (int) $order['userId'] : 0;
        if ($ownerId > 0) {
            $this->gateway->createNotificationMessage(
                $ownerId,
                'P2P Order Released',
                'Your P2P order has been released successfully.',
                $releasedAt
            );
        }

        $this->response->created([
            'message' => 'P2P order released successfully.',
            'orderId' => $orderId,
            'status' => 'Released',
        ]);
    }

    private function resolveCoinIdForP2P(string $symbol): ?string
    {
        $symbol = strtoupper(trim($symbol));
        if ($symbol === '') {
            return null;
        }

        $walletRow = $this->gateway->fetchData(wallet, ['symbol' => $symbol]);
        if (is_array($walletRow) && !empty($walletRow['coin_id'])) {
            return $walletRow['coin_id'];
        }

        $fallback = [
            'BTC' => 'bitcoin',
            'ETH' => 'ethereum',
            'USDT' => 'tether',
            'USDC' => 'usd-coin',
            'XRP' => 'ripple',
            'BNB' => 'binancecoin',
            'SOL' => 'solana',
            'TRX' => 'tron',
            'DOGE' => 'dogecoin',
            'BCH' => 'bitcoin-cash',
            'ADA' => 'cardano',
        ];

        return $fallback[$symbol] ?? null;
    }


}


