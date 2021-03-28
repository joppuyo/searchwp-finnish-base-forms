<?php

namespace NPX\FinnishBaseForms;

use NPX\FinnishBaseForm\FfiLemmatizer;

class LemmatizerHelper
{
    private static $instance;
    private $lemmatizer;

    public static function get_instance()
    {
        if (self::$instance == null)
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function lemmatize($string) {
        return $this->lemmatizer->lemmatize_cached($string);
    }

    public function parse_wordbases($wordbases) {
        return $this->lemmatizer->parse_wordbases($wordbases);
    }

    public function __construct()
    {
        $plugin = Plugin::get_instance();

        if ($plugin->api_type === 'binary' || $plugin->api_type === 'command_line') {

            $binary_path = null;
            if ($plugin->api_type === 'binary') {
                $path = plugin_dir_path($plugin->__FILE__);
                $binary = 'voikkospell';
                if (stripos(PHP_OS, 'darwin') === 0) {
                    $binary = 'voikkospell-mac';
                }
                $this->ensure_permissions("{$path}bin/{$binary}");
                $binary_path = "{$path}bin/{$binary} -p {$path}bin/dictionary";
            } else {
                $binary_path = 'voikkospell';
            }

            $this->lemmatizer = new \NPX\FinnishBaseForms\CliLemmatizer($binary_path);

        }

        if ($plugin->api_type === 'ffi') {
            $this->lemmatizer = new FfiLemmatizer();
        }


    }

    /**
     * Make sure binary is executable
     * @param string $path
     */
    function ensure_permissions($path)
    {
        $permissions = substr(sprintf('%o', fileperms($path)), -4);

        if ($permissions !== '0755') {
            chmod($path, 0755);
        }
    }
}