<?php


namespace Inspector\Wordpress;


class SpanWordpressCollection
{
    protected static $collection = [];

    public static function all()
    {
        return static::get();
    }

    public static function get($key = null)
    {
        if (is_null($key)) {
            return static::$collection;
        }

        return static::$collection[$key];
    }

    public static function set($key, $span)
    {
        if(array_key_exists($key, static::$collection)){
            $item = static::get($key);
            $total = $item->getDuration() + $span->getDuration();
            static::$collection[$key] = $span->end($total);
        }

        static::$collection[$key] = $span;
    }
}