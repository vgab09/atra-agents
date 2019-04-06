<?php

/**
 * Plugin Name: atra-agents
 * Description: atra.hu tanácsadó kereső modul
 * Version:     1.0
 * Author:      vgab09
 * Author URI:  https://github.com/vgab09/
 * License:     GPL3
 * License URI: https://github.com/vgab09/atra-agents/blob/master/LICENSE
 * Text Domain: wporg
 * Domain Path: /languages
 *
 * atra-agents is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * atra-agents is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with {Plugin Name}. If not, see https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
define('ATRA_AGENTS', 1);
define('ATRA_AGENTS_TABLE', 'atra_agents');

function atraAgentsPluginActivation()
{
    global $wpdb;
    $table_name = $wpdb->prefix . ATRA_AGENTS_TABLE;
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
		 `agent_id` int(11) NOT NULL auto_increment,
          `city` varchar(500) NOT NULL,
          `sector` varchar(255) NOT NULL,
          `county` varchar(255) NOT NULL,
          `agent` varchar(500) NOT NULL,
          `phone` varchar(255) NOT NULL,
          `email` varchar(500) NOT NULL,
		  PRIMARY KEY  (`agent_id`),
          KEY `city` (`city`(255))
	) $charset_collate;";
    $wpdb->query($sql);

    $agents = [];
    include(__DIR__ . '/agents.data.php');
    foreach ($agents as $agent) {
        $wpdb->replace($table_name, $agent, ['%d', '%s', '%s', '%s', '%s', '%s', '%s']);
    }


}

function atraAgentsPluginUninstall()
{
    global $wpdb;
    $table_name = $wpdb->prefix . ATRA_AGENTS_TABLE;

    $wpdb->query("DROP TABLE IF EXISTS $table_name");

}

function atraThemeEnqueueScripts()
{
    wp_register_style('atra_jquery_ui',plugin_dir_url( __FILE__ ).'css/jquery-ui.min.css');
    wp_register_style('atra_jquery_ui',plugin_dir_url( __FILE__ ).'/css/jquery-ui.structure.min.css');
    wp_register_style('atra_jquery_ui',plugin_dir_url( __FILE__ ).'/css/jquery-ui.theme.min.css');
    wp_enqueue_style( 'atra_jquery_ui');

    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-autocomplete');
}

function atraThemeEnqueueCss(){



}


function atraAutocompleteInputShortcode($atts)
{
    $placeholder = isset($atts['placeholder']) ? htmlspecialchars($atts['placeholder']) : 'A kereséshez kezdjen el gépelni';
    $button = isset($atts['button']) ? htmlspecialchars($atts['button']) : 'Keresés';

    $html = sprintf(' 
    <div class="form-group">        
        <input name="atra-agents-search-input" class="form-control" id="atra-agents-search-input" type="text"  required placeholder="%s" style="width: 250px;">
        <button type="submit" class="btn btn-primary mb-2" style="margin: 0px 10px; padding: .6em 1em .4em;">%s</button>
    </div>', $placeholder, $button);

    return $html;

}

function atraThemeAutocompleteJs()
{
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {

            jQuery('#atra-agents-search-input').autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                        dataType: "json",
                        data: {
                            'action': 'atra_agent_search',
                            'term': request.term
                        },
                        success: function (data) {
                            response(data);
                        }
                    })
                },
                minLength: 2,
                delay: 0,
            });
        });
    </script>
    <?php

}

function atraAgentSearch()
{
    global $wpdb;

    $searchText = isset($_GET['term']) && !empty($_GET['term']) ? strip_tags($_GET['term']) : false;

    if ($searchText !== false) {
        $table_name = $wpdb->prefix . ATRA_AGENTS_TABLE;
        $data = $wpdb->get_results($wpdb->prepare("SELECT agent_id as id, city as 'value', city as 'label' FROM $table_name WHERE city LIKE %s", $searchText . '%'));

        if (is_array($data)) {
            echo json_encode($data);
            wp_die();
        }
    }

    echo json_encode([$searchText]);
    wp_die();
}
add_action('wp_enqueue_scripts', 'atraThemeEnqueueScripts');
add_action('wp_footer', 'atraThemeAutocompleteJs');
add_action('wp_ajax_atra_agent_search', 'atraAgentSearch');
add_action('wp_ajax_atra_agent_search', 'atraAgentSearch');

add_shortcode('atra_autocomplete_input_shortcode', 'atraAutocompleteInputShortcode');

register_activation_hook(__FILE__, 'atraAgentsPluginActivation');
register_uninstall_hook(__FILE__, 'atraAgentsPluginUninstall');

