<?php
/**
 * Logger class
 */

/**
 * Class for Log handling
 */
class Logger
{
    private static $instance = null;
    private $levels;
    private $levelNames;
    private $loggingLevel;
    private $file;
    
    /**
     * Singleton pattern: private constructor
     * Initialize log file
     */
    private function __construct()
    {
        global $config;
        $levels = $config["log"]["levels"];
        $this->levels = $this->levelNames = array();
        foreach ($levels as $key => $name) {
            $this->levels[$name] = $key;
            $this->levelNames[] = $name;
        }
        
        $loggingLevel = $config["log"]["level"];
        $this->loggingLevel = $this->levels[$loggingLevel];
        $this->file = fopen(__DIR__ . "/" . $config["log"]["file"], "a");
    }

    /**
     * Close the log file
     */
    function __destruct() {
        fclose($this->file);
    }
    
    /**
     * Singleton pattern: Instance
     */
    private static function Instance()
    {
        if (is_null(self::$instance))
            self::$instance = new Logger();
        
        return self::$instance;
    }
    
    /**
     * Put a log message to log
     */
    private function log($level, $message)
    {
        global $currentUser;
        if (!isset($currentUser->id))
            $id = "-none-";
        else
            $id = $currentUser->id;
        
        if (fwrite($this->file, gmdate("d/m/y H:i:s") . " [$id][" . strtoupper($level) . "] $message\n") === false)
            throw new Exception("Couldn't put error message");
            
    }

    /**
     * Take the log function calls
     * @param  string $name      Name of the function called
     * @param  array  $arguments Arguments
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        if (!in_array($name, self::Instance()->levelNames))
            throw new Exception("No such logging level: $name");
        else if (self::Instance()->levels[$name] > self::Instance()->loggingLevel)
            return;
        else if (count($arguments) != 1)
            throw new Exception("Invalid number of arguments");
            
        return self::Instance()->log($name, $arguments[0]);
    }
}