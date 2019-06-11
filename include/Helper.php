<?php

class Helper
{
    /**
     * Files already found
     */
    private static $cached_files = array();

    /**
     * The folders we are looking for
     * @var array
     */
    private static $folders = array(
        'plugins'   => 'plugins',
        'wpcontent' => 'wp-content'
    );

    /**
     * Guess a plugin's name from the file path
     *
     * @param string $path
     *
     * @return string
     */
    public static function get_plugin_name( $path ) {
        // Check the cache
        if ( isset( self::$cached_files[ $path ] ) ) {
            return self::$cached_files[ $path ];
        }

        // Trim off the base path
        $_path = realpath( $path );
        if ( false !== strpos( $_path, '/' . self::$folders['wpcontent'] . '/' . self::$folders['plugins'] . '/' ) ) {
            $_path = substr(
                $_path,
                strpos( $_path, '/' . self::$folders['wpcontent'] . '/' . self::$folders['plugins'] . '/' ) +
                strlen( '/' . self::$folders['wpcontent'] . '/' . self::$folders['plugins'] . '/' )
            );
        } elseif ( false !== stripos( $_path, '\\' . self::$folders['wpcontent'] . '\\' . self::$folders['plugins'] . '\\' ) ) {
            $_path = substr(
                $_path,
                stripos( $_path, '\\' . self::$folders['wpcontent'] . '\\' . self::$folders['plugins'] . '\\' ) +
                strlen( '\\' . self::$folders['wpcontent'] . '\\' . self::$folders['plugins'] . '\\' )
            );
        }

        // Grab the plugin name as a folder or a file
        if ( false !== strpos( $_path, DIRECTORY_SEPARATOR ) ) {
            $plugin = substr( $_path, 0, strpos( $_path, DIRECTORY_SEPARATOR ) );
        } else {
            $plugin = substr( $_path, 0, stripos( $_path, '.php' ) );
        }

        // Save it to the cache
        self::$cached_files[ $path ] = $plugin;

        // Return
        return $plugin;
    }
}