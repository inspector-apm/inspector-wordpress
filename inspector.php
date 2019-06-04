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
 *            Description: Real time monitoring for Web Agency and Freelance. Automate error detection so you can spend time on develop new functionality no needs to check manually that your website and applications works.
 *            Version: 1.0.0
 *            Author: Inspector
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
     * @var array
     */
    private $spans = [];

    /**
     * Inspector_Wordpress constructor.
     */
    public function __construct()
    {
        if ($this->requireInspectorPackage()) {
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

            // Stop monitoring on admin panel
            if(is_admin()){
                $this->configuration->setEnabled(false);
            }

            $this->inspector = new Inspector($this->configuration);
            $this->registerHooks();
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

    private function registerHooks()
    {
        add_action('setup_theme', array($this, 'startTransaction'));
        add_action('after_setup_theme', array($this, 'endThemeSpan'));
        add_action('shutdown', array($this, 'shutdown'));
    }

    public function startTransaction()
    {
        if ( 'cli' === php_sapi_name() ) {
            $t_name = implode(' ', $_SERVER['argv']);
        } else {
            $t_name = $this->getTransactionNameFromRequest();
        }

        $this->inspector->startTransaction($t_name);
        $this->spans['theme'] = $this->inspector->startSpan('Theme');
    }

    public function endThemeSpan()
    {
        if(array_key_exists('theme', $this->spans)){
            $this->spans['theme']->end();
        }
    }

    public function shutdown($name)
    {
        $this->processQueries();
    }

    public function processQueries()
    {
        foreach ( $GLOBALS['wpdb'] as $name => $db ) {
            if ( is_a( $db, 'wpdb' ) ) {
                foreach ( (array) $db->queries as $query ) {
                    $span = $this->inspector->startSpan($name);
                    $span->getContext()->getDb()->setSql($query[0]);
                    $span->getContext()->getDb()->setType('mysql');
                    $span->end($query[1]);
                }
            }
        }
    }

    protected function getTransactionNameFromRequest()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) . ' ' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
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
    require_once 'views/settings.php';
}

try {
    $inspector = new Inspector_Wordpress();
} catch (\Exception $exception) {
    error_log('Inspector Error ' . $exception->getMessage());
}