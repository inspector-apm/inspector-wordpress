<?php


use Inspector\Configuration;
use Inspector\Exceptions\InspectorException;
use Inspector\Wordpress\FilterWrapper;
use Inspector\Wordpress\InspectorWrapper;

class InspectorLoader
{
    private static $COMPOSER_AUTOLOADER = 'vendor/autoload.php';
    private static $PACKAGED_AUTOLOADER = 'inspector-php/autoload.php';

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var InspectorWrapper
     */
    private $inspector;

    /**
     * Inspector_Wordpress constructor.
     */
    public function __construct()
    {
        if ($this->requireInspectorPackage()) {
            $this->initAgent();
            $this->startTransaction();
        } else {
            error_log("Inspector Error: Couldn't activate Inspector Monitoring due to missing Inspector library!");
        }
    }

    /**
     * Include all inspector package files.
     *
     * @return bool
     */
    private function requireInspectorPackage()
    {
        // inspector-php was already loaded by some 3rd-party code, don't need to load it again.
        if (class_exists('InspectorWrapper')) {
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

    /**
     * Initialize Agent.
     */
    private function initAgent()
    {
        try {
            $this->configuration = new Configuration(get_option( 'inspector_api_key' ));
            $this->configuration->setEnabled(get_option( 'inspector_enable' ));

            // Stop monitoring on admin panel
            if(is_admin() && !get_option( 'inspector_track_admin' )){
                $this->configuration->setEnabled(false);
            }

            $this->inspector = new InspectorWrapper($this->configuration);
            $this->registerHooks();
        } catch (InspectorException $exception) {
            error_log('Inspector can not be activated. API KEY seems to be empty.');
        }

        // If handlers are not set, errors are still going to be reported
        // to Inspector, difference is execution will not stop.
        //
        // Can be useful to see inline errors and traces with xdebug too.
        /*$set_error_and_exception_handlers = apply_filters('inspector_set_error_and_exception_handlers', true);
        if ($set_error_and_exception_handlers === true) {
            // Hook up automatic error handling
            set_error_handler(array($this->client, "errorHandler"));
            set_exception_handler(array($this->client, "exceptionHandler"));
        }*/
    }

    public function registerHooks()
    {
        /*add_action('setup_theme', array($this, 'startTransaction'));
        add_action('after_setup_theme', array($this, 'endThemeSpan'));
        add_action('shutdown', array($this, 'shutdown'));*/

        global $wp_filter;
        foreach ( $wp_filter as $hook_name => $filter  ) { // $filter = WP_Hook
            foreach ( $filter->callbacks as $priority => $callback_container ) {
                foreach ( $callback_container as $callback_name => $callback ) {

                    if ( isset( $callback['function'] ) ) {
                        $callback_function = $callback['function'];
                    } else {
                        $callback_function = $callback_name;
                    }

                    //new FilterWrapper($this->inspector, $hook_name, $callback_function, $priority, $callback['accepted_args']);
                }
            }
        }
    }

    public function startTransaction()
    {
        if ( 'cli' === php_sapi_name() ) {
            $t_name = implode(' ', $_SERVER['argv']);
        } else {
            $t_name = $this->getTransactionNameFromRequest();
        }

        $this->inspector->startTransaction($t_name);
    }

    protected function getTransactionNameFromRequest()
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) . ' ' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
}