<?php
require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../EmailSender.php';
require_once __DIR__ . '/Generators/UserDataGenerator.php';
require_once __DIR__ . '/Generators/EmailDataGenerator.php';

use Services\Generators\UserDataGenerator;
use Services\Generators\EmailDataGenerator;

class AuthService
{
    private $dbConnection;
    private $regUsercolumns;
    private $EmailCoulmn;
    private $gateway;
    private $userDataGenerator;
    private $EmailDataGenerator;
    private $createDbTables;
    private $response;
    private $connectToDataBase;
    private $mailsender;
    private $jwtCodec;
    private $refreshTokenGateway;
    private $ForgotPasswordColumns;

    public function __construct($pdoConnection)
    {
        $this->dbConnection = $pdoConnection;
        $this->regUsercolumns = require __DIR__ . '/../Config/UserColumns.php';
        $this->EmailCoulmn = require __DIR__ . '/../Config/EmailCoulmn.php';
        $this->ForgotPasswordColumns = require __DIR__ . '/../Config/ForgotPasswordColumns.php';
        $this->gateway = new TaskGatewayFunction($this->dbConnection);
        $this->userDataGenerator = new UserDataGenerator($this->gateway);
        $this->EmailDataGenerator = new EmailDataGenerator($this->gateway);
        $this->createDbTables = new CreateDbTables($this->dbConnection);
        $this->response = new JsonResponse();
        $this->connectToDataBase = new Database();
        $this->mailsender = new EmailSender();
        $key = $_ENV['SECRET_KEY'];
        $this->jwtCodec = new JWTCodec($key);
        $this->refreshTokenGateway = new RefreshTokenGateway($pdoConnection, $key);
    }

    public function __destruct()
    {
        $this->dbConnection = null;
    }


