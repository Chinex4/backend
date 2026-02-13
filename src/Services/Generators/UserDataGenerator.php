<?php

namespace Services\Generators;

class UserDataGenerator
{
    private $gateway;
    private $defaultConfig;

    public function __construct($gateway)
    {
        $this->gateway = $gateway;
        $this->initializeDefaultConfig();
    }

    private function initializeDefaultConfig(): void
    {
        $this->defaultConfig = [
       
    // --- Schema fields (arranged to match your schema file order) ---
    'name' => null,
    'email' => null,
    'password' => null,
    'referral' => null,
    'createdAt' => fn() => date('Y-m-d H:i:s'),
    'username' => null,
    'language' => null,
    'BasicVerification' => null,
    'AdvancedVerification' => null,
    'InstitutionalVerification' => null,
    'identityNumber' => fn() => $this->gateway->genRandomAlphanumericstrings(10),
    'antiPhishingCode' => null,
    'withdrawalSecurity' => null,
    'uid' => fn() => str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
    'currency' => '$',
    'verifyUser' => null,
    'UserLogin' => null,
    'AllowLogin' => null,

    // keep exactly as your first list
    'emailVerication' => 'false',

    'totalAsset' => '0.00',
    'spotAccount' => '0.00',
    'futureAccount' => '0.00',
    'earnAccount' => '0.00',
    'copyAccount' => '0.00',
    'ipAdress' => fn() => $this->gateway->getIPAddress(),
    'referralBonus' => '0.00',
    'Message' => null,
    'allowMessage' => null,
    'image' => null,
    'accToken' => fn() => bin2hex(random_bytes(16)),
    'lockCopy' => 'false',
    'lockKey' => fn() => $this->gateway->generateRandomCode(),
    'alert' => null,
    'sendKyc' => null,
    'SignalMessage' => null,
    'kyc' => null,
    'encryptedPassword' => null,
    'userAgent' => null,
    'deviceType' => null,
    'lastLogin' => null,
    'tokenRevoked' => 'false',
    'allowOtp' => 'false',

    // keep balances_json BEFORE extras (exactly like your first list)
    'balances_json' => fn() => json_encode($this->buildDefaultBalances(), JSON_UNESCAPED_SLASHES),
    'p2pPaymentMethod' => null,
    'p2pPaymentDetails' => null,
    'tokenExpiry' => null,
    'refreshToken' => null,
    'totp_secret' => null,
 

        ];
    }

    private function buildDefaultBalances(): array
    {
        $coinsPath = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'coins.json';

        if (!is_readable($coinsPath)) {
            return [];
        }

        $coins = json_decode(file_get_contents($coinsPath), true);
        if (!is_array($coins)) {
            return [];
        }

        $balances = [];
        foreach ($coins as $coin) {
            if (!isset($coin['coinId'])) {
                continue;
            }

            $balances[] = [
                'id' => $coin['coinId'],
                'balance' => 0.0,
                'price' => 0.0,
            ];
        }

        return $balances;
    }

    public function generateDefaultData(array $userData): array
    {
        $data = [];

        foreach ($this->defaultConfig as $key => $value) {
            $data[$key] = is_callable($value) ? $value() : $value;
        }

        // Merge user overrides
        $data = array_merge($data, $userData);

        // Password handling
        if (!empty($data['password'])) {
            $data['encryptedPassword'] = password_hash(trim($data['password']), PASSWORD_DEFAULT);
            unset($data['confirmPassword']);
        }

        // If caller passes balances_json as array, normalize it to JSON string
        if (isset($data['balances_json']) && is_array($data['balances_json'])) {
            $data['balances_json'] = json_encode($data['balances_json'], JSON_UNESCAPED_SLASHES);
        }

        return $data;
    }

    public function setDefaultConfig(array $config): void
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);
    }
}
