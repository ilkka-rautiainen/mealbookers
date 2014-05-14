<?php

class TtaloImport extends SodexoImport implements iImport
{
    protected $restaurant_id = 9;
    protected $sodexo_id = 142;

    /**
     * Import and Save opening hours
     */
    protected function saveOpeningHours()
    {
        Logger::debug(__METHOD__ . "T talo Import time");
        $source = $this->fetchURL("http://www.sodexo.fi/tietotekniikantalo");
        phpQuery::newDocument($source);

        $rows = array();

        $part_numbers = array("0", "1", "2");
        foreach ($part_numbers as $part_number)
        // Cafe, Breakfast, Lunch parts
        foreach (pq(".block-sxo-opening-hours div.content > .part:eq($part_number) span") as $span) {
            $rows[] = pq($span)->text();
        }

        $opening_hours_string = implode("|", $rows);

        if (!preg_match('/[\s]*ma[\s]*\-[\s]*to[\s]*\|'
            . '[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\|'
            . '[\s]*pe[\s]*\|'
            . '[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\|'
            . '[\s]*la[\s]*\-[\s]*su[\s]*\|'
            . '[\s]*suljettu[\s]*\|'
            . '[\s]*ma[\s]*\-[\s]*pe[\s]*\|'
            . '[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\|'
            . '[\s]*la[\s]*\-[\s]*su[\s]*\|'
            . '[\s]*suljettu[\s]*\|'
            . '[\s]*ma[\s]*\-[\s]*pe[\s]*\|'
            . '[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\|'
            . '[\s]*la[\s]*\-[\s]*su[\s]*\|'
            . '[\s]*suljettu[\s]*/i',
            $opening_hours_string, $matches))
        {
            Logger::error(__METHOD__ . " lines didn't match regex");
        }
        else {
            Logger::debug(__METHOD__ . " lines matched");
            $mon_thu_cafe_start = str_replace(".", ":", $matches[1]);
            $mon_thu_cafe_end = str_replace(".", ":", $matches[3]);
            $fri_cafe_start = str_replace(".", ":", $matches[5]);
            $fri_cafe_end = str_replace(".", ":", $matches[7]);
            $mon_fri_breakfast_start = str_replace(".", ":", $matches[9]);
            $mon_fri_breakfast_end = str_replace(".", ":", $matches[11]);
            $mon_fri_lunch_start = str_replace(".", ":", $matches[13]);
            $mon_fri_lunch_end = str_replace(".", ":", $matches[15]);

            DB::inst()->query("DELETE FROM restaurant_opening_hours WHERE restaurant_id = {$this->restaurant_id}");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 3, '$mon_thu_cafe_start:00', '$mon_thu_cafe_end:00', 'normal'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 4, 4, '$fri_cafe_start:00', '$fri_cafe_end:00', 'normal'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 3, '$mon_thu_cafe_start:00', '$mon_thu_cafe_end:00', 'cafe'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 4, 4, '$fri_cafe_start:00', '$fri_cafe_end:00', 'cafe'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 4, '$mon_fri_lunch_start:00', '$mon_fri_lunch_end:00', 'lunch'
                )");
            DB::inst()->query("INSERT INTO restaurant_opening_hours (
                    restaurant_id, start_weekday, end_weekday, start_time, end_time, type
                ) VALUES (
                    {$this->restaurant_id}, 0, 4, '$mon_fri_breakfast_start:00', '$mon_fri_breakfast_end:00', 'breakfast'
                )");
            Logger::debug(__METHOD__ . " opening hours saved successfully");
        }
    }
}