    public function registerUser(array $data)
    {
        $defaultData = $this->userDataGenerator->generateDefaultData($data);
        $regdata = array_merge($data, $defaultData);
        $emailData = ['createdAt' => $data['createdAt'], 'email' => $data['email']];
        $EmailValData = $this->EmailDataGenerator->generateVerificationData($emailData);


        $checkMailCond = ['email' => $regdata['email']];
        $arrayRecord = ['uid' => $data['referral']];
        $result = $this->createDbTables->createTableWithTypes(RegTable, $this->regUsercolumns);
        if ($result) {
            if ($this->gateway->checkEmailExistence($this->dbConnection, RegTable, $checkMailCond)) {
                $this->response->unprocessableEntity('This email already exists. try another.');
            } else {
                $bindingArrayforRegUser = $this->gateway->generateRandomStrings($regdata);
                $id = $this->gateway->createForUserWithTypes($this->dbConnection, RegTable, $this->regUsercolumns, $bindingArrayforRegUser, $regdata);
                if ($id) {
                    $fetchUser = $this->gateway->fetchDataWithId(RegTable, $id);
                    $username = $fetchUser['name'];
                    $bindingArrayforEmailVal = $this->gateway->generateRandomStrings($EmailValData);

                    $createEmailVal = $this->createDbTables->createTableWithTypes(EmailValidation, $this->EmailCoulmn);
                    if ($createEmailVal) {
                        $createEmailValTable = $this->connectToDataBase->insertDataWithTypes($this->dbConnection, EmailValidation, $this->EmailCoulmn, $bindingArrayforEmailVal, $EmailValData);
                        if ($createEmailValTable) {
                            if ($data['referral'] === "") {
                                $message = $this->gateway->createNotificationMessage(
                                    $id,
                                    '🎉 Welcome to ' . sitename,
                                    'Your registration was successful! We\'re excited to have you on board. Explore your dashboard and get started today.',
                                    $data['createdAt']
                                );
                                if ($message) {
                                    $sent = $this->mailsender->sendOtpEmail($fetchUser['email'], $username, $EmailValData['verificationToken']);
                                    if ($sent === true) {
                                        $response = ['status' => 'true', 'email' => $fetchUser['email']];
                                        $this->response->created($response);
                                    } else {

                                        $this->response->unprocessableEntity('could not send mail to user');

                                    }
                                }
                            }
                            if ($data['referral'] !== "") {

                                if ($this->gateway->recordExists(RegTable, $arrayRecord)) {
                                    $referralID = $this->gateway->genRandomAlphanumericstrings(10);
                                    $referralColumns = ['nameOfRefers', 'referrerId', 'referredId', 'referralId', 'dateOfReferral', 'amtEarned', 'status'];

                                    $date = $data['createdAt'];
                                    $referralData = [$fetchUser['name'], $data['referral'], $fetchUser['uid'], $referralID, $date, '0.00', 'Pending'];

                                    if (!$this->createDbTables->createTable(referralTable, $referralColumns)) {
                                        $this->response->unprocessableEntity(['Failed to create table: ' . referralTable]);
                                        return;
                                    }

                                    $referralBindingArray = $this->gateway->generateRandomStrings($referralColumns);
                                    $inserted = $this->connectToDataBase->insertData($this->dbConnection, referralTable, $referralColumns, $referralBindingArray, $referralData);

                                    if (!$inserted) {
                                        $this->response->unprocessableEntity(['Failed to insert referral record.']);
                                        return;
                                    }

                                    $notificationSent = $this->gateway->createNotificationMessage(
                                        $id,
                                        '🎉 Welcome to ' . sitename,
                                        'Your registration was successful! We\'re excited to have you on board. Explore your dashboard and get started today.',
                                        $data['createdAt']
                                    );


                                    if (!$notificationSent) {
                                        $this->response->unprocessableEntity(['Failed to send registration notification.']);
                                        return;
                                    }

                                    $emailSent = $this->mailsender->sendOtpEmail($fetchUser['email'], $username, $EmailValData['verificationToken']);

                                    if (!$emailSent) {
                                        $this->response->unprocessableEntity(['Failed to send registration email.']);
                                        return;
                                    }

                                    $this->response->created(['status' => 'true', 'email' => $fetchUser['email']]);
                                }
                            }

                        } else {

                            $this->response->unprocessableEntity('could not insert message');

                        }
                    }
                }
            }
        }
    }
    public function otp(array $data)
    {
        try {
            $fetchUserCondition = ['email' => $data['email']];
            $emailData = ['createdAt' => $data['createdAt'], 'email' => $data['email']];
            $EmailValData = $this->EmailDataGenerator->generateVerificationData($emailData);
            $bindingArrayforEmailVal = $this->gateway->generateRandomStrings($EmailValData);

            $createEmailVal = $this->createDbTables->createTableWithTypes(EmailValidation, $this->EmailCoulmn);
            if ($createEmailVal) {
                $createEmailValTable = $this->connectToDataBase->insertDataWithTypes($this->dbConnection, EmailValidation, $this->EmailCoulmn, $bindingArrayforEmailVal, $EmailValData);
                if ($createEmailValTable) {
                    $fetchUser = $this->gateway->fetchData(RegTable, $fetchUserCondition);
                    $username = $fetchUser['name'];
                            $sent = $this->mailsender->sendOtpForLogin($fetchUser['email'], $username, $EmailValData['verificationToken']);
                            if ($sent === true) {
                                $response = ['status' => 'true'];
                                $this->response->created($response);
                            } else {

                                $this->response->unprocessableEntity('could not send mail to user');

                            }
                         
                    }
            }
        } catch (\Throwable $e) {
            $this->response->unprocessableEntity($e->getMessage());
        }
    }
    public function verifyLoginOtp(array $data)
    {
        try {
            $email = $data['email'];
            $otp = $data['otp'];

            $conditionsForEmailval = ['email' => $email, 'verificationToken' => $otp];
            $conditionsForUser = ['email' => $email];

            $fetchEmailvalDetailsWithEmail = $this->gateway->fetchData(EmailValidation, $conditionsForEmailval);
            $fetchUserDetailsWithEmail = $this->gateway->fetchData(RegTable, $conditionsForUser);

            if ($fetchEmailvalDetailsWithEmail) {
                if ($fetchUserDetailsWithEmail) {
                    $id = $fetchUserDetailsWithEmail['id'];
                    $emailId = $fetchEmailvalDetailsWithEmail['id'];

                    $updateUserStatus = $this->connectToDataBase->updateData(
                        $this->dbConnection,
                        RegTable,
                        ['emailVerication'],
                        ['Verified'],
                        'id',
                        $fetchUserDetailsWithEmail['id']
                    );

                    if ($updateUserStatus) {
                        $h = 'Email Verification Complete';
                        $c = 'Your email address has been successfully verified. Thank you for confirming your identity and helping us keep your account secure.';

                        $message = $this->gateway->createNotificationMessage($id, $h, $c, $data['createdAt']);

                        if ($message) {
                     
                                $deleted = $this->connectToDataBase->deleteData($this->dbConnection, EmailValidation, 'id', $emailId);

                                if ($deleted) {
                                    $this->response->success('Email verified successfully');
                                } else {
                                    $this->response->unprocessableEntity('Failed to delete OTP record from validation table.');
                                }
                           
                        } else {
                            $this->response->unprocessableEntity('Failed to create verification notification message.');
                        }
                    } else {
                        $this->response->unprocessableEntity('Failed to update user verification status.');
                    }
                } else {
                    $this->response->unprocessableEntity('User with this email was not found.');
                }
            } else {
                $this->response->unprocessableEntity('Invalid or expired verification token.');
            }

        } catch (\Throwable $e) {
            $this->response->unprocessableEntity($e->getMessage());
        }
    }
    public function verifyEmail(array $data)
    {
        try {
            $email = $data['email'];
            $otp = $data['otp'];

            $conditionsForEmailval = ['email' => $email, 'verificationToken' => $otp];
            $conditionsForUser = ['email' => $email];

            $fetchEmailvalDetailsWithEmail = $this->gateway->fetchData(EmailValidation, $conditionsForEmailval);
            $fetchUserDetailsWithEmail = $this->gateway->fetchData(RegTable, $conditionsForUser);

            if ($fetchEmailvalDetailsWithEmail) {
                if ($fetchUserDetailsWithEmail) {
                    $id = $fetchUserDetailsWithEmail['id'];
                    $emailId = $fetchEmailvalDetailsWithEmail['id'];

                    $updateUserStatus = $this->connectToDataBase->updateData(
                        $this->dbConnection,
                        RegTable,
                        ['emailVerication'],
                        ['Verified'],
                        'id',
                        $fetchUserDetailsWithEmail['id']
                    );

                    if ($updateUserStatus) {
                        $h = 'Email Verification Complete';
                        $c = 'Your email address has been successfully verified. Thank you for confirming your identity and helping us keep your account secure.';

                        $message = $this->gateway->createNotificationMessage($id, $h, $c, $data['createdAt']);

                        if ($message) {
                            $sent = $this->mailsender->sendWelcomEmail($fetchUserDetailsWithEmail['email']);

                            if ($sent === true) {
                                $deleted = $this->connectToDataBase->deleteData($this->dbConnection, EmailValidation, 'id', $emailId);

                                if ($deleted) {
                                    $this->response->success('Email verified successfully');
                                } else {
                                    $this->response->unprocessableEntity('Failed to delete OTP record from validation table.');
                                }
                            } else {
                                $this->response->unprocessableEntity('Unable to send welcome email to the user.');
                            }
                        } else {
                            $this->response->unprocessableEntity('Failed to create verification notification message.');
                        }
                    } else {
                        $this->response->unprocessableEntity('Failed to update user verification status.');
                    }
                } else {
                    $this->response->unprocessableEntity('User with this email was not found.');
                }
            } else {
                $this->response->unprocessableEntity('Invalid or expired verification token.');
            }

        } catch (\Throwable $e) {
            $this->response->unprocessableEntity($e->getMessage());
        }
    }
    public function login(array $data)
    {
        $conditionsForFetch = ['email' => trim($data['email'])];
        $fetchUserDetailsWithEmail = $this->gateway->fetchData(RegTable, $conditionsForFetch);
    
        if ($fetchUserDetailsWithEmail) {
            $hashedPassword = $fetchUserDetailsWithEmail['encryptedPassword'];
            $verifyPassword = password_verify(trim($data['password']), $hashedPassword);
    
            if ($verifyPassword) {
                $userId = $fetchUserDetailsWithEmail['id'];
    
                // ✅ JWT Access Token
                $accessPayload = [
                    "sub" => $userId,
                    "email" => $fetchUserDetailsWithEmail['email'],
                    "exp" => strtotime($data['createdAt']) + 3600 + 24
                ];
                $accessToken = $this->jwtCodec->encode($accessPayload);
    
                // ✅ Clean up expired tokens before inserting new one
                $this->refreshTokenGateway->deleteExpired();
    
                // ✅ Refresh Token
                $refreshToken = bin2hex(random_bytes(64));
                $refreshExpiry = strtotime($data['createdAt']) + (60 * 60 * 24);
                $this->refreshTokenGateway->create($refreshToken, $refreshExpiry);
    
                // ✅ Set login status
                $updateUserStatus = $this->connectToDataBase->updateData(
                    $this->dbConnection,
                    RegTable,
                    ['UserLogin'],
                    ['True'],
                    'id',
                    $userId
                );
    
                if ($updateUserStatus) {
                    $_SESSION['UID'] = $userId;
    
                    return $this->response->created([
                        "accessToken" => $accessToken,
                        "refreshToken" => $refreshToken
                    ]);
                } else {
                    return $this->response->unprocessableEntity(['Unable to update login status']);
                }
            } else {
                return $this->response->unprocessableEntity(['email or password is incorrect']);
            }
        } else {
            return $this->response->unprocessableEntity(['this email is not registered']);
        }
    }
    

