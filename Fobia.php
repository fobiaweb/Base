<?php

namespace Fobia\Base;

class Fobia
{
    const VERSION = "0.2.0";

    static public $env = array();

    /**
     * A method used to test whether this class is autoloaded.
     *
     * @return bool
     */
    public static function autoloaded()
    {
        static $init = null;
        if ($init !== null) {
            return true;
        }
        $init = true;

        if (!defined('FOBIA_COMMON_FILE')) {
             require_once __DIR__ . '/Core/common.php';
        }

        spl_autoload_register(function($className) {
            $className = ltrim($className, '\\');
            $fileName  = '';
            $namespace = '';
            if ($lastNsPos = strrpos($className, '\\')) {
                $namespace = substr($className, 0, $lastNsPos);
                $className = substr($className, $lastNsPos + 1);
                $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
            }
            $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
            if (basename(__DIR__) != __NAMESPACE__) {
                $fileName = substr($fileName, 6);
            }
            $fileName = __DIR__ . '/' .$fileName;
            // Require file only if it exists. Else let other registered autoloaders worry about it.
            if (file_exists($fileName)) {
                require $fileName;
            }
        });

        return true;
    }
}
