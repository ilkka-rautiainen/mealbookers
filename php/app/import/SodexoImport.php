<?php

abstract class SodexoImport extends Import
{
	/**
	* All weekdays are listed here and are needed for searching Sodexo JSON feed
	* Weekdays work as keys for menus
	*/
	protected $days = array(
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    );
    protected $sodexoUriBase = "http://www.sodexo.fi/ruokalistat/output/weekly_json/";
    /**
     * Load JSON data from given 
     * --------------------------
     * Retuns nothing if no JSON data received from given url
     * Retuns data array of JSON data if data found from given url
     */
    public function getJSONData($jsonurl)
    {
        $data = json_decode(file_get_contents($jsonurl), true);
        if ($data === null) {
            Logger::error(__METHOD__ . " " . json_last_error_msg());
            Application::inst()->exitWithHttpCode(400, "Invalid JSON sent");
            return;
        }
        return $data;
    }
    /**
     * Returns date in Sodexo JSON API format
     * ---------------------------------------
     * Get date of last monday and returns it in correct string format for Sodexo API
     */
    protected function getWeekStartDay()
    {
        return date("Y/m/d" , strtotime("last monday", strtotime("tomorrow")));
    }
    /*
    * Transforms atributes to Mealbookers format
    * ------------------------------------------
    * Takes in atributes in Sodexo JSON format and transforms and adds HTML tags
    */
    protected function atributeHandeling($properties){
    	// TODO split properties and add correct handeling
    	$returnValue = "<span class=\"attribute-group\">";
    	return "<span class=\"attribute-group\"><span class=\"attribute\">".$properties."</span></span>";
    }
    /**
    * Returns correc Sodexo uri
    */
    protected function getSodexoUri(){
    	return $this->sodexoUriBase.$this->sodexo_id."/".$this->getWeekStartDay()."/fi";
    }

    /**
     * Runs the import
     */
    public function run($save_opening_hours = false)
    {
        Logger::note(__METHOD__ . " start");
        require_once __DIR__ . '/../lib/phpQuery.php';

        if (!$this->is_import_needed) {
            Logger::info(__METHOD__ . " import not needed, skipping");
            return;
        }

        // Save opening hours
        if ($save_opening_hours)
            $this->saveOpeningHours();
        $data = $this->getJSONData($this->getSodexoUri());
        $last_current_day = -1; // initialize value
        /**
        * Reading all menus from each day
        */
        foreach ($this->days as $day) {
            $last_current_day = $last_current_day + 1;
            $this->startDay($last_current_day);
            if (array_key_exists($day, $data["menus"])){
	            foreach ($data["menus"][$day] as $course) {
	                /* Finnish */
	                $meal = new Meal();
	                $meal->language = "fi";
	                $nameStr = "";
	              	if (array_key_exists("title_fi", $course))
	                	$nameStr = $course["title_fi"];
	                if (array_key_exists("properties", $course))
	                	$nameStr = $nameStr . $this->atributeHandeling($course["properties"]);
	                $meal->name = $nameStr;
	                $this->addMeal($meal);
	                /* English */
	                $meal = new Meal();
	                $meal->language = "en";
	                $nameStr = "";
	              	if (array_key_exists("title_en", $course))
	                	$nameStr = $course["title_en"];
	                if (array_key_exists("desc_fi", $course))
	                	if ($nameStr == "")
	                		$nameStr = $course["desc_fi"];
	                if (array_key_exists("properties", $course))
	                	$nameStr = $nameStr . $this->atributeHandeling($course["properties"]);
	                $meal->name = $nameStr;
	                $this->addMeal($meal);
            	}
            }
            $this->endDayAndSave();
        }
    }    
}
