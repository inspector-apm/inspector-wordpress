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
 *            Plugin Name: Inspector
 *            Plugin URI: https://www.inspector.dev/
 *            Description: Real time monitoring for Web Agency and Freelance. Automate error detection so you can spend time on develop new functionality no needs to check manually that your website and applications works.
 *            Version: 1.0
 *            Author: Inspector
 *            Author URI: https://www.inspector.dev/
 *            Text Domain: inspector
 *            Contributors: Valerio Barbera
 *            License: GPL-2.0+
 *            License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */
 

function inspector_add_menu() 
{
	add_submenu_page("options-general.php", "Inspector", "Inspector", "manage_options", "inspector", "inspector_page");
}
add_action("admin_menu", "inspector_add_menu");

function inspector_page()
{
?>
<div class="wrap">
	<img src="<?=plugins_url( '/assets/images/logo-horizontal.png', __FILE__ ) ?>" style="width: 200px;"/>
	
	<br/><br/>
 
	<form method="post" action="options.php">
		<?php
			settings_fields("inspector_api_key");
			do_settings_sections("inspector");
			submit_button();
		?>
	 </form>
</div>
 
<?php
}
 
function inspector_settings() {
	add_settings_section("settings", "", null, "inspector");
	add_settings_field("inspector_api_key", "API KEY - Create a new project in your Inspector dashboard to obtain a valid Key.", "inspector_key_options", "inspector", "settings");
	register_setting("settings", "inspector_api_key");
	
	add_settings_section("settings", "", null, "inspector");
	add_settings_field("inspector_enable", "Enable/disable monitoring.", "inspector_enable_options", "inspector", "settings");
	register_setting("settings", "inspector_enable");
}
add_action("admin_init", "inspector_settings");
 
function inspector_key_options() {
?>
<div class="postbox" style="padding: 20px;">
	<input 
		style="width: 80%;"
		type="text" 
		name="inspector_api_key"
		value="<?=stripslashes_deep(esc_attr(get_option('inspector_api_key'))); ?>"
		placehoder="Inspector api key"
	/>
	<br/><br/>
	<a href="https://app.inspector.dev/home" target="_blank">
		Go to Inspector dashboard.
	</a>
</div>
<?php
}

function inspector_enable_options() {
?>
<div class="postbox" style="padding: 20px;">
	<input 
		type="checkbox" 
		name="inspector_enable"
		value="<?=stripslashes_deep(esc_attr(get_option('inspector_enable'))); ?>"
	/>
	Check this flag to activate monitoring
</div>
<?php
}