<?php
namespace Surikat\I18n\Punic\Exception;

/**
 * An exception raised when an data file was not read
 */
class DataFileNotReadable extends \Surikat\I18n\Punic\Exception
{
    protected $dataFilePath;

    /**
     * Initializes the instance
     * @param string $dataFilePath The path to the unreadable file
     * @param \Exception $previous = null The previous exception used for the exception chaining
     */
    public function __construct($dataFilePath, $previous = null)
    {
        $this->dataFilePath = $dataFilePath;
        $message = "Unable to read from the data file '$dataFilePath'";
        parent::__construct($message, \Surikat\I18n\Punic\Exception::DATA_FILE_NOT_READABLE, $previous);
    }

    /**
     * Retrieves the path to the unreadable file
     * @return string
     */
    public function getDataFilePath()
    {
        return $this->dataFilePath;
    }

}
