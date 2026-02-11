<?php
return [
    'symbol' => 'VARCHAR(50) DEFAULT NULL',
    'network' => 'VARCHAR(50) DEFAULT NULL',
    'address' => 'VARCHAR(255) DEFAULT NULL',
    'amount_usd' => 'VARCHAR(50) DEFAULT NULL',
    'coin_amount' => 'VARCHAR(50) DEFAULT NULL',
    'account' => 'VARCHAR(50) DEFAULT NULL',
    'price_usd' => 'VARCHAR(50) DEFAULT NULL',
    'createdAt' => 'VARCHAR(50) DEFAULT NULL',
    'userId' => 'VARCHAR(50) DEFAULT NULL',
    'depositId' => 'VARCHAR(50) DEFAULT NULL',
    'status' => 'VARCHAR(20) DEFAULT "Pending"',
    'updatedAt' => 'VARCHAR(50) DEFAULT NULL',
    'ipAddress' => 'VARCHAR(45) DEFAULT NULL',
    'reviewedAt' => 'VARCHAR(50) DEFAULT NULL',
    'confirmedAt' => 'VARCHAR(50) DEFAULT NULL',
    'confirmations' => 'INT(11) DEFAULT 0',
];
