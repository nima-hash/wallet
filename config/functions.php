<?php
// session_start();
include "database.php";

function page_redirect($url){
  header("location: $url");
  die;
}

function checkLogin(){

  if (!isset($_SESSION['user_id'])) {
    //redirect to login
    header("location: login.php");
    die;
  }

}

function arrangeCardsInDecks ($allCards) {
  $arrangedDecks=[];
      foreach ($allCards as $card){
          $arrangedDecks[$card['deckName']][] = $card;
      }
  return $arrangedDecks;
}

function console_log($variable) {
  echo "<script>console.log(" . json_encode($variable, JSON_PRETTY_PRINT) . ");</script>";
}

//gives correct dateformat    
function validateDate($date, $format = 'd-m-Y'){
  $d = DateTime::createFromFormat($format, $date);
  return $d && $d->format($format) === $date;
}

function test_input($data) {
  $data = trim($data);
  $data = stripslashes($data);
  $data = htmlspecialchars($data);
  return $data;
}

//prevents duplicate user names
// function check_duplicate_user($user){
//   $stmt = new User;
//   // $conn = $connection->connect();
//   // if($stmt -> getuser($user)) {

  
//   // // $query = "SELECT * FROM Users WHERE userName = :user ";
//   // // $stmt = $this -> connect -> prepareStatement($query);
//   // // $stmt -> execute("userName" -> $user);
//   // // $result = $stmt-> fetchAll();





//   // // $result = $conn->query($sql);
  
//   // // if ($result->num_rows > 0) {
//   // //   // output data of each row
//   // //   // while($row = $result->fetch_assoc()) {
//   // //   //   echo "id: " . $row["id"]. " - Name: " . $row["firstname"]. " " . $row["lastname"]. "<br>";
//   // //   // }
//   //   return false;
//   // } else {
//   //   $id = uniqid();
//   //   return $id;
//   // }

// }

//validate pass
function validate_Pass($pass){

    // satisfy password conditions
    $password = filter_var($pass, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $uppercase = preg_match('@[A-Z]@', $password);
    $lowercase = preg_match('@[a-z]@', $password);
    $number    = preg_match('@[0-9]@', $password);
    $specialChars = preg_match('@[^\w]@', $password);
    
    if($uppercase && $lowercase && $number && $specialChars && strlen($password)>8){
      
      return true;
      
    
    }else{

      return false;
    
    }
  }

  

?>