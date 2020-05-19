<?php

namespace Drutiny\Entity\Exception;

/**
 * This exception is thrown when a circular reference in a data is detected.
 */
class DataCircularReferenceException extends \RuntimeException
{
    private $data;

    public function __construct(array $data, \Throwable $previous = null)
    {
        parent::__construct(sprintf('Circular reference detected for data "%s" ("%s" > "%s").', $data[0], implode('" > "', $data), $data[0]), 0, $previous);

        $this->data = $data;
    }

    public function getdata()
    {
        return $this->data;
    }
}
