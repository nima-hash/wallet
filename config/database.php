<?php
require_once __DIR__ . "/constant.php";
include __DIR__ . "/basecontroller.php";
require_once __DIR__ . "/..//vendor/autoload.php"; // Load JWT Library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class DatabaseConnection extends BaseController {
    
    public $connection = null;
    private $secret_key;
  
    public function __construct(){
          include 'config.php';
          try {
              $dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_DATABASE_NAME;charset=utf8mb4"; 
              $this->connection = new PDO(
                  $dsn,
                  $DB_USERNAME,
                  $DB_PASSWORD,
                  [
                      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                      PDO::ATTR_EMULATE_PREPARES => false,
                  ]);
                
                  $this->secret_key = $JWT_SECRET;

                // Enable strict mode
                $this->connection->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
          } catch (PDOException $e) {
              die("Database connection failed: " . $e->getMessage());
          }			
    }
  
    public function executeQuery(string $query, array $params =[]) {
        $stmt = $this -> connection -> prepare($query);
        
        $stmt -> execute($params);
        return $stmt;
    }

    public function insertedId() {
        
        $id = $this -> connection -> lastInsertId();
        return $id;
    }

    public function getSecretKey() {
        return $this->secret_key;
    }
}


class DatabaseController extends DatabaseConnection {
  public function __construct(){
      parent::__construct();
  }

  public function createTable(string $table_name): void{
     try {
          $query = "CREATE TABLE $table_name (
          id INT(6) AUTO_INCREMENT PRIMARY KEY, 
          patment_method VARCHAR(50) NOT NULL, 
          transaction_type ENUM('Received, 'Refunded') NOT NULL, 
          amount DECIMAL(10,2) NOT NULL, 
          transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)";
          
          $this->prepareStatement($query);
          echo "Table $table_name created successfully\n";

     } catch (PDOException $e) {
          echo "Error: " . $e->getMessage();
     } 
  }

  public function insertData(string $table_name, string $payment_method, string $transaction_type, float $amount): void {
      try{
          $query = "INSERT INTO $table_name (payment_method, transaction_type, amount) VALUES (:method, :type, :amount)";
          $this->executeQuery($query, [':method' => $payment_method, ':type' => $transaction_type, ':amount' => $amount]);
          echo "Data inserted successfully\n";

      } catch (PDOException $e) {
          echo "Error: " . $e->getMessage();
      }
  }

  public function selectData(string $table_name){
      try {
          $query = "SELECT * FROM $table_name";
          $stmt = $this-> connection ->query($query);
          return $stmt->fetchAll();
      } catch (PDOEXception $e) {
          echo "Error: " . $e->getMessage();
      }
  }

  public function deleteData($table_name, $condition){
      try {
          $query = "DELETE FROM $table_name WHERE :condition";
          $this->executeQuery($query, [":condition" => $condition]);
          echo "Data deleted successfully\n";
      } catch (PDOException $e) {
          echo "Error: " . $e->getMessage();
      }
      
  }

  public function updateData($table_name, $data, $condition){
      try {
          $query = "UPDATE $table_name SET :data WHERE :condition";
          $this->executeQuery($query, [":data" => $data,  ":condition" => $condition]);
          echo "Data updated successfully\n";
      } catch (PDOException $e) {
          echo "Error: " . $e->getMessage();
      }
  }
}

trait ValidateData {
    public function set(array $params): void {
        foreach ($params as $property => $value) {
            $this->$property = $value;
        }
    }

    public function get($params) {
        return $this->$params;
    }

    public function test_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
      }

      // Verify JWT Token
    public function verifyToken() {

        $token = $this->getAuthorizationToken();
        
        $decoded = $this->validateToken($token);
        
        if (!$decoded) {
            http_response_code(401);
            sendOutput(json_encode(array('success' => false, 'error' => 'Unauthorized.  Invalid or missing token.', 'code' => 401)),
            array('Content-Type: application/json',401));
        
            // Set user_id from token payload
            $this->user_id = $decoded->user_id;
        }
    }

      //  Extract Bearer Token from Authorization Header
    private function getAuthorizationToken() {
        $headers = getallheaders();
       
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    // Validate JWT Token
    private function validateToken($token) {
        if (!$token) return false;
        try {
            return JWT::decode($token, new Key($this->db->getSecretKey(), 'HS256'));
        } catch (Exception $e) {
            return false;
        }
    }
}

