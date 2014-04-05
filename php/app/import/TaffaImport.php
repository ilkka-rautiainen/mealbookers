<?php

class TaffaImport extends Import
{
	protected $restaurant_id = 7;
	protected $url = "https://www.teknologforeningen.fi/fi/menu.html";
	protected $lang = 'fi';
	protected $patterns = array ('/<p>/', '/<\/p>/', '/<ul>/', '/<\/ul>/', '/<li>/', '/<\/li>/', '/<br>/');

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
            'ignore' => array(
                'No menu available',
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
            'ignore' => array(
                'No menu available',
            ),
        ),
    );

	/**
     * Import and Save opening hours
     */
    protected function saveOpeningHours()
    {
    	$source = $this->fetchURL($this->url);
    	phpQuery::newDocument($source);
    	$p_list = pq('#page > p');
    	$p_list = trim(preg_replace($this->patterns, ' ', $p_list));
    	$list = preg_split('/[\s]+/', $p_list);

    	$list2 = array('Måndag', '-', 'Torsdag:', '10:30', '-', '16:00', 'Fredag:', '10:30', '-', '15:00');
    	$count = 0;
    	while ($count < sizeof($list2)) {
    		if ($list[$count + 2] != $list2[$count]) {
    			Logger::debug(__METHOD__ . " lines didn't match regex");
    			return;
    		}
    		$count += 1;
    	}
    	Logger::debug(__METHOD__ . " lines matched");
	    DB::inst()->query("DELETE FROM restaurant_opening_hours WHERE restaurant_id = {$this->restaurant_id}");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 0, 3, '$list[5]', '$list[7]', 'normal'
	        )");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 0, 3, '$list[5]', '$list[7]', 'lunch'
	        )");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 4, 4, '$list[9]', '$list[11]', 'normal'
	        )");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 4, 4, '$list[9]', '$list[11]', 'lunch'
	        )");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 5, 6, '00:00:00', '00:00:00', 'normal'
	        )");
	    Logger::debug(__METHOD__ . " opening hours saved successfully");
    }

    private function getSectionName(&$line_html, $lang)
    {
        foreach ($this->langs[$lang]['sections'] as $name_lang => $name_en) {
            if (preg_match("/^(" . preg_quote("<strong>", "/") . ")?[\\s]*" . $name_lang . "[\\s]*(" . preg_quote("</strong>", "/") . ")?/i", $line_html, $matches)) {
                $line_html = trim(mb_substr($line_html, strlen($matches[0])));
                return $name_en;
            }
        }

        return false;
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

        if ($save_opening_hours)
    		$this->saveOpeningHours();

		$source = $this->fetchURL($this->url);
    	phpQuery::newDocument($source);
    	$p_list = pq('#page > div > p');
    	$p_list = trim(preg_replace($this->patterns, '', $p_list));
    	$list = preg_split('/[\s]+/', $p_list);
    	$count = array_search($list[0], $this->langs[$this->lang]['weekdays']);

    	$source = $this->fetchURL($this->url);
    	phpQuery::newDocument($source);
    	$ul_list = pq('#page > div > ul');
    	foreach ($ul_list as $ul) {
    		$li_list = pq($ul);
    		$li_list = trim(preg_replace($this->patterns, '', $li_list));
    		$list = preg_split('/[\n]+/', $li_list);
    		$this->startDay($count);
    		foreach($list as $line) {
    			if (($section_name = $this->getSectionName($line, $this->lang)) !== false) {
            		$this->startSection($section_name);
            		Logger::debug(__METHOD__ . " section found $section_name");
            	}
            	$line = $this->formatAttributes($line);
				$meal = new Meal();
			    $meal->language = $this->lang;
			    $meal->name = $line;
			    $this->addMeal($meal);
			    $this->endSection();
    		}
    		$this->endDayAndSave();
    		$count += 1;
    		if ($count == 5 && $this->lang == 'fi') {
    			$this->lang = 'en';
    			$this->url = "https://www.teknologforeningen.fi/en/menu.html";
    			$this->run();
    		}
    		if ($count == 5){
    			break;
    		}
    	}
	}

    private function formatAttributes($line)
    {
        preg_match_all("/[\s]+(((g|l|vl) ?)+)$/", $line, $matches);

        $subMatches = $matchStarts = array();
        $lastMatchStart = -1;
        foreach ($matches[0] as $subMatch) {
            preg_match_all("/g|l|vl/", $subMatch, $subMatchArray);
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

        return $line;
    }
}