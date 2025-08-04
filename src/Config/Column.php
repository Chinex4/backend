<?php
return [

    'proofOfAddress' => 'VARCHAR(255) NOT NULL', 
    'createdAt' => 'VARCHAR(50) NOT NULL',
    'userId' => 'VARCHAR(50) NOT NULL',
    'kycId' => 'VARCHAR(20) NOT NULL',
    'status' => 'VARCHAR(20) DEFAULT "Pending"',
    'updatedAt' => 'VARCHAR(50) DEFAULT NULL',   
    'ipAddress' => 'VARCHAR(45) DEFAULT NULL',
    'reviewedAt' => 'VARCHAR(50) DEFAULT NULL', 
    'rejectionReason' => 'TEXT DEFAULT NULL',
    'attempts' => 'INT(11) DEFAULT 0',
];