class User {
    use ValidateData;
    private $db;
    private $secret_key;

    // Initialize database connection with Dependency Injection   
    public function __construct() {
        $this->db = new DatabaseConnection;
        $this->secret_key = $this->db->getSecretKey(); 
    }

    public function getUserData(): array{
        $stmt = $this->db->executeQuery("SELECT name, profile_picture FROM users WHERE id = ?", [$_SESSION['user_id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
        return $userData;
    }
    
    //   Register a new user.
    
    public function registerUser($username, $password, $email, $phone, $address) {
        // Validate inputs
        if (empty($username) || empty($password) || empty($email)) {
            throw new Exception("Username, Password and email fields are required.", 400) ;
        }
        // Check if the username or email already exists
        $stmt = $this->db->executeQuery("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username or email already exists.", 400);
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        // Insert the new user into the database
        $stmt = $this->db->executeQuery(
            "INSERT INTO users (username, password, email, phone, address) VALUES (?, ?, ?, ?, ?)",
            [$username, $hashedPassword, $email, $phone, $address]);
        if ($stmt) {
            return "The user was successfully added.";
        } else {
            throw new Exception("Registration failed. Please try again.", 500);
        }
    }

    
    //   Validate user credentials and log in.

     public function checkPassword($username, $password) {
        // Fetch the user's hashed password from the database
        $stmt = $this->db->executeQuery("SELECT id, password, profile_picture FROM users WHERE username = ?", [$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            // Password is correct, start a session
            // session_start();
           
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            $_SESSION['profile_picture'] = $user['profile_picture'] ?: PROJECT_ROOT_PATH . '/public/default-avatar.jpg'; // Default profile pic
            // Generate JWT Token
            $payload = [
                "user_id" => $user['id'],
                "iat" => time(),
                "exp" => time() + (60 * 60) // Token expires in 1 hour
            ];
            
            $jwt = JWT::encode($payload, $this->secret_key, 'HS256');
            
            return $jwt;
            
        } else {
            // Invalid credentials
            throw new Exception('Invalid credentials.', 401);
        }
    }

    public function validatePassword($password) {
        // Minimum 8 characters
        $minLength = 8;
    
        // Regular expressions for validation
        $hasUppercase = preg_match('/[A-Z]/', $password); // At least 1 uppercase letter
        $hasLowercase = preg_match('/[a-z]/', $password); // At least 1 lowercase letter
        $hasSpecialChar = preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password); // At least 1 special character
    
        // Check all conditions
        if (strlen($password) < $minLength) {
            throw new Exception("Password must be at least 8 characters long.", 400);
        }
        if (!$hasUppercase) {
            throw new Exception("Password must contain at least 1 uppercase letter.", 400);
        }
        if (!$hasLowercase) {
            throw new Exception("Password must contain at least 1 lowercase letter.", 400);
        }
        if (!$hasSpecialChar) {
            throw new Exception("Password must contain at least 1 special character.", 400);
        }
    
        // If all conditions are met
        return true; // No error
    }

    public function getWallets() {
        $query = "SELECT * FROM wallets WHERE user_id = :user_id";
        $params = ['user_id' => $_SESSION['user_id']];
        return $this->db->executeQuery($query, $params)->fetchAll();
    }

    public function getCategories() {
        $query = "SELECT * FROM expense_category WHERE user_id = :user_id";
        $params = ['user_id' => $_SESSION['user_id']];
        return $this->db->executeQuery($query, $params)->fetchAll();
    }
    
    //   Log out the user.
     
    public function logout() {
        session_start();
        session_unset();
        session_destroy();
        header("Location: " . PROJECT_ROOT_PATH . "/login.php");
        exit();
    }
}

class Wallet {
    use ValidateData;
    private $db;
    private $wallet_name;
    private $currency;
    private $balance;
    private $user_id;
    private $wallet_id;

    public function __construct() {
        $this->db = new DatabaseConnection;
        $this->user_id = $_SESSION['user_id'];

    }

    public function validateName(string  $condition, array $params) {
        
        $query = "SELECT * FROM wallets WHERE {$condition}";
        
        $stmt = $this->db->executeQuery($query, $params); 
        $result = $stmt->fetch();
        return $result;
    }

    public function getWallets(): array {
        
        $query = "SELECT * FROM wallets WHERE user_id = :user_id";
        $params = ['user_id' => $this->user_id];
        $stmt = $this->db->executeQuery($query, $params);
        if ($stmt) {
            return $stmt->fetchAll();
        } else {
            throw new PDOException("something went wrong.", 500);
        }
    }

    public function getWallet($params) {
        
        $query = "SELECT * FROM wallets WHERE id = :id";
        // $params = ['user_id' => $this->user_id];
        $stmt = $this->db->executeQuery($query, $params);
        $result = $stmt->fetch();
        
        if ($stmt) {
            if ($result['user_id'] == $_SESSION['user_id']) {
                return $result;
            } else {
                throw new Exception("Invalid Wallet id.", 401);
            }
        } else {
            throw new PDOException("something went wrong.", 500);
        }
    }

    public function deleteWallet(array $params) {
        // Ensure $params contains required keys
        $id = $params['id'] ?? null;
        $wallet_name = $params['wallet_name'] ?? null;

        if (!isset($id) || strlen($wallet_name) === 0) {
            throw new InvalidArgumentException("Missing required parameters: id or wallet_name");
        }

        //checks if recieved id exists
        $checkQuery = "SELECT COUNT(*) FROM wallets WHERE id = :id AND wallet_name = :wallet_name AND user_id  = :user_id";
        $checkParams = [
            'id' => $id,
            'wallet_name' => $wallet_name,
            'user_id' => $this->user_id
        ];
        
        $stmt = $this->db->executeQuery($checkQuery, $checkParams);
        
        if ($stmt->fetchColumn() == 0) {
            throw new Exception ("Record not found.", 404);
        }

        //check if transactions with this category exist
        $transactionsCount = $this->getTransactionCount($id);

        if ($transactionsCount>0){
            throw new Exception('Categories that still have transactions can not be deleted', 400);
        }
        
        $query = "DELETE FROM wallets WHERE id = :id AND wallet_name = :wallet_name AND user_id = :user_id";
        
        // $params =[
        //     'id' => $id,
        //     'wallet_name' => $wallet_name,
        //     'user_id' => $this->user_id
        // ];
        
        $stmt = $this->db->executeQuery($query, $checkParams);
        
        // ✅ Check affected rows
        if ($stmt && $stmt->rowCount() > 0) {
            return $stmt;
        } else {
            throw new Exception ('The record could not be deleted. 500');
        }
    }

    public function getTransactionCount($id) {

        $checkQuery = "SELECT COUNT(*) FROM transactions WHERE wallet_id = :wallet_id AND user_id  = :user_id";
        $checkParams = [
            'wallet_id' => $id,
            'user_id' => $this->user_id
        ];
        
        $stmt = $this->db->executeQuery($checkQuery, $checkParams);
        $rowCount = $stmt->fetchColumn(); 
        return $rowCount;       
    }

    public function duplicateSearch(string $table, string $condition, array $params): int {
        $query = "SELECT count(*) FROM {$table} WHERE {$condition}"; 
        $result = $this->db->executeQuery($query, $params); 
        return  $result->fetchColumn(); 
    }

    public function save(string $tableColumns,string $tableColumnsPlaceholders,array $params): int {
        $query = "INSERT INTO wallets ({$tableColumns}) VALUES ({$tableColumnsPlaceholders})";
        
        $stmt = $this->db->executeQuery($query, $params);
        if ($stmt) {
            return $this->db->insertedId();
        } else {
            throw new Exception("Registration failed. Please try again.", 500);
        }
    }

    public function getTransactions() {
        $query = "SELECT * FROM transactions WHERE wallet_id = ?";
        $params = ['wallet_id' => $this->wallet_id];
        return $this->db->executeQuery($query, $params)->fetchAll();
    }

    public function update ($setValues, $condition, $params) {
        try {
            $query = "UPDATE wallets SET $setValues WHERE $condition";
            
        
            $stmt = $this->db->executeQuery($query, $params);
            
            if ($stmt->rowCount() > 0) {
                return "Update successful!";
            } else {
                throw new Exception("No changes made.", 501) ;
            }
        } catch (PDOException $e) {
            throw new Exception("Error: " . $e->getMessage(), 501);

        }
       

    }

}

class ExpenseCategory {
    private $category_id;
    private $user_id;
    private $expense_name;
    private $created_at;
    private $db;
    use ValidateData;

    public function __construct() {
        $this->db = new DatabaseConnection;
        
        $this->user_id = $_SESSION['user_id'];
    }

    public function getCategories() {
        
        $query = "SELECT * FROM expense_category WHERE user_id = :user_id";
        $params = ['user_id' => $this->user_id];
        $stmt = $this->db->executeQuery($query, $params);
        
        if ($stmt) {
            return $stmt->fetchAll();
        } else {
            throw new PDOException("something went wrong.", 500);
        }
    }

    public function getCategory($params) {
        
        $query = "SELECT * FROM expense_category WHERE id = :id";
        // $params = ['user_id' => $this->user_id];
        $stmt = $this->db->executeQuery($query, $params);
        $result = $stmt->fetch();
        
        if ($stmt) {
            if ($result['user_id'] == $_SESSION['user_id']) {
                return $result;
            } else {
                throw new Exception("Invalid Expense id.", 401);
            }
        } else {
            throw new PDOException("something went wrong.", 500);
        }
    }

    public function update ($setValues, $condition, $params) {
        try {
            $query = "UPDATE expense_category SET $setValues WHERE $condition";
        
            $stmt = $this->db->executeQuery($query, $params);
            
            if ($stmt->rowCount() > 0) {
                return "Update successful!";
            } else {
                throw new Exception("No changes made.", 501) ;
            }
        } catch (PDOException $e) {
            throw new Exception("Error: " . $e->getMessage(), 501);

        }
       

    }

    public function deleteCategory(array $params) {
        // Ensure $params contains required keys
        $id = $params['id'] ?? null;
        $expense_name = $params['expense_name'] ?? null;

        if (!isset($id) || strlen($expense_name) === 0) {
            throw new InvalidArgumentException("Missing required parameters: id or expense_name");
        }

        //checks if recieved id exists
        $checkQuery = "SELECT COUNT(*) FROM expense_category WHERE id = :id AND expense_name = :expense_name AND user_id  = :user_id";
        $checkParams = [
            'id' => $id,
            'expense_name' => $expense_name,
            'user_id' => $this->user_id
        ];
        
        $stmt = $this->db->executeQuery($checkQuery, $checkParams);
        
        if ($stmt->fetchColumn() == 0) {
            throw new Exception ("Record not found.", 404);
        }

        //check if transactions with this category exist
        $transactionsCount = $this->getTransactionCount($id);

        if ($transactionsCount>0){
            throw new Exception('Categories that still have transactions can not be deleted', 400);
        }
        
        $query = "DELETE FROM expense_category WHERE id = :id AND expense_name = :expense_name AND user_id = :user_id";
        
        $params =[
            'id' => $id,
            'expense_name' => $expense_name,
            'user_id' => $this->user_id
        ];
        
        $stmt = $this->db->executeQuery($query, $params);

        // ✅ Check affected rows
        if ($stmt && $stmt->rowCount() > 0) {
            return $stmt;
        } else {
            throw new Exception ('The record could not be deleted. 500');
        }
    }

    public function getTransactionCount($id) {

        $checkQuery = "SELECT COUNT(*) FROM transactions WHERE expense_id = :expense_id AND user_id  = :user_id";
        $checkParams = [
            'expense_id' => $id,
            'user_id' => $this->user_id
        ];
        
        $stmt = $this->db->executeQuery($checkQuery, $checkParams);
        $rowCount = $stmt->fetchColumn(); 
        return $rowCount;       
    }

    public function set(array $params): void {
        foreach ($params as $property => $value) {
            $this->$property = $value;
        }
    }

    public function get($params) {
        return $this->$params;
    }

    public function save(string $tableColumns, string $tableColumnsPlaceholders, array $params): int {
        $query = "INSERT INTO expense_category ({$tableColumns}) VALUES ({$tableColumnsPlaceholders})";
        
        // $query = "INSERT INTO expense_category (user_id, category_name) VALUES (? ,?)";
        // $params = [
        //     'user_id' => $this->user_id,
        //     'category_name' => $this->category_name,
        // ];
       
        $stmt = $this->db->executeQuery($query, $params);
        if ($stmt) {
            
            return $this->db->insertedId();
           
        } else {
            throw new Exception("Registration failed. Please try again.", 500);
        }
    }

    public function getTransactions(array $condition = []) {
        $query = "SELECT * FROM transactions WHERE category_id = ?";
        $params = ['category_id' => $this->category_id]; 
        // $query = "SELECT * FROM transactions WHERE ";
        if (count($condition) > 0) {
            foreach ($condition as $key => $value) {
                $query .= " AND $key = ?";
                $params[] = [$key => $value];
            }
        }
        
        // $params = ['category_id' => $this->category_id];
        return $this->db->executeQuery($query, $params)->fetchAll();
    }

    public function duplicateSearch(string $table, string $condition, array $params): int {
        $query = "SELECT count(*) FROM {$table} WHERE {$condition}"; 
        $result = $this->db->executeQuery($query, $params); 
        return  $result->fetchColumn(); 
    }

    public function validateName(string  $condition, array $params) {
        
        $query = "SELECT * FROM expense_category WHERE {$condition}";
      
        $stmt = $this->db->executeQuery($query, $params); 
        $result = $stmt->fetch();
        return $result;
    }

    public function duplicateName($name) {
        $condition = "expense_name = :expense_name AND user_id = :user_id"; 
        $params = [
            'expense_name' => $name,
            'user_id' => $this->user_id
        ];
        
        return $this->duplicateSearch('expense_category', $condition, $params); 
    }
}

class Transaction {
    use ValidateData;
    private $db;
    private $user_id;
    private $expense_id;
    private $wallet_id;
    private $description;
    private float $amount;
    private $transaction_id;

    public function __construct(){
        $this->db = new DatabaseConnection;
        $this->user_id = $_SESSION['user_id'];
        // $this->user_id = $expense_id;re
        // $this->user_id = $wallet_id;
        // $this->user_id = $description;
        // $this->user_id = $amount;
    }

    public function getTransactions ($condition, $params) {
         
        $query = "SELECT * FROM transactions WHERE $condition";
        
        $stmt = $this->db->executeQuery($query, $params);
        
        if ($stmt) {
            return $stmt->fetchAll();
        } else {
            throw new PDOException("something went wrong.", 500);
        }
    }

    public function save(string $tableColumns,string $tableColumnsPlaceholders,array $params): int {
        $query = "INSERT INTO transactions ({$tableColumns}) VALUES ({$tableColumnsPlaceholders})";
        // echo $query;
        // var_dump($params);
        // die;
        $stmt = $this->db->executeQuery($query, $params);
        if ($stmt) {
            return $this->db->insertedId();
        } else {
            throw new Exception("Registration failed. Please try again.", 500);
        }
    }
    

    public function delete() {
        $query = "DELETE FROM transactions WHERE transaction_id = ?";
        $params = ['transaction_id' => $this->transaction_id];
        if ($this->db->executeQuery($query, $params)) {
            return "The transaction was successfully deleted.";
        } else {
            throw new Exception("Error: could not delete the transaction.", 500);
        };
    }

    public function update ($setValues, $condition, $params) {
        try {
            $query = "UPDATE transactions SET $setValues WHERE $condition";
            
        
            $stmt = $this->db->executeQuery($query, $params);
            
            if ($stmt->rowCount() > 0) {
                return "Update successful!";
            } else {
                throw new Exception("No changes made.", 501) ;
            }
        } catch (PDOException $e) {
            throw new Exception("Error: " . $e->getMessage(), 501);

        }
       

    }

}
?>