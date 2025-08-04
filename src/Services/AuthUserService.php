<?php
require_once __DIR__ . '/../constants.php';
require_once __DIR__ . '/../EmailSender.php';
require_once __DIR__ . '/Generators/UserDataGenerator.php';
require_once __DIR__ . '/Generators/EmailDataGenerator.php';
require_once __DIR__ . '/Generators/KycDataGenerator.php';
require_once __DIR__ . '/Generators/advancedVerifDataGenerator.php';

use Services\Generators\UserDataGenerator;
use Services\Generators\EmailDataGenerator;
use Services\Generators\KycDataGenerator;
use Services\Generators\advancedVerifDataGenerator;

class AuthUserService
{
    private $dbConnection;
    private $regUsercolumns;
    private $EmailCoulmn;
    private $gateway;
    private $userDataGenerator;
    private $EmailDataGenerator;
    private $KycDataGenerator;
    private $advancedVerifDataGenerator;
    private $createDbTables;
    private $response;
    private $connectToDataBase;
    private $mailsender;
    private $jwtCodec;
    private $refreshTokenGateway;
    private $ForgotPasswordColumns;
    private $KycColumn;
    private $advancedVerifColumn;

    public function __construct($pdoConnection)
    {
        $this->dbConnection = $pdoConnection;
        $this->regUsercolumns = require __DIR__ . '/../Config/UserColumns.php';
        $this->EmailCoulmn = require __DIR__ . '/../Config/EmailCoulmn.php';
        $this->KycColumn = require __DIR__ . '/../Config/KycColumn.php';
        $this->ForgotPasswordColumns = require __DIR__ . '/../Config/ForgotPasswordColumns.php';
        $this->advancedVerifColumn = require __DIR__ . '/../Config/advancedVerifColumn.php';
        $this->gateway = new TaskGatewayFunction($this->dbConnection);
        $this->userDataGenerator = new UserDataGenerator($this->gateway);
        $this->EmailDataGenerator = new EmailDataGenerator($this->gateway);
        $this->KycDataGenerator = new KycDataGenerator($this->gateway);
        $this->advancedVerifDataGenerator = new advancedVerifDataGenerator($this->gateway);
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
    public function advancedVerification(array $data = null, array $file)
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];

        // try {
        // Decode JWT token
        $decodedPayload = $this->jwtCodec->decode($token);
        $userid = $decodedPayload['sub'];

        $user = $this->gateway->fetchData(RegTable, ['id' => $userid]);
        $proofOfAddress = $this->gateway->processImageWithgivenNameFiles($file['proofOfAddress']);
        if (is_array($proofOfAddress) || !is_string($proofOfAddress) || empty($proofOfAddress)) {
            return $this->response->unprocessableEntity('Invalid or failed to process front image.');
        }

        $regdata = array_merge([
            'proofOfAddress' => $proofOfAddress
        ], $data, ['userId' => $user['accToken']]);
        $advancedData = $this->advancedVerifDataGenerator->generateDefaultData($regdata);
        $result = $this->createDbTables->createTableWithTypes(advancedVerification, $this->advancedVerifColumn);
        $bindingArrayforRegUser = $this->gateway->generateRandomStrings($advancedData);

        if ($result) {
            $id = $this->gateway->createForUserWithTypes($this->dbConnection, advancedVerification, $this->advancedVerifColumn, $bindingArrayforRegUser, $advancedData);
            if ($id) {
                $notification = $this->gateway->createNotificationMessage(
                    $userid,
                    '📄 Advanced Verification Submitted',
                    'Thank you for submitting your proof of address. Our team will review it shortly.',
                    $data['createdAt'] ?? date('Y-m-d H:i:s')
                );
                if ($notification) {
                    $username = $user['name'];
                    // $sent = $this->mailsender->sendOtpEmail($user['email'], $username, $EmailValData['verificationToken']);
                    // if ($sent === true) {
                    $response = ['status' => 'true'];
                    $this->response->created($response);
                    // } else {

                    //     $this->response->unprocessableEntity('could not send mail to user');

                    // }
                }
            }

        }

