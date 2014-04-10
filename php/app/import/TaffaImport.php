<?php

class TaffaImport extends Import
{
    protected $restaurant_id = 7;

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
                'A la Carte\:?' => 'alacarte',
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
                'A la Carte\:?' => 'alacarte',
            ),
        ),
    );

    /**
     * Import and Save opening hours
     */
    protected function saveOpeningHours()
    {
        $source = $this->fetchURL("https://www.teknologforeningen.fi/menu.html?lang=fi");
        phpQuery::newDocument($source);

        $alacarte_element = pq('#page > p:last');
        if (!$alacarte_element)
            throw new ParseException("No alacarte element found");
        $opening_hour_element = pq($alacarte_element)->prev();
        if (!$opening_hour_element)
            throw new ParseException("No opening hour element found");

        $fetch_alacarte = false;
        if (pq($alacarte_element)->html() == 'À la carten slutar serveras en halv timme före stängningstid.')
            $fetch_alacarte = true;

        $html = pq($opening_hour_element)->html();
        if (!preg_match("/Måndag[\s]*\-[\s]*Torsdag\:[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])\<br\>"
            . "Fredag\:[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])/", $html, $matches)) {
            Logger::debug(__METHOD__ . " lines didn't match regex");

        }
        else {
            Logger::debug(__METHOD__ . " lines matched");
            DB::inst()->query("DELETE FROM restaurant_opening_hours WHERE restaurant_id = {$this->restaurant_id}");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 3, '" . $matches[1] . "', '" . $matches[3] . "', 'normal'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 3, '" . $matches[1] . "', '" . $matches[3] . "', 'lunch'
                )");
            if ($fetch_alacarte) {
                DB::inst()->query("INSERT INTO restaurant_opening_hours (
                        restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                    ) VALUES (
                        {$this->restaurant_id}, 0, 3, '" . $matches[1] . "', '" . $this->decreaseWithHalfHour($matches[3]) . "', 'alacarte'
                    )");
            }
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 4, 4, '" . $matches[5] . "', '" . $matches[7] . "', 'normal'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 4, 4, '" . $matches[5] . "', '" . $matches[7] . "', 'lunch'
                )");
            if ($fetch_alacarte) {
                DB::inst()->query("INSERT INTO restaurant_opening_hours (
                        restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                    ) VALUES (
                        {$this->restaurant_id}, 4, 4, '" . $matches[5] . "', '" . $this->decreaseWithHalfHour($matches[7]) . "', 'alacarte'
                    )");
            }
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 5, 6, '00:00:00', '00:00:00', 'normal'
                )");
            Logger::debug(__METHOD__ . " opening hours saved successfully");
        }
    }

    private function decreaseWithHalfHour($time)
    {
        return date("H:i", strtotime("1.2.2010 $time") - 1800);
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

        foreach (array_keys($this->langs) as $lang) {
            $source = $this->fetchURL("https://www.teknologforeningen.fi/menu.html?lang=$lang");
            phpQuery::newDocument($source);

            $last_current_day = -1;

            // Loop through the days
            $children = pq('#page div:eq(1)')->children('p, ul');
            if (!$children)
                throw new ParseException("No menu element found");

            foreach ($children as $child) {
                if ($child->tagName == 'p') {
                    $text = pq($child)->text();
                    $end = mb_strpos($text, ' ');
                    if ($end === false)
                        $end = mb_strlen($text);
                    // Check the day
                    $day_string = mb_substr($text, 0, $end);
                    $current_day = array_search($day_string, $this->langs[$lang]['weekdays']);
                    if ($current_day === false)
                        throw new ParseException("Weekday not recognized");
                    if ($current_day < $last_current_day)
                        break;
                    $last_current_day = $current_day;
                    $this->startDay($current_day);
                }
                else if ($child->tagName == 'ul') {
                    foreach (pq($child)->children('li') as $li) {
                        $line = pq($li)->text();

                        $section = $this->getSectionName($line, $lang);
                        $line = $this->formatAttributes($line);

                        $meal = new Meal();
                        $meal->language = $lang;
                        $meal->name = $line;
                        $meal->section = $section;
                        $this->addMeal($meal);
                    }
                    $this->endDayAndSave();
                }
            }
        }
    }

    private function getSectionName(&$line_html, $lang)
    {
        foreach ($this->langs[$lang]['sections'] as $name_lang => $name_en) {
            if (preg_match("/^[\\s]*" . $name_lang . "[\\s]*/i", $line_html, $matches)) {
                $line_html = trim(mb_substr($line_html, strlen($matches[0])));
                return $name_en;
            }
        }

        return false;
    }

    private function formatAttributes($line)
    {
        preg_match_all("/[\s]+(((g|l|vl|m) ?)+)$/", $line, $matches);

        $subMatches = $matchStarts = array();
        $lastMatchStart = -1;
        foreach ($matches[0] as $subMatch) {
            preg_match_all("/g|l|vl|m/", $subMatch, $subMatchArray);
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

        return trim($line);
    }
}