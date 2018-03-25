<?php

defined('ABSPATH') or die('No direct access');

add_filter('searchwp_set_post', function ($the_post)
{
    return $the_post;
});
