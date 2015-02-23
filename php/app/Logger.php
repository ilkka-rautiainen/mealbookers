<?php

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
        $levels = Conf::inst()->get('log.levels');
        $this->levels = $this->levelNames = array();
        foreach ($levels as $key => $name) {
            $this->levels[$name] = $key;
            $this->levelNames[] = $name;
        }

        $loggingLevel = Conf::inst()->get('log.level');
        $this->loggingLevel = $this->levels[$loggingLevel];
        $this->file = fopen(__DIR__ . "/../log/" . Conf::inst()->get('log.file'), "a");
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
    private static function inst()
    {
        if (is_null(self::$instance))
            self::$instance = new Logger();

        return self::$instance;
    }

    /**
     * Put a log message to log
     */
    private function log($level, $message, $backtrace = false)
    {
        global $current_user;
        if (!isset($current_user->id))
            $id = "-none-";
        else
            $id = $current_user->id;


        $backtrace_str = "";
        if ($backtrace) {
            foreach (debug_backtrace() as $bt) {
                $backtrace_str .= "   $bt[file]:$bt[line] $bt[class]::$bt[function]\n";
            }
        }

        if (fwrite($this->file, gmdate("d/m/y H:i:s"). substr((string)microtime(), 1, 4) . " [$id][" . strtoupper($level) . "] $message\n$backtrace_str") === false)
            throw new Exception("Unable to write to log");
    }

    /**
     * Take the log function calls
     * @param  string $name      Name of the function called
     * @param  array  $arguments Arguments
     * @return void
     */
    public static function __callStatic($name, $arguments)
    {
        $backtrace = false;
        if (!in_array($name, self::inst()->levelNames))
            throw new Exception("No such logging level: $name");
        else if (self::inst()->levels[$name] > self::inst()->loggingLevel)
            return;
        else if (count($arguments) != 1)
            throw new Exception("Invalid number of arguments");

        if (self::inst()->levels[$name] <= self::inst()->levels['error']) {
            $backtrace = true;
        }

        return self::inst()->log($name, $arguments[0], $backtrace);
    }
}