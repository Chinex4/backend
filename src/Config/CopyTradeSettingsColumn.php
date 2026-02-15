<?php
return [
    'copyRequestId' => 'VARCHAR(20) DEFAULT NULL',
    'userId' => 'VARCHAR(255) DEFAULT NULL',
    'traderId' => 'INT(11) DEFAULT 0',
    'amountMode' => 'VARCHAR(20) DEFAULT NULL',
    'amountPerOrder' => 'DECIMAL(18,8) DEFAULT NULL',
    'amount' => 'DECIMAL(18,8) DEFAULT 0',
    'stopLoss' => 'DECIMAL(10,2) DEFAULT 0',
    'marginMode' => 'VARCHAR(20) DEFAULT NULL',
    'leverageMode' => 'VARCHAR(20) DEFAULT NULL',
    'fixedLeverage' => 'DECIMAL(10,2) DEFAULT NULL',
    'slippageRange' => 'DECIMAL(10,2) DEFAULT 0',
    'agree' => 'TINYINT(1) DEFAULT 0',
    'status' => 'VARCHAR(20) DEFAULT "active"',
    'createdAt' => 'VARCHAR(50) DEFAULT NULL',
    'updatedAt' => 'VARCHAR(50) DEFAULT NULL',
    'ipAddress' => 'VARCHAR(64) DEFAULT NULL',
];
