<?php

abstract class AmicaJSONImport extends Import
{

    /**
     * @var Language configuration for the import
     */
    protected $langs = array(
        'fi' => array(
            'sections' => array(
                'A la carte' => 'alacarte',
            ),
        ),
        'en' => array(
            'sections' => array(
                'A la carte' => 'alacarte',
            ),
        ),
    );

    /**
     * Runs the import
     */
    public function run($save_opening_hours = false)
    {
        Logger::note(__METHOD__ . " start");

        if (!$this->is_import_needed) {
            Logger::info(__METHOD__ . " import not needed, skipping");
            return;
        }

        if ($save_opening_hours) {
            require_once __DIR__ . '/../lib/phpQuery.php';
            $source = $this->fetchURL($this->url);
            phpQuery::newDocument($source);
            $this->saveOpeningHours();
        }

        foreach ($this->langs as $lang => $lang_config) {
            try {
                Logger::debug(__METHOD__ . " start lang $lang");
                $source = $this->fetchURL("http://www.amica.fi/modules/json/json/Index?CostNumber={$this->costNumber}&Language=$lang&"
                    . "firstDay=" . Application::inst()->getDateForDay('this_week_monday')
                    . "&lastDay=" . Application::inst()->getDateForDay('this_week_sunday'));

                $menu = json_decode($source, true);
                if (!$menu || json_last_error())
                    throw new ParseException("Couldn't parse json");
                
                if (!is_array($menu['MenusForDays']))
                    throw new ParseException("MenusForDays not an array");

                // Loop the days and meals
                foreach ($menu['MenusForDays'] as $day_menu) {
                    Logger::trace(__METHOD__ . " start day " . $day_menu['Date']);
                    if (!is_array($day_menu['SetMenus']))
                        throw new ParseException("SetMenus not an array");
                    
                    $this->startDay($day_menu['Date']);
                    foreach ($day_menu['SetMenus'] as $meal) {
                        $this->addMeal($meal, $lang);
                    }
                    $this->endDayAndSave();
                }
            }
            catch (ImportException $e) {
                DB::inst()->rollbackTransaction();
                Logger::error(__METHOD__ . " Error in import: " . $e->getMessage()
                    . ", from:" . $e->getFile() . ":" . $e->getLine()
                    . ", in restaurant: {$this->restaurant->name}");
                $exception = $e;
            }
        }

        if (isset($exception))
            throw $exception;

        Logger::note(__METHOD__ . " succeeded");
        $this->postImport();
    }

    /**
     * Starts day from JSON formatted day string
     */
    protected function startDay($date)
    {
        Logger::debug(__METHOD__ . " start day $date");
        $date = substr($date, 0, 10);
        $time = strtotime($date);
        if ($time < strtotime(Application::inst()->getDateForDay('this_week_monday')))
            throw new ImportException("Date $date was too early");
        else if ($time > strtotime(Application::inst()->getDateForDay('this_week_sunday')))
            throw new ImportException("Date $date was too late");

        parent::startDay(((int)date("N", $time)) - 1);
    }

    /**
     * Adds meal from JSON
     */
    protected function addMeal($meal, $lang)
    {
        Logger::debug(__METHOD__ . " " . $meal['Name']);
        $meal_obj = new Meal();
        $components = array();
        foreach ($meal['Components'] as $component) {
            $components[] = $this->formatAttributes($component);
        }
        $meal_obj->language = $lang;
        $meal_obj->name = implode("<span class=\"line-break\"></span>", $components);
        
        if ($section = $this->getSectionName($meal['Name'], $lang)) {
            $this->startSection($section);
        }
        
        parent::addMeal($meal_obj);
        $this->endSection();
    }

    /**
     * Get section name from meal name
     * @return int if section is found, otherwise null
     */
    private function getSectionName(&$name, $lang)
    {
        foreach ($this->langs[$lang]['sections'] as $name_lang => $name_en) {
            if (stripos($name, $name_lang) !== false) {
                $name = trim($name);
                return $name_en;
            }
        }

        return null;
    }

    /**
     * Formats the attributes in a row.
     */
    private function formatAttributes($line)
    {
        preg_match_all("/[\s]*\(((Veg|VS|G|L|VL|M|\*)(\,[\s]*))*(Veg|VS|G|L|VL|M|\*)(?:\,[\s]*)?\)[\s]*/i", $line, $matches);

        $subMatches = $matchStarts = array();
        $lastMatchStart = -1;
        foreach ($matches[0] as $subMatch) {
            preg_match_all("/(?:Veg)|(?:VS)|G|L|(?:VL)|M|\*/i", $subMatch, $subMatchArray);
            foreach ($subMatchArray[0] as $key => $value)
                $subMatchArray[0][$key] = $value;
            $lastMatchStart = mb_stripos($line, $subMatch, $lastMatchStart + 1);
            $matchStarts[] = $lastMatchStart;
            $subMatches[] = array(
                'start' => $lastMatchStart,
                'length' => strlen($subMatch),
                'attributes' => $subMatchArray[0],
            );
        }
        array_multisort($matchStarts, SORT_DESC, $subMatches);
        foreach ($subMatches as $subMatch) {
            foreach ($subMatch['attributes'] as $key => $attribute)
                $subMatch['attributes'][$key] = "<span class=\"attribute\">$attribute</span>";
            $line = mb_substr($line, 0, $subMatch['start'])
                . " <span class=\"attribute-group\">" . implode(" ", $subMatch['attributes']) . "</span>"
                . mb_substr($line, $subMatch['start'] + $subMatch['length']);
        }

        return $line;
    }
}