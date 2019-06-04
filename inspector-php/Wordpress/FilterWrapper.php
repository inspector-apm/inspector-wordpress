<?php

namespace Inspector\Wordpress;


use Inspector\Inspector;

class FilterWrapper
{
    /**
     * @var Inspector
     */
    protected $inspector;

    /**
     * @var array
     */
    protected $spans = [];

    /**
     * @var string  This will be the hook we inject. Could either be an existing wordpress hook or alternative an
     *              custom hook created by the plugin/theme.
     */
    private $hook_name = '';

    /**
     * @var string|array  The origin callback function which was implemented by the plugin|theme. it could either be
     *                    a string or an array with the given object and method of the object.
     */
    private $callback_function = '';

    /**
     * @var int the priority of the hook.
     */
    private $priority = 10;

    /**
     * @var int the number of arguments accepted by the hook.
     */
    private $accepted_args = 0;

    /**
     * HookObject constructor.
     *
     * @param string $hook_name
     * @param string $callback_function
     * @param int $priority
     * @param int $accepted_args
     */
    public function __construct( $hook_name, $callback_function, $priority, $accepted_args ) {
        $this->hook_name         = $hook_name;
        $this->priority          = $priority;
        $this->accepted_args     = $accepted_args;
        $this->callback_function = $callback_function;

        /**
         * First we need to remove the existing hook, this will be replaced with an custom filter.
         */
        if ( ! remove_action( $hook_name, $callback_function, $priority ) ) {
            echo "FAILED TO REMOVE FUNCTION";
        } else {
            /**
             * Add thhe new callback filter. This will be used as a wrapper calling the origin callback.
             */
            add_filter( $hook_name, array( $this, 'wrapper' ), $priority, $accepted_args );
        }
    }

    /**
     * Magic _getter function provided by php
     *
     * @param string $property the property
     *
     * @return null|mixed returns null if the property doesn't exist
     */
    public function __get( $property ) {
        if ( property_exists( $this, $property ) ) {
            return $this->$property;
        }

        return null;
    }

    /**
     * The wrapper function. This function will be used to wrap the origin callback within our time measuring method.
     * To ensure compatibility between filters and actions we always return a value.
     *
     * This function will call the origin filter|action and then track the time the function required to complete. After
     * it will add the time to the class.
     *
     * @param mixed ...$args
     *
     * @return mixed|string
     */
    public function wrapper( ...$args ) {
        $num_args   = count( $args );
        $value      = '';
        $time_start = microtime( true );

        // Avoid the array_slice if possible.
        // we used the origin code from wordpress to ensure the same functionality.
        if ( $this->accepted_args == 0 ) {
            $value = call_user_func_array( $this->callback_function, array() );
        } elseif ( $this->accepted_args >= $num_args ) {
            $value = @call_user_func_array( $this->callback_function, $args );
        } else {
            $value = call_user_func_array( $this->callback_function, array_slice( $args, 0, (int) $this->accepted_args ) );
        }
        $time_end = microtime( true );
        $time     = $time_end - $time_start;

        // Load debug backtrace to get the file / folder
        $debug_stack = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT );

        /**
         * loop through every stack to find the root of the filter|action.
         */
        foreach ( $debug_stack as $stack ) {
            if ( isset( $stack['file'] ) && ( strpos( $stack['file'], 'themes' ) || strpos( $stack['file'], 'plugins' ) ) ) {
                break;
            }
        }
        if ( ! isset( $stack ) || ! isset( $stack['file'] ) ) {
            return $value;
        }

        /**
         * Add the time to the found plugin|theme. If not found we use the unknown keyword to track the time.
         */
        if ( strpos( $stack['file'], 'themes' ) ) {
            // Theme functions
            $span = $this->inspector->startSpan('themes');
            $span->end($time);
        } else if ( strpos( $stack['file'], 'plugins' ) ) {
            // Plugin functions
            $span = $this->inspector->startSpan(Helper::get_plugin_name( $stack['file'] ));
            $span->end($time);
        } else {
            // Wordpress Core functions
            $span = $this->inspector->startSpan('WordPress Core');
            $span->end($time);
        }

        /*echo "<pre>";
        print_r( debug_backtrace( null, 4 ) );
        echo "</pre>";*/

        return $value;
    }
}