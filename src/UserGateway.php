<?php
require_once __DIR__ . '/Services/UserFetchService.php';
require_once __DIR__ . '/Services/UserPutService.php';
require_once __DIR__ . '/Services/UserPatchService.php';
require_once __DIR__ . '/Services/UserDeleteService.php';
require_once __DIR__ . '/Services/UserPostService.php';
class UserGateway
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
        $this->fetch = new UserfetchService($this->pdovar);
        $this->put = new UserPutService($this->pdovar);
        $this->patch = new UserPatchService($this->pdovar);
        $this->delete = new UserDeleteService($this->pdovar);
        $this->post = new UserPostService($this->pdovar);
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handleAction($action, array $data = [], $file = null)
    {
        $this->post->handlePost($action, $data, $file);
    }
    public function handleFetch(string $action): void
    {
        $this->fetch->handleFetch($action);
    }
    public function handlePut(string $action): void
    {
        $this->put->handlePut($action);
    }
    public function handlePatch(string $action, array $data): void
    {
        $this->patch->handlePatch($action, $data);
    }
    public function handleDelete(string $action): void
    {
        $this->delete->handleDelete($action);
    }



}
