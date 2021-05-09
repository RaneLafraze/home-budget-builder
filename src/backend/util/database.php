<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class DatabaseAgent {
    
    private $dbConnection;
    
    function __construct($databaseId = 1, $credentialPath = "../../database-setup/credentials/", $credentialPrefix = "gateway") {
        // Add trailing slash if needed
        if($credentialPath[strlen($credentialPath) - 1] != "/") {
            $credentialPath = $credentialPath . "/";
        }
        // Get credential if it exists
        $filePath = $credentialPath . $credentialPrefix . "-" . $databaseId . ".txt";
        $credentials = file_get_contents($filePath);
        $didParse = false;
        if($credentials === false) {
            throw new Exception("Invalid database credential path: " . $filePath);
        } else {
            try {
                $credentials = json_decode($credentials, true);
                $didParse = true;
            } catch(Exception $parseError) {
                echo("\nFailed to parse database credentials: " . $parseError->getMessage() . "\n");
            }
        }
        
        // Open database connection
        if($didParse) {
            $dbUser = $credentials["username"];
            $dbPassword = $credentials["password"];
            $dbName = $credentials["database"];
            $this->dbConnection = new mysqli("localhost", $dbUser, $dbPassword, $dbName);
            
            if($this->dbConnection->connect_error) {
                echo("\nConnection failed at " . $dbName . "@" . $dbUser . " : " . $dbPassword . "\n");
            }
        }
    }
    
    /**
     * Selects values from the initialized database based on the provided
     * query. This function is constructed to encourage preparing
     * statements to discourage injection attacks (use of ? marks). The
     * types argument will attempt to be programatically computed
     * if left blank. However, this may cause problems for ambiguous types
     * (like "4" and 4 being interpreted as a string or integer)
     * 
     * @example selectValues("SELECT * FROM entries")
     *          --> Returns array where each index contains key-value pairs of database table
     *              array(array("id" => 1, "summary" => "Test", ...), array("id" => 2, ...))
     * @example selectValues("SELECT summary FROM entries")
     *          --> Returns single dimension array with values
     *              array("Test entry", "New entry", ...)
     * @example selectValues("SELECT * FROM entries WHERE summary LIKE ?", "Test%")
     *          --> Returns array with entries with the summary similar to Test...
     *              array(array("id" => 1, "summary" => "Test", ...), array("id" => 4, ...))
     * 
     * @param String query - SQL query that filters and retrieves values
     * @param Array values - values to select by (strings, integers, etc.) [default = []]
     * @param String types - characters representing types of values [default = ""], computed if not provided
     * 
     * @return
     * A 1-D or 2-D array containing the result. False, if an error occurred
     */
    function selectValues($query, $values = [], $types = "") {
        
        if(is_null($this->dbConnection)) {
            return false;
        }
        
        // If only one value was given, turn it into an array
        if(gettype($values) != "array") {
            $values = array($values);
        }
        
        // Verify $types validity or compute if necessary
        if((strlen($types) != 0) && (strlen($types) != count($values))) {
            echo("\nTypes and Values don't match!\n");
            return false;
        } else if(strlen($types) == 0) {
            // Compute types (0 is valid, it just won't loop)
            for($t = 0; $t < count($values); $t++) {
                $types = $types . substr(gettype($values[$t]), 0, 1);
            }
        } // Else everything is fine
        
        // Create the statement
        $preparedStatement = $this->dbConnection->prepare($query);
        if($preparedStatement === false) {
            echo("\nUnable to prepare command: " . $query . "\n");
            return false;
        }
        
        // Bind (if needed)
        if(count($values) > 0) {
            if($preparedStatement->bind_param($types, ...$values) === false) {
                echo("\nBinding failed!\nTypes: " . print_r($types) . "\nValues: " . print_r($values) . "\n");
                return false;
            }
        }
        
        // And execute
        if($preparedStatement->execute() === false) {
            echo("\nExecution failed!\n");
            return false;
        }
        
        // Retrieve the result(s)
        $result = $preparedStatement->get_result();
        if($result->num_rows > 0) {
            $resultsArray = array();
            for($r = 0; $r < $result->num_rows; $r++) {
                // Push the result onto the array or into index
                $nextResult = $result->fetch_assoc();
                if(count($nextResult) == 1) { // Selected one column; push directly to index
                    $resultsArray[$r] = $nextResult[array_keys($nextResult)[0]];
                } else { // Select mulitple columns; push array to index
                    $resultsArray[] = $nextResult;
                }
            }
            return $resultsArray;
            
        } else {
            return array(); // Empty array instead of NULL
        }
    }
    
    
}

?>