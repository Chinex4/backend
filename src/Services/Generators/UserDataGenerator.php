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
            'username' => null,
            'language' => null,
            'BasicVerification' => null,
            'AdvancedVerification' => null,
            'InstitutionalVerification' => null,
            'identityNumber' => fn() => $this->gateway->genRandomAlphanumericstrings(10),
            'antiPhishingCode' => null,
            'withdrawalSecurity' => null,
            'uid' => str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'currency' => '$',
            'verifyUser' => null, 
            'UserLogin' => null, 
            'AllowLogin' => null, 
            'emailVerication' => null,
            'totalAsset' => '0.00',
            'spotAccount' => '0.00',
            'futureAccount' => '0.00',
            'earnAccount' => '0.00',
            'copyAccount' => '0.00', 
            'referralBonus' => '0.00',
            'ipAdress' => fn() => $this->gateway->getIPAddress(),
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
            'allowOtp' => null
        ];
    }

    public function generateDefaultData(array $userData): array
    {
        $data = [];

        foreach ($this->defaultConfig as $key => $value) {
            $data[$key] = is_callable($value) ? $value() : $value;
        }

        $data = array_merge($data, $userData);

        if (isset($data['password'])) {
            $data['encryptedPassword'] = password_hash(trim($data['password']), PASSWORD_DEFAULT);
            unset($data['confirmPassword']);
        }

        return $data;
    }

    public function setDefaultConfig(array $config): void
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);
    }
}
