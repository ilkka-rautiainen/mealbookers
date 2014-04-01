<?php

class TaffaImport extends Import
{
	protected $restaurant_id = 7;
	protected $url = "https://www.teknologforeningen.fi/menu.html?lang=fi";

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


    protected function saveOpeningHours()
    {
	    DB::inst()->query("DELETE FROM restaurant_opening_hours WHERE restaurant_id = {$this->restaurant_id}");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 0, 3, '10:30', '16:00', 'normal'
	        )");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 0, 3, '10:30', '16:00', 'lunch'
	        )");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 4, 4, '10:30', '15:00', 'normal'
	        )");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 4, 4, '10:30', '15:00', 'lunch'
	        )");
	    DB::inst()->query("INSERT INTO restaurant_opening_hours (
	            restaurant_id, start_weekday, end_weekday, start_time, end_time, type
	        ) VALUES (
	            {$this->restaurant_id}, 5, 6, '00:00:00', '00:00:00', 'normal'
	        )");
	    Logger::debug(__METHOD__ . " opening hours saved successfully");
    }
    public function run()
    {
    	require_once __DIR__ . '/../lib/phpQuery.php';
    	$this->saveOpeningHours();
    	$source = $this->fetchURL($this->url);
    	phpQuery::newDocument($source);
    	$p_list = pq('#page > div > p');
    	$patterns = array ('/<p>/', '/<\/p>/');
    	$p_list = trim(preg_replace($patterns, '', $p_list));
    	$list = preg_split('/[\s]+/', $p_list);
    	$count = array_search($list[0], $this->langs['fi']['weekdays']);
    	$source = $this->fetchURL($this->url);
    	phpQuery::newDocument($source);
    	$ul_list = pq('#page > div > ul');
    	$patterns = array ('/<ul>/', '/<\/ul>/', '/<li>/', '/<\/li>/');
    	foreach ($ul_list as $ul) {
    		$li_list = pq($ul);
    		$li_list = trim(preg_replace($patterns, '', $li_list));
    		$list = preg_split('/[\n]+/', $li_list);
    		foreach($list as $line) {
    			$this->endDayAndSave();
		    	$this->startDay($count);
				$meal = new Meal();
			    $meal->language = 'fi';
			    $meal->name = $line;
			    $this->addMeal($meal);
			    $this->endSection();
    		}
    		$count += 1;
    		if ($count == 5) {
    			break;
    		}
    	}
    }
}