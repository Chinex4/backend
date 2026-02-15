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
    private $copyTradeColumn;

    public function __construct($pdoConnection)
    {
        $this->dbConnection = $pdoConnection;
        $this->regUsercolumns = require __DIR__ . '/../Config/UserColumns.php';
        $this->EmailCoulmn = require __DIR__ . '/../Config/EmailCoulmn.php';
        $this->ForgotPasswordColumns = require __DIR__ . '/../Config/ForgotPasswordColumns.php';
        $this->p2pTraderColumn = require __DIR__ . '/../Config/P2PTraderColumn.php';
        $this->copyTradeColumn = require __DIR__ . '/../Config/CopyTradeColumn.php';
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

    private function toBoolInt($value, bool $default = false): int
    {
        if ($value === null || $value === '') {
            return $default ? 1 : 0;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_numeric($value)) {
            return ((int) $value) === 1 ? 1 : 0;
        }
        $normalized = strtolower(trim((string) $value));
        return in_array($normalized, ['true', '1', 'yes', 'y', 'on'], true) ? 1 : 0;
    }

    private function toJsonArrayFlexible($value): string
    {
        if ($value === null || $value === '') {
            return json_encode([], JSON_UNESCAPED_UNICODE);
        }

        if (is_array($value)) {
            return json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                return json_encode([], JSON_UNESCAPED_UNICODE);
            }

            $decoded = json_decode($trimmed, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return json_encode(array_values($decoded), JSON_UNESCAPED_UNICODE);
            }

            $parts = array_values(array_filter(array_map('trim', explode(',', $trimmed)), function ($item) {
                return $item !== '';
            }));
            return json_encode($parts, JSON_UNESCAPED_UNICODE);
        }

        return json_encode([], JSON_UNESCAPED_UNICODE);
    }

    private function pickRandomItems(array $pool, int $count): array
    {
        if ($count <= 0 || empty($pool)) {
            return [];
        }
        if ($count >= count($pool)) {
            $all = array_values($pool);
            shuffle($all);
            return $all;
        }

        $keys = array_rand($pool, $count);
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $selected = [];
        foreach ($keys as $key) {
            $selected[] = $pool[$key];
        }

        return array_values(array_unique($selected));
    }

    private function randomFloat(float $min, float $max, int $precision = 2): float
    {
        $scale = (int) pow(10, $precision);
        $rand = random_int((int) round($min * $scale), (int) round($max * $scale));
        return $rand / $scale;
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

    public function addCopyTrade(array $data, array $file = []): void
    {
        $displayName = $this->cleanString($data['display_name'] ?? null, 120);
        $username = $this->cleanString($data['username'] ?? null, 80);

        if (!$displayName || !$username) {
            $this->response->unprocessableEntity('Missing required fields: display_name and username.');
            return;
        }

        $username = strtolower(preg_replace('/[^a-z0-9_]/i', '', $username));
        if ($username === '') {
            $this->response->unprocessableEntity('Username is invalid after sanitization.');
            return;
        }

        if ($this->gateway->recordExists(copy_trade, ['username' => $username])) {
            $username .= random_int(10, 9999);
        }

        $avatarUrl = null;
        if (isset($file['avatar']) && is_array($file['avatar']) && !empty($file['avatar']['name'])) {
            $uploaded = $this->gateway->processImageWithgivenNameFiles($file['avatar']);
            if (is_string($uploaded) && $uploaded !== '') {
                $avatarUrl = $uploaded;
            } else {
                return;
            }
        }

        $createdAt = $this->cleanString($data['created_at'] ?? date('Y-m-d\TH:i:s\Z'), 50);
        $copyTradeData = [
            'display_name' => $displayName,
            'username' => $username,
            'avatar' => $avatarUrl,
            'status' => $this->cleanString($data['status'] ?? 'active', 20),
            'kyc_status' => $this->cleanString($data['kyc_status'] ?? 'verified', 20),
            'copiers_count' => $this->toIntOrDefault($data['copiers_count'] ?? null, 0),
            'aum_usd' => $this->toFloatOrNull($data['aum_usd'] ?? null) ?? 0,
            'total_return_pct' => $this->toFloatOrNull($data['total_return_pct'] ?? null) ?? 0,
            'roi_30d_pct' => $this->toFloatOrNull($data['roi_30d_pct'] ?? null) ?? 0,
            'roi_90d_pct' => $this->toFloatOrNull($data['roi_90d_pct'] ?? null) ?? 0,
            'profit_factor' => $this->toFloatOrNull($data['profit_factor'] ?? null) ?? 0,
            'total_trades' => $this->toIntOrDefault($data['total_trades'] ?? null, 0),
            'max_drawdown_pct' => $this->toFloatOrNull($data['max_drawdown_pct'] ?? null) ?? 0,
            'volatility_30d' => $this->toFloatOrNull($data['volatility_30d'] ?? null) ?? 0,
            'sharpe_ratio' => $this->toFloatOrNull($data['sharpe_ratio'] ?? null) ?? 0,
            'avg_leverage' => $this->toFloatOrNull($data['avg_leverage'] ?? null) ?? 0,
            'risk_score' => $this->toIntOrDefault($data['risk_score'] ?? null, 0),
            'liquidation_events' => $this->toIntOrDefault($data['liquidation_events'] ?? null, 0),
            'win_rate_pct' => $this->toFloatOrNull($data['win_rate_pct'] ?? null) ?? 0,
            'profit_share_pct' => $this->toFloatOrNull($data['profit_share_pct'] ?? null) ?? 0,
            'management_fee_pct' => $this->toFloatOrNull($data['management_fee_pct'] ?? null) ?? 0,
            'min_copy_amount_usd' => $this->toFloatOrNull($data['min_copy_amount_usd'] ?? null) ?? 0,
            'max_copiers' => $this->toIntOrDefault($data['max_copiers'] ?? null, 0),
            'copy_mode' => $this->cleanString($data['copy_mode'] ?? 'proportional', 30),
            'slippage_limit_pct' => $this->toFloatOrNull($data['slippage_limit_pct'] ?? null) ?? 0,
            'markets' => $this->toJsonArrayFlexible($data['markets'] ?? []),
            'instruments' => $this->toJsonArrayFlexible($data['instruments'] ?? []),
            'time_horizon' => $this->cleanString($data['time_horizon'] ?? 'swing', 30),
            'strategy_description' => $this->cleanString($data['strategy_description'] ?? null),
            'tags' => $this->toJsonArrayFlexible($data['tags'] ?? []),
            'verified_track_record' => $this->toBoolInt($data['verified_track_record'] ?? true, true),
            'exchange_linked' => $this->toBoolInt($data['exchange_linked'] ?? true, true),
            'warning_flags' => $this->toJsonArrayFlexible($data['warning_flags'] ?? []),
            'terms_accepted_at' => $this->cleanString($data['terms_accepted_at'] ?? $createdAt, 50),
            'last_active_at' => $this->cleanString($data['last_active_at'] ?? date('Y-m-d\TH:i:s\Z'), 50),
            'created_at' => $createdAt,
            'is_public' => $this->toBoolInt($data['is_public'] ?? true, true),
        ];

        $createTable = $this->createDbTables->createTableWithTypes(copy_trade, $this->copyTradeColumn);
        if (!$createTable) {
            $this->response->unprocessableEntity('Could not create copy trade table.');
            return;
        }

        $bindingArray = $this->gateway->generateRandomStrings($copyTradeData);
        $inserted = $this->connectToDataBase->insertDataWithTypes(
            $this->dbConnection,
            copy_trade,
            $this->copyTradeColumn,
            $bindingArray,
            $copyTradeData
        );

        if (!$inserted) {
            $this->response->unprocessableEntity('Could not save copy trade record.');
            return;
        }

        $created = $this->gateway->fetchData(copy_trade, ['username' => $username]);
        $this->response->created([
            'message' => 'Copy trade profile created successfully.',
            'profile' => $created ?: ['username' => $username],
        ]);
    }

    public function seedCopyTradeData(array $data = []): void
    {
        $count = $this->toIntOrDefault($data['count'] ?? null, 100);
        if ($count < 1) {
            $count = 1;
        }
        if ($count > 1000) {
            $count = 1000;
        }

        $createTable = $this->createDbTables->createTableWithTypes(copy_trade, $this->copyTradeColumn);
        if (!$createTable) {
            $this->response->unprocessableEntity('Could not create copy trade table.');
            return;
        }

        $firstNames = ['Jane', 'Liam', 'Ava', 'Noah', 'Mia', 'Ethan', 'Emma', 'Lucas', 'Sophia', 'Mason', 'Isabella', 'Logan', 'Amelia', 'Elijah', 'Harper', 'James', 'Evelyn', 'Benjamin', 'Abigail', 'Daniel'];
        $lastNames = ['Quant', 'Walker', 'Chen', 'Patel', 'Garcia', 'Kim', 'Morgan', 'Brooks', 'Ivanov', 'Nakamura', 'Singh', 'Silva', 'Khan', 'Meyer', 'Costa', 'Novak', 'Rivera', 'Santos', 'Fischer', 'Ali'];
        $marketPool = ['crypto', 'forex', 'stocks', 'commodities', 'indices'];
        $instrumentPool = ['BTCUSDT', 'ETHUSDT', 'SOLUSDT', 'XAUUSD', 'EURUSD', 'GBPUSD', 'NAS100', 'SPX500', 'USOIL', 'USDJPY', 'ADAUSDT', 'XRPUSDT'];
        $tagPool = ['low-dd', 'trend', 'mean-reversion', 'swing', 'scalp', 'macro', 'risk-first', 'systematic', 'breakout'];
        $copyModes = ['proportional', 'fixed-size', 'risk-adjusted'];
        $horizons = ['intraday', 'swing', 'position'];
        $statusPool = ['active', 'active', 'active', 'paused'];
        $kycPool = ['verified', 'verified', 'pending'];
        $warningsPool = ['high-volatility', 'recent-drawdown', 'high-leverage', 'low-history'];
        $strategyPool = [
            'Trend following with strict downside risk limits.',
            'Multi-market momentum with adaptive position sizing.',
            'Mean reversion around volatility bands and news filters.',
            'Breakout strategy with low-latency execution and stop discipline.',
            'Macro-driven swing trading with correlation hedging.',
        ];

        $inserted = 0;
        $nowTs = time();
        $startTs = strtotime('-540 days');
        $endTs = strtotime('-30 days');
        $activeStartTs = strtotime('-7 days');

        for ($i = 1; $i <= $count; $i++) {
            $first = $firstNames[array_rand($firstNames)];
            $last = $lastNames[array_rand($lastNames)];

            $displayName = $first . ' ' . $last;
            $username = strtolower(preg_replace('/[^a-z0-9]/', '', $first . $last)) . $i;

            $createdTs = random_int($startTs, $endTs);
            $termsTs = min($nowTs, $createdTs + random_int(0, 5 * 86400));
            $activeTs = random_int($activeStartTs, $nowTs);

            $markets = $this->pickRandomItems($marketPool, random_int(1, 2));
            $instruments = $this->pickRandomItems($instrumentPool, random_int(2, 4));
            $tags = $this->pickRandomItems($tagPool, random_int(2, 4));

            $warningFlags = [];
            if (random_int(1, 100) <= 18) {
                $warningFlags = $this->pickRandomItems($warningsPool, random_int(1, 2));
            }

            $copiersCount = random_int(10, 1200);
            $aumUsd = $this->randomFloat(25000, 3500000, 2);
            $totalReturn = $this->randomFloat(-12, 165, 2);
            $roi30 = $this->randomFloat(-6, 22, 2);
            $roi90 = $this->randomFloat(-10, 45, 2);
            $profitFactor = $this->randomFloat(0.7, 3.1, 2);
            $totalTrades = random_int(60, 4000);
            $maxDrawdown = $this->randomFloat(2.5, 32, 2);
            $volatility = $this->randomFloat(1.1, 16.5, 2);
            $sharpe = $this->randomFloat(-0.4, 2.9, 2);
            $avgLeverage = $this->randomFloat(1.0, 8.5, 2);
            $riskScore = random_int(10, 92);
            $liqEvents = random_int(1, 100) <= 82 ? 0 : random_int(1, 4);
            $winRate = $this->randomFloat(38, 86, 2);
            $profitShare = $this->randomFloat(8, 28, 2);
            $managementFee = $this->randomFloat(0.5, 4.5, 2);
            $minCopyAmount = $this->randomFloat(50, 5000, 2);
            $maxCopiers = random_int(80, 6000);
            $slippageLimit = $this->randomFloat(0.1, 2.8, 2);

            $row = [
                'display_name' => $displayName,
                'username' => $username,
                'avatar' => null,
                'status' => $statusPool[array_rand($statusPool)],
                'kyc_status' => $kycPool[array_rand($kycPool)],
                'copiers_count' => $copiersCount,
                'aum_usd' => $aumUsd,
                'total_return_pct' => $totalReturn,
                'roi_30d_pct' => $roi30,
                'roi_90d_pct' => $roi90,
                'profit_factor' => $profitFactor,
                'total_trades' => $totalTrades,
                'max_drawdown_pct' => $maxDrawdown,
                'volatility_30d' => $volatility,
                'sharpe_ratio' => $sharpe,
                'avg_leverage' => $avgLeverage,
                'risk_score' => $riskScore,
                'liquidation_events' => $liqEvents,
                'win_rate_pct' => $winRate,
                'profit_share_pct' => $profitShare,
                'management_fee_pct' => $managementFee,
                'min_copy_amount_usd' => $minCopyAmount,
                'max_copiers' => $maxCopiers,
                'copy_mode' => $copyModes[array_rand($copyModes)],
                'slippage_limit_pct' => $slippageLimit,
                'markets' => json_encode($markets, JSON_UNESCAPED_UNICODE),
                'instruments' => json_encode($instruments, JSON_UNESCAPED_UNICODE),
                'time_horizon' => $horizons[array_rand($horizons)],
                'strategy_description' => $strategyPool[array_rand($strategyPool)],
                'tags' => json_encode($tags, JSON_UNESCAPED_UNICODE),
                'verified_track_record' => random_int(1, 100) <= 85 ? 1 : 0,
                'exchange_linked' => random_int(1, 100) <= 92 ? 1 : 0,
                'warning_flags' => json_encode($warningFlags, JSON_UNESCAPED_UNICODE),
                'terms_accepted_at' => gmdate('Y-m-d\TH:i:s\Z', $termsTs),
                'last_active_at' => gmdate('Y-m-d\TH:i:s\Z', $activeTs),
                'created_at' => gmdate('Y-m-d\TH:i:s\Z', $createdTs),
                'is_public' => random_int(1, 100) <= 88 ? 1 : 0,
            ];

            $bindingArray = $this->gateway->generateRandomStrings($row);
            $ok = $this->connectToDataBase->insertDataWithTypes(
                $this->dbConnection,
                copy_trade,
                $this->copyTradeColumn,
                $bindingArray,
                $row
            );

            if ($ok) {
                $inserted++;
            }
        }

        $this->response->created([
            'message' => 'Copy trade seed completed.',
            'requested' => $count,
            'inserted' => $inserted,
        ]);
    }


}


