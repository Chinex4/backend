<?php

class RefreshTokenGateway
{
    private $gateway;
    private $createDbTables;
    private $connectToDataBase;
    private $dbConnection;
    private string $key;

    public function __construct(PDO $database, string $key)
    {
        $this->dbConnection = $database;
        $this->connectToDataBase = new Database();
        $this->gateway = new TaskGatewayFunction($this->dbConnection);
        $this->createDbTables = new CreateDbTables($this->dbConnection);
        $this->key = $key;
    }

    public function create(string $token, int $expiry): bool
    { 
        $hash = hash_hmac("sha256", $token, $this->key);
        $columns = ['token_hash', 'expires_at'];
        $values = [$hash, $expiry];
        $bindings = $this->gateway->generateRandomStrings($columns);

        $created = $this->createDbTables->createTable(refresh_token, regArray: $columns);

        if ($created) {
            return $this->connectToDataBase->insertData(
                $this->dbConnection,
                refresh_token,
                $columns,
                $bindings,
                $values
            );
        }

        return false;
    }

    public function delete(string $token): int
    {
        $hash = hash_hmac("sha256", $token, $this->key);
        return $this->connectToDataBase->deleteData(
            $this->dbConnection,
            refresh_token,
            'token_hash',
            $hash
        );
    }

    public function getByToken(string $token): array|false
    {
        $hash = hash_hmac("sha256", $token, $this->key);
        return $this->gateway->fetchData(
            refresh_token,
            ['token_hash' => $hash]
        );
    }

    public function deleteExpired(): int
    {
        $sql = "DELETE FROM refresh_token WHERE expires_at < UNIX_TIMESTAMP()";
        $stmt = $this->dbConnection->query($sql);
        return $stmt->rowCount();
    }
}
