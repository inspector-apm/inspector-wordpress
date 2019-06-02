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
use Inspector\Inspector;

class Inspector_Wordpress
{
    private static $COMPOSER_AUTOLOADER = 'vendor/autoload.php';
    private static $PACKAGED_AUTOLOADER = 'inspector-php/autoload.php';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Inspector
     */
    private $inspector;

    /**
     * Inspector_Wordpress constructor.
     *
     * @throws \Inspector\Exceptions\InspectorException
     */
    public function __construct()
    {
        $this->activateInspector();
    }

    /**
     * Activate Inspector monitoring as soon as possible.
     *
     * @throws \Inspector\Exceptions\InspectorException
     */
    private function activateInspector()
    {
        $is_load_success = $this->requireInspectorPackage();

        if (!$is_load_success) {
            error_log("Inspector Error: Couldn't activate Inspector Monitoring due to missing Inspector library!");
            return;
        }

        $this->initAgent();
    }

    /**
     * @throws \Inspector\Exceptions\InspectorException
     */
    private function initAgent()
    {
        if(empty(get_option( 'inspector_api_key' ))){
            return;
        }

        $this->configuration = new Configuration(get_option( 'inspector_api_key' ));
        $this->configuration->setEnabled(get_option( 'inspector_enable' ));

        $this->inspector = new Inspector($this->configuration);

        // If handlers are not set, errors are still going to be reported
        // to bugsnag, difference is execution will not stop.
        //
        // Can be useful to see inline errors and traces with xdebug too.
        /*$set_error_and_exception_handlers = apply_filters('bugsnag_set_error_and_exception_handlers', true);
        if ($set_error_and_exception_handlers === true) {
            // Hook up automatic error handling
            set_error_handler(array($this->client, "errorHandler"));
            set_exception_handler(array($this->client, "exceptionHandler"));
        }*/
    }

    /**
     * Include all inspector package files.
     *
     * @return bool
     */
    private function requireInspectorPackage()
    {
        // inspector-php was already loaded by some 3rd-party code, don't need to load it again.
        if (class_exists('Inspector')) {
            return true;
        }

        // Try loading bugsnag-php with composer autoloader.
        $composer_autoloader_path = dirname(__FILE__) . '/' . self::$COMPOSER_AUTOLOADER;
        if (file_exists($composer_autoloader_path)) {
            require_once $composer_autoloader_path;
            return true;
        }

        // Try loading bugsnag-php from packaged autoloader.
        $packaged_autoloader_path = dirname(__FILE__) . '/' . self::$PACKAGED_AUTOLOADER;
        if (file_exists($packaged_autoloader_path)) {
            require_once $packaged_autoloader_path;
            return true;
        }

        return false;
    }

    public function registerHooks()
    {
        $spans = [];

        apply_filters('setup_theme', function () use ($spans) {
            if ( 'cli' === php_sapi_name() ) {
                $t_name = implode(' ', $_SERVER['argv']);
            } else {
                $t_name = strtoupper($_SERVER['REQUEST_METHOD'] . ' /' . trim($_SERVER['REQUEST_URI'], '/'));
            }

            $this->inspector->startTransaction($t_name);

            $spans['theme'] = $this->inspector->startSpan('Theme');
        });

        apply_filters('after_setup_theme', function () use($spans) {
            if(array_key_exists('theme', $spans)){
                $spans['theme']->end();
            }
        });

        apply_filters('shutdown', function () {
            foreach ( $GLOBALS['wpdb'] as $name => $db ) {
                if ( is_a( $db, 'wpdb' ) ) {
                    $this->processQuery( $name, $db );
                }
            }
        });
    }

    protected function processQuery($name, $db)
    {
        foreach ( (array) $db->queries as $query ) {
            $span = $this->inspector->startSpan($name);
            $span->getContext()->getDb()->setSql($query[0]);
            $span->getContext()->getDb()->setType('mysql');
            $span->end($query[1]);
        }
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