<?php

namespace Inspector\Wordpress;


use Inspector\Models\Span;

class FilterWrapper
{
    /**
     * @var InspectorWrapper
     */
    protected $inspector;

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
     * @param InspectorWrapper $inspector
     * @param $hook_name
     * @param $callback_function
     * @param $priority
     * @param $accepted_args
     */
    public function __construct(
        $inspector,
        $hook_name,
        $callback_function,
        $priority,
        $accepted_args
    )
    {
        $this->inspector         = $inspector;
        $this->hook_name         = $hook_name;
        $this->priority          = $priority;
        $this->accepted_args     = $accepted_args;
        $this->callback_function = $callback_function;

        // First we need to remove the existing hook, this will be replaced with a custom filter.
        if ( ! remove_action( $this->hook_name, $this->callback_function, $this->priority ) ) {
            error_log('INSPECTOR FAILED TO REMOVE FILTER FUNCTION');
        } else {
            // Add the new callback filter. This will be used as a wrapper calling the origin callback.
            add_filter( $this->hook_name, array( $this, 'wrapper' ), $this->priority, $this->accepted_args );
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
        $value = '';
        $time_start = microtime( true );

        // Avoid the array_slice if possible.
        // We execute the origin hook function to ensure the original functionality.
        if ( $this->accepted_args == 0 ) {
            $value = call_user_func_array( $this->callback_function, array() );
        } elseif ( $this->accepted_args >= count( $args ) ) {
            $value = @call_user_func_array( $this->callback_function, $args );
        } else {
            $value = call_user_func_array( $this->callback_function, array_slice( $args, 0, (int) $this->accepted_args ) );
        }

        // Track how many time was needed to execute
        $time_end = microtime( true );
        $time     = $time_end - $time_start;

        // Load debug backtrace to get the file / folder
        // useful to identify if function was called from theme/plugin/wordpress core
        $debug_stack = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT );

        // Loop through every stack to find the root of the filter|action.
        foreach ( $debug_stack as $stack ) {
            if ( isset( $stack['file'] ) && ( strpos( $stack['file'], 'themes' ) || strpos( $stack['file'], 'plugins' ) ) ) {
                $this->generateSpan($stack, $time);
                break;
            }
        }

        // After tracking return the original value to preserve original functionality
        return $value;
    }

    /**
     * Add the time to the found plugin|theme. If not found we use the unknown keyword to track the time.
     *
     * @param $stack
     * @param $time
     */
    public function generateSpan($stack, $time)
    {
        if ( strpos( $stack['file'], 'themes' ) ) {
            // Theme functions
            $type = 'Theme';
        } else if ( strpos( $stack['file'], 'plugins' ) ) {
            // Plugin functions
            $type = Helper::get_plugin_name($stack['file']);
        } else {
            // Wordpress Core functions
            $type = 'WordPress Core';
        }

        $span = new Span($type, $this->inspector->currentTransaction());
        $span->start()->end($time);

        SpanCollection::set($type, $span);
    }
}