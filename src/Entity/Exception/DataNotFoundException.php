<?php

namespace Drutiny\Entity\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * This exception is thrown when a non-existent data is used.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DataNotFoundException extends \InvalidArgumentException implements NotFoundExceptionInterface
{
    private $key;
    private $sourceId;
    private $sourceKey;
    private $alternatives;
    private $nonNestedAlternative;

    /**
     * @param string      $key                  The requested data key
     * @param string      $sourceId             The service id that references the non-existent data
     * @param string      $sourceKey            The data key that references the non-existent data
     * @param \Throwable  $previous             The previous exception
     * @param string[]    $alternatives         Some data name alternatives
     * @param string|null $nonNestedAlternative The alternative data name when the user expected dot notation for nested data
     */
    public function __construct(string $key, string $sourceId = null, string $sourceKey = null, \Throwable $previous = null, array $alternatives = [], string $nonNestedAlternative = null)
    {
        $this->key = $key;
        $this->sourceId = $sourceId;
        $this->sourceKey = $sourceKey;
        $this->alternatives = $alternatives;
        $this->nonNestedAlternative = $nonNestedAlternative;

        parent::__construct('', 0, $previous);

        $this->updateRepr();
    }

    public function updateRepr()
    {
        if (null !== $this->sourceId) {
            $this->message = sprintf('The service "%s" has a dependency on a non-existent data "%s".', $this->sourceId, $this->key);
        } elseif (null !== $this->sourceKey) {
            $this->message = sprintf('The data "%s" has a dependency on a non-existent data "%s".', $this->sourceKey, $this->key);
        } else {
            $this->message = sprintf('You have requested non-existent data "%s".', $this->key);
        }

        if ($this->alternatives) {
            if (1 == \count($this->alternatives)) {
                $this->message .= ' Did you mean this: "';
            } else {
                $this->message .= ' Did you mean one of these: "';
            }
            $this->message .= implode('", "', $this->alternatives).'"?';
        } elseif (null !== $this->nonNestedAlternative) {
            $this->message .= ' You cannot access nested array items, do you want to inject "'.$this->nonNestedAlternative.'" instead?';
        }
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getSourceId()
    {
        return $this->sourceId;
    }

    public function getSourceKey()
    {
        return $this->sourceKey;
    }

    public function setSourceId($sourceId)
    {
        $this->sourceId = $sourceId;

        $this->updateRepr();
    }

    public function setSourceKey($sourceKey)
    {
        $this->sourceKey = $sourceKey;

        $this->updateRepr();
    }
}
