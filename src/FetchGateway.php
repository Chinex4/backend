<?php
require_once 'JWTCodec.php';
use OTPHP\TOTP;
use Symfony\Component\Clock\NativeClock;
use Base32\Base32;

class FetchGateway
{
    private $dbConnection;
    private $gateway;
    private $response;
    private $jwtCodec;
    private $createDbTables;
    private $connectToDataBase;

    public function __construct($pdoConnection)
    {
        $this->dbConnection = $pdoConnection;
        $this->gateway = new TaskGatewayFunction($this->dbConnection);
        $secretKey = $_ENV['SECRET_KEY'];
        $this->jwtCodec = new JWTCodec($secretKey);
        $this->response = new JsonResponse();
        $this->createDbTables = new CreateDbTables($this->dbConnection);
        $this->connectToDataBase = new Database();
    }

    public function __destruct()
    {
        $this->dbConnection = null;
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
    public function fetchWallets()
    {
        $wallet = $this->gateway->fetchAllData(wallet);
        return $this->response->success($wallet);
    }

    public function generate2Fa()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }
    
        $token = $matches[1];
    
        try {
            $decodedPayload = $this->jwtCodec->decode($token);
            $userId = $decodedPayload['sub'];
    
            // Fetch user details
            $user = $this->gateway->fetchData(RegTable, ['id' => $userId]);
            if (!$user || !isset($user['email'])) {
                return $this->response->unprocessableEntity("User not found or missing email.");
            }
    
            // Get user input
            $input = json_decode(file_get_contents('php://input'), true);
            $customLabel = !empty($input['label']) ? trim($input['label']) : 'CashTradePro';
    
            // Step 1: Generate 80-bit secret (10 bytes)
            $rawSecret = random_bytes(10);
            $base32 = strtoupper(Base32::encode($rawSecret));
    
            // Step 2: Create TOTP object
            $clock = new NativeClock();
            $totp = TOTP::create(
                $base32,  // secret
                30,       // period
                'sha1',   // digest
                6,        // digits
                0,        // epoch
                $clock    // PSR-20 clock
            );
    
            // Step 3: Build otpauth URI with custom label
            $issuer = 'CashTradePro';
            $label = rawurlencode("{$issuer}:{$user['name']}");
            $issuerEncoded = rawurlencode($issuer);
            $provisioningUri = "otpauth://totp/{$label}?secret={$base32}&issuer={$issuerEncoded}&algorithm=SHA1&digits=6&period=30";
    
            // Step 4: Generate QR via QuickChart
            $qrUrl = 'https://quickchart.io/qr?text=' . urlencode($provisioningUri);
    
            // Step 5: Ensure DB column exists
            $createColumn = $this->createDbTables->createTable(RegTable, ['totp_secret']);
            if ($createColumn === false) {
                return $this->response->unprocessableEntity("Failed to create or validate totp_secret column.");
            }
    
            // Step 6: Save to database
            $saved = $this->connectToDataBase->updateData(
                $this->dbConnection,
                RegTable,
                ['totp_secret'],
                [$base32],
                'id',
                $userId
            );
    
            if (!$saved) {
                return $this->response->unprocessableEntity("Failed to save 2FA secret.");
            }
    
            // Step 7: Return success response
            return $this->response->success([
                'secret' => $base32,
                'label'  => $customLabel,
                'qr'     => $qrUrl
            ]);
    
        } catch (\Exception $e) {
            return $this->response->unprocessableEntity("Token decode or 2FA error: " . $e->getMessage());
        }
    }
    
    

}
