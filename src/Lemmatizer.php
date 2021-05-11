<?php

namespace NPX\FinnishBaseForms;

abstract class Lemmatizer {

    private $cache_array;
    public $__FILE__;

    abstract protected function lemmatize(string $word);

    public function __construct()
    {

        if (!$this->check_table_exists()) {
            $this->create_db_table();
        }


        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}swpfbf_cache", ARRAY_A);

        $cache_array = [];

        foreach ($results as $result) {

            $new = [
                'baseform' => $result['baseform'],
                'wordbases' => explode(' ',$result['wordbases']),
            ];

            if (isset($cache_array[$result['term']]) || array_key_exists($result['term'], $cache_array)) {
                array_push($cache_array[$result['term']], $new);
            } else {
                $cache_array[$result['term']] = [$new];
            }
        }

        $this->cache_array = $cache_array;
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

        if (isset($this->cache_array[$word]) || array_key_exists($word, $this->cache_array)) {
            //dump('cache hit');
            return $this->cache_array[$word];
        }

        $lemmatized_data = $this->lemmatize($word);

        global $wpdb;

        if (!empty($lemmatized_data)) {
            foreach ($lemmatized_data as $sub_item) {
                $baseform = $sub_item['baseform'];
                $wordbases = '';
                if (!empty($sub_item['wordbases'])) {
                    $wordbases = implode(' ', $sub_item['wordbases']);
                }
                $wpdb->query("REPLACE INTO {$wpdb->prefix}swpfbf_cache (term, baseform, wordbases) VALUES('$word', '$baseform', '$wordbases')");
            }

        } else {
            $wpdb->query("REPLACE INTO {$wpdb->prefix}swpfbf_cache (term, baseform, wordbases) VALUES('$word', '', '')");
        }

        $this->cache_array[$word] = $lemmatized_data;

        //dump('cache miss');

        return $lemmatized_data;

    }

    function create_db_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'swpfbf_cache';
        $wpdb_collate = $wpdb->collate;
        $sql =
            "CREATE TABLE {$table_name} (
  `term` varchar(255) NOT NULL DEFAULT '',
  `baseform` varchar(255) NOT NULL DEFAULT '',
  `wordbases` varchar(255) DEFAULT NULL,
  PRIMARY KEY  (`term`,`baseform`),
  UNIQUE KEY `term` (`term`,`baseform`)
  ) COLLATE {$wpdb_collate}";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    function check_table_exists()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'swpfbf_cache';

        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            return true;
        }
        return false;
    }
}