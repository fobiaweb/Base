<?php
/**
 * Exception class  - Exception.php file
 *
 * @author     Dmitryi Tyurin <fobia3d@gmail.com>
 * @copyright  (c) 2014 Dmitryi Tyurin
 */

namespace Fobia\Base;

/**
 * Exception class
 *
 * @package		Fobia.Base
 */
class Exception extends \Exception
{
    protected $statusCode;

    public function __construct($message, $code = 1, $statusCode = 400)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $code, null);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getArray()
    {
        return array(
            "error_msg" => $this->getMessage(),
            "error_code" => $this->getCode(),
        );
    }
}
