<?php
require_once __DIR__ . '/AuthUserService.php';

class UserPostService
{
    private $pdovar;
    private $authService;
    private $columns;

    public function __construct($pdoConnection)
    {
        $this->pdovar = $pdoConnection;
        $this->authService = new AuthUserService($this->pdovar);
        $this->columns = require __DIR__ . '/../Config/UserColumns.php';
    }

    public function __destruct()
    {
        $this->pdovar = null;
    }

    public function handlePost(string $action, array $data, $file): void
    {

        switch ($action) {
            case "registerUser":
                $this->authService->registerUser($data);
                break;
            case "verify-email":
                $this->authService->verifyEmail($data);
                break;
            case "verifyLoginOtp":
                $this->authService->verifyLoginOtp($data);
                break;
            case "otp":
                $this->authService->otp($data);
                break;
            case "resend-otp":
                $this->authService->resendOtp($data);
                break;
            case "generateChangePasswordOtp":
                $this->authService->generateChangePasswordOtp($data);
                break;
            case "generateGoogleAuthOtp":
                $this->authService->generateGoogleAuthOtp($data);
                break;
            case "verifyGoogleAuthOtp":
                $this->authService->verifyGoogleAuthOtp($data);
                break;
            case "resendChangePasswordOtp":
                $this->authService->resendChangePasswordOtp($data);
                break;
            case "verifyChangePasswordOtp":
                $this->authService->verifyChangePasswordOtp($data);
                break;
            case "login":
                $this->authService->login($data);
                break;
            case "forgot-password":
                $this->authService->forgotPassword($data);
                break;
            case "verifyResetPassword":
                $this->authService->verifyResetPassword($data);
                break;
            case "changePassword":
                $this->authService->changePassword($data);
                break;
            case "insert-coins":
                $this->authService->insertcoins($data);
                break;
            case "updateAvatar":
                $this->authService->updateAvatar($file);
                break;
            case "verify2fa":
                $this->authService->verify2fa($data);
                break;
            case "setAntiPhishingCode":
                $this->authService->setAntiPhishingCode($data);
                break;
            case "submitVerification":
                $this->authService->submitVerification($data, $file);
                break;
            case "advancedVerification":
                $this->authService->advancedVerification($data, $file);
                break;
            case "institutionalVerification":
                $this->authService->institutionalVerification($data, $file);
                break;
            case "disableAccount":
                $this->authService->disableAccount();
                break;
            case "deposit":
                $this->authService->deposit($data);
                break;
            case "createorder":
                $this->authService->createOrder($data);
                break;

        }
    }
}
