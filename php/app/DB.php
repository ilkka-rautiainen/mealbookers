<?php

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
        if (isset($_SERVER['OPENSHIFT_MYSQL_DB_HOST'])) {
            $this->connection = new mysqli(
                $_SERVER['OPENSHIFT_MYSQL_DB_HOST'],
                $_SERVER['OPENSHIFT_MYSQL_DB_USERNAME'],
                $_SERVER['OPENSHIFT_MYSQL_DB_PASSWORD']
            );
        }
        else {
            $this->connection = new mysqli(
                Conf::inst()->get('db.host'),
                Conf::inst()->get('db.user'),
                Conf::inst()->get('db.pass')
            );
        }
        if ($this->connection->connect_error)
            throw new Exception($mysqli->connect_error);
        
        if (!$this->connection->set_charset("utf8"))
            throw new Exception("Unable to set character set in db connection");

        if (!$this->connection->select_db(Conf::inst()->get('db.dbname')))
            throw new Exception("Could not choose database");
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
        Logger::trace(__METHOD__ . " " . str_replace(
            array("\r\n", "\n", "                    ", "                ", "            ", "        ", "    "),
            array(" ", " ", " ", " ", " ", " ", " "),
            $queryString
        ));
        if (!$result = $this->connection->query($queryString)) {
            Logger::error(__METHOD__ . " MySQL error: " . $this->connection->error);
            throw new SqlException($this->connection->error);
        }

        $this->insertId = $this->connection->insert_id;
        
        return $result;
    }
    
    /**
     * Fetches the query result as an associative array
     * @param $result got from query()
     */
    public function fetchAssoc($result)
    {
        return $result->fetch_assoc();
    }
    
    /**
     * Fetches the query result as numerically indexed array
     * @param $result got from query()
     */
    public function fetchRow($result)
    {
        return $result->fetch_row();
    }
    
    /**
     * Fetches the first field from the result array
     * @param $result got from query()
     */
    public function fetchFirstField($result)
    {
        $array = $result->fetch_row();
        if (!isset($array[0]))
            return null;
        else
            return $array[0];
    }

    /**
     * Returns the last inserted id
     * @return id
     */
    public function getInsertId()
    {
        return $this->insertId;
    }
    
    /**
     * Fetches the query result as array
     * @param $result got from query()
     */
    public function getRowCount()
    {
        return $this->connection->affected_rows;
    }
    
    /**
     * Gets first field of the first row in the result
     * @param $queryString
     * @return mixed result field or NULL if no more results
     */
    public function getOne($queryString)
    {
        $result = $this->query($queryString);
        $row = $result->fetch_array();
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
        $result = $this->connection->query($queryString);
        $row = $result->fetch_assoc();
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
        return str_replace(
            array(
                '%',
                '_',
            ),
            array(
                '\%',
                '\_',
            ),
            $this->connection->escape_string($string)
        );
    }

    /**
     * Resets the db
     */
    public function resetDB()
    {
        Logger::note(__METHOD__ . " resetting db");
        if (!Conf::inst()->get('developerMode'))
            throw new Exception("Operation permitted only in developer mode");

        $this->query("SET foreign_key_checks = 0");
        $result = $this->query("SHOW TABLES");
        while ($row = $this->fetchRow($result)) {
            $this->query("DROP TABLE `" . $row[0] . "`");
        }
        $this->query("SET foreign_key_checks = 1");
    }

    /**
     * Runs sql updates
     */
    public function runUpdates()
    {
        global $config;
        Logger::info(__METHOD__ . " run sql updates");
        
        $this->query("SHOW TABLES");
        if ($this->getRowCount() == 0)
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
            throw new Exception("SQL update $file_name failed");
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

    public function isTransactionActive()
    {
        return $this->transactionActive;
    }
}