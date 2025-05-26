<?php

class TaskGatewayFunction
{
    private $conn;
    private $pdo;
    private $createDbTables;
    private $mailsender;
    private $response;

    public function __construct($pdoConnection)
    { 
        $this->pdo = $pdoConnection;
        $this->createDbTables = new CreateDbTables($pdoConnection);
        $this->mailsender = new EmailSender();
        $this->response = new JsonResponse();
        $this->conn = new Database();
    }

    public function __destruct()
    {
        $this->pdo = null; 
        $this->conn = null; 
        $this->createDbTables = null; 
        $this->mailsender = null; 
        $this->response = null; 
    }


    public function generateRandomStrings($data)
    {
        $count = count($data);
        $randomStrings = [];
        for ($i = 0; $i < $count; $i++) {
            $randomStrings[] = $this->generateRandomString();
        }
        return $randomStrings;
    }

    public function generateRandomString($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
    public function checkEmailExistence($dbConnection, string $tableName, array $conditions)
    {
        // Build the WHERE clause dynamically
        $whereClause = '';
        foreach ($conditions as $column => $value) {
            if ($whereClause === '') {
                $whereClause .= "WHERE $column = :$column";
            } else {
                $whereClause .= " AND $column = :$column";
            }
        }
        // Prepare the SQL query
        $query = "SELECT COUNT(*) FROM $tableName $whereClause";
        try {
            $stmt = $dbConnection->prepare($query);
            // Bind parameters for the WHERE clause
            foreach ($conditions as $column => $value) {
                $stmt->bindParam(":$column", $value, PDO::PARAM_STR);
            }
            $stmt->execute();
            $count = $stmt->fetchColumn();
            return ($count > 0);
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
            return false;
        }
    }

    public function createNotiMessage($table, array $columns, $BindingArray  , $dataArray)
    {
        $createTable =  $this->createDbTables->createTable($table, $columns);
        if ($createTable) {
        $inserted = $this->conn->insertData($this->pdo, $table, $columns, $BindingArray, $dataArray);
         if ($inserted) {
            return true;
         }else{
            return false;
         }
        }

    }
   public function createNotificationMessage($id, $h, $c, $s=null) {
        $messageColumn = ['userid', 'messageHeader', 'content', 'sent_at', 'read_at', 'status'];
        $messageColumnArray = ['userid', 'messageHeader', 'content', 'sent_at', 'read_at', 'status'];
        $BindingArray = $this->generateRandomStrings($messageColumnArray);
        // $s = date('d-m-Y h:i:s a', time());
        $r = null;
        $st = null;
        $messageData = [$id, $h, $c, $s, $r, $st];
        $message = $this->createNotiMessage(messagenoti, $messageColumn, $BindingArray, $messageData);
        if ($message) {
            return true;
        } else {
       return false;
        }
    }
    
 
    public function createForUser($pdo, string $tableName, array $columns, array $bindingArray, array $data)
    {

        $inserted = $this->conn->insertData($pdo, $tableName, $columns, $bindingArray, $data);
        if ($inserted === true) {
            $lastInsertedId = $pdo->lastInsertId();
            return $lastInsertedId;
        } else {
            return false;
        }
    }
    public function createForUserWithTypes($pdo, string $tableName, array $columns, array $bindingArray, array $data)
    {
        // var_dump($pdo);
        $inserted = $this->conn->insertDataWithTypes($pdo, $tableName, $columns, $bindingArray, $data);
        if ($inserted === true) {
            $lastInsertedId = $pdo->lastInsertId();
            return $lastInsertedId;
        } else {
            return false;
        }
    }

    public function fetchDataWithId($table, $id)
    {
        $id = trim($id);
        $sql = "SELECT * FROM $table WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user;
    }

    public function fetchData($tableName, $conditions)
    {
        $sql = "SELECT * FROM $tableName WHERE ";
        $whereConditions = [];
        foreach ($conditions as $column => $value) {
            $whereConditions[] = "$column = :$column";
        }
        $sql .= implode(" AND ", $whereConditions);
        $sql .= " ORDER BY id DESC"; // Adding the ORDER BY clause

        $stmt = $this->pdo->prepare($sql);
        foreach ($conditions as $column => $value) {
            $stmt->bindValue(":$column", $value);
            // var_dump($column, $value);
        }

        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }
    public function tokenExists($tableName, $id)   
    {
        $sql = "SELECT COUNT(*) FROM $tableName WHERE accToken = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);       
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count > 0;
    }
    public function idExists($tableName, $id)   
    {
        $sql = "SELECT COUNT(*) FROM $tableName WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id', $id);       
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count > 0;
    }
    
    public function getAllColumns($tableName)
    {
        $sql = "SHOW COLUMNS FROM $tableName";
        $stmt = $this->pdo->query($sql);
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $columns;
    }
    public function searchAllColumns($tableName, $searchTerm)
    {
        $sql = "SELECT * FROM $tableName WHERE ";
        $columns = $this->getAllColumns($tableName); // Function to get all column names of the table
        $whereConditions = [];
        foreach ($columns as $column) {
            $whereConditions[] = "$column LIKE :searchTerm";
        }
        $sql .= implode(" OR ", $whereConditions);
        $sql .= " ORDER BY id DESC"; // Adding the ORDER BY clause

        $stmt = $this->pdo->prepare($sql);
        $param = '%' . $searchTerm . '%';
        $stmt->bindParam(':searchTerm', $param, PDO::PARAM_STR);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
    public function fetchAllDataWithCOnditionAscd($tableName, $conditions)
    {
        $sql = "SELECT * FROM $tableName WHERE ";
        $whereConditions = [];
        foreach ($conditions as $column => $value) {
            $whereConditions[] = "$column = :$column";
        }
        $sql .= implode(" AND ", $whereConditions);
        $sql .= " ORDER BY id ASC"; // Adding the ORDER BY clause

        $stmt = $this->pdo->prepare($sql);
        foreach ($conditions as $column => $value) {
            $stmt->bindValue(":$column", $value);
            // var_dump($column, $value);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function fetchMultipleDataWithLimit($tableName, $conditions,$limit)
    {
        $sql = "SELECT * FROM $tableName WHERE ";
        $whereConditions = [];
        foreach ($conditions as $column => $value) {
            $whereConditions[] = "$column = :$column";
        }
        $sql .= implode(" AND ", $whereConditions);
        $sql .= " ORDER BY id DESC Limit ".$limit; // Adding the ORDER BY clause

        $stmt = $this->pdo->prepare($sql);
        foreach ($conditions as $column => $value) {
            $stmt->bindValue(":$column", $value);
            // var_dump($column, $value);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
 
    public function fetchAllDataWithCondition($tableName, $conditions)
{
    $sql = "SELECT * FROM $tableName WHERE ";
    $whereConditions = [];
    foreach ($conditions as $column => $value) {
        $whereConditions[] = "$column = :$column";
    }
    $sql .= implode(" AND ", $whereConditions);
    $sql .= " ORDER BY id DESC"; // Adding the ORDER BY clause

    $stmt = null; // Initialize the statement
    try {
        $stmt = $this->pdo->prepare($sql);
        foreach ($conditions as $column => $value) {
            $stmt->bindValue(":$column", $value);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    } catch (PDOException $e) {
        echo "Fetch Error: " . $e->getMessage();
        return false;
    } finally {
        if ($stmt) {
            $stmt = null; // Close the statement to free resources
        }
    }
}

public function fetchPaginatedDataWithConditions($tableName, $conditions, $limit, $page)
{
    $offset = ($page - 1) * $limit;
    $sql = "SELECT * FROM $tableName";

    // Add WHERE clause if conditions are provided
    if (!empty($conditions)) {
        $whereConditions = [];
        foreach ($conditions as $column => $value) {
            $whereConditions[] = "$column = :$column";
        }
        $sql .= " WHERE " . implode(" AND ", $whereConditions);
    }

    $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

    $stmt = null;
    try {
        $stmt = $this->pdo->prepare($sql);

        // Bind condition values
        foreach ($conditions as $column => $value) {
            $stmt->bindValue(":$column", $value);
        }

        // Bind pagination
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Pagination Fetch Error: " . $e->getMessage();
        return false;
    } finally {
        if ($stmt) {
            $stmt = null;
        }
    }
}

public function fetchPaginatedDataWithConditions2($table, $conditions, $limit, $page)
{
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM $table WHERE ";
    $whereConditions = [];
    foreach ($conditions as $column => $value) {
        $whereConditions[] = "$column = :$column";
    }
    $sql .= implode(" AND ", $whereConditions);
    $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

    $stmt = $this->pdo->prepare($sql);
    foreach ($conditions as $column => $value) {
        $stmt->bindValue(":$column", $value);
    }
    $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function fetchAllData($tableName)
    {
        $sql = "SELECT * FROM $tableName";
        $sql .= " ORDER BY id DESC"; // Adding the ORDER BY clause

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function fetchoneData($tableName)
    {
        $sql = "SELECT * FROM $tableName";
        $sql .= " ORDER BY id DESC"; // Adding the ORDER BY clause

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data;
    }

    public function fetchRandomData($tableName, $limit, $condition = '', $params = array())
    {
        $sql = "SELECT * FROM $tableName";
        if (!empty($condition)) {
            $sql .= " WHERE $condition";
        }
        $sql .= " ORDER BY RAND()"; // Ordering randomly
        $sql .= " LIMIT :limit"; // Adding dynamic limit

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT); // Binding the limit parameter

        // Bind condition parameters if provided
        foreach ($params as $key => &$value) {
            $stmt->bindParam($key, $value);
        }

        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }
public function generateRandomCode() { 
    function generateLetters($length) {
        $letters = '';
        for ($i = 0; $i < $length; $i++) {
            $letters .= chr(rand(65, 90));  
        }
        return $letters;
    }
 
    function generateDigits($length) {
        $digits = '';
        for ($i = 0; $i < $length; $i++) {
            $digits .= rand(0, 9);
        }
        return $digits;
    }
 
    $prefix = generateLetters(3);  
    $numbers = generateDigits(9);  
    $suffix = generateLetters(2);  

    return $prefix . $numbers . $suffix;
}
 

 

    public function genRandomAlphanumericstrings($length_of_string)
    { 
        $str_result = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'; 
        return substr(str_shuffle($str_result), 0, $length_of_string);
    }

    public function getIPAddress()
    {
        //whether ip is from the share internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //whether ip is from the proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //whether ip is from the remote address
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
    public function fetchPaginatedData($tableName, $limit, $page)
    {
        $offset = ($page - 1) * $limit;
        $sql = "SELECT * FROM $tableName";
        $sql .= " ORDER BY id DESC"; 
        $sql .= " LIMIT $limit OFFSET $offset"; 

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $data;
    }

    public function processImageWithgivenNameFiles($file)
    {
        $imgHolder ='';
        $errors = [];

        $fileDoc = $file;
        if (isset($fileDoc['name']) && is_string($fileDoc['name'])) {
            $fileName = $fileDoc['name'] ;
            $fileType = $fileDoc['type'] ;
            $fileError = $fileDoc['error'] ;
            $fileSize = $fileDoc['size'] ;
            $tmp_name = $fileDoc['tmp_name'] ;
            $currentFile = array(
                'name' => $fileName,
                'type' => $fileType,
                'tmp_name' => $tmp_name,
                'error' => $fileError,
                'size' => $fileSize
            );

            $result = $this->validateImageFile($currentFile);


            if (is_string($result)) {
                $imgHolder = $result;
            } elseif (is_array($result)) {
                switch ($result[0]) {
                    case 1:
                        $errors[] = "You can only upload a PNG, JPG, or JPEG file.";
                        break;
                    case 2:
                        $errors[] = "There was an error while uploading this file. Please try again.";
                        break;
                    case 3:
                        $errors[] = "Your file is too big. Please upload a file smaller than 5MB.";
                        break;
                    case 4:
                        $errors[] = "Failed to create upload directory.";
                        break;
                    case 5:
                        $errors[] = "Could not move the file to the destination directory.";
                        break;
                }
            }


            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
            return $imgHolder;

        } else {
            $error = ['this file is not an array'];
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }


    }

    public function processImageFiles($file)
    {
        $imgHolder = [];
        $errors = [];

        $fileDoc = $file['documents'];

        if (isset($fileDoc['name']) && is_array($fileDoc['name'])) {
            if (count($fileDoc['name']) > 0) {
                foreach ($fileDoc['tmp_name'] as $key => $tempName) {
                    $fileName = $fileDoc['name'][$key];
                    $fileType = $fileDoc['type'][$key];
                    $fileError = $fileDoc['error'][$key];
                    $fileSize = $fileDoc['size'][$key];
                    $currentFile = array(
                        'name' => $fileName,
                        'type' => $fileType,
                        'tmp_name' => $tempName,
                        'error' => $fileError,
                        'size' => $fileSize
                    );

                    $result = $this->validateImageFile($currentFile);

                    if (is_string($result)) {
                        $imgHolder = $result;
                    } elseif (is_array($result)) {
                        switch ($result[0]) {
                            case 1:
                                $errors[] = "You can only upload a PNG, JPG, or JPEG file.";
                                break;
                            case 2:
                                $errors[] = "There was an error while uploading this file. Please try again.";
                                break;
                            case 3:
                                $errors[] = "Your file is too big. Please upload a file smaller than 5MB.";
                                break;
                            case 4:
                                $errors[] = "Failed to create upload directory.";
                                break;
                            case 5:
                                $errors[] = "Could not move the file to the destination directory.";
                                break;
                        }
                    }
                }

                if (!empty($errors)) {
                    $this->response->respondUnprocessableEntity($errors);
                    return;
                }
                return $imgHolder;
            }
        }


    }
    public function getCurrentUrl()
    {
        // Determine the protocol
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

        // Get the hostname
        $hostname = $_SERVER['HTTP_HOST'];

        // Get the request URI
        $requestUri = $_SERVER['REQUEST_URI'];

        // Combine to form the full URL
        $currentUrl = $protocol . $hostname . $requestUri;

        return $currentUrl;
    }

    public function validateImageFile($file)
    {
        $errors = [];
        $fileName = $file['name'];
        $fileType = $file['type'];
        $fileTemp = $file['tmp_name'];
        $fileError = $file['error'];
        $fileSize = $file['size'];
        $allowedExtensions = array('jpg', 'jpeg', 'png');
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (!in_array($fileExt, $allowedExtensions)) {
            $errors[] = 1;
        }
        if ($fileError != 0) {
            $errors[] = 2;
        }
        $maxFileSize = 5242880;
        // $maxFileSize = 52;
        if ($fileSize > $maxFileSize) {
            $errors[] = 3;
        }

        $uploadDirectory = '../img/';

        if (!file_exists($uploadDirectory)) {
            if (!mkdir($uploadDirectory, 0777, true)) {
                $errors[] = 4;
            }
        }
        $newFileName = md5(uniqid('', true)) . '.' . $fileExt;
        $fileDestination = $uploadDirectory . $newFileName;
        // var_dump($fileTemp);
        if (!move_uploaded_file($fileTemp, $fileDestination)) {
            $errors[] = 5;
        }
        if (!empty($errors)) {
            return $errors;
        } else {
            return apiLink . 'img/' . $newFileName;
        }
    }
    public function checkForEmailVerification($second)
    {
        $sql = "SELECT * FROM emailvalidation WHERE email = '$second' ";
        $stmt = $this->pdo->prepare($sql);
        // $stmt->bindParam(':t', $second);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // var_dump($stmt);
        return $user;
        // $user = $stmt->rowCount();
        // if ($user > 0) {
        //    return true;
        // } else {
        //    return false;
        // }

    }


}
