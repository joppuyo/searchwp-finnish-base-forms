<?php
/*
Plugin Name: SearchWP Finnish Base Forms
Plugin URI: https://github.com/joppuyo/searchwp-finnish-base-forms
Description: SearchWP plugin to add Finnish base forms in search index
Version: 3.2.0
Author: Johannes Siipola
Author URI: https://siipo.la
Text Domain: searchwp-finnish-base-forms
*/

use NPX\FinnishBaseForms\Plugin;
use Siiptuo\Voikko\Voikko;

if (!defined('ABSPATH')) {
    exit;
}

require __DIR__ . '/vendor/autoload.php';


$finnish_base_forms = Plugin::get_instance();
$finnish_base_forms->__FILE__ = __FILE__;

function searchwp_finnish_base_forms_get_excerpt($post, $options = []) {

    $excerpt = \NPX\FinnishBaseForms\Excerpt::get_instance();
    return $excerpt->get_excerpt($post, $options);
}