    public function resendOtp(array $data)
    {
        $emailData = ['createdAt' => $data['createdAt'], 'email' => $data['email']];
        $EmailValData = $this->EmailDataGenerator->generateVerificationData($emailData);
        try {
            $conditions = ['email' => $data['email']];
            $fetchUserWithEmail = $this->gateway->fetchData(RegTable, $conditions);

            if (!$fetchUserWithEmail || !is_array($fetchUserWithEmail)) {
                return $this->response->unprocessableEntity('User with the given email does not exist.');
            }

            $userEmail = $fetchUserWithEmail['email'];

            $checkEmailConditions = ['email' => $userEmail];
            $emailVerificationData = $this->gateway->fetchData(EmailValidation, $checkEmailConditions);

            if ($emailVerificationData && is_array($emailVerificationData)) {
                $emailVerificationID = $emailVerificationData['id'];

                // Delete old verification record
                $deleted = $this->connectToDataBase->deleteData($this->dbConnection, EmailValidation, 'id', $emailVerificationID);
                if (!$deleted) {
                    return $this->response->unprocessableEntity('Could not delete previous verification data.');
                }
            }


            $bindingArrayforEmailVal = $this->gateway->generateRandomStrings($EmailValData);
            $createEmailVal = $this->createDbTables->createTableWithTypes(EmailValidation, $this->EmailCoulmn);
            if ($createEmailVal) {
                $createEmailValTable = $this->connectToDataBase->insertDataWithTypes($this->dbConnection, EmailValidation, $this->EmailCoulmn, $bindingArrayforEmailVal, $EmailValData);
                if ($createEmailValTable) {
                    $sent = $this->mailsender->sendOtpEmail($fetchUserWithEmail['email'], $fetchUserWithEmail['name'], $EmailValData['verificationToken']);
                    if ($sent === true) {
                        $response = ['status' => 'true', 'email' => $fetchUserWithEmail['email']];
                        $this->response->created($response);
                    } else {
                        $this->response->unprocessableEntity('could not send mail to user');
                    }
                }
            }

        } catch (\Throwable $e) {
            $this->response->unprocessableEntity($e->getMessage());
        }
    }
    public function forgotPassword(array $data)
    {

        try {
            $createForgottenPaasword = $this->createDbTables->createTableWithTypes(forgotPass, $this->ForgotPasswordColumns);
            if ($createForgottenPaasword) {
                $conditions = ['email' => $data['email']];
                $fetchUserWithEmail = $this->gateway->fetchData(RegTable, $conditions);

                if (!$fetchUserWithEmail || !is_array($fetchUserWithEmail)) {
                    return $this->response->unprocessableEntity('User with the given email does not exist.');
                }
                $forgotPasswordData = $this->EmailDataGenerator->generateForgotPasswordData(['email' => $data['email'], 'createdAt' => $data['createdAt'], 'identifier' => $fetchUserWithEmail['accToken']]);
                $userEmail = $fetchUserWithEmail['email'];

                $checkEmailConditions = ['userEmail' => $userEmail];
                $emailVerificationData = $this->gateway->fetchData(forgotPass, $checkEmailConditions);

                if ($emailVerificationData && is_array($emailVerificationData)) {
                    $emailVerificationID = $emailVerificationData['id'];

                    // Delete old verification record
                    $deleted = $this->connectToDataBase->deleteData($this->dbConnection, forgotPass, 'id', $emailVerificationID);
                    if (!$deleted) {
                        return $this->response->unprocessableEntity('Could not delete previous forgotten password data.');
                    }
                }

                $bindingArrayforForgottenPaasword = $this->gateway->generateRandomStrings($forgotPasswordData);

                $createForgottenPaaswordTable = $this->connectToDataBase->insertDataWithTypes($this->dbConnection, forgotPass, $this->ForgotPasswordColumns, $bindingArrayforForgottenPaasword, $forgotPasswordData);
                if ($createForgottenPaaswordTable) {
                    $sent = $this->mailsender->sendResetPasswordEmail($fetchUserWithEmail['name'], $forgotPasswordData['resetToken'], $fetchUserWithEmail['email'], $fetchUserWithEmail['accToken']);
                    if ($sent === true) {
                        $response = ['status' => 'true'];
                        $this->response->created($response);
                    } else {
                        $this->response->unprocessableEntity('could not send mail to user');
                    }
                }
            }

        } catch (\Throwable $e) {
            $this->response->unprocessableEntity($e->getMessage());
        }
    }
    public function verifyResetPassword(array $data)
    {
        try {
            $conditionsForFetch = ['userEmail' => trim($data['email']), 'resetToken' => trim($data['token']), 'userIdentifier' => trim($data['num'])];
            $fetchResetDetailsWithId = $this->gateway->fetchData(forgotPass, $conditionsForFetch);
            if ($fetchResetDetailsWithId) {
                $fetchedDataId = $fetchResetDetailsWithId['id'];
                $columnOfDeleteForUser = 'id';
                $deleted = $this->connectToDataBase->deleteData($this->dbConnection, forgotPass, $columnOfDeleteForUser, $fetchedDataId);
                if ($deleted) {
                    return $this->response->created('Reset Password Link Verified');
                }
            } else {
                $errors[] = 'Invalid Password Reset Link, Try Again';
                if (!empty($errors)) {
                    $this->response->unprocessableEntity($errors);
                    return;
                }
            }
        } catch (\Throwable $e) {
            $this->response->unprocessableEntity($e->getMessage());
        }
    }
    public function changePassword(array $data)
    {
        try {
            $email = trim($data['email']);
            $newPassword = trim($data['password']);

            // Fetch user by email
            $conditions = ['email' => $email];
            $fetchUser = $this->gateway->fetchData(RegTable, $conditions);

            if (!$fetchUser) {
                return $this->response->unprocessableEntity('User with this email was not found.');
            }

            $userId = $fetchUser['id'];
            $username = $fetchUser['name'];
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            // Update password in RegTable
            $updated = $this->connectToDataBase->updateData(
                $this->dbConnection,
                RegTable,
                ['password', 'encryptedPassword'],
                [$newPassword, $hashedPassword],
                'id',
                $userId
            );

            if ($updated) {
                // Send password change confirmation email
                $sent = $this->mailsender->sendPasswordChangedEmail($username, $email);
                if (!$sent) {
                    return $this->response->unprocessableEntity('Password updated, but failed to send confirmation email.');
                }
                return $this->response->success('Password updated successfully. Confirmation email sent.');
            } else {
                return $this->response->unprocessableEntity('Failed to update password.');
            }
        } catch (\Throwable $e) {
            $this->response->unprocessableEntity($e->getMessage());
        }
    }

}


