<?php

class LaureaJSONImport extends AmicaJSONImport implements iImport
{
    protected $restaurant_id = 6;
    protected $costNumber = 3292;
    protected $url = "http://www.amica.fi/laureaotaniemi";

    protected function saveOpeningHours()
    {
        Logger::debug(__METHOD__ . " called");
        $p_list = pq("#ctl00_RegionPageBody_RegionPage_RegionContent_RegionMainContent_RegionMainContentMiddle_RegionMainContentInnerMiddle_RegionMainContentText_MainContentBottomTextArea_MainContentToolBox_OpeningHours p");

        $lines = array();
        if (!$p_list instanceof Traversable) {
            Logger::warn(__METHOD__ . " {$this->restaurant_id} opening hours no p list found");
            return;
        }
        foreach ($p_list as $p) {
            $html = trim(pq($p)->html());
            $lines = array_merge($lines, explode("<br>", $html));
        }

        $processed_lines = array();
        foreach ($lines as $line) {
            $line = trim(str_replace(array(
                    "\xc2\xa0",
                ),
                array(
                    ' ',
                ),
                $line
            ));
            if (!$line)
                continue;

            $processed_lines[] = $line;
            Logger::debug(__METHOD__ . " got line: $line");
        }

        if (count($processed_lines) != 5) {
            Logger::warn(__METHOD__ . " {$this->restaurant_id} opening days got "
                . count($processed_lines) . " lines instead of 5");
            return;
        }

        $imploded = implode("|", $processed_lines);

        if (!preg_match("/ma[\s]*\\-[\s]*to[\s]+kl[\S]*\\.?[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])\\|"
            . "pe[\s]+kl[\S]*\\.?[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])\\|"
            . "lounas\\|"
            . "ma[\s]*\\-[\s]*to[\s]+kl[\S]*\\.?[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])\\|"
            . "pe[\s]+kl[\S]*\\.?[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])/i",
            $imploded,
            $matches))
        {
            Logger::debug(__METHOD__ . " lines didn't match regex");
        }
        else {
            Logger::debug(__METHOD__ . " lines matched");
            $mon_thu_start = str_replace(".", ":", $matches[1]);
            $mon_thu_end = str_replace(".", ":", $matches[3]);
            $fri_start = str_replace(".", ":", $matches[5]);
            $fri_end = str_replace(".", ":", $matches[7]);
            $mon_thu_lunch_start = str_replace(".", ":", $matches[9]);
            $mon_thu_lunch_end = str_replace(".", ":", $matches[11]);
            $fri_lunch_start = str_replace(".", ":", $matches[13]);
            $fri_lunch_end = str_replace(".", ":", $matches[15]);

            DB::inst()->query("DELETE FROM restaurant_opening_hours WHERE restaurant_id = {$this->restaurant_id}");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 3, '$mon_thu_start:00', '$mon_thu_end:00', 'normal'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 3, '$mon_thu_lunch_start:00', '$mon_thu_lunch_end:00', 'lunch'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 4, 4, '$fri_start:00', '$fri_end:00', 'normal'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 4, 4, '$fri_lunch_start:00', '$fri_lunch_end:00', 'lunch'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 5, 6, '00:00:00', '00:00:00', 'normal'
                )");
            Logger::debug(__METHOD__ . " opening hours saved successfully");
        }
    }
}