<?php
namespace EasyRouter\Exception;

use Exception;

class BadRouteException extends \LogicException
{
    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link http://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param Exception $previous [optional] The previous exception used for the exception chaining. Since 5.3.0
     * @since 5.1.0
     */
    public function __construct($message = "", $route = '', $code = 0, Exception $previous = null)
    {
        $message.= ", route = {$route}";
        parent::__construct($message, $code, $previous);
    }

}