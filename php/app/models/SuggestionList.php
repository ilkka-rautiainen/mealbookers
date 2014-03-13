<?php

class SuggestionList {
    
    private $suggestions;

    public function __construct()
    {
        $this->suggestions = array();
    }

    public function addSuggestion(Suggestion $suggestion)
    {
        $day = ((int)date("N", strtotime($suggestion->datetime))) - 1;
        if (!isset($this->suggestions[$day]))
            $this->suggestions[$day] = array();
        $this->suggestions[$day][] = $suggestion;
    }

    public function getAsArray()
    {
        $result = array();
        foreach ($this->suggestions as $day => $daySuggestions) {
            $result[$day] = array();
            foreach ($daySuggestions as $suggestion) {
                $result[$day][] = $suggestion->getAsArray();
            }
        }
        return $result;
    }

    public function length()
    {
        return count($this->suggestions);
    }
}