<?php
/*
Plugin Name: SearchWP Finnish Base Forms
Plugin URI: https://github.com/joppuyo/searchwp-finnish-base-forms
Description: SearchWP plugin to add Finnish base forms in search index
Version: 2.3.2
Author: Johannes Siipola
Author URI: https://siipo.la
Text Domain: searchwp-finnish-base-forms
*/

if (!defined('ABSPATH')) {
    exit;
}

// Check if we are using local Composer
if (file_exists(__DIR__ . '/vendor')) {
    require 'vendor/autoload.php';
}

class FinnishBaseForms {

    // This is used in the admin UI
    private $plugin_name = 'SearchWP';

    // This is used for option keys etc.
    private $plugin_slug = 'searchwp';

    public function __construct()
    {
        $update_checker = Puc_v4_Factory::buildUpdateChecker(
            'https://github.com/joppuyo/searchwp-finnish-base-forms',
            __FILE__,
            "{$this->plugin_slug}-finnish-base-forms"
        );

        $update_checker->getVcsApi()->enableReleaseAssets();

        // Add settings link on the plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
            $settings_link = "<a href=\"options-general.php?page={$this->plugin_slug}_finnish_base_forms\">" . __('Settings', "{$this->plugin_slug}_finnish_base_forms") . '</a>';
            array_push($links, $settings_link);
            return $links;
        });

        // Ajax endpoint to test that lemmatization works
        add_action("wp_ajax_{$this->plugin_slug}_finnish_base_forms_test", function () {
            $api_type = $_POST['api_type'];
            if ($api_type === 'binary' || $api_type === 'command_line') {
                $baseforms = $this->voikkospell(['käden'], $api_type);
            } else {
                $baseforms = $this->web_api(['käden'], $_POST['api_root']);
            }
            if (count($baseforms) && $baseforms === ['käsi']) {
                wp_die();
            } else {
                wp_die('', '', ['response' => 500]);
            }
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
        if (get_option("{$this->plugin_slug}_finnish_base_forms_api_url") || in_array(get_option("{$this->plugin_slug}_finnish_base_forms_api_type"), ['binary', 'command_line'])) {
            if ($this->plugin_slug === 'searchwp') {
                add_filter('searchwp_indexer_pre_process_content', function ($content) {
                    return get_option("{$this->plugin_slug}_finnish_base_forms_indexed_post_is_finnish") ? $this->lemmatize($content) : $content;
                });
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
        if ((get_option("{$this->plugin_slug}_finnish_base_forms_api_url") || in_array(get_option("{$this->plugin_slug}_finnish_base_forms_api_type"), ['binary', 'command_line'])) && get_option("{$this->plugin_slug}_finnish_base_forms_lemmatize_search_query")) {
            if ($this->plugin_slug === 'searchwp') {
                add_filter('searchwp_pre_search_terms', function ($terms, $engine) {

                    // Polylang compat
                    if (function_exists('pll_current_language') && pll_current_language() !== 'fi') {
                        return $terms;
                    }

                    $terms = implode(' ', $terms);
                    $terms = $this->lemmatize($terms);
                    $terms = explode(' ', $terms);
                    $terms = array_unique($terms);

                    return $terms;
                }, 10, 2);

                // Double amount of maximum search terms just to be sure (the default is 6)
                add_filter('searchwp_max_search_terms', function ($max_terms, $engine) {
                    // Polylang compat
                    if (function_exists('pll_current_language') && pll_current_language() !== 'fi') {
                        return $max_terms;
                    }
                    return get_option('searchwp_finnish_base_forms_split_compound_words') ? 24 : 12;
                }, 10, 2);

                // By default SearchWP will try AND logic first and after that OR logic if there are no results.
                // Because we have the same search term multiple times, we want to always use OR logic
                add_filter('searchwp_and_logic', '__return_false');
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
        add_action('admin_menu', function () {
            add_submenu_page(
                null,
                __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms"),
                __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms"),
                'manage_options',
                "{$this->plugin_slug}_finnish_base_forms",
                [$this, 'settings_page']
            );
        });
    }

    /**
     * Append lemmatized words to the original text
     * @param $content
     * @return string
     * @throws Exception
     */
    private function lemmatize($content)
    {
        $tokenized = $this->tokenize(strip_tags(html_entity_decode($content)));

        $api_type = get_option("{$this->plugin_slug}_finnish_base_forms_api_type") ? get_option("{$this->plugin_slug}_finnish_base_forms_api_type") : 'binary';

        if ($api_type === 'binary' || $api_type === 'command_line') {
            $extra_words = $this->voikkospell($tokenized, $api_type);
        } else {
            $api_root = get_option("{$this->plugin_slug}_finnish_base_forms_api_url");
            $extra_words = $this->web_api($tokenized, $api_root);
        }

        $content = trim($content . ' ' . implode(' ', $extra_words));

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

    /**
     * Render WordPress plugin settings page
     */
    public function settings_page()
    {
        $updated = false;
        if (!empty($_POST)) {
            check_admin_referer("{$this->plugin_slug}_finnish_base_forms");
            update_option("{$this->plugin_slug}_finnish_base_forms_api_url", $_POST['api_url']);

            update_option("{$this->plugin_slug}_finnish_base_forms_lemmatize_search_query", !empty($_POST['lemmatize_search_query']) && $_POST['lemmatize_search_query'] === 'checked' ? 1 : 0);
            update_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words", !empty($_POST['split_compound_words']) && $_POST['split_compound_words']  === 'checked' ? 1 : 0);
            update_option("{$this->plugin_slug}_finnish_base_forms_api_type", in_array($_POST['api_type'], ['binary', 'command_line', 'web_api']) ? $_POST['api_type'] : 'command_line');
            $updated = true;
        }

        $api_url = get_option("{$this->plugin_slug}_finnish_base_forms_api_url");
        $api_type = get_option("{$this->plugin_slug}_finnish_base_forms_api_type") ? get_option("{$this->plugin_slug}_finnish_base_forms_api_type") : 'binary';

        echo '<div class="wrap">';
        echo '    <h1>' . __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms") . '</h1>';
        echo '    <div class="js-finnish-base-forms-admin-notices"></div>';
        if ($updated) {
            echo '    <div class="notice notice-success">';
            echo '        <p>' . __('Options have been updated', "{$this->plugin_slug}_finnish_base_forms") . '</p>';
            echo '    </div>';
        }
        echo '    <form method="post" class="js-finnish-base-forms-form" data-slug="' . $this->plugin_slug . '">';
        echo '    <table class="form-table">';
        echo '        <tbody>';
        echo '            <tr>';
        echo '                <th scope="row">';
        echo '                    <label for="api_url">' . __('API type', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <p><input type="radio" id="binary" name="api_type" value="binary" ' . checked($api_type, 'binary', false) . '><label for="binary">Voikko binary (bundled)</label></p>';
        echo '                <p><input type="radio" id="web_api" name="api_type" value="web_api" ' . checked($api_type, 'web_api', false) . '><label for="web_api">Web API</label></p>';
        echo '                <p><input type="radio" id="command_line" name="api_type" value="command_line" ' . checked($api_type, 'command_line', false) . '><label for="command_line">Voikko command line</label></p>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr class="js-finnish-base-forms-api-url">';
        echo '                <th scope="row">';
        echo '                    <label for="api_url">' . __('Web API URL', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <input name="api_url" type="url" id="api_url" value="' . esc_url($api_url) . '" class="regular-text">';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr>';
        echo '                <th colspan="2">';
        echo '                <span style="font-weight: 400">Note: "Voikko command line" option requires voikkospell command line application installed on the server.</span>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr class="js-finnish-base-forms-split-compound-words">';
        echo '                <th scope="row">';
        echo '                    <label>' . __('Split compound words', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <input type="checkbox" name="split_compound_words" id="split_compound_words" value="checked" ' . checked(get_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words"), '1', false) . ' />';
        echo '                <label for="split_compound_words">Enabled</label>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr>';
        echo '                <th scope="row">';
        echo '                    <label>' . __('Add base forms to search query', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <input type="checkbox" name="lemmatize_search_query" id="lemmatize_search_query" value="checked" ' . checked(get_option("{$this->plugin_slug}_finnish_base_forms_lemmatize_search_query"), '1', false) . ' />';
        echo '                <label for="lemmatize_search_query">Enabled</label>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr>';
        echo '                <th colspan="2">';
        echo '                <span style="font-weight: 400">Note: if you enable "Add base forms to search query", Voikko will be called every time a search if performed, this might have performance implications.</span>';
        echo '                </td>';
        echo '            </tr>';
        echo '        </tbody>';
        echo '    </table>';
        echo '    <p class="submit">';
        echo '        <input class="button-primary js-finnish-base-forms-submit-button" type="submit" name="submit-button" value="Save">';
        echo '    </p>';
        wp_nonce_field("{$this->plugin_slug}_finnish_base_forms");
        echo '    </form>';
        echo '</div>';
    }

    /**
     * Split compound words into word bases
     * @param $wordbases
     * @return array
     */
    function parse_wordbases($wordbases)
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

    /**
     * @param $words
     * @param $api_type 'binary' or 'command_line'
     * @return array
     * @throws Exception
     */
    function voikkospell($words, $api_type)
    {
        $binaryPath = null;
        if ($api_type === 'binary') {
            $path = plugin_dir_path(__FILE__);
            $this->ensure_permissions("{$path}bin/voikkospell");
            $binaryPath = "{$path}bin/voikkospell -p {$path}bin/dictionary";
        } else {
            $binaryPath = 'voikkospell';
        }

        $process = new \Symfony\Component\Process\Process('locale -a | grep -i "utf-\?8"');
        $process->run();
        $locale = strtok($process->getOutput(), "\n");

        $process = new \Symfony\Component\Process\Process("$binaryPath -M", null, [
            'LANG' => $locale,
            'LC_ALL' => $locale,
        ]);
        $process->setInput(implode($words, "\n"));
        $process->run();

        if ($process->getErrorOutput()) {
            throw new Exception($process->getErrorOutput());
        }

        preg_match_all('/BASEFORM=(.+)$/m', $process->getOutput(), $matches);
        $baseforms = $matches[1];

        $wordbases = [];

        if (get_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words")) {
            preg_match_all('/WORDBASES=(.+)$/m', $process->getOutput(), $matches);
            $wordbases = $this->parse_wordbases($matches[1]);
        }

        return array_unique(array_merge($baseforms, $wordbases));
    }

    function web_api($tokenized, $api_root)
    {
        $client = new \GuzzleHttp\Client();

        $extra_words = [];

        $split_compound_words = get_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words");

        $requests = function () use ($client, $tokenized, $api_root) {
            foreach ($tokenized as $token) {
                yield function () use ($client, $token, $api_root) {
                    return $client->getAsync(trailingslashit($api_root) . 'analyze/' . $token);
                };
            }
        };

        $pool = new \GuzzleHttp\Pool($client, $requests(), [
            'concurrency' => 10,
            'fulfilled' => function ($response) use (&$extra_words, $split_compound_words) {
                $response = json_decode($response->getBody()->getContents(), true);
                if (count($response)) {
                    $baseforms = array_column($response, 'BASEFORM');
                    $wordbases = [];
                    if ($split_compound_words) {
                        $wordbases = $this->parse_wordbases(array_column($response, 'WORDBASES'));
                    }
                    $extra_words = array_unique(array_merge($extra_words, $baseforms, $wordbases));
                }
            },
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $extra_words;
    }

    /**
     * Make sure binary is executable
     * @param string $path
     */
    function ensure_permissions($path) {
        $permissions = substr(sprintf('%o', fileperms($path)), -4);
        if ($permissions !== '0755') {
            chmod($path, 0755);
        }
    }

    /**
     * @param mixed $message
     */
    function debug($message) {
        error_log(print_r($message, true));
    }

    /**
     * @param WP_Post $post
     * @param array $options
     */
    function get_excerpt($post, $options = [])
    {

        $defaults = [
            'length' => 300,
            'default_excerpt' => function ($post) {
                if (strlen($post->post_excerpt)) {
                    return $post->post_excerpt;
                }
                return $post->post_content;
            }
        ];

        $options = array_merge($defaults, $options);

        $query = explode(' ', $this->lemmatize(mb_strtolower(get_search_query())));
        $fields = get_post_meta($post->ID, '');
        //dump($fields);
        foreach ($fields as $name => $field) {
            $matches = $this->get_matches($field[0], $query);
            if (count($matches)) {
                // Sort matches by length, so that longest match is highlighted.
                usort($matches, function ($a, $b) {
                    return strlen($b) - strlen($a);
                });
                $field[0] = strip_tags($field[0]);
                $result = $this->do_it($field[0], $matches, $options['length']);
                $result = preg_replace("/" . implode('|', array_map('preg_quote', $matches)) . "/i", '<strong>$0</strong>', $result);
                return $result;
                break;
            }
        }
        return Stringy\Stringy::create($options['default_excerpt']($post))
            ->safeTruncate($options['length']);
    }

    /**
     * Tuomas Siipola <siiptuo@kapsi.fi>
     * @param string $haystack
     * @param array $needles
     * @return string
     */
    function find_spans($haystack, $needles)
    {
        return preg_replace_callback(
            '/(.*?)(' . implode('|', array_map('preg_quote', $needles)) . ')?/ui',
            function ($matches) {
                $ws = str_repeat(' ', mb_strlen($matches[1]));
                return isset($matches[2]) ? $ws . '[' . str_repeat(' ', mb_strlen($matches[2]) - 2) . ']' : $ws;
            },
            $haystack
        );
    }

    /**
     * Tuomas Siipola <siiptuo@kapsi.fi>
     * @param string $spans
     * @param int $window_size
     * @return int
     */
    function find_best_window($spans, $window_size)
    {
        $words = 0;
        for ($i = 0; $i < $window_size; $i++) {
            if ($spans[$i] === ']') {
                $words++;
            }
        }
        $max_start = 0;
        $max_end = 0;
        $max_words = $words;
        $in_max = false;
        for ($i = 0; $i < strlen($spans) - $window_size; $i++) {
            if ($spans[$i] === '[') {
                $words--;
            }
            if ($spans[$i + $window_size] === ']') {
                $words++;
            }
            if ($words > $max_words) {
                $max_start = $max_end = $i + 1;
                $max_words = $words;
                $in_max = true;
            } elseif ($in_max && $words === $max_words) {
                $max_end++;
            } else {
                $in_max = false;
            }
        }
        if ($max_start === 0) {
            return $max_start;
        } elseif ($in_max) {
            return $max_end;
        } else {
            return $max_start + intdiv($max_end - $max_start, 2);
        }
    }

    /**
     * @author Tuomas Siipola <siiptuo@kapsi.fi>
     * @param string $text
     * @param array $terms
     * @param int $window_size
     * @return string
     */
    function do_it($text, $terms, $window_size)
    {
        if (mb_strlen($text) <= $window_size) {
            return $text;
        }
        $spans = $this->find_spans($text, $terms);
        $window_start = $this->find_best_window($spans, $window_size);
        if ($window_start === 0) {
            return trim(mb_substr($text, $window_start, $window_size)) . '...';
        } elseif ($window_start === mb_strlen($text) - $window_size) {
            return '...' . trim(mb_substr($text, $window_start, $window_size));
        } else {
            return '...' . trim(mb_substr($text, $window_start, $window_size)) . '...';
        }
    }

    /**
     * @author Tuomas Siipola <siiptuo@kapsi.fi>
     * @param $value
     * @param array $query
     * @return array
     */
    function get_matches($value, $query)
    {

        $split_compound_words = get_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words");

        $tokenized = $this->tokenize(mb_strtolower($value));

        $matches = [];

        $api_type = get_option('searchwp_finnish_base_forms_api_type') ? get_option('searchwp_finnish_base_forms_api_type') : 'web_api';

        if ($api_type === 'command_line' || $api_type === 'binary') {

            foreach ($tokenized as $token) {
                if (in_array(mb_strtolower($token), $query)) {
                    array_push($matches, $token);
                }
            }

            $binaryPath = null;
            if ($api_type === 'binary') {
                $path = plugin_dir_path(__FILE__);
                $this->ensure_permissions("{$path}bin/voikkospell");
                $binaryPath = "{$path}bin/voikkospell -p {$path}bin/dictionary";
            } else {
                $binaryPath = 'voikkospell';
            }

            $process = new \Symfony\Component\Process\Process('locale -a | grep -i "utf-\?8"');
            $process->run();
            $locale = strtok($process->getOutput(), "\n");

            $process = new \Symfony\Component\Process\Process("$binaryPath -M", null, [
                'LANG' => $locale,
                'LC_ALL' => $locale,
            ]);
            $process->setInput(implode($tokenized, "\n"));
            $process->run();

            preg_match_all('/A\((.*)\).*BASEFORM=(.*)$/m', $process->getOutput(), $matches2);

            $words = [];

            for ($i = 0; $i < count($matches2[0]); $i++) {
                $words[$matches2[1][$i]][] = $matches2[2][$i];
            }

            if ($split_compound_words) {
                preg_match_all('/A\((.*)\).*WORDBASES=(.+)$/m', $process->getOutput(), $matches3);
                for ($i = 0; $i < count($matches3[0]); $i++) {
                    $wordbases = $this->parse_wordbases([$matches3[2][$i]]);
                    $words[$matches3[1][$i]] = array_merge($words[$matches3[1][$i]], $wordbases);
                }
            }

            foreach ($words as $original => $tokens) {
                if (array_intersect($tokens, $query)) {
                    array_push($matches, $original);
                }
            }

        }

        return $matches;

    }

}

$finnish_base_forms = new FinnishBaseForms();

function searchwp_finish_base_forms_get_excerpt($post, $options) {
    global $finnish_base_forms;
    return $finnish_base_forms->get_excerpt($post, $options);
}
