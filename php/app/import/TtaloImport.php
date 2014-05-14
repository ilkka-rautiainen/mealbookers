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
        $children = pq("div.content:eq(15)>div:eq(2)>div>span.times");
        /* Lunch */
        #Logger::debug(__METHOD__ . $children->text());
        Logger::debug(__METHOD__ . " " . sizeof($children[0]));
        foreach ($children as $child) {
            Logger::debug(__METHOD__ . " " . pq($child)->text());
        }
        /* Opening */
        $children = pq("div.content:eq(15)>div:eq(0)>div>span.times");
        #Logger::debug(__METHOD__ . $children->text());
        foreach ($children as $child) {
            Logger::debug(__METHOD__ . " " . pq($child)->text());
        }

        if (!preg_match("/Ma[\s]*\-[\s]*To\:[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])"
            . "Pe\:[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])[\s]*\-[\s]*(([0-9]|0[0-9]|1[0-9]|2[0-3])[:.][0-5][0-9])/", $children, $matches)) {
            Logger::debug(__METHOD__ . " lines didn't match regex");

        }
        else {
            Logger::debug(__METHOD__ . " lines matched");
            Logger::debug(__METHOD__ . $matches[1]);
        }

        #Logger::debug(__METHOD__ . );
        #content clearfix
    }

    private function decreaseWithHalfHour($time)
    {
        return date("H:i", strtotime("1.2.2010 $time") - 1800);
    }
    




}