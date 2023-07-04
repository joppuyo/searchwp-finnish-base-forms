<?php

namespace NPX\FinnishBaseForms;

use NPX\FinnishBaseForms\Lemmatizer;
use NPX\FinnishBaseForms\Plugin;
use Siiptuo\Voikko\Voikko;

class FfiLemmatizer extends Lemmatizer
{

    private static $instance;

    private $voikko = null;

    public static function get_instance()
    {
        if (self::$instance == null)
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function lemmatize(string $word) {

        $plugin = Plugin::get_instance();

        if (empty($this->voikko)) {
            $path = plugin_dir_path($plugin->__FILE__);
            $this->voikko = new Voikko('fi', "$path/bin/dictionary", $this->get_library_path());
        }

        $output = [];

        $analyzed = $this->voikko->analyzeWord($word);
        foreach ($analyzed as $analysis) {

            $output_item = [];

            $output_item['baseform'] = $analysis->baseForm;

            $wordbases = $analysis->wordBases;

            if (!empty($wordbases)) {
                $output_item['wordbases'] = $this->parse_wordbases([$wordbases]);
            }
            array_push($output, $output_item);
        }

        return $output;
    }

    private function get_library_path() {

        $plugin = Plugin::get_instance();

        $path = plugin_dir_path($plugin->__FILE__);

        $library_path = "$path/lib/libvoikko.so.1.14.5";

        if (stripos(PHP_OS, 'darwin') === 0) {
            $library_path = "$path/lib/libvoikko.1.dylib";
        }

        return $library_path;

    }
}