<?php

class AmicaImport extends Import
{

    /**
     * @var Language configuration for the import
     */
    protected $langs = array(
        'fi' => array(
            'weekdays' => array(
                'Maanantai',
                'Tiistai',
                'Keskiviikko',
                'Torstai',
                'Perjantai',
                'Lauantai',
                'Sunnuntai',
            ),
            'sections' => array(
                'Kahvila\:?' => 'cafe',
                'A la carte\:?' => 'alacarte',
                'Bistro\:?' => 'bistro',
                'Tuunaa oma hampurilaisesi' => 'tune_own_burger',
            ),
            'ignore' => array(
                'Hyvää ruokahalua!',
            ),
        ),
        'en' => array(
            'weekdays' => array(
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ),
            'sections' => array(
                'Cafe\:?' => 'cafe',
                'A la carte\:?' => 'alacarte',
                'Bistro\:?' => 'bistro',
                'Fine-tune your own burger' => 'tune_own_burger',
            ),
            'ignore' => array(
                'Enjoy your meal!',
            ),
        ),
    );

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

        // Fetch parameters from front page
        $source = $this->fetchURL($this->url);
        if (!preg_match(
            "/" . preg_quote("window.open('/Templates/RestaurantPage/RestaurantMenuPrintPage.aspx?id=", "/") . "([0-9]+)/",
            $source,
            $matches
        ))
            throw new ParseException("Error in Amica import: no id found");

        phpQuery::newDocument($source);
        $daterange = trim(pq("#ctl00_RegionPageBody_RegionPage_RegionContent_RegionMainContent_RegionMainContentMiddle_MainContentMenu_ctl00_HeadingMenu")->html());
        if (!$this->isValidDaterange($daterange))
            throw new ImportException("Wrong menu, date range was: $daterange");

        if ($save_opening_hours)
            $this->saveOpeningHours();

        $id = $matches[1];

        if (!preg_match(
            "/printMenu\\([^\\,]+\\,([^\\,]+)\\,([^\\)]+)\\);/",
            $source,
            $matches
        ))
            throw new ParseException("Error in Amica import: no menu type or number found");

        $menu_type = $matches[1];
        $menu_number = $matches[2];
        Logger::debug(__METHOD__ . " parameters fetched");
        
        // Fetch print page with the fetched parameters
        $error = false;

