<?php
require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../EmailSender.php';
require_once __DIR__ . '/Generators/UserDataGenerator.php';
require_once __DIR__ . '/Generators/EmailDataGenerator.php';

use Services\Generators\UserDataGenerator;
use Services\Generators\EmailDataGenerator;

class AdminAuthService
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
    private $p2pTraderColumn;

    public function __construct($pdoConnection)
    {
        $this->dbConnection = $pdoConnection;
        $this->regUsercolumns = require __DIR__ . '/../Config/UserColumns.php';
        $this->EmailCoulmn = require __DIR__ . '/../Config/EmailCoulmn.php';
        $this->ForgotPasswordColumns = require __DIR__ . '/../Config/ForgotPasswordColumns.php';
        $this->p2pTraderColumn = require __DIR__ . '/../Config/P2PTraderColumn.php';
        $this->gateway = new TaskGatewayFunction($this->dbConnection);
        $this->userDataGenerator = new UserDataGenerator($this->gateway);
        $this->EmailDataGenerator = new EmailDataGenerator($this->gateway);
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

    private function cleanString($value, ?int $maxLen = null): ?string
    {
        if ($value === null) {
            return null;
        }
        $clean = trim((string) $value);
        if ($clean === '') {
            return null;
        }
        if ($maxLen !== null) {
            return mb_substr($clean, 0, $maxLen);
        }
        return $clean;
    }

    private function toDbBoolean($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 'true' : 'false';
        }
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['true', '1', 'yes', 'y'], true) ? 'true' : 'false';
    }

    private function toIntOrDefault($value, int $default = 0): int
    {
        if ($value === null || $value === '') {
            return $default;
        }
        if (!is_numeric($value)) {
            return $default;
        }
        return (int) $value;
    }

    private function toFloatOrNull($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return (float) $value;
    }

    private function toJsonArray($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            return json_encode(array_values(array_filter(array_map('trim', explode(',', $value)), fn($v) => $v !== '')));
        }
        if (is_array($value)) {
            return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        }
        return null;
    }

    private function toJsonObject($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        return null;
    }

    
  
    public function adminLogin(array $data)
    {
        $adminMail = $data['email'];
        $adminPassword = $data['password'];
        $conditions = ['adminMal' => $adminMail];
        $fetchAdmin = $this->gateway->fetchData(admintable, $conditions);
        if ($fetchAdmin) {
            $password = $fetchAdmin['Password'];
            if ($adminPassword === $password) {
                $_SESSION['AdminSession'] = $fetchAdmin['id'];
                $response = ["message" => "your account has been logged in successful"];
                $this->response->created(array_merge(['id' => $fetchAdmin['id']], $response));
            } else {
                    $this->response->unprocessableEntity('incorrect password try again');
            }
        } else {
                $this->response->unprocessableEntity('email address is wrong try again with a correct enail');
        }
    }
    public function resendVerification(array $data)
    {
        $emailData = ['createdAt' => $data['createdAt'], 'email' => $data['email']];
        $EmailValData = $this->EmailDataGenerator->generateVerificationData($emailData);
        $fetchUserCondition = ['accToken' => $data['userId']];
        $fetchUser = $this->gateway->fetchData(RegTable, $fetchUserCondition); 
        $username = $fetchUser['name'];
        $bindingArrayforEmailVal = $this->gateway->generateRandomStrings($EmailValData);
        $createEmailVal = $this->createDbTables->createTableWithTypes(EmailValidation, $this->EmailCoulmn);
        if ($createEmailVal) {
            $createEmailValTable = $this->connectToDataBase->insertDataWithTypes($this->dbConnection, EmailValidation, $this->EmailCoulmn, $bindingArrayforEmailVal, $EmailValData);
            if ($createEmailValTable) {
            }
        }
    }

    public function addP2PTrader(array $data)
    {
        $name = $this->cleanString($data['name'] ?? null, 100);
        $username = $this->cleanString($data['username'] ?? null, 100);
        $adType = $this->cleanString($data['adType'] ?? null, 20);
        $status = $this->cleanString($data['status'] ?? 'Active', 50);
        $asset = $this->cleanString($data['asset'] ?? null, 20);
        $fiatCurrency = $this->cleanString($data['fiatCurrency'] ?? null, 10);

        if (!$name || !$username || !$adType || !$status) {
            return $this->response->unprocessableEntity('Missing required fields: name, username, adType, status.');
        }

        $createdAt = $data['createdAt'] ?? date('Y-m-d H:i:s');
        $traderId = $this->gateway->generateRandomCode();
        $paymentMethods = $this->toJsonArray($data['paymentMethods'] ?? null);
        $paymentDetails = $this->toJsonObject($data['paymentDetails'] ?? null);
        $allowedRegions = $this->toJsonArray($data['allowedRegions'] ?? null);
        $blockedRegions = $this->toJsonArray($data['blockedRegions'] ?? null);

        $p2pData = [
            'name' => $name,
            'username' => ltrim($username, '@'),
            'traderId' => $traderId,
            'merchantType' => $this->cleanString($data['merchantType'] ?? 'individual', 20),
            'verified' => $this->cleanString($data['verified'] ?? 'Yes', 10),
            'emailVerified' => $this->toDbBoolean($data['emailVerified'] ?? true),
            'smsVerified' => $this->toDbBoolean($data['smsVerified'] ?? true),
            'idVerified' => $this->toDbBoolean($data['idVerified'] ?? true),
            'topSeller' => $this->toDbBoolean($data['topSeller'] ?? false),
            'asset' => $asset,
            'fiatCurrency' => $fiatCurrency,
            'priceType' => $this->cleanString($data['priceType'] ?? 'fixed', 10),
            'priceValue' => $this->toFloatOrNull($data['priceValue'] ?? null),
            'priceMargin' => $this->toFloatOrNull($data['priceMargin'] ?? null),
            'referencePriceSource' => $this->cleanString($data['referencePriceSource'] ?? null, 30),
            'completion' => $this->cleanString($data['completion'] ?? null, 10),
            'orders' => $this->toIntOrDefault($data['orders'] ?? null, 0),
            'price' => $this->cleanString($data['price'] ?? null, 50),
            'limits' => $this->cleanString($data['limits'] ?? null, 50),
            'minLimit' => $this->toFloatOrNull($data['minLimit'] ?? null),
            'maxLimit' => $this->toFloatOrNull($data['maxLimit'] ?? null),
            'quantity' => $this->cleanString($data['quantity'] ?? null, 50),
            'availableQuantity' => $this->toFloatOrNull($data['availableQuantity'] ?? null),
            'avgRelease' => $this->cleanString($data['avgRelease'] ?? null, 50),
            'avgReleaseMinutes' => $this->toIntOrDefault($data['avgReleaseMinutes'] ?? null, 15),
            'orderTimeLimitMinutes' => $this->toIntOrDefault($data['orderTimeLimitMinutes'] ?? null, 15),
            'payment' => $this->cleanString($data['payment'] ?? null),
            'paymentMethods' => $paymentMethods,
            'paymentDetails' => $paymentDetails,
            'country' => $this->cleanString($data['country'] ?? null, 10),
            'allowedRegions' => $allowedRegions,
            'blockedRegions' => $blockedRegions,
            'kycRequired' => $this->cleanString($data['kycRequired'] ?? 'none', 20),
            'kycTierRequired' => $this->cleanString($data['kycTierRequired'] ?? null, 20),
            'complianceNote' => $this->cleanString($data['complianceNote'] ?? null),
            'status' => $status,
            'isOnline' => $this->toDbBoolean($data['isOnline'] ?? true),
            'isHidden' => $this->toDbBoolean($data['isHidden'] ?? false),
            'isDeleted' => $this->toDbBoolean($data['isDeleted'] ?? false),
            'lastActive' => $this->cleanString($data['lastActive'] ?? null, 50),
            'adType' => $adType,
            'terms' => $this->cleanString($data['terms'] ?? null),
            'paymentWindow' => $this->toIntOrDefault($data['paymentWindow'] ?? null, 15),
            'rating' => $this->toFloatOrNull($data['rating'] ?? 0) ?? 0,
            'ratingCount' => $this->toIntOrDefault($data['ratingCount'] ?? null, 0),
            'thirtyDayVolume' => $this->toFloatOrNull($data['thirtyDayVolume'] ?? 0) ?? 0,
            'thirtyDayTrades' => $this->toIntOrDefault($data['thirtyDayTrades'] ?? null, 0),
            'cancelRate' => $this->toFloatOrNull($data['cancelRate'] ?? 0) ?? 0,
            'appealRate' => $this->toFloatOrNull($data['appealRate'] ?? 0) ?? 0,
            'approvedBy' => $this->cleanString($data['approvedBy'] ?? null, 100),
            'approvedAt' => $this->cleanString($data['approvedAt'] ?? null, 50),
            'updatedBy' => $this->cleanString($data['updatedBy'] ?? null, 100),
            'createdAt' => $createdAt,
            'updatedAt' => null,
        ];

        $createTable = $this->createDbTables->createTableWithTypes(p2p_traders, $this->p2pTraderColumn);
        if (!$createTable) {
            return $this->response->unprocessableEntity('Could not create P2P trader table.');
        }

        $bindingArray = $this->gateway->generateRandomStrings($p2pData);
        $inserted = $this->connectToDataBase->insertDataWithTypes(
            $this->dbConnection,
            p2p_traders,
            $this->p2pTraderColumn,
            $bindingArray,
            $p2pData
        );

        if (!$inserted) {
            return $this->response->unprocessableEntity('Could not save P2P trader.');
        }

        $created = $this->gateway->fetchData(p2p_traders, ['traderId' => $traderId]);

        $notifyUserId = isset($data['userId']) ? $data['userId'] : 0;
        $this->gateway->createNotificationMessage(
            $notifyUserId,
            'P2P Trader Added',
            'A new P2P trader has been added.',
            $createdAt
        );

        return $this->response->created([
            'message' => 'P2P trader added successfully.',
            'trader' => $created ?: ['traderId' => $traderId],
        ]);
    }
    
  

}


