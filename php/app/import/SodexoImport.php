<?php

abstract class SodexoImport extends Import
{
	protected $days = array(
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    );
    /**
     * Load JSON data from given url
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
     */
    protected function getWeekStartDay()
    {
        return date("Y/m/d" , strtotime("last monday", strtotime("tomorrow")));
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
        // TODO: URL Handeling
        $data = $this->getJSONData("http://www.sodexo.fi/ruokalistat/output/weekly_json/".$this->sodexo_id."/".$this->getWeekStartDay()."/fi");
        Logger::note(__METHOD__ . $data);
        $last_current_day = -1;
        

        foreach ($this->days as $day) {
            $last_current_day = $last_current_day + 1;
            $this->startDay($last_current_day);
            foreach ($data["menus"][$day] as $course) {
                /* Finnish */
                $meal = new Meal();
                $meal->language = "fi";
                $meal->name = $course["title_fi"]."<span class=\"attribute-group\"><span class=\"attribute\">".$course["properties"]."</span></span>";
                $this->addMeal($meal);
                /* English */
                $meal = new Meal();
                $meal->language = "en";
                $meal->name = $course["title_en"]."<span class=\"attribute-group\"><span class=\"attribute\">".$course["properties"]."</span></span>";
                $this->addMeal($meal);
            }
            $this->endDayAndSave();
        }
    }    
}
