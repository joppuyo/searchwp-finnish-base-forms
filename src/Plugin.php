<?php

namespace NPX\FinnishBaseForms;

use Puc_v4_Factory;

class Plugin
{

    // This is used in the admin UI
    public $plugin_name = 'SearchWP';

    // This is used for option keys etc.
    public $plugin_slug = 'searchwp';

    public $api_type;

    public $__FILE__;

    private static $instance;

    public static function get_instance()
    {
        if (self::$instance == null)
        {
            self::$instance = new Plugin();
        }

        return self::$instance;
    }

    /**
     * @param mixed $message
     */
    public static function debug($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG === true) {
            error_log(print_r($message, true));
        }
    }

    public function __construct()
    {
        $this->api_type = get_option("{$this->plugin_slug}_finnish_base_forms_api_type") ? get_option("{$this->plugin_slug}_finnish_base_forms_api_type") : 'binary';

        add_action('init', [$this, 'init']);

        // Ajax endpoint to test that lemmatization works
        add_action("wp_ajax_{$this->plugin_slug}_finnish_base_forms_test", function () {
            wp_die();
        });

        add_action('admin_enqueue_scripts', function ($hook) {
            if ($hook !== "settings_page_{$this->plugin_slug}_finnish_base_forms") {
                return;
            }
            wp_enqueue_script("{$this->plugin_slug}-finnish-base-forms-js", plugin_dir_url(__FILE__) . '/js/script.js');
        });

        add_action('searchwp_index_post', function ($post) {
            // Polylang compat
            if (function_exists('pll_get_post_language')) {
                $language = pll_get_post_language($post->ID);
                update_option("{$this->plugin_slug}_finnish_base_forms_indexed_post_is_finnish", $language === 'fi');
            }
        });

        // If plugin is installed, pass all content through lemmatization process
        if (get_option("{$this->plugin_slug}_finnish_base_forms_api_url") || in_array(get_option("{$this->plugin_slug}_finnish_base_forms_api_type"), ['binary', 'command_line', 'ffi'])) {
            if ($this->plugin_slug === 'searchwp') {
                add_filter('searchwp_indexer_pre_process_content', [$this, 'indexer_pre_process_content']);
            } else if ($this->plugin_slug === 'relevanssi') {
                add_filter('relevanssi_post_content_before_tokenize', function ($content, $post) {
                    // Polylang compat
                    if (function_exists('pll_get_post_language') && pll_get_post_language($post->ID) !== 'fi') {
                        return $content;
                    }
                    return $this->lemmatize($content);
                }, 10, 2);
                add_filter('relevanssi_post_title_before_tokenize', function ($content, $post) {
                    // Polylang compat
                    if (function_exists('pll_get_post_language') && pll_get_post_language($post->ID) !== 'fi') {
                        return $content;
                    }
                    return $this->lemmatize($content);
                }, 10, 2);
                add_filter('relevanssi_custom_field_value', function ($content, $post) {
                    // Polylang compat
                    if (function_exists('pll_get_post_language') && pll_get_post_language($post->ID) !== 'fi') {
                        return [$content];
                    }
                    return [$this->lemmatize($content[0])];
                }, 10, 2);
            }
        }

        // If "lemmatize search query" option is set, pass user query through lemmatization
        if ((get_option("{$this->plugin_slug}_finnish_base_forms_api_url") || in_array(get_option("{$this->plugin_slug}_finnish_base_forms_api_type"), ['binary', 'command_line', 'ffi'])) && get_option("{$this->plugin_slug}_finnish_base_forms_lemmatize_search_query")) {
            if ($this->plugin_slug === 'searchwp') {
                add_filter('searchwp_pre_search_terms', [$this, 'searchwp_lemmatize_search_query'], 10, 2);
            } else if ($this->plugin_slug === 'relevanssi') {
                add_filter('relevanssi_search_filters', function ($parameters) {

                    // Polylang compat
                    if (function_exists('pll_current_language') && pll_current_language() !== 'fi') {
                        return $parameters;
                    }

                    $parameters['q'] = $this->lemmatize($parameters['q']);

                    return $parameters;
                });
            }
        }

        // Add plugin to WordPress admin menu
        add_action('admin_menu', [$this, 'admin_menu']);
    }

    public function init()
    {

        $update_checker = Puc_v4_Factory::buildUpdateChecker(
            'https://github.com/joppuyo/searchwp-finnish-base-forms',
            $this->__FILE__,
            "{$this->plugin_slug}-finnish-base-forms"
        );

        $update_checker->getVcsApi()->enableReleaseAssets();

        // Add settings link on the plugin page
        add_filter('plugin_action_links_' . plugin_basename($this->__FILE__), [Admin::get_instance(), 'plugin_action_links']);
    }

    public function admin_menu()
    {
        add_submenu_page(
            null,
            __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms"),
            __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms"),
            'manage_options',
            "{$this->plugin_slug}_finnish_base_forms",
            [Admin::get_instance(), 'settings_page']
        );
    }

    public function indexer_pre_process_content($content) {
        // Polylang compat
        if (function_exists('pll_get_post_language')) {
            return get_option("{$this->plugin_slug}_finnish_base_forms_indexed_post_is_finnish") ? $this->lemmatize($content) : $content;
        }
        return $this->lemmatize($content);
    }

    /**
     * Append lemmatized words to the original text
     * @param $content
     * @return string
     * @throws Exception
     */
    public function lemmatize($content)
    {
        $tokenized = $this->tokenize($content);

        $lemmatizer = LemmatizerHelper::get_instance();

        $out_array = [];

        foreach ($tokenized as $token) {
            $output = $lemmatizer->lemmatize($token);

            foreach ($output as $output_item) {
                array_push($out_array, $output_item['baseform']);
                if (get_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words") && count($output_item['wordbases'])) {
                    $out_array = array_merge($out_array, $output_item['wordbases']);
                }
            }

        }

        $content = trim($content . ' ' . implode(' ', $out_array));

        return $content;
    }

    /**
     * Simple white space tokenizer. Breaks either on whitespace or on word
     * boundaries (ex.: dots, commas, etc) Does not include white space or
     * punctuations in tokens.
     *
     * Based on NlpTools (http://php-nlp-tools.com/) under WTFPL license.
     *
     * @param $str
     * @return mixed
     */
    function tokenize($str)
    {
        $str = html_entity_decode($str);
        $str = strip_tags($str);
        $arr = [];
        // for the character classes
        // see http://php.net/manual/en/regexp.reference.unicode.php
        $pat
            = '/
                ([\pZ\pC]*)       # match any separator or other
                                  # in sequence
                (
                    [^\pP\pZ\pC]+ # match a sequence of characters
                                  # that are not punctuation,
                                  # separator or other
                )
                ([\pZ\pC]*)       # match a sequence of separators
                                  # that follows
            /xu';
        preg_match_all($pat, $str, $arr);

        return $arr[2];
    }

    public function searchwp_lemmatize_search_query($terms, $engine) {

        // Polylang compat
        if (function_exists('pll_current_language') && pll_current_language() !== 'fi') {
            return $terms;
        }

        $lemmatizer = LemmatizerHelper::get_instance();

        $has_multiple_meanings = false;

        $output_terms = [];

        foreach ($terms as $term) {
            $lemmatized_data = $lemmatizer->lemmatize($term);

            foreach ($lemmatized_data as $lemma) {
                array_push($output_terms, $lemma['baseform']);
            }

            if (!count($lemmatized_data)) {
                array_push($output_terms, $term);
            }

            if (count($lemmatized_data) > 1) {
                $has_multiple_meanings = true;
            }
        }

        if ($has_multiple_meanings) {
            add_filter('searchwp_and_logic', '__return_false');
        }

        $output_terms = array_unique($output_terms);

        return $output_terms;
    }
}