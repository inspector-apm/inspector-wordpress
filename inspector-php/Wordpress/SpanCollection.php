<?php


namespace Inspector\Wordpress;


use Inspector\Models\Span;

class SpanCollection
{
    protected static $collection;

    public static function all()
    {
        return self::get();
    }

    public static function get($key = null): Span
    {
        if (is_null($key)) {
            return self::$collection;
        }

        return self::$collection[$key];
    }

    public static function set($key, $span)
    {
        if(array_key_exists($key, self::$collection)){
            $item = self::get($key);
            $total = $item->getDuration() + $span->getDuration();
            self::$collection[$key] = $span->end($total);
        }

        self::$collection[] = [$key => $span];
    }
}