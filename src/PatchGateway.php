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

    public function approveKyc(string $accToken, array $data)
    {
        var_dump($accToken);
        $createColumn = $this->createDbTables->createTable(idVer, ['status']);
        if ($createColumn) {
            $updated = $this->connectToDataBase->updateData($this->dbConnection, idVer, ['status', 'updatedAt'], ['Verified', $data['createdAt']], 'id', $accToken);
            if ($updated) {
                // $this->response->created("KYC has been approved for this user.");  
                $updateUSerKyc = $this->connectToDataBase->updateData($this->dbConnection, RegTable, ['BasicVerification'], ['Verified'], 'id', $accToken);
            if ($updateUSerKyc) {
                // $this->response->created("KYC has been approved for this user.");
            } else {
                $this->response->unprocessableEntity('KYC has been disapproved');
            }
            } else {
                $this->response->unprocessableEntity('KYC has been disapproved');
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
            $userid =  $decodedPayload['sub'];
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
            $userid =  $decodedPayload['sub'];
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
            $userid =  $decodedPayload['sub'];
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


}


