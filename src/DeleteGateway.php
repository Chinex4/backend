<?php

class DeleteGateway
{
    private $pdovar;
    private $response;
    private $encrypt;
    private $mailsender;
    private $conn;
    private $createDbTables;
    private $gateway;
    public function __construct($pdoConnection)
    {

        $this->pdovar = $pdoConnection;
        $this->createDbTables = new CreateDbTables($pdoConnection);
        $this->mailsender = new EmailSender();
        $this->response = new JsonResponse();
        $this->gateway = new TaskGatewayFunction($pdoConnection);
        $this->conn = new Database();
        $this->createDbTables = new CreateDbTables($pdoConnection);
    }



    public function deletDeposit($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, depositTable, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('
 reinitiate another deposit, The previous record has been deleted.');
        } else {
            $errors[] = 'could not delete transaction';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deletPlanDeposit($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, usersPlan, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('
 reinitiate another plan deposit, The previous record has been deleted.');
        } else {
            $errors[] = 'could not delete transaction';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function unsubscribe($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, copytrade, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('true');
        } else {
            $errors[] = 'could not delete transaction';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deletShare($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, Shares, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('
 reinitiate another Purchase, The previous record has been deleted.');
        } else {
            $errors[] = 'could not delete transaction';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deleteDeposit($id)
    {
        $fetchUserWithIdcond = ['id' => $id];
        $fetchDeposit = $this->gateway->fetchData(trans, $fetchUserWithIdcond);
        if ($fetchDeposit) {
            (float) $oldamount = $fetchDeposit['amount'];
            $fetchUserWithIdcondition = ['accToken' => $fetchDeposit['token']];
            $fetchUserDetails = $this->gateway->fetchData(RegTable, $fetchUserWithIdcondition);
            if ($fetchUserDetails) {
                $balance = (float) $fetchUserDetails['userBalance'];
                $newbalance = $balance - $oldamount;
                $columnToBeUpdate = ['userBalance'];
                $dataToUpdateUser = [$newbalance];
                $updateReference = 'accToken';
                $updated = $this->conn->updateData($this->pdovar, RegTable, $columnToBeUpdate, $dataToUpdateUser, $updateReference, $fetchDeposit['token']);
                if ($updated) {
                    $columnOfDeleteForUser = 'id';
                    $deletDeposit = $this->conn->deleteData($this->pdovar, trans, $columnOfDeleteForUser, $id);
                    if ($deletDeposit) {
                        return $this->response->respondCreated('this deposit has been deleted successfully');
                    } else {
                        $errors[] = 'could not delete transaction';
                        if (!empty($errors)) {
                            $this->response->respondUnprocessableEntity($errors);
                            return;
                        }
                    }
                } else {
                    $errors[] = 'could not edit this user balance ';
                    if (!empty($errors)) {
                        $this->response->respondUnprocessableEntity($errors);
                    }
                }

            } else {
                $errors[] = 'could not fetch any user';
                if (!empty($errors)) {
                    $this->response->respondUnprocessableEntity($errors);
                }
            }


        } else {
            $errors[] = 'could not fetch any deposit';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
            }
        }



    }
    public function deleteShares($id)
    {
        $fetchUserWithIdcond = ['id' => $id];
        $fetchDeposit = $this->gateway->fetchData(Shares, $fetchUserWithIdcond);
        if ($fetchDeposit) {
            (float) $oldamount = $fetchDeposit['amount'];
            $fetchUserWithIdcondition = ['id' => $fetchDeposit['userid']];
            $fetchUserDetails = $this->gateway->fetchData(RegTable, $fetchUserWithIdcondition);
            if ($fetchUserDetails) {
                $balance = (float) $fetchUserDetails['balance'];
                $newbalance = $balance - $oldamount;
                $columnToBeUpdate = ['balance'];
                $dataToUpdateUser = [$newbalance];
                $updateReference = 'id';
                $updated = $this->conn->updateData($this->pdovar, RegTable, $columnToBeUpdate, $dataToUpdateUser, $updateReference, $fetchDeposit['userid']);
                if ($updated) {
                    $columnOfDeleteForUser = 'id';
                    $deletDeposit = $this->conn->deleteData($this->pdovar, Shares, $columnOfDeleteForUser, $id);
                    if ($deletDeposit) {
                        return $this->response->respondCreated('this deposit has been deleted successfully');
                    } else {
                        $errors[] = 'could not delete transaction';
                        if (!empty($errors)) {
                            $this->response->respondUnprocessableEntity($errors);
                            return;
                        }
                    }
                } else {
                    $errors[] = 'could not edit this user balance ';
                    if (!empty($errors)) {
                        $this->response->respondUnprocessableEntity($errors);
                    }
                }

            } else {
                $errors[] = 'could not fetch any user';
                if (!empty($errors)) {
                    $this->response->respondUnprocessableEntity($errors);
                }
            }


        } else {
            $errors[] = 'could not fetch any deposit';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
            }
        }



    }
    public function deletecryptowithdrawal($id)
    {
        $fetchUserWithIdcond = ['id' => $id];
        $fetchDeposit = $this->gateway->fetchData(trans, $fetchUserWithIdcond);
        if ($fetchDeposit) {
            (float) $oldamount = $fetchDeposit['amount'];
            $fetchUserWithIdcondition = ['accToken' => $fetchDeposit['token']];
            $fetchUserDetails = $this->gateway->fetchData(RegTable, $fetchUserWithIdcondition);
            if ($fetchUserDetails) {
                $balance = (float) $fetchUserDetails['userBalance'];
                $deposit = (float) $fetchUserDetails['userProfit'];
                $newbalance = $balance - $oldamount;
                $newDeposit = $deposit - $oldamount;
                $columnToBeUpdate = ['userBalance', 'userProfit'];
                $dataToUpdateUser = [$newbalance, $newDeposit];
                $updateReference = 'accToken';
                $updated = $this->conn->updateData($this->pdovar, RegTable, $columnToBeUpdate, $dataToUpdateUser, $updateReference, $fetchDeposit['token']);
                if ($updated) {
                    $columnOfDeleteForUser = 'id';
                    $deletDeposit = $this->conn->deleteData($this->pdovar, trans, $columnOfDeleteForUser, $id);
                    if ($deletDeposit) {
                        return $this->response->respondCreated('this crypto withdrawal has been deleted successfully');
                    } else {
                        $errors[] = 'could not delete transaction';
                        if (!empty($errors)) {
                            $this->response->respondUnprocessableEntity($errors);
                            return;
                        }
                    }
                } else {
                    $errors[] = 'could not edit this user balance ';
                    if (!empty($errors)) {
                        $this->response->respondUnprocessableEntity($errors);
                    }
                }

            } else {
                $errors[] = 'could not fetch any user';
                if (!empty($errors)) {
                    $this->response->respondUnprocessableEntity($errors);
                }
            }


        } else {
            $errors[] = 'could not fetch any withdrawal';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
            }
        }



    }
    public function deleteBankwithdrawal($id)
    {
        $fetchUserWithIdcond = ['id' => $id];
        $fetchDeposit = $this->gateway->fetchData(bnkwithdrawalTable, $fetchUserWithIdcond);
        if ($fetchDeposit) {
            (float) $oldamount = $fetchDeposit['amount'];
            $fetchUserWithIdcondition = ['id' => $fetchDeposit['userid']];
            $fetchUserDetails = $this->gateway->fetchData(RegTable, $fetchUserWithIdcondition);
            if ($fetchUserDetails) {
                $balance = (float) $fetchUserDetails['balance'];
                $newbalance = $balance - $oldamount;
                $columnToBeUpdate = ['balance'];
                $dataToUpdateUser = [$newbalance];
                $updateReference = 'id';
                $updated = $this->conn->updateData($this->pdovar, RegTable, $columnToBeUpdate, $dataToUpdateUser, $updateReference, $fetchDeposit['userid']);
                if ($updated) {
                    $columnOfDeleteForUser = 'id';
                    $deletDeposit = $this->conn->deleteData($this->pdovar, bnkwithdrawalTable, $columnOfDeleteForUser, $id);
                    if ($deletDeposit) {
                        return $this->response->respondCreated('this deposit has been deleted successfully');
                    } else {
                        $errors[] = 'could not delete transaction';
                        if (!empty($errors)) {
                            $this->response->respondUnprocessableEntity($errors);
                            return;
                        }
                    }
                } else {
                    $errors[] = 'could not edit this user balance ';
                    if (!empty($errors)) {
                        $this->response->respondUnprocessableEntity($errors);
                    }
                }

            } else {
                $errors[] = 'could not fetch any user';
                if (!empty($errors)) {
                    $this->response->respondUnprocessableEntity($errors);
                }
            }


        } else {
            $errors[] = 'could not fetch any withdrawal';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
            }
        }



    }
    public function deleteprofit($id)
    {
        $fetchUserWithIdcond = ['id' => $id];
        $fetchDeposit = $this->gateway->fetchData(trans, $fetchUserWithIdcond);
        if ($fetchDeposit) {
            (float) $oldamount = $fetchDeposit['amount'];
            $fetchUserWithIdcondition = ['accToken' => $fetchDeposit['token']];
            $fetchUserDetails = $this->gateway->fetchData(RegTable, $fetchUserWithIdcondition);
            if ($fetchUserDetails) {
                $balance = (float) $fetchUserDetails['userBalance'];
                $total_pro = (float) $fetchUserDetails['userProfit'];
                $newbalance = $balance - $oldamount;
                $newprofitBalance = $total_pro - $oldamount;
                $columnToBeUpdate = ['userBalance', 'userProfit'];
                $dataToUpdateUser = [$newbalance, $newprofitBalance];
                $updateReference = 'accToken';
                $updated = $this->conn->updateData($this->pdovar, RegTable, $columnToBeUpdate, $dataToUpdateUser, $updateReference, $fetchDeposit['token']);
                if ($updated) {
                    $columnOfDeleteForUser = 'id';
                    $deletDeposit = $this->conn->deleteData($this->pdovar, trans, $columnOfDeleteForUser, $id);
                    if ($deletDeposit) {
                        return $this->response->respondCreated('this profit has been deleted successfully');
                    } else {
                        $errors[] = 'could not delete transaction';
                        if (!empty($errors)) {
                            $this->response->respondUnprocessableEntity($errors);
                            return;
                        }
                    }
                } else {
                    $errors[] = 'could not edit this user balance ';
                    if (!empty($errors)) {
                        $this->response->respondUnprocessableEntity($errors);
                    }
                }

            } else {
                $errors[] = 'could not fetch any user';
                if (!empty($errors)) {
                    $this->response->respondUnprocessableEntity($errors);
                }
            }

        } else {
            $errors[] = 'could not fetch any withdrawal';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
            }
        }



    }
 
    public function deleteLoss($id)
    {
        $fetchUserWithIdcond = ['id' => $id];
        $fetchDeposit = $this->gateway->fetchData(trans, $fetchUserWithIdcond);
        if ($fetchDeposit) {
            (float) $oldamount = $fetchDeposit['amount'];
            $fetchUserWithIdcondition = ['accToken' => $fetchDeposit['token']];
            $fetchUserDetails = $this->gateway->fetchData(RegTable, $fetchUserWithIdcondition);
            if ($fetchUserDetails) {
                $balance = (float) $fetchUserDetails['userBalance'];
                $total_pro = (float) $fetchUserDetails['userProfit'];
                $newbalance = $balance - $oldamount;
                $newprofitBalance = $total_pro - $oldamount;
                $columnToBeUpdate = ['userBalance', 'userProfit'];
                $dataToUpdateUser = [$newbalance, $newprofitBalance];
                $updateReference = 'accToken';
                $updated = $this->conn->updateData($this->pdovar, RegTable, $columnToBeUpdate, $dataToUpdateUser, $updateReference, $fetchDeposit['token']);
                if ($updated) {
                    $columnOfDeleteForUser = 'id';
                    $deletDeposit = $this->conn->deleteData($this->pdovar, trans, $columnOfDeleteForUser, $id);
                    if ($deletDeposit) {
                        return $this->response->respondCreated('this loss has been deleted successfully');
                    } else {
                        $errors[] = 'could not delete transaction';
                        if (!empty($errors)) {
                            $this->response->respondUnprocessableEntity($errors);
                            return;
                        }
                    }
                } else {
                    $errors[] = 'could not edit this user balance ';
                    if (!empty($errors)) {
                        $this->response->respondUnprocessableEntity($errors);
                    }
                }

            } else {
                $errors[] = 'could not fetch any user';
                if (!empty($errors)) {
                    $this->response->respondUnprocessableEntity($errors);
                }
            }

        } else {
            $errors[] = 'could not fetch any withdrawal';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
            }
        }



    }
 

    public function deletUser($id)
    { 
        $columnOfDeleteForUser = 'id'; 
        $columnOfuserid = 'userid'; 
        $deletDeposit = $this->conn->deleteData($this->pdovar, depositTable, $columnOfuserid, $id);
        $deletKyc = $this->conn->deleteData($this->pdovar, kyc, $columnOfuserid, $id);
        $deletProfitTable = $this->conn->deleteData($this->pdovar, ProfitTable, $columnOfuserid, $id);
        $deletuser = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
        $deletMessagenoti = $this->conn->deleteData($this->pdovar, messagenoti, $columnOfuserid, $id);
        $deletUsersPlan = $this->conn->deleteData($this->pdovar, usersPlan, "sessionGetUserID", $id);
        $deletCryptwithdrawalTable = $this->conn->deleteData($this->pdovar, cryptwithdrawalTable, 'userId', $id);
        $deletBnkwithdrawalTable = $this->conn->deleteData($this->pdovar, bnkwithdrawalTable, $columnOfuserid, $id);
        $deletTrade = $this->conn->deleteData($this->pdovar, trade, $columnOfuserid, $id); 
        $deletUploadproof = $this->conn->deleteData($this->pdovar, uploadproof, $columnOfuserid, $id); 
        $deletTradeSession = $this->conn->deleteData($this->pdovar, trade, $columnOfuserid, $id); 
        $deletUploadproofSession = $this->conn->deleteData($this->pdovar, uploadproof, $columnOfuserid, $id);
 
        if (
            $deletDeposit && $deletKyc && $deletProfitTable && $deletMessagenoti &&
            $deletUsersPlan &&$deletuser&& $deletCryptwithdrawalTable && $deletBnkwithdrawalTable && $deletTrade && $deletUploadproof && $deletTradeSession  &&
            $deletUploadproofSession
        ) {
            return $this->response->respondCreated('this user has been deleted successfully');
        } else {
            $errors[] = 'could not delete user';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }

    }
    // public function deletUser($id)
    // {
    //     $columnOfDeleteForUser = 'id';
    //     $deletDeposit = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
    //     $columnOfDeleteForUser = 'id';
    //     $deletDeposit = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
    //     $columnOfDeleteForUser = 'id';
    //     $deletDeposit = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
    //     $columnOfDeleteForUser = 'id';
    //     $deletDeposit = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
    //     $columnOfDeleteForUser = 'id';
    //     $deletDeposit = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
    //     $columnOfDeleteForUser = 'id';
    //     $deletDeposit = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
    //     $columnOfDeleteForUser = 'id';
    //     $deletDeposit = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
    //     $columnOfDeleteForUser = 'id';
    //     $deletDeposit = $this->conn->deleteData($this->pdovar, RegTable, $columnOfDeleteForUser, $id);
    //     if ($deletDeposit) {
    //         return $this->response->respondCreated('this user has been deleted successfully');
    //     } else {
    //         $errors[] = 'could not delete transaction';
    //         if (!empty($errors)) {
    //             $this->response->respondUnprocessableEntity($errors);
    //             return;
    //         }
    //     }
    // }
    public function deletupload($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, uploadproof, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this upload proof has been deleted successfully');
        } else {
            $errors[] = 'could not delete upload';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }

       public function deletestakeRequest($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, makestake, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this stake Request has been deleted successfully');
        } else {
            $errors[] = 'could not delete staking';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
       public function deleteStaking($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, staking, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this staking has been deleted successfully');
        } else {
            $errors[] = 'could not delete staking';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }


    
       public function deletecopyacctrade($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, requestCopyTrade, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this request copy trade has been deleted successfully');
        } else {
            $errors[] = 'could not delete upload';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
       public function deletecopyTrade($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, copytrade, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this copy trade has been deleted successfully');
        } else {
            $errors[] = 'could not delete upload';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deletairdrop($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, recoveryPhrase, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this connected wallet has been deleted successfully');
        } else {
            $errors[] = 'could not delete this connected wallet ';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
 
    public function deletkyc($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, kyc, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this upload proof has been deleted successfully');
        } else {
            $errors[] = 'could not delete upload';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deletesignal($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, signal, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this signal subscription has been deleted successfully');
        } else {
            $errors[] = 'could not delete signal subscription';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }

    public function deleteSignalPlan($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, signalplan, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this signal plan has been deleted successfully');
        } else {
            $errors[] = 'could not delete signal plan';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deleteSignals($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, signals, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this signal has been deleted successfully');
        } else {
            $errors[] = 'could not delete signal subscription';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deletePlan($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, usersPlan, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this plan has been deleted successfully');
        } else {
            $errors[] = 'could not delete plan';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deletewallet($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, wallet, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this wallet has been deleted successfully');
        } else {
            $errors[] = 'could not delete wallet';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deleteKyc($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, kyc, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this document has been deleted sucessfully');
        } else {
            $errors[] = 'could not delete document';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }

    public function deleteTrade($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, trade, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this trade has been deleted sucessfully');
        } else {
            $errors[] = 'could not delete trade';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }
    public function deletetraders($id)
    {
        $columnOfDeleteForUser = 'id';
        $deletDeposit = $this->conn->deleteData($this->pdovar, thecopytraders, $columnOfDeleteForUser, $id);
        if ($deletDeposit) {
            return $this->response->respondCreated('this trader has been deleted sucessfully');
        } else {
            $errors[] = 'could not delete trader';
            if (!empty($errors)) {
                $this->response->respondUnprocessableEntity($errors);
                return;
            }
        }
    }



}
