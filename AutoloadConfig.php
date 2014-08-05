<?php
/**
 * AutoloadConfig class  - AutoloadConfig.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2014 Dmitriy Tyurin
 */

namespace Fobia\Base;

use Fobia\Debug\Log;

/**
 * AutoloadConfig class
 *
 * @package   Fobia\Base
 */
class AutoloadConfig implements \ArrayAccess
{

    private $configDir;
    private $keys   = array();
    private $values = array();

    public function __construct($configDir)
    {
        $this->configDir = $configDir;
    }

    public function setKey($key, $file)
    {
        $this->keys[$key] = $file;
    }

    public function setKeys($keys)
    {
        $this->keys = array_merge($this->keys, (array) $keys);
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->values);
    }

    public function offsetGet($id)
    {
        if ( ! $this->offsetExists($id)) {
            if (array_key_exists($id, $this->keys)) {
                $file = $this->keys[$id];
                if (!file_exists($file)) {
                    $file = null;
                }
            } else {
                $arr = array('php', 'ini', 'yml', 'json', 'cache');
                foreach ($arr as $v) {
                    $file = $this->configDir . "/" . $id . "." . $v;
                    if (file_exists($file)) {
                        break;
                    } else {
                        $file = null;
                    }
                }
            }
            \Fobia\Debug\Log::debug(">> autoload config", array($id, $file));

            if ( ! $file ) {
                trigger_error("Нет автозагрузочной секции конфигурации '$id'" . "/$file",
                              E_USER_ERROR);
                return;
            }
            $this->values[$id] = Utils::loadConfig($file);
        }

        return $this->values[$id];
    }

    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }
}


class AutoloadConfigPimple extends \Pimple
{
    public function __construct($configDir)
    {
        parent::__construct();

        $this['configDir'] = $this->protect(function () use ($configDir) {
            return $configDir;
        });
    }

    public function offsetGet($id)
    {
        if (!$this->offsetExists($id)) {
            $this[$id] = function($c) use($id) {
                $arr = array ( 'php', 'ini', 'yml', 'json', 'cache');
                $configDir = $c['configDir']();
                foreach ($arr as $v) {
                    $file = $configDir . "/" . $id . "." . $v;
                    if (file_exists($file)) {
                        break;
                    } else {
                        $file = null;
                    }
                }
                \Fobia\Debug\Log::debug(">> autoload config", array($id, $file));

                if ( ! $file ) {
                    trigger_error("Нет автозагрузочной секции конфигурации '$id'" . "/$file",
                                  E_USER_ERROR);
                    return;
                }
                return Utils::loadConfig($file);
            };
        }

        return parent::offsetGet($id);
    }
}