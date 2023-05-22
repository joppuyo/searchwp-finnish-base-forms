<?php

namespace NPX\FinnishBaseForms;

use SearchWP\Engine;
use SearchWP\Settings;

class Excerpt
{

    private static $instance;
    private $lemmatizer;

    public static function get_instance()
    {
        if (self::$instance == null)
        {
            self::$instance = new Excerpt();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $this->lemmatizer = '';
    }

    /**
     * @param WP_Post $post
     * @param array $options
     */
    function get_excerpt($post, $options)
    {
        $defaults = [
            'length' => 300,
            'fallback' => function ($post) {
                if (strlen($post->post_excerpt)) {
                    return strip_tags(html_entity_decode($post->post_excerpt));
                }
                return strip_tags(html_entity_decode($post->post_content));
            },
            'query' => get_search_query(),
        ];

        $options = array_merge($defaults, $options);

        $lemmatizer = LemmatizerHelper::get_instance();

        $parts = explode(' ', mb_strtolower($options['query']));

        $query = [];

        foreach ($parts as $part) {
            $lemmatized = $lemmatizer->lemmatize(mb_strtolower($part));

            if (count($lemmatized)) {
                foreach ($lemmatized as $lemma) {
                    array_push($query, $lemma['baseform']);
                }
            } else {
                array_push($query, $part);
            }
        }

        $query = array_values(
            array_filter(
                $query,
                function ($word) {
                    return mb_strlen($word) > 2;
                }
            )
        );

        global $searchwp;

        Plugin::debug('$searchwp');
        Plugin::debug($searchwp);

        $keys = ['wp_excerpt', 'wp_content'];

        // SearchWP 4
        if (empty($searchwp->diagnostics)) {

            if (empty($searchwp)) {
                return '';
            }

            $settings = Settings::get_engine_settings($searchwp);

            Plugin::debug($settings);

            $post_type = $post->post_type;

            if (empty($settings['sources']["post.$post_type"])) {
                return '';
            }

            $post_type_settings = $settings['sources']["post.$post_type"]['attributes'];

            $fields = get_post_meta($post->ID);

            if (!empty($post_type_settings['content'])) {
                $fields['wp_content'][0] = $post->post_content;
            }

            if (!empty($post_type_settings['excerpt'])) {
                $fields['wp_excerpt'][0] = $post->post_excerpt;
            }

            // TODO: maybe take weight into account?
            if (!empty($post_type_settings['meta'])) {
                foreach ($post_type_settings['meta'] as $key => $meta) {
                    array_push($keys, str_replace('%', '*', $key));
                }
            }

        }

        // SearchWP 3
        if (!empty($searchwp->diagnostics)) {
            // TODO: maybe find a better way to get this?
            $searchwp_engine = $searchwp->diagnostics[0]['engine'];

            $searchwp_settings = $searchwp->settings;
            $post_type = $post->post_type;

            $post_type_settings = $searchwp_settings['engines'][$searchwp_engine][$post_type];

            $fields = get_post_meta($post->ID);

            if (!empty($post_type_settings['weights']['content'])) {
                $fields['wp_content'][0] = $post->post_content;
            }

            if (!empty($post_type_settings['weights']['excerpt'])) {
                $fields['wp_excerpt'][0] = $post->post_excerpt;
            }

            // TODO: maybe take weight into account?
            if (!empty($post_type_settings['weights']['cf'])) {
                foreach ($post_type_settings['weights']['cf'] as $meta_key) {
                    array_push($keys, str_replace('%', '*', $meta_key['metakey']));
                }
            }
        }

        foreach ($fields as $name => $field) {
            // TODO: make this more functional
            $match = false;
            foreach ($keys as $key) {
                // searchwpcfdefault is special case for "any custom field" (SearchWP 3)
                // * is special case for "any custom field" (SearchWP 4)
                if ($key === $name || fnmatch($key, $name) || $key === 'searchwpcfdefault' || $key === '*') {
                    $match = true;
                }
            }

            if (!$match) {
                continue;
            }

            $matched_field = strip_tags(html_entity_decode($field[0]));

            $matches = $this->get_matches($matched_field, $query, $options['length']);

            if (count($matches)) {
                // Sort matches by length, so that longest match is highlighted.
                usort(
                    $matches,
                    function ($a, $b) {
                        return strlen($b) - strlen($a);
                    }
                );

                $result = $this->do_it($matched_field, $matches, $options['length']);
                $result = preg_replace(
                    "/" . implode('|', array_map('preg_quote', $matches)) . "/i",
                    '<strong>$0</strong>',
                    $result
                );
                return $result;
                break;
            }
        }
        return (string)\Stringy\Stringy::create($options['fallback']($post))
            ->safeTruncate($options['length'], '...');

    }

    /**
     * @param string $haystack
     * @param array $needles
     * @return string
     * @author Tuomas Siipola <siiptuo@kapsi.fi>
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
     * @param string $spans
     * @param int $window_size
     * @return int
     * @author Tuomas Siipola <siiptuo@kapsi.fi>
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
     * @param string $text
     * @param array $terms
     * @param int $window_size
     * @return string
     * @author Tuomas Siipola <siiptuo@kapsi.fi>
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
     * @param $value
     * @param array $query
     * @return array
     * @author Tuomas Siipola <siiptuo@kapsi.fi>
     */
    function get_matches($value, $query, $length)
    {

        $plugin = Plugin::get_instance();

        $split_compound_words = get_option("{$plugin->plugin_slug}_finnish_base_forms_split_compound_words");

        $plugin = Plugin::get_instance();

        $tokenized = $plugin->tokenize(mb_strtolower($value));

        $characters_processed = 0;
        $desired_length = $length * 2;

        $matches = [];


            $lemmatizer = LemmatizerHelper::get_instance();


            $array = [];


            $words_with_tokens = [];

            foreach($tokenized as $token) {


                if ($characters_processed > $desired_length) {
                    break;
                }

                $result = $lemmatizer->lemmatize($token);
                $words_with_tokens[$token] = [];

                foreach ($result as $item) {
                    array_push($words_with_tokens[$token], $item['baseform']);
                    if (!empty($item['wordbases'])) {
                        $words_with_tokens[$token] = array_merge($words_with_tokens[$token], $item['wordbases']);
                    }
                }

                foreach ($words_with_tokens as $original => $tokens) {
                    if (array_intersect($tokens, $query)) {
                        $characters_processed = $characters_processed + mb_strlen($token);
                    }
                }
            }

            //dump($words_with_tokens);

            foreach ($words_with_tokens as $original => $tokens) {
                if (array_intersect($tokens, $query)) {
                    array_push($matches, $original);
                }
            }

        return $matches;

    }
}