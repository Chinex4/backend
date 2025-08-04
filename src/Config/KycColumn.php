<?php
return [
    'country' => 'VARCHAR(100) NOT NULL',
    'documentType' => 'VARCHAR(50) NOT NULL',
    'idNumber' => 'VARCHAR(100) NOT NULL',
    'firstName' => 'VARCHAR(100) NOT NULL',
    'lastName' => 'VARCHAR(100) NOT NULL',
    'dateOfBirth' => 'VARCHAR(50) NOT NULL',
    'createdAt' => 'VARCHAR(50) NOT NULL',
    'frontImage' => 'VARCHAR(255) NOT NULL',
    'backImage' => 'VARCHAR(255) NOT NULL',
    'userId' => 'VARCHAR(50) NOT NULL',
    'kycId' => 'VARCHAR(20) NOT NULL',
    'status' => 'VARCHAR(20) DEFAULT "Pending"',
    'updatedAt' => 'VARCHAR(50) DEFAULT NULL',
    'phoneNumber' => 'VARCHAR(20) DEFAULT NULL',
    'ipAddress' => 'VARCHAR(45) DEFAULT NULL',
    'reviewedBy' => 'VARCHAR(100) DEFAULT NULL',
    'reviewedAt' => 'VARCHAR(50) DEFAULT NULL',
    'remarks' => 'TEXT DEFAULT NULL',
    'rejectionReason' => 'TEXT DEFAULT NULL',
    'attempts' => 'INT(11) DEFAULT 0',
];

