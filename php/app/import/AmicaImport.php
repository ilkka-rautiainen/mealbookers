<?php

abstract class AmicaImport extends Import
{
    protected $amicaUrlBase = "http://www.amica.fi/modules/json/json/Index?costNumber={cost_number}&language={language}";

    /**
     * Amica uses ongoing window in their menu's. The default needed is not used and the run
     * function itself keeps track of if the import is needed in a daily manner.
     */
    public function init()
    {
        parent::init();
        $this->is_import_needed = true;
    }

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
            Logger::error(__METHOD__ . " json error " . json_last_error());
            throw new ImportException("Invalid JSON sent", $this->restaurant_id, "");
        }
        return $data;
    }

    /*
    * Transforms atributes to Mealbookers format
    * ------------------------------------------
    * Takes in atributes in Sodexo JSON format and transforms and adds HTML tags
    */
    protected function atributeHandeling($properties){
        $propertiesList = explode(",", $properties); // Splits properties to array
        $returnString = "<span class=\"attribute-group\">";  // Atribute group span open
        foreach ($propertiesList as $propertie) {
            // Atribute span open
            $returnString = $returnString . "<span class=\"attribute\">" . $propertie . "</span>";
        }
        $returnString = $returnString . "</span>"; // Atribute group span close
        return $returnString;
    }

    /**
    * Returns correct Amica uri
    */
    protected function getUrl($language) {
        return str_replace(
            array('{cost_number}', '{language}'),
            array($this->cost_number, $language),
            $this->amicaUrlBase
        );
    }

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

        // Save opening hours
        if ($save_opening_hours)
            $this->saveOpeningHours();

        $langs = array('fi', 'en');

        foreach ($langs as $lang) {
            $data = $this->getJSONData($this->getUrl($lang));

            // Loop through days
            foreach ($data['MenusForDays'] as $day_number => $menu) {
                $this->startDayForDate(mb_substr($menu['Date'], 0, 10));
                if (DB::inst()->getOne("SELECT id FROM meals WHERE
                    `day` = '" . DB::inst()->quote(mb_substr($menu['Date'], 0, 10), false) . "' AND
                    language = '$lang' AND
                    restaurant_id = {$this->restaurant_id} LIMIT 1") !== null) {
                    continue;
                }
                foreach ($menu['SetMenus'] as $course) {
                    if (count($course['Components']) == 0) {
                        continue;
                    }
                    $meal = new Meal();
                    $meal->language = $lang;
                    $meal->name = $this->formatAttributes(implode('<span class="line-break"></span>', $course['Components']));
                    $this->addMeal($meal);
                }
                $this->endDayAndSave();
            }
        }
    }

    /**
     * Formats the attributes in a row.
     */
    private function formatAttributes($line)
    {
        $line = preg_replace("/[\s]*\([\s]*\)[\s]*/", "", $line);
        preg_match_all("/[\s]*\(((Veg|VS|G|L|VL|M|A|\*)([\s]*\,[\s]*))*(Veg|VS|G|L|VL|M|A|\*)(?:[\s]*\,[\s]*)?[\s]*\)[\s]*/i", $line, $matches);

        $subMatches = $matchStarts = array();
        $lastMatchStart = -1;
        foreach ($matches[0] as $subMatch) {
            preg_match_all("/(?:Veg)|(?:VS)|G|L|(?:VL)|M|A|\*/i", $subMatch, $subMatchArray);
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
