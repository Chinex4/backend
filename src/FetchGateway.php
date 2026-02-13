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
            if ($user && is_array($user)) {
                $hiddenFields = [
                    'id',
                    'password',
                    'encryptedPassword',
                    'refreshToken',
                    'tokenExpiry',
                    'tokenRevoked',
                    'totp_secret',
                    'accToken',
                    'referral',
                    'BasicVerification',
                    'AdvancedVerification',
                    'InstitutionalVerification',
                    'antiPhishingCode',
                    'withdrawalSecurity',
                    'uid',
                    'currency',
                    'verifyUser',
                    'UserLogin',
                    'AllowLogin',
                    'emailVerication',
                    'lockKey',
                    'lockCopy',
                    'alert',
                    'sendKyc',
                    'SignalMessage',
                    'kyc',
                    'identityNumber',
                    'ipAdress',
                    'totalAsset',
                    'spotAccount',
                    'futureAccount',
                    'earnAccount',
                    'copyAccount',
                    'referralBonus',
                    'Message',
                    'allowMessage',
                    'allowOtp', 
                    'balances_json',
                    'userAgent',
                    'deviceType',
                    'lastLogin',
                ];

                foreach ($hiddenFields as $field) {
                    unset($user[$field]);
                }
            }
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
    public function getCoins()
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
            $user = $this->gateway->fetchAllData(wallet);
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
    public function getBasicKycs()
    {
        $BasicKycs = $this->gateway->fetchAllData(idVer);
        return $this->response->success($BasicKycs);
    }
    public function getAdvancedKycs()
    {
        $advancedVerification = $this->gateway->fetchAllData(advancedVerification);
        return $this->response->success($advancedVerification);
    }
    public function getInstitutionalVerifications()
    {
        $advancedVerification = $this->gateway->fetchAllData(institutionalVerification);
        return $this->response->success($advancedVerification);
    }
    public function getDeposits()
    {
        $deposits = $this->gateway->fetchAllData(deposit);
        return $this->response->success($deposits);
    }
    public function getP2PTraders()
    {

        $traders = $this->gateway->fetchAllData(p2p_traders);
        return $this->response->success($traders);

    }

    public function fetchBalance()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];
        try {
            $decodedPayload = $this->jwtCodec->decode($token);
            $user = $this->gateway->fetchData(RegTable, ['id' => $decodedPayload['sub']]);
            if (!$user || !is_array($user)) {
                return $this->response->unprocessableEntity('User not found.');
            }

            $balances = [];
            if (!empty($user['balances_json'])) {
                $decodedBalances = json_decode($user['balances_json'], true);
                if (is_array($decodedBalances)) {
                    $balances = $decodedBalances;
                }
            }

            return $this->response->success([
                'balances' => $balances,
            ]);
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
    public function getP2POrders()
    {
        $orders = $this->gateway->fetchAllData(p2p_orders);
        return $this->response->success($orders);
    }
    public function getP2PTradersPublic()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];
        try {
            $this->jwtCodec->decode($token);
            $traders = $this->gateway->fetchAllData(p2p_traders);
            if (is_array($traders)) {
                $hidden = ['id', 'createdAt', 'updatedAt'];
                foreach ($traders as $index => $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    foreach ($hidden as $field) {
                        unset($row[$field]);
                    }
                    $traders[$index] = $row;
                }
            }
            return $this->response->success($traders);
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
    public function getP2PTradersPublicOpen()
    {
        $traders = $this->gateway->fetchAllData(p2p_traders);
        if (is_array($traders)) {
            $hidden = ['id', 'createdAt', 'updatedAt'];
            foreach ($traders as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }
                foreach ($hidden as $field) {
                    unset($row[$field]);
                }
                $traders[$index] = $row;
            }
        }
        return $this->response->success($traders);
    }
    public function getP2PAssets()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];
        try {
            $this->jwtCodec->decode($token);
            $assets = [
                'BTC', 'ETH', 'USDT', 'XRP', 'BNB', 'USDC', 'SOL', 'TRX',
                'DOGE', 'BCH', 'ADA', 'HYPE', 'LEO', 'USDe', 'CC'
            ];
            return $this->response->success($assets);
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
    public function getP2POrder($id)
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }
 
        $orderId = $id;
        if (!$orderId) {
            return $this->response->unprocessableEntity('orderId is required.');
        }

        $token = $matches[1];
        try {
            $this->jwtCodec->decode($token);
            $order = $this->gateway->fetchData(p2p_orders, ['orderId' => $orderId]);
            if (!$order) {
                return $this->response->unprocessableEntity('Order not found.');
            }
            return $this->response->success($order);
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
                'label' => $customLabel,
                'qr' => $qrUrl
            ]);

        } catch (\Exception $e) {
            return $this->response->unprocessableEntity("Token decode or 2FA error: " . $e->getMessage());
        }
    }



}
