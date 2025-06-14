<?php
require_once __DIR__ . '/Services/AdminFetchService.php';
require_once __DIR__ . '/Services/AdminPutService.php';
require_once __DIR__ . '/Services/AdminPatchService.php';
require_once __DIR__ . '/Services/AdminDeleteService.php';
require_once __DIR__ . '/Services/AdminPostService.php';

class AdminGateway
{
    private $pdovar;
    private $fetch;
    private $put;
    private $patch;
    private $delete;
    private $post;
    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection;
        $this->fetch = new AdminfetchService($this->pdovar);
        $this->put = new AdminPutService($this->pdovar);
        $this->patch = new AdminPatchService($this->pdovar);
        $this->delete = new AdminDeleteService($this->pdovar);
        $this->post = new AdminPostService($this->pdovar);
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

 
  
    public function handleAction(string $action, array $data): void
    {
        $this->post->handlePost($action, $data);
    }
    public function handleFetch(string $action): void
    {
        $this->fetch->handleFetch($action);
    }
    public function handleAdminFetchAll(string $action): void
    {
        $this->fetch->handleFetch($action);
    }
    public function handleAdminPut(string $action,  ?array $data, string $accToken): void
    {
        $this->put->handleAdminPut($action, $data, $accToken);
    }
    public function handleAdminPatch(string $action,  ?array $data, string $accToken): void
    {
        $this->patch->handlePatch($action, $data, $accToken);
    }
    public function handleAdminDelete(string $action, int $id): void
    {
        $this->delete->handleDelete($action, $id);
    }


}
