<?php
/**
 * AutoloadConfig class  - AutoloadConfig.php file
 *
 * @author     Dmitriy Tyurin <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2014 Dmitriy Tyurin
 */

namespace Fobia\Base;

/**
 * AutoloadConfig class
 *
 * @package   Fobia\Base
 */
class AutoloadConfig extends \Pimple
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