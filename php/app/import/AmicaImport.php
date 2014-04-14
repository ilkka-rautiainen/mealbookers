<?php

abstract class AmicaImport extends Import
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
        Logger::note(__METHOD__ . " start " . $this->restaurant->name);
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
                $source = str_replace("&nbsp;", ' ', $source); // replace &nbsp; in utf-8

                phpQuery::newDocument($source);

                // Get lines
                $lines = $this->getLines();
                // Get days
                $days = $this->getDays($lines, $lang);
                // Process the lines
                foreach ($days as $day => $day_lines) {
                    $this->processDay($day, $day_lines, $lang);
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
        $this->postImport();
    }

    /**
     * Retrieves meal lines from source code
     */
    private function getLines()
    {
        $html = pq('#ctl00_RegionPageBody_RegionPage_MenuLabel')->html();
        if (!$html) {
            throw new ParseException("No menu element found");
        }
        $html = str_replace(array('<br>'), array(" "), $html);
        $lines = preg_split("/[\s]*\r?\n[\s]*/", trim(pq($html)->text()));
        return $lines;
    }

    private function getDays($lines, $lang)
    {
        $active_day = false;
        $days = array();
        foreach ($lines as $line) {
            $day = $this->getDayNumber($line, $lang);
            if ($day !== false) {
                $active_day = $day;
            }
            if ($active_day !== false && mb_strlen($line) && !$this->isLineIgnored($line, $lang)) {
                if (!isset($days[$active_day]))
                    $days[$active_day] = array();
                $line = trim($line);
                $line = strip_tags($line, '<strong>');
                $line = html_entity_decode($line, ENT_QUOTES);
                $days[$active_day][] = $line;
            }
        }
        return $days;
    }

    private function fixOneLine($line)
    {
        if (!preg_match_all('/\(((Veg|VS|G|L|VL|M|\*)(\,[\s]*))*(Veg|VS|G|L|VL|M|\*)(?:\,[\s]*)?\)/', $line, $matches, PREG_OFFSET_CAPTURE)) {
            return array($line);
        }
        else {
            $lines = array();
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $end = trim(substr($line, $matches[0][$i][1] + mb_strlen($matches[0][$i][0])));
                if (mb_strlen($end)) {
                    $lines[] = $end;
                }
                $line = substr($line, 0, $matches[0][$i][1] + mb_strlen($matches[0][$i][0]));
            }
            $lines[] = $line;
            $lines = array_reverse($lines);
            return $lines;
        }
    }

    /**
     * Computes one line in the menu <p> list
     */
    private function processDay($day, $day_lines, $lang)
    {
        Logger::trace(__METHOD__ . " with meals: " . count($day_lines));

        if (count($day_lines) <= 2) {
            $day_lines = $this->fixOneLine(implode(" ", $day_lines));
        }

        $this->startDay($day);
        foreach ($day_lines as $line) {
            $section = $this->getSection($line, $lang);
            $line = $this->formatAttributes($line);

            if (!mb_strlen(trim($line))) {
                Logger::debug(__METHOD__ . " empty line");
                return;
            }

            $line = $this->formatLineBreaks($line);

            $meal = new Meal();
            $meal->language = $lang;
            $meal->name = $line;
            $meal->section = $section;

            $this->addMeal($meal);
        }
        $this->endDayAndSave();
    }

    /**
     * Get number of the day if a day starts at the current line.
     * This function strips the day also away from the line string.
     * @return int if start day is found, otherwise false
     */
    private function getDayNumber(&$line, $lang)
    {
        foreach ($this->langs[$lang]['weekdays'] as $day_number => $weekday) {
            if (mb_stripos(trim(strip_tags($line)), $weekday) === 0) {

                // There's most oftenly <strong> around the day
                $strong_end = mb_stripos($line, "</strong>");
                if ($strong_end !== false)
                    $line = trim(mb_substr($line, $strong_end + 9));
                else { // But not always
                    $weekday_pos = mb_stripos($line, $weekday);
                    $line = trim(substr($line, $weekday_pos + mb_strlen($weekday)));
                }
                return $day_number;
            }
        }
        return false;
    }

    /**
     * Get section name from the current line, and strip away the section name
     * @return int if section is found, otherwise false
     */
    private function getSection(&$line, $lang)
    {
        foreach ($this->langs[$lang]['sections'] as $name_lang => $name_en) {
            if (preg_match("/^(" . preg_quote("<strong>", "/") . ")?[\\s]*" . $name_lang . "[\\s]*(" . preg_quote("</strong>", "/") . ")?/i", $line, $matches)) {
                $line = trim(htmlspecialchars((mb_substr($line, strlen($matches[0])))));
                return $name_en;
            }
        }

        return null;
    }

    private function isLineIgnored($line, $lang)
    {
        foreach ($this->langs[$lang]['ignore'] as $ignore) {
            if (preg_match("/" . $ignore . "/", $line))
                return true;
        }
        return false;
    }

    /**
     * Formats the line breaks in a row
     */
    protected function formatLineBreaks($line)
    {
        return str_replace(array("\r\n", "\n"), array('<span class="line-break"></span>', '<span class="line-break"></span>'), $line);
    }

    /**
     * Formats the attributes in a row.
     * Adds line break after attribute group if there's not.
     */
    private function formatAttributes($line)
    {
        preg_match_all("/[\s]*\(((Veg|VS|G|L|VL|M|\*)(\,[\s]*))*(Veg|VS|G|L|VL|M|\*)(?:\,[\s]*)?\)[\s]{0,2}\,?[\s]*/i", $line, $matches);

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
                . " <span class=\"attribute-group\">" . implode(" ", $subMatch['attributes']) . "</span>\n"
                . mb_substr($line, $subMatch['start'] + $subMatch['length']);
        }

        return trim($line);
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