<?php
// Database connection
$host = "localhost";
$dbname = "cashpro";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Load coins.json
$jsonFile = __DIR__ . '/coins.json';
$coins = json_decode(file_get_contents($jsonFile), true);

if (!is_array($coins)) {
    die("Error: JSON could not be decoded.");
}

// Prepare the update statement
$sql = "UPDATE coin_wallets SET img_link = :img_link WHERE coin_id = :coin_id";
$stmt = $pdo->prepare($sql);

$updatedCount = 0;

foreach ($coins as $coin) {
    // Make sure both coinId and image exist
    if (!empty($coin['coinId']) && !empty($coin['image'])) {
        $stmt->execute([
            ':img_link' => $coin['image'],
            ':coin_id'  => $coin['coinId'] // match DB's coin_id with JSON's coinId
        ]);

        if ($stmt->rowCount() > 0) {
            $updatedCount++;
        }
    }
}

echo "✅ $updatedCount coin_wallets records updated successfully.\n";
