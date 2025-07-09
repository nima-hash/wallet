<?php
// require_once __DIR__ . "/functions.php";
require_once __DIR__ . "/constant.php";
// if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
//     header('Location: https://bithorizon.de/login.php');
//     exit();
// }
class BaseController
{
        /** 
    * __call magic method. 
    */
        // public function __call($data, $headers)
        // {
        //     var_dump($data);
        //     die;
        //     $this->sendOutput($data, $headers);
        // }
        /** 
    * Get URI elements. 
    * 
    * @return array 
    */
        protected function getUriSegments()
        {
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $uri = explode( '/', $uri );
            return $uri;
        }
        /** 
    * Get querystring params. 
    * 
    * @return array 
    */
        protected function getQueryStringParams()
        {
            
            parse_str($_SERVER['QUERY_STRING'], $query);
            
            return $query;
        }

        // protected function getFragmentStringParams()
        // {
        //     return parse_str($_SERVER['QUERY_STRING'], $query);
        // }
        /** 
     * Send API output. 
    * 
    * @param mixed $data 
    * @param string $httpHeader 
    */
        public function dropNull($data) {
            return is_array($data) 
                ? array_map(fn($item) => dropNull($item), $data) 
                : (($data === null || strtolower($data) === "null") ? "N/A" : $data);
        }
    
        // public function sendOutput($data, array $httpHeaders=array())
        // {
        //     header_remove('Set-Cookie');
        //     if (!empty($httpHeaders)) {
        //         foreach ($httpHeaders as $header) {
        //             header($header); // Set all headers properly
        //         };
            

        //         if (isset($httpHeaders[1]) && is_int($httpHeaders[1])) {
        //             http_response_code($httpHeaders[1]);
        //         }
        //     } else {
        //         header("Content-Type: application/json"); // Default to JSON
        //     }
        //     // if (is_array($httpHeaders) && count($httpHeaders)) {

        //     //     header($httpHeaders[0]);
        //     //     http_response_code($httpHeaders[1]);
                
        //     // }
        //     $data = str_replace([':null', ':"NULL"', ':"null"'], ':"N/A"', $data);
            
        //     // var_dump($data);
        //     // die;
        //     echo $data;
        //     exit;
        // }
        public function sendOutput($data, array $httpHeaders = array())
{
    // Entfernt potenziell problematische Set-Cookie-Header
    header_remove('Set-Cookie');

    // Standard-HTTP-Statuscode
    $statusCode = 200;

    // Verarbeite die bereitgestellten HTTP-Header
    if (!empty($httpHeaders)) {
        foreach ($httpHeaders as $header) {
            // Wenn der Header eine Ganzzahl ist, behandle ihn als Statuscode
            if (is_int($header)) {
                $statusCode = $header;
            }
            // Wenn es ein String ist, gehe davon aus, dass es ein regulärer Header ist
            // (z.B. "Content-Type: application/json")
            elseif (is_string($header)) {
                header($header);
            }
        }
    } else {
        // Wenn kein Header-Array bereitgestellt wird, standardmäßig auf JSON-Content-Type setzen
        header("Content-Type: application/json");
    }

    // Setze den HTTP-Antwortcode. Dies sollte nach anderen Headern erfolgen.
    http_response_code($statusCode);

    // Datenmanipulation: Ersetze null-ähnliche Strings durch "N/A"
    // Dies setzt voraus, dass $data ein String ist (z.B. bereits json_encoded)
    $data = str_replace([':null', ':"NULL"', ':"null"'], ':"N/A"', $data);

    // Gib die Daten aus
    echo $data;

    // Beende die Skriptausführung, um weitere Ausgaben zu verhindern
    exit;
}
}

class CustomException extends Exception {
    private array $data;

    public function __construct(string $message, int $code = 0, array $data = [], ?Exception $previous = null) { 
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    public function getData(): array {
        return $this->data;
    }
}




// function sendOutput($data, array $httpHeaders=array())
//         {
//             header_remove('Set-Cookie');
//             if (!empty($httpHeaders)) {
//                 foreach ($httpHeaders as $header) {
//                     header($header); // Set all headers properly
//                 };
            

//                 if (isset($httpHeaders[1]) && is_int($httpHeaders[1])) {
//                     http_response_code($httpHeaders[1]);
//                 }
//             } else {
//                 header("Content-Type: application/json"); // Default to JSON
//             }
//             // if (is_array($httpHeaders) && count($httpHeaders)) {

//             //     header($httpHeaders[0]);
//             //     http_response_code($httpHeaders[1]);
                
//             // }
//             $data = str_replace([':null', ':"NULL"', ':"null"'], ':"N/A"', $data);
//             // var_dump($data);
//             // die;
//             echo $data;
//             exit;
//         }

function sendOutput($data, array $httpHeaders = array())
{
    // Entfernt potenziell problematische Set-Cookie-Header
    header_remove('Set-Cookie');

    // Standard-HTTP-Statuscode
    $statusCode = 200;

    // Verarbeite die bereitgestellten HTTP-Header
    if (!empty($httpHeaders)) {
        foreach ($httpHeaders as $header) {
            // Wenn der Header eine Ganzzahl ist, behandle ihn als Statuscode
            if (is_int($header)) {
                $statusCode = $header;
            }
            // Wenn es ein String ist, gehe davon aus, dass es ein regulärer Header ist
            // (z.B. "Content-Type: application/json")
            elseif (is_string($header)) {
                header($header);
            }
        }
    } else {
        // Wenn kein Header-Array bereitgestellt wird, standardmäßig auf JSON-Content-Type setzen
        header("Content-Type: application/json");
    }

    // Setze den HTTP-Antwortcode. Dies sollte nach anderen Headern erfolgen.
    http_response_code($statusCode);

    // Datenmanipulation: Ersetze null-ähnliche Strings durch "N/A"
    // Dies setzt voraus, dass $data ein String ist (z.B. bereits json_encoded)
    $data = str_replace([':null', ':"NULL"', ':"null"'], ':"N/A"', $data);

    // Gib die Daten aus
    echo $data;

    // Beende die Skriptausführung, um weitere Ausgaben zu verhindern
    exit;
}
function dropNull($data) {
    return is_array($data) 
        ? array_map(fn($item) => dropNull($item), $data) 
        : (($data === null || strtolower($data) === "null") ? "N/A" : $data);
}
?>