        // } catch (InvalidArgumentException $e) {
        //     return $this->response->unauthorized("Invalid token format.");
        // } catch (InvalidSignatureException $e) {
        //     return $this->response->unauthorized("Invalid token signature.");
        // } catch (TokenExpiredException $e) {
        //     return $this->response->unauthorized("Token has expired.");
        // } catch (Exception $e) {
        //     return $this->response->unauthorized("Token decode error: " . $e->getMessage());
        // }


    }
    public function submitVerification(array $data, array $file)
    {

        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];

        try {
            // Decode JWT token
            $decodedPayload = $this->jwtCodec->decode($token);
            $userid = $decodedPayload['sub'];

            $user = $this->gateway->fetchData(RegTable, ['id' => $userid]);
            $frontImage = $this->gateway->processImageWithgivenNameFiles($file['frontImage']);
            if (is_array($frontImage) || !is_string($frontImage) || empty($frontImage)) {
                return $this->response->unprocessableEntity('Invalid or failed to process front image.');
            }

            // Process back image
            $backImage = $this->gateway->processImageWithgivenNameFiles($file['backImage']);
            if (is_array($backImage) || !is_string($backImage) || empty($backImage)) {
                return $this->response->unprocessableEntity('Invalid or failed to process back image.');
            }

            $regdata = array_merge($data, [
                'frontImage' => $frontImage,
                'backImage' => $backImage
            ], ['accToken' => $user['accToken']]);

            // var_dump($regdata);
            $kycData = $this->KycDataGenerator->generateDefaultData($regdata);
            $result = $this->createDbTables->createTableWithTypes(idVer, $this->KycColumn);
            $bindingArrayforRegUser = $this->gateway->generateRandomStrings($kycData);

            if ($result) {
                $id = $this->gateway->createForUserWithTypes($this->dbConnection, idVer, $this->KycColumn, $bindingArrayforRegUser, $kycData);
                // if ($id) {
                //     $notification = $this->gateway->createNotificationMessage(
                //         $userid,
                //         'Basic Verification Submitted',
                //         'Thank you for submitting your basic verification. Our team will review it shortly.',
                //         $data['createdAt']
                //     );
                //     if ($notification) {
                //         $username = $user['name'];
                //         // $sent = $this->mailsender->sendOtpEmail($user['email'], $username, $EmailValData['verificationToken']);
                //         // if ($sent === true) {
                //         $response = ['status' => 'true'];
                //         $this->response->created($response);
                //         // } else {

                //         //     $this->response->unprocessableEntity('could not send mail to user');

                //         // }
                //     }
                // }

            }

        } catch (InvalidArgumentException $e) {
            return $this->response->unauthorized("Invalid token format.");
        } catch (InvalidSignatureException $e) {
            return $this->response->unauthorized("Invalid token signature.");
        } catch (TokenExpiredException $e) {
            return $this->response->unauthorized("Token has expired.");
        } catch (Exception $e) {
            return $this->response->unauthorized("Token decode error: " . $e->getMessage());
        }


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
    public function generateChangePasswordOtp(array $data)
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
                    $sent = $this->mailsender->sendOtpForChangePassword($fetchUser['email'], $username, $EmailValData['verificationToken']);
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
    public function generateGoogleAuthOtp(array $data)
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
                    $sent = $this->mailsender->sendGoogleAuthEmailVerificationCode($fetchUser['email'], $username, $EmailValData['verificationToken']);
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
                        ['emailVerication', 'refreshToken'],
                        ['Verified', 'true'],
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
    public function verifyChangePasswordOtp(array $data)
    {
        try {
            $email = $data['email'] ?? null;
            $otp = $data['otp'] ?? null;
            $createdAt = $data['createdAt'] ?? date('Y-m-d H:i:s');

            if (!$email || !$otp) {
                return $this->response->unprocessableEntity("Email and OTP are required.");
            }

            // Fetch OTP record
            $emailValidationRecord = $this->gateway->fetchData(EmailValidation, [
                'email' => $email,
                'verificationToken' => $otp
            ]);

            if (!$emailValidationRecord) {
                return $this->response->unprocessableEntity('Invalid or expired verification token.');
            }

            // Fetch user by email
            $user = $this->gateway->fetchData(RegTable, ['email' => $email]);
            if (!$user) {
                return $this->response->unprocessableEntity('User not found.');
            }

            $userId = $user['id'];
            $emailValId = $emailValidationRecord['id'];

            // Update verification status only (no password change)
            $update = $this->connectToDataBase->updateData(
                $this->dbConnection,
                RegTable,
                ['emailVerication', 'refreshToken'],
                ['Verified', 'true'],
                'id',
                $userId
            );

            if (!$update) {
                return $this->response->unprocessableEntity("Failed to update user verification status.");
            }

            // Notify user
            $this->gateway->createNotificationMessage(
                $userId,
                '📧 Email OTP Verified',
                'Your verification code has been successfully confirmed. You may now proceed to change your password.',
                $createdAt
            );

            // Delete used OTP token
            $this->connectToDataBase->deleteData($this->dbConnection, EmailValidation, 'id', $emailValId);

            return $this->response->success('OTP verified successfully.');

        } catch (\Throwable $e) {
            return $this->response->unprocessableEntity("Error: " . $e->getMessage());
        }
    }
    public function verifyGoogleAuthOtp(array $data)
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

                    // Ensure the column exists
                    $createColumn = $this->createDbTables->createTable(RegTable, ['isGoogleAUthEnabled']);
                    if ($createColumn) {
                        $updateUserStatus = $this->connectToDataBase->updateData(
                            $this->dbConnection,
                            RegTable,
                            ['isGoogleAUthEnabled'],
                            ['Verified'],
                            'id',
                            $id
                        );
                    }

                    if ($updateUserStatus) {
                        $header = 'Google Authenticator Email Verification Successful';
                        $content = 'You have successfully verified your email address to activate Google Authenticator on your account. Your account is now secured with two-factor authentication (2FA).';

                        $message = $this->gateway->createNotificationMessage($id, $header, $content, $data['createdAt']);

                        if ($message) {
                            $deleted = $this->connectToDataBase->deleteData($this->dbConnection, EmailValidation, 'id', $emailId);

                            if ($deleted) {
                                $this->response->success('Your Google Authenticator email verification is complete. 2FA is now active.');
                            } else {
                                $this->response->unprocessableEntity('Verification succeeded, but failed to remove the OTP record from validation table.');
                            }
                        } else {
                            $this->response->unprocessableEntity('Verification succeeded, but failed to create a confirmation notification.');
                        }
                    } else {
                        $this->response->unprocessableEntity('Could not update user to reflect Google Authenticator status.');
                    }
                } else {
                    $this->response->unprocessableEntity('No user found with this email address.');
                }
            } else {
                $this->response->unprocessableEntity('Invalid or expired email verification code for Google Authenticator.');
            }
        } catch (\Throwable $e) {
            $this->response->unprocessableEntity('Server error: ' . $e->getMessage());
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
                    "exp" => strtotime($data['createdAt']) + (60 * 60 * 24)
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
                    ['UserLogin', 'refreshToken'],
                    ['True', 'false'],
                    'id',
                    $userId
                );

                if ($updateUserStatus) {
                    $fetchUserDetailsWithEmail = $this->gateway->fetchData(RegTable, $conditionsForFetch);
                    if ($fetchUserDetailsWithEmail) {
                        return $this->response->created([
                            "accessToken" => $accessToken,
                            "allowOtp" => $fetchUserDetailsWithEmail['allowOtp'],
                            "confirmOtp" => $fetchUserDetailsWithEmail['refreshToken']
                        ]);
                    }
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
    public function resendChangePasswordOtp(array $data)
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
                    $sent = $this->mailsender->sendOtpForChangePassword($fetchUserWithEmail['email'], $fetchUserWithEmail['name'], $EmailValData['verificationToken']);
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
    public function insertcoins(array $data)
    {
        try {
            $columns = ['coin_id', 'symbol', 'name'];
            $inserted = 0;

            foreach ($data as $item) {
                $row = [
                    'coin_id' => $item['id'],
                    'symbol' => $item['symbol'],
                    'name' => $item['name']
                ];

                // Generate binding keys
                $bindingArray = ['coin_id', 'symbol', 'name'];

                $success = $this->connectToDataBase->insertData(
                    $this->dbConnection,
                    wallet,
                    $columns,
                    $bindingArray,
                    $row
                );

                if ($success) {
                    $inserted++;
                }
            }

            if ($inserted > 0) {
                $this->response->success("Inserted $inserted records.");
            } else {
                $this->response->unprocessableEntity('Could not insert any records');
            }
        } catch (\Throwable $e) {
            $this->response->unprocessableEntity($e->getMessage());
        }

    }
    public function updateAvatar($file)
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];

        try {
            // Decode JWT token
            $decodedPayload = $this->jwtCodec->decode($token);
            $userid = $decodedPayload['sub'];

            // Validate and upload image
            $imgResult = $this->gateway->processImageWithgivenNameFiles($file['documents']);

            if (is_array($imgResult)) {
                // Error handled already inside `processImageWithgivenNameFiles`
                return; // prevent continuing execution
            }

            if (!is_string($imgResult) || empty($imgResult)) {
                return;
            }

            // Store image path in DB
            $updated = $this->connectToDataBase->updateData(
                $this->dbConnection,
                RegTable,
                ['image'],
                [json_encode($imgResult)],
                'id',
                $userid
            );

            if ($updated) {
                return $this->response->created("Profile image has been updated successfully.");
            } else {
                return $this->response->unprocessableEntity("Failed to update profile image. Please try again.");
            }

        } catch (InvalidArgumentException $e) {
            return $this->response->unauthorized("Invalid token format.");
        } catch (InvalidSignatureException $e) {
            return $this->response->unauthorized("Invalid token signature.");
        } catch (TokenExpiredException $e) {
            return $this->response->unauthorized("Token has expired.");
        } catch (Exception $e) {
            return $this->response->unauthorized("Token decode error: " . $e->getMessage());
        }
    }
    public function disableAccount()
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];

    //     try {
    //         // Decode JWT token
    //         $decodedPayload = $this->jwtCodec->decode($token);
    //         $userid = $decodedPayload['sub'];

    //  $advancedData = $this->advancedVerifDataGenerator->generateDefaultData($regdata);
    //     $result = $this->createDbTables->createTableWithTypes(advancedVerification, $this->advancedVerifColumn);
    //     $bindingArrayforRegUser = $this->gateway->generateRandomStrings($advancedData);

    //     if ($result) {
    //         $id = $this->gateway->createForUserWithTypes($this->dbConnection, advancedVerification, $this->advancedVerifColumn, $bindingArrayforRegUser, $advancedData);
    //         if ($id) {
    //             $notification = $this->gateway->createNotificationMessage(
    //                 $userid,
    //                 '📄 Advanced Verification Submitted',
    //                 'Thank you for submitting your proof of address. Our team will review it shortly.',
    //                 $data['createdAt'] ?? date('Y-m-d H:i:s')
    //             );
    //             if ($notification) {
    //                 $username = $user['name'];
    //                 // $sent = $this->mailsender->sendOtpEmail($user['email'], $username, $EmailValData['verificationToken']);
    //                 // if ($sent === true) {
    //                 $response = ['status' => 'true'];
    //                 $this->response->created($response);
    //                 // } else {

    //                 //     $this->response->unprocessableEntity('could not send mail to user');

    //                 // }
    //             }
    //         }

    //     }
            

    //     } catch (InvalidArgumentException $e) {
    //         return $this->response->unauthorized("Invalid token format.");
    //     } catch (InvalidSignatureException $e) {
    //         return $this->response->unauthorized("Invalid token signature.");
    //     } catch (TokenExpiredException $e) {
    //         return $this->response->unauthorized("Token has expired.");
    //     } catch (Exception $e) {
    //         return $this->response->unauthorized("Token decode error: " . $e->getMessage());
    //     }
    }
    public function setAntiPhishingCode($data)
    {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];

        try {
            // Decode JWT token
            $decodedPayload = $this->jwtCodec->decode($token);
            $userid = $decodedPayload['sub'];

            $createColumn = $this->createDbTables->createTable(RegTable, ['setAntiPhishingCode']);
            if ($createColumn) {
                // Store anti-phishing code in DB
                $updated = $this->connectToDataBase->updateData(
                    $this->dbConnection,
                    RegTable,
                    ['setAntiPhishingCode'],
                    [$data['code']],
                    'id',
                    $userid
                );
                if ($updated) {
                    return $this->response->created("Anti-phishing code has been set successfully.");
                } else {
                    return $this->response->unprocessableEntity("Failed to set anti-phishing code. Please try again.");
                }
            }


        } catch (InvalidArgumentException $e) {
            return $this->response->unauthorized("Invalid token format.");
        } catch (InvalidSignatureException $e) {
            return $this->response->unauthorized("Invalid token signature.");
        } catch (TokenExpiredException $e) {
            return $this->response->unauthorized("Token has expired.");
        } catch (Exception $e) {
            return $this->response->unauthorized("Token decode error: " . $e->getMessage());
        }
    }

    public function verify2fa($data)
    {

        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->response->unauthorized("Authorization header missing or invalid");
        }

        $token = $matches[1];
        try {
            $decodedPayload = $this->jwtCodec->decode($token);
            $userId = $decodedPayload['sub'];
            $userCode = $data['token'] ?? null;
            if (!$userCode) {
                return $this->response->unprocessableEntity("Verification code required.");
            }

            $user = $this->gateway->fetchData(RegTable, ['id' => $userId]);
            if (!$user || !isset($user['totp_secret'])) {
                return $this->response->unprocessableEntity("User not found or 2FA not initialized.");
            }


            $secret = $user['totp_secret'];

            // Create TOTP instance
            $clock = new \Symfony\Component\Clock\NativeClock();
            $totp = \OTPHP\TOTP::create(
                secret: $secret,
                period: 30,
                digest: 'sha1',
                digits: 6,
                epoch: OTPHP\TOTP::DEFAULT_EPOCH,
                clock: $clock
            );

            if ($totp->verify($userCode)) {

                $createColumn = $this->createDbTables->createTable(RegTable, ['isGoogleAUthEnabled']);
                if ($createColumn) {
                    $updateUserStatus = $this->connectToDataBase->updateData(
                        $this->dbConnection,
                        RegTable,
                        ['isGoogleAUthEnabled'],
                        ['Pending'],
                        'id',
                        $userId
                    );
                    if ($updateUserStatus) {
                        return $this->response->success([
                            'success' => true,
                            'message' => '2FA code verified successfully.',
                        ]);
                    }
                }
            } else {
                return $this->response->unprocessableEntity("Invalid 2FA code.");
            }

        } catch (Exception $e) {
            return $this->response->unprocessableEntity("Token decode or verification error: " . $e->getMessage());
        }
    }


}


