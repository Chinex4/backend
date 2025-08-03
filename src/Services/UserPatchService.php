<?php 

class UserPatchService{
    private $pdovar;
    private $authService;
    private $encrypt;
    private $mailsender;
    private $conn;
    private $createDbTables;
    private $gateway;
    private $columns;
    private $userDataGenerator;
    private $patch;
    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection; 
        $this->patch = new PatchGateway($this->pdovar); 
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handlePatch(string $action, ?array $data = null): void
    {
        switch ($action) {
            case "updateNickname":
                $this->patch->updateNickname($data);
                break;
            case "updateLanguage":
                $this->patch->updateLanguage($data);
                break;
            case "disableGoogleAuth":
                $this->patch->disableGoogleAuth();
                break;
        }
    }
}