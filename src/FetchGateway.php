<?php
require_once 'JWTCodec.php';

class FetchGateway
{
    private $pdovar;
    private $gateway;
    private $response;
    private $jwtCodec;

    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection;
        $this->gateway = new TaskGatewayFunction($this->pdovar);
        $secretKey = $_ENV['SECRET_KEY'];
        $this->jwtCodec = new JWTCodec($secretKey);
        $this->response = new JsonResponse();
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    // public function fetchuser($id)
    // {
    //     $condForfetch = ['id' => $id];
    //     $fetchuser = $this->gateway->fetchData(RegTable, $condForfetch);
    //     return $this->response->success(['userDetails' => $fetchuser]);
    // }

    public function fetchUserWithToken()
    {
        
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1]; 
        try {
            $decodedPayload = $this->jwtCodec->decode($token);
            // Fetch user by ID
            $user = $this->gateway->fetchData(RegTable, ['id' => $decodedPayload['sub']]);
            return $this->response->success(['userDetails' => $user]);
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

    public function fetchAlluser()
    {
        $fetchuser = $this->gateway->fetchAllData(RegTable);
        return $this->response->success(['userDetails' => $fetchuser]);
    }
}
