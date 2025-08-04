<?php
return [
    'contact' => 'VARCHAR(100) NOT NULL',
    'email' => 'VARCHAR(100) NOT NULL',
    'institutionName' => 'VARCHAR(150) NOT NULL',
    'location' => 'VARCHAR(100) NOT NULL',
    'assets' => 'VARCHAR(50) NOT NULL',
    'createdAt' => 'VARCHAR(50) NOT NULL',
    'UserId' => 'VARCHAR(50) NOT NULL',
    'kycId' => 'VARCHAR(20) NOT NULL',
    'status' => 'VARCHAR(20) DEFAULT "Pending"',
    'updatedAt' => 'VARCHAR(50) DEFAULT NULL',
    'reviewedAt' => 'VARCHAR(50) DEFAULT NULL',
    'ipAddress' => 'VARCHAR(45) DEFAULT NULL',
];
