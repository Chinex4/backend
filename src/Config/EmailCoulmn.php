<?php
return [
    'verificationToken' => 'VARCHAR(100) NOT NULL',        
    'isVerified' => 'VARCHAR(100) NOT NULL',         
    'tokenExpiry' => 'VARCHAR(500) NOT NULL',              
    'requestedAt' => 'VARCHAR(50) DEFAULT NULL',            
    'email' => 'VARCHAR(150) NOT NULL',
];
