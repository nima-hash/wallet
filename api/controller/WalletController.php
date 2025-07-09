<?php
include '../../config/database.php';
//sanitize input
function sanitizeInput($input) {
    return is_array($input) ? array_map('sanitizeInput', $input) : htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

try {
    $wallet = new Wallet;
    


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $wallet->verifyToken();
        $sanitizedData =sanitizeInput($_POST) ?? null;
        $action = $sanitizedData["action"] ?? null;
        if ($action) {
            unset($sanitizedData["action"]);
        } else {
            throw new Exception ("False request.", 400);
        }
        
        
        if ($sanitizedData && $action) {
            if (empty($sanitizedData['wallet_name'])) {
                throw new CustomException ("Wallet name can not be empty.", 400, ["fieldName" => "wallet_name", "message" => "Wallet name can not be empty."]);
            }
            if (!is_numeric($sanitizedData['balance'])) {
                throw new CustomException ("Please enter a positive balance.", 400, ["fieldName" => "balance", "message" => "please enter a positive balance."]);
    
            }
            
            if (!array_search($sanitizedData['currency'], $acceptedCurrencies) && $sanitizedData['currency']) {
                throw new CustomException ("{$sanitizedData['currency']} is not supported.", 400, ["fieldName" => "currency", "message" => "{$sanitizedData['currency']} is not supported."]);
            }
            if ((!(floatval($sanitizedData['balance'])) && floatval($sanitizedData['balance']) != 0) ||  $sanitizedData['balance'] < 0) {
                throw new CustomException ("The spent amount must be a positive number.", 400, ["fieldName" => "balance", "message" => "The spent amount must be a positive number."]);
    
            }
            
            switch ($action) {
                case 'updateWallet':
                    $setValues = '';
                    $condition = "wallet_name = :wallet_name AND user_id = :user_id";
                    $params = [
                        'wallet_name' => $sanitizedData['wallet_name'],
                        'user_id' => $_SESSION['user_id']
                    ];
                    
                    $record = $wallet->validateName($condition, $params);
                    
                    if ($record) {
                        
                        if ($record['id'] != $sanitizedData['id']) {
                            throw new Exception ("This name is already in use.", 400);
                        } 
                    }
                    

                    unset($sanitizedData['created_at']);
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
                
                    if ($wallet->update($setValues, $condition, $params)) {
                        
                        sendOutput(json_encode(['success' => true, 'messade' => 'the Record was successfully updated.']),  ['Content-Type: application/json', 200]);
                        
                    }
                    break;
                case 'addWallet':

                    //check for duplicate name
                    $condition = "wallet_name = :wallet_name AND user_id = :user_id"; 
                    $params = [
                        'wallet_name' => $sanitizedData['wallet_name'],
                        'user_id' => $wallet->get('user_id')
                    ];
                    
                    if ($wallet->duplicateSearch('wallets', $condition, $params)) {
                        throw new CustomException ("{$sanitizedData['wallet_name']} already exists. please choose another name.", 400, ["fieldName" => "wallet_name", "message" => "{$sanitizedData['wallet_name']} already exists. please choose another name."]);
                    }

                    //save to table
                    $params = ['user_id' => $wallet->get('user_id')];
                    $tableColumnPlaceholders = ':user_id';
                    $tableColumns = 'user_id';
                    foreach ($sanitizedData as $key => $value) {
                        $params[$key] = $value;
                        $tableColumnPlaceholders .= ', :' . $key;
                        $tableColumns .= ' ,' . $key;  
                    }
                    $wallet_id= $wallet->save($tableColumns, $tableColumnPlaceholders, $params);

                    //check the returned id
                    if ($wallet_id) {
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
        if ($action) {
            unset($params["action"]);
            
        } else {
            throw new Exception ("False request.", 400);
        }

        switch ($action) {
            case 'getWallets':
                $walletList = $wallet->getWallets();
                break;
            case 'getWallet':                
                $walletList = $wallet->getWallet($params);
                break;
            default:
                throw new Exception("Action not defined", 401);
        }
        
        // $Wallets = $wallet->getWallets();
            if (count($walletList)>=0) {

                sendOutput(json_encode(['success' => true, 'data' => $walletList]),  ['Content-Type: application/json', 200]);
            }
       
    } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $wallet->verifyToken();
        parse_str($_SERVER['QUERY_STRING'], $params);
        $params =sanitizeInput($params) ?? null;
        $action = $params["action"] ?? null;
        if ($action === "deleteWallet") {
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
    // if((int)$e->getCode()) {
    //     $errorCode = (int)$e->getCode() ?: 500;
    // }
    $wallet->get('db')->sendOutput(json_encode(array('success' => false, 'error' => $e->getData(), 'code' => (int)$e->getCode())),
        array('Content-Type: application/json',(int)$e->getCode() ?: 500)
    );
} catch (Exception $e) {
    $wallet->get('db')->sendOutput(json_encode(array('success' => false, 'error' => $e->getMessage(), 'code' => (int)$e->getCode())),
        array('Content-Type: application/json',(int)$e->getCode() ?: 500));
}
?>