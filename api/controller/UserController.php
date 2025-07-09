<?php
include '../../config/database.php';
//sanitize input
function sanitizeInput($input) {
    return is_array($input) ? array_map('sanitizeInput', $input) : htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

try {
    $user = new User;
    


    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $expense->verifyToken();
        $sanitizedData =sanitizeInput($_POST) ?? null;
        $action = $sanitizedData["action"] ?? null;
        if ($action) {
            unset($sanitizedData["action"]);
        } else {
            throw new Exception ("False request.", 400);
        }
        
        if ($sanitizedData && $action) {
            if (empty($sanitizedData['expense_name'])) {
                throw new CustomException ("Category name can not be empty.", 400, ["fieldName" => "expense_name", "message" => "Category name can not be empty."]);
            }
            if (!is_numeric($sanitizedData['spent_this_month'])) {
                throw new CustomException ("Please enter a positive balance.", 400, ["fieldName" => "spent_this_month", "message" => "please enter a positive balance."]);
    
            }
            
            if (!array_search($sanitizedData['currency'], $acceptedCurrencies) && $sanitizedData['currency']) {
                throw new CustomException ("{$sanitizedData['currency']} is not supported.", 400, ["fieldName" => "currency", "message" => "{$sanitizedData['currency']} is not supported."]);
            }
            if ((!(floatval($sanitizedData['spent_this_month'])) && floatval($sanitizedData['spent_this_month']) != 0) ||  $sanitizedData['spent_this_month'] < 0) {
                throw new CustomException ("The spent amount must be a positive number.", 400, ["fieldName" => "spent_this_month", "message" => "The spent amount must be a positive number."]);
    
            }
            
            switch ($action) {
                case 'updateExpense':
                    $setValues = '';
                    $condition = "expense_name = :expense_name AND user_id = :user_id";
                    $params = [
                        'expense_name' => $sanitizedData['expense_name'],
                        'user_id' => $_SESSION['user_id']
                    ];
                    $record = $expense->validateName($condition, $params);
                    
                    if ($record) {
                        if ($record['id'] !== $sanitizedData['id']) {
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
                
                    if ($expense->update($setValues, $condition, $params)) {
                        
                        sendOutput(json_encode(['success' => true, 'messade' => 'the Record was successfully updated.']),  ['Content-Type: application/json', 200]);
                        
                    }
                    break;
                case 'addCategory':
                    if ($expense->duplicateName($sanitizedData['expense_name'])) {
                        throw new CustomException ("{$sanitizedData['expense_name']} already exists. please choose another name.", 400, ["fieldName" => "expense_name", "message" => "{$sanitizedData['expense_name']} already exists. please choose another name."]);
                    }
                    $params = ['user_id' => $expense->get('user_id')];
                    $tableColumnPlaceholders = ':user_id';
                    $tableColumns = 'user_id';
                    foreach ($sanitizedData as $key => $value) {
                        $params[$key] = $value;
                        $tableColumnPlaceholders .= ', :' . $key;
                        $tableColumns .= ' ,' . $key;  
                    }
                    $expense_id= $expense->save($tableColumns, $tableColumnPlaceholders, $params);
                    if ($expense_id) {
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
        $user->verifyToken();

        if (isset($_SERVER['QUERY_STRING'])){
            parse_str($_SERVER['QUERY_STRING'], $params);
            $params = sanitizeInput($params) ?? null;
        }
        if (isset($params['action']) && $params['action'] === 'getWallets') {
            $Wallets = $user->getWallets();
            if (count($Wallets)>=0) {
                sendOutput(json_encode(['success' => true, 'data' => $Wallets]),  ['Content-Type: application/json', 200]);
            } else {

            }
        }
        if (isset($params['action']) && $params['action'] === 'getExpenses') {
            $expenses = $user->getCategories();
            if (count($expenses)>=0) {
                sendOutput(json_encode(['success' => true, 'data' => $expenses]),  ['Content-Type: application/json', 200]);
            } else {

            }
        }
        
        
    } else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        parse_str($_SERVER['QUERY_STRING'], $params);
        $params = sanitizeInput($params);
        $stmt = $expense->deleteCategory($params);
        if ($stmt) {
            sendOutput(json_encode(['success' => true, 'data' => 'Record successfully deleted.']),  ['Content-Type: application/json', 200]);
        }
    } 

} catch (CustomException $e) {
    // if((int)$e->getCode()) {
    //     $errorCode = (int)$e->getCode() ?: 500;
    // }
    $user->get('db')->sendOutput(json_encode(array('success' => false, 'error' => $e->getData(), 'code' => (int)$e->getCode())),
        array('Content-Type: application/json',(int)$e->getCode() ?: 500)
    );
} catch (Exception $e) {
    $user->get('db')->sendOutput(json_encode(array('success' => false, 'error' => $e->getMessage(), 'code' => (int)$e->getCode())),
        array('Content-Type: application/json',(int)$e->getCode() ?: 500));
}
?>