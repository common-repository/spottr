<?php

/**
 * Plugin Name: Spottr
 * Plugin URI:  https://wordpress.org/plugins/spottr/
 * Author:      Spottr
 * Author URI:  http://www.spottr.app
 * Description: Spottr helps connect your WordPress site to your Spottr account.
 * Version:     1.0.0
 * License:     GPL-2.0+
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: spottr
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

//define
define('SPOTTR_VERSION', '1.0.0');
define('SPOTTR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPOTTR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SPOTTR_PLUGIN_FILE', __FILE__);
//default category id
define('SPOTTR_DEFAULT_CATEGORY_ID', 'e22dd4ac-62f6-44d4-a6a1-c104a0cb4388');
//default tag id
define('SPOTTR_DEFAULT_TAG_ID', '3b252e7e-fec5-4dff-b605-35e789da03b0');
//api
define('SPOTTR_API_URL', 'https://prod-newbackend-spottr-nextjs.azurewebsites.net/api/');

// Include the main Spottr class.
if (!class_exists('Spottr')) {
    //include composer autoload
    require_once dirname(__FILE__) . '/vendor/autoload.php';
    include_once dirname(__FILE__) . '/includes/class-spottr.php';
    // Add the settings link to the plugins page
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), array('Spottr', 'add_settings_link'));
}

//on plugin deactivation
register_deactivation_hook(__FILE__, array('Spottr', 'deactivate'));
