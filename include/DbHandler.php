<?php

class DbHandler {

    //Establish Database Connection
    private $conn;
    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    // Register a new card
    public function register($number, $name, $pin){
        require_once 'PassHash.php';
        
        // First check if card already existed in db
        if (!$this->isCardExists($number)) {
            
            $pin_hash = PassHash::hash($pin);
            // insert query
            $stmt = $this->conn->prepare("INSERT INTO card(number, name, pin_hash, status) values(?, ?, ?, 1)");
            $stmt->bind_param("sss", $number, $name, $pin_hash);
            $result = $stmt->execute();
            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return $response="CARD_CREATED_SUCCESSFULLY";  
            } else {
                // Failed to create user
                return $response="CARD_CREATE_FAILED";
            }
        } else {
            // User with same email already existed in the db
            return $response="CARD_ALREADY_EXISTS";
        }
    }
    
    //Get balance details
    public function checkBalance($number, $pin){
        
        if ($this->checkDetails($number, $pin)){
            $stmt = $this->conn->prepare("SELECT * FROM card WHERE number = ?");
            $stmt->bind_param("i", $number);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
            return $result;
        } else {
            return NULL;
        }
    }
    
    //Credit Transactions - Add amounts to the card
    public function creditTransaction($number, $pin, $amount){

        if ($this->checkDetails($number, $pin)){
                $stmt = $this->conn->prepare("UPDATE card SET balance = balance + ? WHERE number = ?");
                $stmt->bind_param("ii", $amount,$number);
                $stmt->execute();
                $stmt->close();
                
                $stmt = $this->conn->prepare("INSERT INTO transaction (number, amount, type) VALUES (?,?,'CREDIT')");
                $stmt->bind_param("ii", $number,$amount);
                $stmt->execute();
                $stmt->close();
                
                return $response="SUCCESS";
        } else {
            return $response = "INVALID_CREDENTIALS";
        }
                      
    }

    //Debit Transactions - Deduct amount from the card
    public function debitTransaction($number, $pin, $amount){

        if ($this->checkDetails($number, $pin)){
            $stmt = $this->conn->prepare("SELECT balance FROM card WHERE number = ?");
            $stmt->bind_param("i", $number);
            $stmt->execute();
            $stmt->bind_result($balance);
            $stmt->fetch();
            $stmt->close();
            
            if($balance>=$amount){
                $stmt = $this->conn->prepare("UPDATE card SET balance = balance - ? WHERE number = ?");
                $stmt->bind_param("ii", $amount,$number);
                $stmt->execute();
                $stmt->close();
                
                $stmt = $this->conn->prepare("INSERT INTO transaction (number, amount, type) VALUES (?,?,'DEBIT')");
                $stmt->bind_param("ii", $number,$amount);
                $stmt->execute();
                $stmt->close();
                
                return $response="SUCCESS";
            }
            else{
                return $response="INSUFFICIENT_FUNDS";
            }            
        }
        else{
            return $response="INCORRECT_DETAILS";
        } 

    }
    
    //Check if the card with same number exists
    private function isCardExists($number) {
        $stmt = $this->conn->prepare("SELECT number FROM card WHERE number = ?");
        $stmt->bind_param("s", $number);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
    
    //Check the card credentials
    private function checkDetails($number, $pin) {
        // fetching password hash by number
        $stmt = $this->conn->prepare("SELECT pin_hash FROM card WHERE number = ?");

        $stmt->bind_param("s", $number);

        $stmt->execute();
        
        $stmt->bind_result($pin_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the number
            // Now verify the pin

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($pin_hash, $pin)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }
    
}  

