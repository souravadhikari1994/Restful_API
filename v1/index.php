<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

//A hello world welcome message
$app->get('/welcome', function() {
    $response = array();
    $response["Hello World Message"]= "Welcome to the bank!";
    echoRespnse(200,$response);
});

//Register a new card
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('number', 'name', 'pin'));

            $response = array();

            // reading post params
            $number = $app->request->post('number');
            $name= $app->request->post('name');
            $pin = $app->request->post('pin');

            $db = new DbHandler();
            $res = $db->register($number, $name, $pin);

            if ($res == "CARD_CREATED_SUCCESSFULLY") {
                $response["error"] = false;
                $response["message"] = "Card successfully registered";
            } else if ($res == "CARD_CREATE_FAILED") {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registering";
            } else if ($res == "CARD_ALREADY_EXISTS") {
                $response["error"] = true;
                $response["message"] = "Sorry, this card already exists";
            }
            // echo json response
            echoRespnse(201, $response);
        });

 //Get your account balance
$app->post('/balance', function() use ($app) {
    
            verifyRequiredParams(array('number', 'pin'));

            $response = array();

            // reading post params
            $number = $app->request->post('number');
            $pin = $app->request->post('pin');
   
            $db = new DbHandler();
            $result = $db->checkBalance($number, $pin);
           
            if ($result == NULL){
                $response["error"] = true;
                $response["name"] = "Oops! Invalid Credentials";
            } else{
                    $detail = $result->fetch_assoc();
                    $response["error"] = false;
                    $response["name"] = $detail["name"];
                    $response["balance"] = $detail["balance"];
            }
            echoRespnse(200, $response);
        });

//Credit Transaction route
$app->post('/credit', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('number', 'pin', 'amount'));

            $response = array();

            // reading post params
            $number     =   $app->request->post('number');
            $pin        =   $app->request->post('pin');
            $amount     =   $app->request->post('amount');

            $db = new DbHandler();
            $res = $db->creditTransaction($number, $pin, $amount);

            if ($res == "SUCCESS") {
                $response["error"] = false;
                $response["message"] = "Transaction Successful";
            } else if ($res == "INVALID_CREDENTIALS") {
                $response["error"] = true;
                $response["message"] = "Sorry, invalid credentials";
            }
            // echo json response
            echoRespnse(201, $response);
        }); 
  
//Debit Transaction route
$app->post('/debit', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('number', 'pin', 'amount'));

            $response = array();

            // reading post params
            $number = $app->request->post('number');
            $pin = $app->request->post('pin');
            $amount = $app->request->post('amount');

            $db = new DbHandler();
            $res = $db->debitTransaction($number, $pin, $amount);

            if ($res == "SUCCESS") {
                $response["error"] = false;
                $response["message"] = "Transaction Successful";
            } else if ($res == "INSUFFICIENT_FUNDS") {
                $response["error"] = true;
                $response["message"] = "Oops! You have insufficient funds in your account.";
            } else if ($res == "INCORRECT_DETAILS") {
                $response["error"] = true;
                $response["message"] = "Sorry, invalid credentials";
            }
            // echo json response
            echoRespnse(201, $response);
        });
        

/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>