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
use Inspector\Exceptions\InspectorException;
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
        if ($this->requireInspectorPackage()) {
            error_log('Init Inspector Agent');
            $this->initAgent();
        } else {
            error_log("Inspector Error: Couldn't activate Inspector Monitoring due to missing Inspector library!");
        }
    }

    /**
     * Initialize Agent.
     */
    private function initAgent()
    {
        try {
            $this->configuration = new Configuration(get_option( 'inspector_api_key' ));
            $this->configuration->setEnabled(get_option( 'inspector_enable' ));

            $this->inspector = new Inspector($this->configuration);
            $this->registerHooks();
            error_log('Inspector activated.');
        } catch (InspectorException $exception) {
            error_log('Inspector can not be activated. API KEY seems to be empty.');
        }

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

    protected function registerHooks()
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

add_action('admin_menu', 'inspector_add_menu');
function inspector_add_menu() 
{
	add_menu_page('Inspector', 'Inspector', 'administrator', __FILE__, 'inspector_page', plugins_url('/assets/images/menu_icon_colored.png', __FILE__));
    add_action('admin_init', 'register_inspector_settings');
}

function register_inspector_settings() {
    register_setting('inspector-settings', 'inspector_api_key');
    register_setting('inspector-settings', 'inspector_enable');
}


function inspector_page(){
?>
<div class="wrap">
	<img src="<?=plugins_url( '/assets/images/logo-horizontal.png', __FILE__ ) ?>" style="width: 200px;"/>
	
	<br/><br/>
 
	<form method="post" action="options.php">
        <?php settings_fields( 'inspector-settings' ); ?>
        <?php do_settings_sections( 'inspector-settings' ); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    API KEY <br/>
                    Create a new project in your Inspector dashboard to obtain a valid Key.
                </th>
                <td>
                    <input
                            style="width: 80%;"
                            type="text"
                            name="inspector_api_key"
                            value="<?=esc_attr(get_option('inspector_api_key')); ?>"
                            placeholder="Paste here your project api key..."
                    />
                    <br/><br/>
                    <a href="https://app.inspector.dev/home" target="_blank">
                        Go to Inspector dashboard.
                    </a>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row">
                    Activate <br/>
                    Enable/disable monitoring.
                </th>
                <td>
                    <input
                            type="checkbox"
                            name="inspector_enable"
                            value="1"
                        <?php if(esc_attr(get_option('inspector_enable'))) echo 'checked' ?>
                    />
                    Check this flag to activate monitoring
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
	 </form>
</div>
 
<?php
}

$inspector = new Inspector_Wordpress();