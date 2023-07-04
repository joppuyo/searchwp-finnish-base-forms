<?php

namespace NPX\FinnishBaseForms;

class Admin
{

    private static $instance;
    public $plugin_slug;
    public $plugin_name;
    public $api_type;

    public static function get_instance()
    {
        if (self::$instance == null)
        {
            self::$instance = new Admin();
        }

        return self::$instance;
    }

    public function __construct()
    {
        $plugin = Plugin::get_instance();

        $this->plugin_name = $plugin->plugin_name;
        $this->plugin_slug = $plugin->plugin_slug;
        $this->api_type = $plugin->api_type;

    }

    public function plugin_action_links ($links) {
        $settings_link = "<a href=\"options-general.php?page={$this->plugin_slug}_finnish_base_forms\">" . __('Settings', "{$this->plugin_slug}_finnish_base_forms") . '</a>';

        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Render WordPress plugin settings page
     */
    public function settings_page()
    {

        $updated = false;
        $cache_cleared = false;

        $api_type = $this->api_type;

        if (!empty($_POST) && isset($_POST['clear_cache']) && $_POST['clear_cache'] === '0') {
            check_admin_referer("{$this->plugin_slug}_finnish_base_forms");
            //update_option("{$this->plugin_slug}_finnish_base_forms_api_url", $_POST['api_url']);

            update_option("{$this->plugin_slug}_finnish_base_forms_lemmatize_search_query", !empty($_POST['lemmatize_search_query']) && $_POST['lemmatize_search_query'] === 'checked' ? 1 : 0);
            update_option("{$this->plugin_slug}_finnish_base_forms_split_compound_words", !empty($_POST['split_compound_words']) && $_POST['split_compound_words'] === 'checked' ? 1 : 0);
            update_option("{$this->plugin_slug}_finnish_base_forms_api_type", in_array($_POST['api_type'], ['binary', 'command_line', 'web_api', 'ffi']) ? $_POST['api_type'] : 'command_line');
            update_option("{$this->plugin_slug}_finnish_base_forms_enable_cache", !empty($_POST['enable_cache']) && $_POST['enable_cache'] === 'checked' ? 1 : 0);

            $api_type = get_option("{$this->plugin_slug}_finnish_base_forms_api_type");

            $updated = true;
        }

        if (!empty($_POST) && isset($_POST['clear_cache']) && $_POST['clear_cache'] === '1') {
            check_admin_referer("{$this->plugin_slug}_finnish_base_forms");
            //update_option("{$this->plugin_slug}_finnish_base_forms_api_url", $_POST['api_url']);

            $plugin = Plugin::get_instance();
            $plugin->clear_cache();

            $cache_cleared = true;
        }

        $ffi_available = extension_loaded('ffi');

        $disabled = $ffi_available ? '' : 'disabled';

        echo '<div class="wrap">';
        echo '    <h1>' . __("$this->plugin_name Finnish Base Forms", "{$this->plugin_slug}_finnish_base_forms") . '</h1>';
        echo '    <div class="js-finnish-base-forms-admin-notices"></div>';
        if ($updated) {
            echo '    <div class="notice notice-success">';
            echo '        <p>' . __('Options have been updated', "{$this->plugin_slug}_finnish_base_forms") . '</p>';
            echo '    </div>';
        }
        if ($cache_cleared) {
            echo '    <div class="notice notice-success">';
            echo '        <p>' . __('Cache has been cleared', "{$this->plugin_slug}_finnish_base_forms") . '</p>';
            echo '    </div>';
        }
        echo '    <form method="post" class="js-finnish-base-forms-form" data-slug="' . $this->plugin_slug . '">';
        echo '    <table class="form-table">';
        echo '        <tbody>';
        echo '            <tr>';
        echo '                <th scope="row">';
        echo '                    <label for="api_type">' . __('API type', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <p><input type="radio" id="ffi" name="api_type" value="ffi" ' . checked($api_type, 'ffi', false) . $disabled . '><label for="ffi">FFI (requires FFI extension, PHP 7.4+)</label></p>';
        echo '                <p><input type="radio" id="binary" name="api_type" value="binary" ' . checked($api_type, 'binary', false) . '><label for="binary">Voikko binary (bundled)</label></p>';
        echo '                <p><input type="radio" id="command_line" name="api_type" value="command_line" ' . checked($api_type, 'command_line', false) . '><label for="command_line">Voikko command line</label></p>';
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
        echo '                    <label>' . __('Cache word analysis', "searchwp_finnish_base_forms") . '</label>';
        echo '                </th>';
        echo '                <td>';
        echo '                <input type="checkbox" name="enable_cache" id="enable_cache" value="checked" ' . checked(get_option("{$this->plugin_slug}_finnish_base_forms_enable_cache"), '1', false) . ' />';
        echo '                <label for="lemmatize_search_query">Enabled</label>';
        echo '                </td>';
        echo '            </tr>';
        echo '            <tr>';
        echo '                <th scope="row">';
        echo '                    <label>' . __('Convert search query to base forms', "{$this->plugin_slug}_finnish_base_forms") . '</label>';
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
        echo '        <input type="hidden" name="clear_cache" value="0">';
        echo '        <input class="button-primary js-finnish-base-forms-submit-button" type="submit" name="submit-button" value="Save">';
        echo '    </p>';
        wp_nonce_field("{$this->plugin_slug}_finnish_base_forms");
        echo '    </form>';
        echo '     <form method="post">';
        echo '        <input type="hidden" name="clear_cache" value="1">';
        wp_nonce_field("{$this->plugin_slug}_finnish_base_forms");
        echo '        <input class="button js-finnish-base-forms-submit-button" type="submit" name="submit-button" value="Clear Cache">';
        echo '    </form>';
        echo '</div>';
    }
}