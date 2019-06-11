<?php

/**
 * Real time monitoring for Web Agency and Freelance. Automate error detection so you can spend time on develop new functionality no needs to check manually that your website and applications works.
 *
 * @package Inspector
 * @author Inspector
 * @license GPL-2.0+
 * @link https://www.inspector.dev/
 * @copyright 2019 Aventure s.r.l. All rights reserved.
 *
 *            @wordpress-plugin
 *            Plugin Name: Inspector Real Time Monitoring
 *            Plugin URI: https://www.inspector.dev/
 *            Description: Word Press Real time monitoring. Identify bad plugins and themes in real time.
 *            Version: 1.2.0
 *            Author: Inspector
 *            Text Domain: inspector
 *            Contributors: Valerio Barbera
 *            License: GPL-2.0+
 *            License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Make sure it's wordpress
/*if ( !defined( 'ABSPATH') ) {
    die('Forbidden');
}*/

function inspector_add_menu() 
{
	add_menu_page('Inspector', 'Inspector', 'administrator', __FILE__, 'inspector_page', plugins_url('/assets/images/menu_icon_colored.png', __FILE__));
    add_action('admin_init', 'register_inspector_settings');
}

function register_inspector_settings() {
    register_setting('inspector-settings', 'inspector_api_key');
    register_setting('inspector-settings', 'inspector_enable');
    register_setting('inspector-settings', 'inspector_track_admin');
}

function inspector_page(){
    require_once 'views/settings.php';
}

function inspector_profiler_load() {
    require_once 'include/Helper.php';
    require_once 'include/FilterWrapper.php';
    require_once 'include/InspectorLoader.php';

    add_action('admin_menu', 'inspector_add_menu');

    try {
        new InspectorLoader();
    } catch (\Exception $exception) {
        error_log('Inspector Error ' . $exception->getMessage());
    }
}

add_action( 'init', 'inspector_profiler_load' );