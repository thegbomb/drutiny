<?php

namespace Drutiny\Entity;

use Drutiny\Entity\Exception\DataCircularReferenceException;
use Drutiny\Entity\Exception\DataNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drutiny\Event\DataBagEvent;
use Psr\Log\LoggerInterface;

/**
 * Holds data.
 */
class EventDispatchedDataBag extends DataBag
{

    protected $eventDispatcher;
    protected $logger;
    protected $eventPrefix = '';
    protected $object;

    public function __construct(EventDispatcher $eventDispatcher, LoggerInterface $logger)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    public function create(array $data = [])
    {
        return (new static($this->eventDispatcher, $this->logger))->setObject($this->object ?? $this)->add($data);
    }

    public function setEventPrefix($prefix)
    {
        $this->eventPrefix = $prefix;
        return $this;
    }

    protected function dispatch($action, $key = null, $value = null)
    {
      $event = new DataBagEvent($this, $key, $value);
      $event_name = array_filter([$this->eventPrefix, $key]);
      $event_name = $action . ':' . implode('.', $event_name);

      $before_type = gettype($value);
      $before_type = $before_type == 'object' ? get_class($value) : $before_type;

      //$this->logger->debug("Event '$event_name' triggered on $before_type.");
      $this->eventDispatcher->dispatch($event, $event_name);

      if ($event->getValue() !== $value) {
        $type = gettype($event->getValue());
        $type = $type == 'object' ? get_class($event->getValue()) : $type;
        //$this->logger->debug("Event '$event_name' changed value to type of $type.");
      }

      return $event->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->dispatch('clear');
        parent::clear();
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $name, $value)
    {
        parent::set($name, $this->dispatch('set', $name, $value));
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $name)
    {
        $this->dispatch('remove', $name);
        parent::remove($name);
    }

    public function setObject(object $object)
    {
        $this->object = $object;
        return $this;
    }

    public function getObject()
    {
        return $this->object;
    }
}
