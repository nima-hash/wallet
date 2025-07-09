<?php
include '../../config/database.php';
//sanitize input
function sanitizeInput($input) {
    return is_array($input) ? array_map('sanitizeInput', $input) : htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

//gives correct dateformat    
// function validateDate($date, $format = 'd-m-Y'){
//     $d = DateTime::createFromFormat($format, sanitizeInput($date));
//     $currentDate = new DateTime();
//     $newDate = $d && $d->format($format) === $date;
//     $inputDate = $d && $d <= $currentDate;
//     if ($newDate && $inputDate) {
//         return $d;
//     }
//     return false;
//   }

function validateDate($date, $format = 'd-m-Y') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
try {
    $transaction = new Transaction();
    


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $transaction->verifyToken();
        $sanitizedData =sanitizeInput($_POST) ?? null;
        $action = $sanitizedData["action"] ?? null;
        if ($action) {
            unset($sanitizedData["action"]);
        } else {
            throw new Exception ("False request.", 400);
        }
        
        //validate date format
        // $sanitizedData['transaction_date'] = validateDate($sanitizedData['transaction_date']);
        $sanitizedData['wallet_id'] = (int)$sanitizedData['wallet_id'];
        $sanitizedData['expense_id'] = (int)$sanitizedData['expense_id'];
        $sanitizedData['amount'] = (float)$sanitizedData['amount'];
        
       
        if ($sanitizedData && $action) {
            if (empty($sanitizedData['expense_id']) || !is_numeric($sanitizedData['expense_id']) || $sanitizedData['expense_id']<=0) {
                throw new CustomException ("expenseCategory name can not be empty.", 400, ["fieldName" => "expense_category", "message" => "Expense name can not be empty."]);
            }
            
            if (empty($sanitizedData['wallet_id']) || !is_numeric($sanitizedData['wallet_id']) || $sanitizedData['wallet_id']<=0) {
                throw new CustomException ("Wallet name can not be empty.", 400, ["fieldName" => "wallet", "message" => "Wallet name can not be empty."]);
            }
            
            if ((!(floatval($sanitizedData['amount'])) && floatval($sanitizedData['amount']) != 0) ||  $sanitizedData['amount'] < 0) {
                throw new CustomException ("The amount must be a positive number.", 400, ["fieldName" => "amount", "message" => "The amount must be a positive number."]);
    
            }

            if (!$sanitizedData['transaction_date']) {
                throw new CustomException ("Date format is not accepted.", 400, ["fieldName" => "transaction_date", "message" => "Date format is not accepted."]);
            }


            
            switch ($action) {
                case 'updateTransaction':
                    $setValues = '';
                    $condition = "id = :id AND user_id = :user_id";
                    $params = [
                        'id' => $sanitizedData['id'],
                        'user_id' => $_SESSION['user_id']
                    ];
                    
                    // $record = $wallet->validateName($condition, $params);
                    
                    // if ($record) {
                        
                    //     if ($record['id'] != $sanitizedData['id']) {
                    //         throw new Exception ("This name is already in use.", 400);
                    //     } 
                    // }
                    

                    // unset($sanitizedData['created_at']);
                    $id = $sanitizedData['id'];
                    unset($sanitizedData['id']);

                    
                    $params = [];
                    // $tableColumnPlaceholders = ':user_id';
        
                    for ($i=0; $i< count(array_keys($sanitizedData)); $i++) {
                        $key = array_keys($sanitizedData)[$i];
                        $value = $sanitizedData[$key];
                        
                        if ($i===0) {
                            $setValues .= $key . ' = :' . $key;
                            $params[$key] = $value;
                        } else {
                            $setValues .= ", " . $key . ' = :' . $key;
                            $params[$key] = $value;
                        }
        
                    }
        
                    $condition = 'id = :id';
                    $params['id'] = $id;
                
                    if ($transaction->update($setValues, $condition, $params)) {
                        
                        sendOutput(json_encode(['success' => true, 'messade' => 'the Record was successfully updated.']),  ['Content-Type: application/json', 200]);
                        
                    }
                    break;
                case 'addTransaction':

                    
                    //save to table
                    $params = ['user_id' => $transaction->get('user_id')];
                    $tableColumnPlaceholders = ':user_id';
                    $tableColumns = 'user_id';
                    foreach ($sanitizedData as $key => $value) {
                        if ($value !== null && $value !== '' && $value !== []) {  // Ignore empty strings
                            $params[$key] = $value;
                            $tableColumnPlaceholders .= ', :' . $key;
                            $tableColumns .= ', ' . $key;  
                        } else {
                            $params[$key] = null; // Force NULL if value is empty
                            $tableColumnPlaceholders .= ', :' . $key;
                            $tableColumns .= ', ' . $key;
                        }
                        
                    }
                    $transaction_id= $transaction->save($tableColumns, $tableColumnPlaceholders, $params);

                    //check the returned id
                    if ($transaction_id) {
                        sendOutput(json_encode(['success' => true, 'data' => 'Record successfully added.']),  ['Content-Type: application/json', 200]);
                    } else {
                        throw new Exception("Could not save the record", 500);
                    }
                    break;
                
                default:
                    # code...
                    break;
            }
            
        } else {
            throw new Exception("Unapproved action", 401);
        }
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_SERVER['QUERY_STRING'])){
            parse_str($_SERVER['QUERY_STRING'], $params);
            $params = sanitizeInput($params) ?? null;
        }

        $action = $params["action"] ?? null;
        if ($action === 'getExpenseTransactions' || $action === 'getWalletTransactions') {
            unset($params["action"]);
            $filters = $params['filters'] ?? [];
            $expenseId = $params['expense_id'] ?? null;
            $walletId = $params['wallet_id'] ?? null;
            $condition = "user_id = :user_id";
            if ($filters) {
                var_dump($params);
                die;
                if ($expenseId) {
                    $condition .= " AND expense_id = :expense_id" . $filters;
                    $params = [
                        "user_id" => $transaction->get('user_id'),
                        'expense_id' => $expenseId 
                    ];

                } else  if($walletId) {
                    $condition .= " AND wallet_id = :wallet_id" . $filters;
                    $params = [
                        "user_id" => $transaction->get('user_id'),
                        'wallet_id' => $walletId 
                    ];
                }
            } else {
                if ($expenseId) {
                    $condition .= " AND expense_id = :expense_id";
                    $params = [
                        "user_id" => $transaction->get('user_id'),
                        'expense_id' => $expenseId 
                    ];

                } else  if($walletId) {
                    $condition .= " AND wallet_id = :wallet_id";
                    $params = [
                        "user_id" => $transaction->get('user_id'),
                        'wallet_id' => $walletId 
                    ];
                }
                $transactions = $transaction->getTransactions($condition, $params);
            }
            if (count($transactions)>=0) {

                sendOutput(json_encode(['success' => true, 'data' => $transactions]),  ['Content-Type: application/json', 200]);
            }
        } else {
            throw new Exception ("False request.", 400);
        }

        
        
       
    } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $wallet->verifyToken();
        parse_str($_SERVER['QUERY_STRING'], $params);
        $params =sanitizeInput($params) ?? null;
        $action = $params["action"] ?? null;
        if ($action) {
            unset($params["action"]);
        } else {
            throw new Exception ("False request.", 400);
        }
        
        

        $stmt = $wallet->deleteWallet($params);
        if ($stmt) {
            sendOutput(json_encode(['success' => true, 'data' => 'Record successfully deleted.']),  ['Content-Type: application/json', 200]);
        }
    } 

} catch (CustomException $e) {
   
    $transaction->get('db')->sendOutput(json_encode(array('success' => false, 'error' => $e->getData(), 'code' => (int)$e->getCode())),
        array('Content-Type: application/json',(int)$e->getCode() ?: 500)
    );

} catch (Exception $e) {
    $transaction->get('db')->sendOutput(json_encode(array('success' => false, 'error' => $e->getMessage(), 'code' => (int)$e->getCode())),
        array('Content-Type: application/json',(int)$e->getCode() ?: 500));
}
?>