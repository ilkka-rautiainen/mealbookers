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
                'A la carte\:?' => 'alacarte',
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
                'A la carte\:?' => 'alacarte',
            ),
            'ignore' => array(
                'No menu available',
            ),
        ),
    );


    protected function saveOpeningHours()
    {
    	$source = $this->fetchURL($this->url);
    	phpQuery::newDocument($source);
    	$p_list = pq('#page > p');
    	$p_list = trim(preg_replace($this->patterns, ' ', $p_list));
    	$list = preg_split('/[\s]+/', $p_list);
    	Logger::debug(__METHOD__ . implode($list));
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
}