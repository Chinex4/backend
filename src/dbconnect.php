<?php
class dbconnect {

    private PDO $connection;
    private int $timestamp;

    public function __construct(
        private string $db,
        private string $host,
        private string $user,
        private string $password,
    ) {
        $this->timestamp = (new DateTime())->getTimestamp();
    }

    public function getDbconnect(): PDO {
        $dbc = "mysql:host={$this->host};dbname={$this->db};charset=utf8";
        $this->connection = new PDO(
            $dbc,
            $this->user,
            $this->password,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return $this->connection;
    }

    public function getCurrentTimestamp(): int {
        return $this->timestamp;
    }

    public function query($sql) {
 
        return $this->connection->query($sql);
    }

    public function exec($sql) {
 
        return $this->connection->exec($sql);
    }

    // Uncomment if needed
    // public function prepare($sql) {
    //     return $this->connection->prepare($sql);
    // }
}

// Usage example:
$dateTime = new DateTime();
$timestamp = $dateTime->getTimestamp();
// echo "Current Timestamp: " . $timestamp . PHP_EOL;

// $db = new dbconnect('your_db', 'localhost', 'username', 'password');
// $conn = $db->getDbconnect();
// $sql = "SELECT * FROM your_table;";
// $db->query($sql);

