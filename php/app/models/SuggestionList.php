<?php

class SuggestionList {
    
    private $suggestions;

    public function __construct()
    {
        $this->suggestions = array();
    }

    public function addSuggestion($day, Suggestion $suggestion)
    {
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
}