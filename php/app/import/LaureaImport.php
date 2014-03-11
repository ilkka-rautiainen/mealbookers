<?php

class LaureaImport extends AmicaImport
{
    protected $restaurantId = 6;
    protected $url = "http://www.amica.fi/laureaotaniemi";

    public function __construct()
    {
        // Only finnish
        $langs = array('fi' => $this->langs['fi']);
        $this->langs = $langs;
    }

    /**
     * Retrieves meal lines from source code
     */
    protected function getLines(&$source)
    {
        phpQuery::newDocument($source);

        $p_list = pq('#ctl00_RegionPageBody_RegionPage_MenuLabel > p');
        if (!$p_list->length)
            throw new ParseException("No <p> elements found in the menu");

        $p_lines = array();
        foreach ($p_list as $p) {
            $html = pq($p)->html();
            $p_lines[] = trim($html);
        }

        $lines = array();
        foreach ($p_lines as $p_line) {
            $lines_row = explode("<br>", $p_line);
            $lines = array_merge($lines, $lines_row);
        }
        return $lines;
    }

    /**
     * Formats the line breaks in a row
     */
    protected function formatLineBreaks($line_html)
    {
        return str_replace(array(","), array('<span class="line-break"></span>'), $line_html);
    }
}