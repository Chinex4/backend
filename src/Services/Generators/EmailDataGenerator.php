<?php

namespace Services\Generators;

class EmailDataGenerator
{
    private $gateway;
    private $defaultConfig;

    public function __construct($gateway)
    {
        $this->gateway = $gateway;
        $this->initializeDefaultConfig();
    }

    private function initializeDefaultConfig()
    {
        $this->defaultConfig = [
            'verificationToken' => fn() => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'isVerified' => 'notYet',
        ];
    }
    


    public function generateVerificationData(array $userData): array
    {
        $data = [];

        foreach ($this->defaultConfig as $key => $value) {
            $data[$key] = is_callable($value) ? $value() : $value;
        }

        // Handle tokenExpiry based on createdAt + 1 hour
        if (isset($userData['createdAt'])) {
            $createdTimestamp = strtotime($userData['createdAt']);
            $data['tokenExpiry'] = date('Y-m-d H:i:s', $createdTimestamp + 3600); // +1 hour
        } else {
            $data['tokenExpiry'] = date('Y-m-d H:i:s', strtotime('+1 hour'));
        }

        // Merge in user-provided email and createdAt
        return array_merge($data, $userData);
    }

    public function setDefaultConfig(array $config): void
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);
    }
}
