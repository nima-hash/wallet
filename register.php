<?php



require __DIR__ . "/inc/bootstrap.php";

// Sanitize inputspasswordHash
if (is_array($_POST)) {
  foreach ($_POST as $key => $value) {
      $_POST[$key] = htmlspecialchars(strip_tags(trim($value)));
  }
}

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-headers: Content-Type,Authorization,X-Requested-with');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode( '/', $uri );

if ((isset($uri[1]) && $uri[1] != 'api') ) {
    header("HTTP/1.1 404 Not Found");
    exit();
}
require "/Applications/MAMP/htdocs/Flashcards/api/Controller/Api/UserController.php";
$objFeedController = new User();

// Determine how data is being sent
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
  $data = json_decode(file_get_contents("php://input"), true);
} else {
  $data = $_POST;
}

if (isset($data['action']) && $data['action'] === 'login'){
  $action = 'login';
  unset($data['action']);
}else{
  $action = 'post';
}



  
$strMethodName = $action . 'Action';
$objFeedController->{$strMethodName}($data);