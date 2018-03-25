<?php
/*
Plugin Name: SearchWP Finnish Base Forms
Plugin URI: https://github.com/joppuyo/searchwp-finnish-base-forms
Description: SearchWP plugin to add Finnish base forms in search index
Version: 0.0.0
Author: Johannes Siipola
Author URI: https://siipo.la
Text Domain: searchwp-finnish-base-forms
*/

defined('ABSPATH') or die('I wish I was using a real MVC framework');

require 'vendor/autoload.php';

add_filter('searchwp_indexer_pre_process_content', function ($content) {
    $tokenizer = new \NlpTools\Tokenizers\WhitespaceAndPunctuationTokenizer();
    $tokenized = $tokenizer->tokenize(strip_tags($content));

    $apiRoot = 'http://xxx';

    $client = new \GuzzleHttp\Client();

    $extraWords = [];

    $requests = function () use ($client, $tokenized, $apiRoot) {
        foreach ($tokenized as $token) {
            yield function () use ($client, $token, $apiRoot) {
                return $client->getAsync($apiRoot . '/analyze/' . $token);
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
});

