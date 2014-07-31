<?php
/**
 * Model class  - Model.php file
 *
 * @author     Tyurin D. <fobia3d@gmail.com>
 * @copyright  Copyright (c) 2012 AC Software
 */

namespace Fobia\Base;

use \Fobia\Base\Application;

/**
 * Модель описывающая таблицу базы
 *
 * @package  Fobia.Base
 */
abstract class Model // extends \Slim\Collection
{
    /** @var array */
    static protected $_rules = array();

    public function __construct($data = null)
    {
        if (is_array($data)) {
            foreach ($data as $k => $v ) {
                $this->$k = $v;
            }
        } elseif (is_int($data) && $this->rules('id')) {
            $this->id = $data;
        }
    }

    /**
     * Возвращает правело для параметра
     *
     * @param string $name имя параметра
     * @return string|array
     */
    public function rules($name = null)
    {
        $class = get_class($this);
        return ($name !== null) ? $class::$_rules[$name] : $class::$_rules;
    }

    /**
     * Название используемого класса
     *
     * @return string
     */
    public final function getClass()
    {
        return get_class($this);
    }

    /**
     * Название таблицы
     *
     * @return string
     */
    public function getTableName()
    {
        $class = get_class($this);
        return $class::TABLE_NAME;
    }

    /**
     * Создает запись, на основе установленых значений
     * @return int ID новой строки
     */
    public function create()
    {
        $db = Application::getInstance()->db;

        $q = $db->createInsertQuery();
        $q->insertInto($this->getTableName());

        $keys = array_keys( $this->rules() );
        foreach ($keys as $k) {
            if (isset($this->$k)) {
                $q->set($k, $db->quote($this->$k));
            }
        }

        $stmt = $q->prepare();
        $stmt->execute();
        return $db->lastInsertId();
    }

    /**
     * Стичать из базы и построить объект по заданым полям (id)
     *
     * @param int|array $data
     * @return boolean
     */
    public function select($data = null)
    {
        $db = Application::getInstance()->db;

        $q = $db->createSelectQuery();
        $q->select('*')->from($this->getTableName());

        $column = 'id';
        $value = $this->$column;

        if ($data) {
            if (is_array($data)) {
                foreach ($data as $k=>$v) {
                    $q->where($q->expr->eq($k, $db->quote($v)));
                }
            } else {
                $value = $data;
                $q->where($q->expr->eq($column, $db->quote($value)));
            }
        } else {
            $q->where($q->expr->eq($column, $db->quote($value)));
        }

        $stmt = $q->prepare();
        if ($stmt->execute()) {
            if ($result = $stmt->fetch()) {
                foreach ($result as $k => $v) {
                    $this->$k = $v;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * $params
     *   pkey  - primary key (имя первичного ключа)
     *   id    - значение первичного ключа
     *
     * @param array $params
     * @return boolean
     */
    public function update($params = null)
    {
        $db = Application::getInstance()->db;
        $params = (array) $params;

        $pkey = ($params['pkey']) ? $params['pkey'] : 'id';
        $id   = ($params['id'])   ? $params['id'] : $this->$pkey;

        $q = $db->createUpdateQuery();
        $q->update($this->getTableName());

        $keys = $this->rules();
        unset($keys[$pkey]);
        $keys = array_keys($keys);

        foreach ($keys as $k) {
            if (isset($this->$k)) {
                $q->set($k, $db->quote($this->$k));
            }
        }

        $q->where($q->expr->eq($pkey, $db->quote($id)));
        $stmt = $q->prepare();
        return $stmt->execute();
    }

    /**
     * params:
     *   pkey  - primary key (имя первичного ключа)
     *   id    - значение первичного ключа
     *
     * @param array $params
     * @return boolean
     */
    public function delete($params = null)
    {
        $db = Application::getInstance()->db;
        $params = (array) $params;

        $pkey = ($params['pkey']) ? $params['pkey'] : 'id';
        $id = ($params['id']) ? $params['id'] : $this->$pkey;

        $q = $db->createDeleteQuery();
        $q->deleteFrom($this->getTableName());
        $q->where($q->expr->eq($pkey, $db->quote($id)));
        $stmt = $q->prepare();
        return $stmt->execute();
    }

    /**
     *
     * @return array
     */
    public function toArray()
    {
        $array = array();
        $keys = array_keys( $this->rules() );
        foreach ($keys as $k) {
            $array[$k] = $this->$k;
        }
        return $array;
    }
}