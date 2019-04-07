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
    wp_register_style('atra_jquery_ui', plugin_dir_url(__FILE__) . 'css/jquery-ui.min.css');
    wp_register_style('atra_jquery_ui', plugin_dir_url(__FILE__) . '/css/jquery-ui.structure.min.css');
    wp_register_style('atra_jquery_ui', plugin_dir_url(__FILE__) . '/css/jquery-ui.theme.min.css');
    wp_enqueue_style('atra_jquery_ui');

    wp_enqueue_script('jquery-ui-core');
    wp_enqueue_script('jquery-ui-autocomplete');
}


function atraAutocompleteInputShortcode($atts)
{
    $placeholder = isset($atts['placeholder']) ? htmlspecialchars($atts['placeholder']) : 'A kereséshez kezdjen el gépelni';
    $button = isset($atts['button']) ? htmlspecialchars($atts['button']) : 'Keresés';

    $html = sprintf(' 
    <div class="form-group">        
        <input name="atra-agents-search-input" class="form-control" id="atra-agents-search-input" type="text"  required placeholder="%s" style="width: 250px;">
        <button id="atra-agents-search-button" class="btn btn-primary mb-2" style="margin: 0px 10px; padding: .6em 1em .4em;">%s</button>
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
                select: function (event, ui) {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php') ?>',
                        dataType: "html",
                        data: {
                            'action': 'atra_agent_select',
                            'selected': ui.item.id
                        },
                        success: function (data) {
                            jQuery('#atra-result-box').html(data);
                        }
                    })
                }
            });
            jQuery('#atra-agents-search-button').click(function (e) {
                e.stopPropagation();
                var value = jQuery('#atra-agents-search-input').first().val();
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php') ?>',
                    dataType: "html",
                    data: {
                        'action': 'atra_agent_select',
                        'search': value.trim()
                    },
                    success: function (data) {
                        jQuery('#atra-result-box').html(data);
                    }
                })
            })
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

function atraAgentSelect()
{

    global $wpdb;

    $selected_id = isset($_GET['selected']) && !empty($_GET['selected']) ? intval($_GET['selected']) : false;
    $searchText = isset($_GET['search']) && !empty($_GET['search']) ? strip_tags($_GET['search']) : false;
    $table_name = $wpdb->prefix . ATRA_AGENTS_TABLE;

    $sql = "SELECT agent, phone, email  FROM $table_name WHERE ";

    if ($selected_id !== false) {
        $data = $wpdb->get_row($wpdb->prepare($sql . ' agent_id = %d', $selected_id),ARRAY_A);

    } elseif ($searchText !== false) {
        $data = $wpdb->get_row($wpdb->prepare($sql . ' city LIKE %s LIMIT 1', $searchText),ARRAY_A);
    }
    else{
        echo 'Nincs találálat';
        wp_die();
    }

    if(!count($data)){
        echo 'Nincs találálat';
        wp_die();
    }
/*
 * Gagyi adatbázis ... egy sorban több adatot is tárol vesszővel elválasztva. :(
 */
    $agents = explode(',', $data['agent']);
    $phones = explode(',', $data['phone']);
    $emails = explode(',', $data['email']);

echo '
<div class="ast-container">
    <div class="ast-row">
        <span>A megadott településhez tartozó tanácsadónk:</span>
    </div>
</div>
<div class="ast-container">
    <div class="ast-row">
    ';
for ($i = 0; $i < count($agents); $i++) {

        $agent = empty($agents[$i]) ? '-' : $agents[$i];

        if(empty($agents[$i])){
            $phones[$i] = '-';
        }

        $email = empty($emails[$i]) ? '-' : $emails[$i];

        $phone = preg_replace('/([0-9]{2})([0-9]{3})([0-9]{2})([0-9]{2})/', '+(36)$1/$2 $3 $4', $phones[$i]);

        echo '
        <div class="ast-col-sm-6">
            <div class="elementor-widget-container">
                <ul class="elementor-icon-list-items">
                    <li class="elementor-icon-list-item">
                        <span class="elementor-icon-list-icon">
                            <i class="fa fa-user" aria-hidden="true"></i>
                        </span>
                        <span class="elementor-icon-list-text">'.$agent.'</span>
                    </li> 
                    <li class="elementor-icon-list-item">
                        <a href="tel:'.$phone.'">
                            <span class="elementor-icon-list-icon">
                                <i class="fa fa-mobile" aria-hidden="true"></i>
                            </span>
                            <span class="elementor-icon-list-text">'.$phone.'</span>
                        </a>
                    </li>
                   <li class="elementor-icon-list-item">
                        <a href="mailto:'.$emails[$i].'">			
                            <span class="elementor-icon-list-icon">
                                <i class="fa fa-at" aria-hidden="true"></i>
                            </span>
                            <span class="elementor-icon-list-text">'.$email.'</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>';
    }
echo '
    </div>
</div>';

    wp_die();
}

function atraResultBoxShortcode()
{
    return '<div id="atra-result-box"></div>';
}

add_action('wp_enqueue_scripts', 'atraThemeEnqueueScripts');
add_action('wp_footer', 'atraThemeAutocompleteJs');
add_action('wp_ajax_atra_agent_search', 'atraAgentSearch');
add_action('wp_ajax_atra_agent_search', 'atraAgentSearch');
add_action('wp_ajax_atra_agent_select', 'atraAgentSelect');
add_action('wp_ajax_atra_agent_select', 'atraAgentSelect');

add_shortcode('atra_autocomplete_input_shortcode', 'atraAutocompleteInputShortcode');
add_shortcode('atra_result_box_shortcode', 'atraResultBoxShortcode');

register_activation_hook(__FILE__, 'atraAgentsPluginActivation');
register_uninstall_hook(__FILE__, 'atraAgentsPluginUninstall');

