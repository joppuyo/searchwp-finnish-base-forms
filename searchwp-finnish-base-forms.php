<?php
/*
Plugin Name: SearchWP Finnish Base Forms
Plugin URI: https://github.com/joppuyo/searchwp-finnish-base-forms
Description: SearchWP plugin to add Finnish base forms in search index
Version: 1.0.1
Author: Johannes Siipola
Author URI: https://siipo.la
Text Domain: searchwp-finnish-base-forms
*/

defined('ABSPATH') or die('I wish I was using a real MVC framework');

// Check if we are using local composer
if (file_exists(__DIR__ . '/vendor')) {
    require 'vendor/autoload.php';
}

if (get_option('searchwp_finnish_base_forms_api_url')) {
    add_filter('searchwp_indexer_pre_process_content', function ($content) {
        return searchwp_finnish_base_forms_lemmatize($content);
    });
}

if (get_option('searchwp_finnish_base_forms_api_url') && get_option('searchwp_finnish_base_forms_lemmatize_search_query')) {
    add_filter('searchwp_pre_search_terms', function ($terms, $engine) {
        $terms = implode(' ', $terms);
        $terms = searchwp_finnish_base_forms_lemmatize($terms);
        $terms = explode(' ', $terms);
        return $terms;
    }, 10, 2);
}

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $settings_link = '<a href="options-general.php?page=searchwp_finnish_base_forms">' . __('Settings', 'searchwp_finnish_base_forms') . '</a>';
    array_push($links, $settings_link);
    return $links;
});

function searchwp_finnish_base_forms_settings_page()
{
    $updated = false;
    if (!empty($_POST['submit'])) {
        check_admin_referer('searchwp_finnish_base_forms');
        update_option('searchwp_finnish_base_forms_api_url', $_POST['api_url']);
        update_option('searchwp_finnish_base_forms_lemmatize_search_query', $_POST['lemmatize_search_query'] === 'checked' ? 1 : 0);
        $updated = true;
    }

    $apiUrl = get_option('searchwp_finnish_base_forms_api_url');

    echo '<div class="wrap">';
    echo '    <h1>' . __('SearchWP Finnish Base Forms', 'searchwp_finnish_base_forms') . '</h1>';
    if ($updated) {
        echo '<div class="notice notice-success">';
        echo '    <p>' . __('Options have been updated', 'searchwp_finnish_base_forms') . '</p>';
        echo '</div>';
    }
    echo '    <form method="post">';
    echo '    <table class="form-table">';
    echo '        <tbody>';
    echo '            <tr>';
    echo '                <th scope="row">';
    echo '                    <label for="api_url">' . __('Node API URL', 'searchwp_finnish_base_forms') . '</label>';
    echo '                </th>';
    echo '                <td>';
    echo '                <input name="api_url" type="url" id="api_url" value="' . esc_url($apiUrl) . '" class="regular-text">';
    echo '                </td>';
    echo '            </tr>';
    echo '            <tr>';
    echo '                <th scope="row">';
    echo '                    <label>' . __('Add base forms to search query', 'searchwp_finnish_base_forms') . '</label>';
    echo '                </th>';
    echo '                <td>';
    echo '                <input type="checkbox" name="lemmatize_search_query" id="lemmatize_search_query" value="checked" ' . checked(get_option('searchwp_finnish_base_forms_lemmatize_search_query'), '1', false) . ' />';
    echo '                <label for="lemmatize_search_query">Enabled</label>';
    echo '                </td>';
    echo '            </tr>';
    echo '            <tr>';
    echo '                <th colspan="2">';
    echo '                <span style="font-weight: 400">Note: if you enable "Add base forms to search query", the API will be called every time a search if performed, this might have performance implications.</span>';
    echo '                </td>';
    echo '            </tr>';
    echo '        </tbody>';
    echo '    </table>';
    echo '    <p class="submit">';
    echo '        <input class="button-primary" type="submit" name="submit" value="Save">';
    echo '    </p>';
    wp_nonce_field('searchwp_finnish_base_forms');
    echo '    </form>';
    echo '</div>';
}

function searchwp_finnish_base_forms_lemmatize($content) {
    $tokenizer = new \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer();
    $tokenized = $tokenizer->tokenize(strip_tags($content));

    $apiRoot = get_option('searchwp_finnish_base_forms_api_url');

    $client = new \GuzzleHttp\Client();

    $extraWords = [];

    $requests = function () use ($client, $tokenized, $apiRoot) {
        foreach ($tokenized as $token) {
            yield function () use ($client, $token, $apiRoot) {
                return $client->getAsync(trailingslashit($apiRoot) . 'analyze/' . $token);
            };
        }
    };

    $pool = new \GuzzleHttp\Pool($client, $requests(), [
      'concurrency' => 10,
      'fulfilled' => function ($response) use (&$extraWords) {
          $response = json_decode($response->getBody()->getContents(), true);
          if (count($response)) {
              array_push($extraWords, $response[0]['BASEFORM']);
          }
      },
    ]);

    $promise = $pool->promise();
    $promise->wait();

    $content = $content . ' ' . implode(' ', $extraWords);

    return $content;
}

add_action('admin_menu', function () {
    add_submenu_page(
        null,
        __('SearchWP Finnish Base Forms', 'searchwp_finnish_base_forms'),
        __('SearchWP Finnish Base Forms', 'searchwp_finnish_base_forms'),
        'manage_options',
        'searchwp_finnish_base_forms',
        'searchwp_finnish_base_forms_settings_page'
    );
});


