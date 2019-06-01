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

use Inspector\Configuration;

class Inspector_Wordpress
{
    private static $COMPOSER_AUTOLOADER = 'vendor/autoload.php';
    private static $PACKAGED_AUTOLOADER = 'inspector-php/autoload.php';

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var bool
     */
    private $enable = false;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Inspector_Wordpress constructor.
     */
    public function __construct()
    {
        $this->activateInspector();
    }

    /**
     * Activate Inspector monitoring as soon as possible.
     */
    private function activateInspector()
    {
        $is_load_success = $this->requireBugsnagPhp();

        if (!$is_load_success) {
            error_log("Bugsnag Error: Couldn't activate Bugsnag Error Monitoring due to missing Bugsnag library!");
            return;
        }

        // Load inspector settings
        $this->apiKey = get_option( 'inspector_api_key' );
        $this->enable = get_option( 'inspector_enable' );

        $this->init();
    }

    private function init() {
        if(!empty($this->apiKey)) {
            $this->configuration = new Configuration($this->apiKey);

            $this->client->setReleaseStage($this->releaseStage())
                ->setErrorReportingLevel($this->errorReportingLevel())
                ->setFilters($this->filterFields());

            $this->client->setNotifier(self::$NOTIFIER);

            // If handlers are not set, errors are still going to be reported
            // to bugsnag, difference is execution will not stop.
            //
            // Can be useful to see inline errors and traces with xdebug too.
            $set_error_and_exception_handlers = apply_filters('bugsnag_set_error_and_exception_handlers', true);
            if ($set_error_and_exception_handlers === true) {
                // Hook up automatic error handling
                set_error_handler(array($this->client, "errorHandler"));
                set_exception_handler(array($this->client, "exceptionHandler"));
            }
        }
    }

    private function requireInspectorPackage()
    {
        // Bugsnag-php was already loaded by some 3rd-party code, don't need to load it again.
        if (class_exists('Bugsnag_Client')) {
            return true;
        }

        // Try loading bugsnag-php with composer autoloader.
        $composer_autoloader_path = $this->relativePath(self::$COMPOSER_AUTOLOADER);
        $composer_autoloader_path_filtered = apply_filters('bugsnag_composer_autoloader_path', $composer_autoloader_path);
        if (file_exists($composer_autoloader_path_filtered)) {
            require_once $composer_autoloader_path_filtered;
            return true;
        }

        // Try loading bugsnag-php from packaged autoloader.
        $packaged_autoloader_path = $this->relativePath(self::$PACKAGED_AUTOLOADER);
        $packaged_autoloader_path_filtered = apply_filters('bugsnag_packaged_autoloader_path', $packaged_autoloader_path);
        if (file_exists($packaged_autoloader_path_filtered)) {
            require_once $packaged_autoloader_path_filtered;
            return true;
        }

        return false;
    }
}

function inspector_add_menu() 
{
	add_submenu_page('options-general.php', 'Inspector', 'Inspector', 'manage_options', 'inspector', 'inspector_page');
}
add_action('admin_menu', 'inspector_add_menu');


function inspector_page()
{
?>
<div class="wrap">
	<img src="<?=plugins_url( '/assets/images/logo-horizontal.png', __FILE__ ) ?>" style="width: 200px;"/>
	
	<br/><br/>
 
	<form method="post" action="options.php">
		<?php
			settings_fields('inspector_api_key', 'inspector_enable');
			do_settings_sections('inspector');
			submit_button();
		?>
	 </form>
</div>
 
<?php
}
 
function inspector_settings() {
	add_settings_section('settings', '', null, 'inspector');
	add_settings_field('inspector_api_key', 'API KEY <br/> Create a new project in your Inspector dashboard to obtain a valid Key.', 'inspector_key_options', 'inspector', 'settings');
	register_setting('settings', 'inspector_api_key');
	
	add_settings_section('settings', '', null, 'inspector');
	add_settings_field('inspector_enable', 'Activate <br/> Enable/disable monitoring.', 'inspector_enable_options', 'inspector', 'settings');
	register_setting('settings', 'inspector_enable');
}
add_action('admin_init', 'inspector_settings');


function inspector_key_options() {
?>
<div class="postbox" style="padding: 20px;">
	<input 
		style="width: 80%;"
		type="text" 
		name="inspector_api_key"
		value="<?=stripslashes_deep(esc_attr(get_option('inspector_api_key'))); ?>"
		placeholder="Paste here your project api key..."
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