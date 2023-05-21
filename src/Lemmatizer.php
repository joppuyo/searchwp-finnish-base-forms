<?php

namespace NPX\FinnishBaseForms;

abstract class Lemmatizer {

    private $cache_array;
    public $__FILE__;

    abstract protected function lemmatize(string $word);

    public function __construct()
    {

    }

    public function parse_wordbases(array $wordbases)
    {
        $baseforms = [];
        foreach ($wordbases as $wordbase) {
            preg_match_all('/\(([^+].*?)\)/', $wordbase, $matches);
            foreach ($matches[1] as $match) {
                array_push($baseforms, str_replace('=', '', $match));
            }
        }
        return $baseforms;
    }

    function lemmatize_cached(string $word) {

        $word = preg_replace('/[^a-zA-Z0-9ŠšŽžÅåÄäÖö\-]/u', ' ', $word);
        $word = trim($word);

        $key = 'swpfbf_' . md5($word);

        $lemmatized_data = get_transient($key);

        if ($lemmatized_data === false) {
            $lemmatized_data = $this->lemmatize($word);
            set_transient($key, $lemmatized_data, YEAR_IN_SECONDS);
        }
        
        return $lemmatized_data;
    }
}