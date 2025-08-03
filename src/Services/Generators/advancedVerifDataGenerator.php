<?php

namespace Services\Generators;

class advancedVerifDataGenerator
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
            'kycId' => str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'status' => "Pending",
            'updatedAt' => null, 
            'ipAddress' =>  fn() => $this->gateway->getIPAddress(),
            'reviewedAt' => null, 
            'rejectionReason' => null,
            'attempts' => 0
        ];
    }

    public function generateDefaultData(array $userData): array
    {
        $data = [];

        foreach ($this->defaultConfig as $key => $value) {
            $data[$key] = is_callable($value) ? $value() : $value;
        }

        $data = array_merge($userData,$data );
        return $data;
    }

    public function setDefaultConfig(array $config): void
    {
        $this->defaultConfig = array_merge($this->defaultConfig, $config);
    }
}
