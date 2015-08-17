<?php
namespace Wild\Localize\Punic\Exception;

/**
 * An exception raised when a function meets an argument of an unsupported type
 */
class NotImplemented extends \Wild\Localize\Punic\Exception
{
    protected $function;
    /**
     * Initializes the instance
     * @param string $function The function/method that's not implemented
     * @param \Exception $previous = null The previous exception used for the exception chaining
     */
    public function __construct($function, $previous = null)
    {
        $this->function = $function;
        $message = "$function is not implemented";
        parent::__construct($message, \Wild\Localize\Punic\Exception::NOT_IMPLEMENTED, $previous);
    }

    /**
     * Retrieves the name of the not implemented function/method
     * @return string
     */
    public function getFunction()
    {
        return $this->function;
    }
}