        foreach ($this->langs as $lang => $lang_config) {
            try {
                Logger::debug(__METHOD__ . " start lang $lang");
                $source = $this->fetchURL("http://www.amica.fi/Templates/RestaurantPage/RestaurantMenuPrintPage.aspx?id=$id&page=$id&bn=$lang&a=$menu_type&s=$menu_number");

                phpQuery::newDocument($source);
                
                // Get lines and process them
                $lines = $this->getLines();
                foreach ($lines as $line) {
                    $this->processLine($line, $lang);
                }

                $this->endDayAndSave(); // Save the last day which is open
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
    }

    /**
     * Retrieves meal lines from source code
     */
    protected function getLines()
    {
        $p_list = pq('#ctl00_RegionPageBody_RegionPage_MenuLabel > p');
        if (!$p_list->length)
            throw new ParseException("No <p> elements found in the menu");

        $lines = array();
        // Go through the menu
        foreach ($p_list as $p) {
            $html = pq($p)->html();
            $lines[] = trim($html);
        }
        return $lines;
    }

    /**
     * Computes one line in the menu <p> list
     */
    private function processLine($line_html, $lang)
    {
        Logger::trace(__METHOD__ . " processing line: $line_html");

        // Check for <br><strong> within the line
        $pos = mb_stripos($line_html, "<br><strong>");
        if ($pos !== false) {
            Logger::debug(__METHOD__ . " <br><strong> found processing first part");
            $this->processLine(trim(mb_substr($line_html, 0, $pos)), $lang);
            Logger::debug(__METHOD__ . " <br><strong> found processing second part");
            $this->processLine(trim(mb_substr($line_html, $pos + 4)), $lang);
            return;
        }

        // Do some replacements
        $line_html = str_replace(array('\r\n', '\n'), array(" ", " "), $line_html);
        $line_html = preg_replace('#<br\s*/?>#i', "\n", $line_html);
        $line_html = strip_tags($line_html, '<strong>');
        $line_html = html_entity_decode($line_html, ENT_QUOTES);
        $line_html = str_replace(chr(194) . chr(160), ' ', $line_html); // replace &nbsp; in utf-8
        $line_html = trim($line_html);

        Logger::trace(__METHOD__ . " line after replacements: $line_html");

        if (!$line_html) {
            Logger::debug(__METHOD__ . " empty line");
            return;
        }
        else if ($this->isLineIgnored($line_html, $lang)) {
            Logger::debug(__METHOD__ . " line in ignore list");
            return;
        }

        // Day starts
        if (($day_number = $this->getDayNumber($line_html, $lang)) !== false) {
            $this->endDayAndSave();
            $this->startDay($day_number);
            Logger::debug(__METHOD__ . " start day found $day_number");
        }
        // Section starts
        else if (($section_name = $this->getSectionName($line_html, $lang)) !== false) {
            $this->startSection($section_name);
            Logger::debug(__METHOD__ . " section found $section_name");
        }

        if (!$this->isDayActive()) {
            Logger::debug(__METHOD__ . " no day active, ignoring line");
            return;
        }

        // Add the meal
        $line_html = $this->formatAttributes($line_html);
        $line_html = $this->formatLineBreaks($line_html);

        if (!$line_html) {
            Logger::debug(__METHOD__ . " empty line");
            return;
        }

        $meal = new Meal();
        $meal->language = $lang;
        $meal->name = $line_html;

        $this->addMeal($meal);

        // Amicas sections are only for one row
        $this->endSection();
    }

    /**
     * Get number of the day if a day starts at the current line.
     * This function strips the day also away from the line_html string.
     * @return int if start day is found, otherwise false
     */
    private function getDayNumber(&$line_html, $lang)
    {
        foreach ($this->langs[$lang]['weekdays'] as $day_number => $weekday) {
            if (mb_stripos(trim(strip_tags($line_html)), $weekday) === 0) {

                // There's most oftenly <strong> around the day
                $strong_end = mb_stripos($line_html, "</strong>");
                if ($strong_end !== false)
                    $line_html = trim(mb_substr($line_html, $strong_end + 9));
                else { // But not always
                    $weekday_pos = mb_stripos($line_html, $weekday);
                    $line_html = trim(substr($line_html, $weekday_pos + mb_strlen($weekday)));
                }
                return $day_number;
            }
        }
        return false;
    }

    /**
     * Get section name from the current line
     * @return int if section is found, otherwise false
     */
    private function getSectionName(&$line_html, $lang)
    {
        foreach ($this->langs[$lang]['sections'] as $name_lang => $name_en) {
            if (preg_match("/^" . preg_quote("<strong>", "/") . "[\\s]*" . $name_lang . "[\\s]*" . preg_quote("</strong>", "/") . "/i", $line_html, $matches)) {
                $line_html = trim(mb_substr($line_html, strlen($matches[0])));
                return $name_en;
            }
        }

        return false;
    }

    private function isLineIgnored($line_html, $lang)
    {
        foreach ($this->langs[$lang]['ignore'] as $ignore) {
            if (preg_match("/" . $ignore . "/", $line_html))
                return true;
        }
        return false;
    }

    /**
     * Formats the line breaks in a row
     */
    protected function formatLineBreaks($line_html)
    {
        return str_replace(array("\r\n", "\n"), array('<span class="line-break"></span>', '<span class="line-break"></span>'), $line_html);
    }

    /**
     * Formats the attributes in a row
     */
    private function formatAttributes($line_html)
    {
        preg_match_all("/\(((Veg|VS|G|L|VL|M|\*)(\, ))*(Veg|VS|G|L|VL|M|\*)\)/i", $line_html, $matches);

        $subMatches = $matchStarts = array();
        $lastMatchStart = -1;
        foreach ($matches[0] as $subMatch) {
            preg_match_all("/(?:Veg)|(?:VS)|G|L|(?:VL)|M|\*/i", $subMatch, $subMatchArray);
            foreach ($subMatchArray[0] as $key => $value)
                $subMatchArray[0][$key] = $value;
            $lastMatchStart = mb_stripos($line_html, $subMatch, $lastMatchStart+1);
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
            $line_html = mb_substr($line_html, 0, $subMatch['start'])
                . "<span class=\"attribute-group\">" . implode(" ", $subMatch['attributes']) . "</span>"
                . mb_substr($line_html, $subMatch['start'] + $subMatch['length']);
        }

        return $line_html;
    }

    /**
     * Validates the date range got from the front page
     */
    private function isValidDaterange($daterange)
    {
        if (!preg_match("/([0-9]{1,2})\\.([0-9]{1,2})\\.([0-9]{4})[ \\-]+([0-9]{1,2})\\.([0-9]{1,2})\\.([0-9]{4})/",
            $daterange,
            $matches))
        {
            throw new ParseException("Didn't find date range in the document");
        }
        $start = mktime(0, 0, 0, $matches[2], $matches[1], $matches[3]);
        $end = mktime(0, 0, 0, $matches[5], $matches[4], $matches[6]);
        if ($start >= strtotime($this->getWeekStartDay()) && $end <= strtotime($this->getWeekEndDay()))
            return true;
        else
            return false;
    }
}