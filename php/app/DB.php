<?php
/**
 * DB class
 */

/**
 * Class for db handling
 */
class DB
{
    private static $instance = null;
    private $connection = null;
    private $insertId;
    private $lastResult;
    private $transactionActive = false;
    
    /**
     * Singleton pattern: private constructor
     * Connects to PostgreSQL database
     */
    private function __construct()
    {
        global $config;
        $this->connection = @pg_connect(
            "host=" . $config["db"]["host"] .
            " user=" . $config["db"]["user"] .
            " password=" . $config["db"]["pass"] .
            " dbname=" . $config["db"]["dbname"] .
            " port=" . $config["db"]["port"] .
            " options='--client_encoding=UTF8'"
        );
        if ($this->connection === false) {
            $this->connection = @pg_connect("dbname=" . $config["db"]["dbname"] . " options='--client_encoding=UTF8'");
            if ($this->connection === false)
                throw new Exception("Unable to connect to database");
        }
    }
    
    /**
     * Singleton pattern: Get instance
     */
    public static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new DB();
        
        return self::$instance;
    }
    
    /**
     * Performs a db query
     * @param $queryString
     */
    public function query($queryString)
    {
        Logger::trace(__METHOD__ . " $queryString");
        if (($result = @pg_query($this->connection, $queryString)) === false) {
            Logger::error(__METHOD__ . " DB error: " . pg_last_error($this->connection));
            throw new Exception(pg_last_error($this->connection));
        }
        $this->lastResult = $result;

        return $result;
    }
    
    /**
     * Fetches the query result as array
     * @param $result got from query()
     */
    public function fetchAssoc($result)
    {
        return pg_fetch_assoc($result);
    }
    
    /**
     * Fetches the query result as array
     * @param $result got from query()
     */
    public function getRowCount()
    {
        return pg_num_rows($this->lastResult);
    }
    
    /**
     * Gets first field of the first row in the result
     * @param $queryString
     * @return mixed result field or NULL if no more results
     */
    public function getOne($queryString)
    {
        $result = $this->query($queryString);
        $row = pg_fetch_array($result);
        if (!$row || !is_array($row) || !isset($row[0]))
            return null;
        
        return $row[0];
    }
    
    /**
     * Gets first row in the result
     * @param $queryString
     * @return mixed row array or NULL if no more results
     */
    public function getRowAssoc($queryString)
    {
        $result = $this->query($queryString);
        $row = pg_fetch_assoc($result);
        if (!$row || !is_array($row))
            return null;
        
        return $row;
    }
    
    /**
     * Escape the given string
     * @param $string
     * @return escaped string
     */
    public function quote($string)
    {
        return pg_escape_string($this->connection, $string);
    }

    /**
     * Runs sql updates
     */
    public function init()
    {
        global $config;
        Logger::info(__METHOD__ . " init db");
        
        if ($this->getOne("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public'") == 0)
            $this->runUpdate("initial_create_database.sql");

        $sql_version = (int)$this->getOne("SELECT sql_version FROM config LIMIT 1");

        $update_files = scandir(dirname(__FILE__) . "/sql_updates");
        foreach ($update_files as $update_file) {
            if (substr($update_file, 0, 6) == "update") {
                $update_number = (int)substr($update_file, 6, 4);
                if ($update_number > $sql_version)
                    $this->runUpdate($update_file, $update_number);
            }
        }
    }

    private function runUpdate($file_name, $sql_number = false)
    {
        Logger::note(__METHOD__ . " running sql update $file_name");

        // Temporary variable, used to store current query
        $current_query = '';
        // Read in entire file
        $lines = file(dirname(__FILE__) . "/sql_updates/$file_name");

        try {
            $this->startTransaction();
            // Loop through each line
            foreach ($lines as $line)
            {
                // Skip it if it's a comment
                if (substr($line, 0, 2) == '--' || $line == '')
                    continue;

                // Add this line to the current segment
                $current_query .= $line;
                // If it has a semicolon at the end, it's the end of the query
                if (substr(trim($line), -1, 1) == ';')
                {
                    // Perform the query
                    $this->query($current_query);
                    if ($sql_number !== false)
                        $this->query("UPDATE config SET sql_version = $sql_number");
                    // Reset temp variable to empty
                    $current_query = '';
                }
            }
            $this->commitTransaction();
        }
        catch (Exception $e) {
            $this->rollbackTransaction();
            Logger::critical(__METHOD__ . " running sql update $file_name failed: " + $e->getMessage());
            throw new Exception("SQL update failed");
        }
    }

    public function startTransaction()
    {
        $this->query("START TRANSACTION");
        $this->transactionActive = true;
    }

    public function commitTransaction()
    {
        if (!$this->transactionActive)
            return;
        $this->query("COMMIT");
        $this->transactionActive = false;
    }

    public function rollbackTransaction()
    {
        if (!$this->transactionActive)
            return;
        $this->query("ROLLBACK");
        $this->transactionActive = false;
    }
}