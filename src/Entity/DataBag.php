<?php

namespace Drutiny\Entity;

use Drutiny\Entity\Exception\DataCircularReferenceException;
use Drutiny\Entity\Exception\DataNotFoundException;

/**
 * Holds data.
 */
class DataBag implements ExportableInterface
{
    protected $data = [];
    protected $resolved = false;

    /**
     * Clears all data.
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * Adds data to the service container data.
     *
     * @param array $data An array of data
     */
    public function add(array $data)
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function export():array
    {
        return array_map(function ($data) {
            if ($data instanceof ExportableInterface) {
                return $data->export();
            }

            return $data;
        }, $this->data);
    }

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __isset(string $name)
    {
        return \array_key_exists($name, $this->data);
    }

    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name)
    {
        if (!\array_key_exists($name, $this->data)) {
            if (!$name) {
                throw new DataNotFoundException($name);
            }

            $alternatives = [];
            foreach ($this->data as $key => $dataValue) {
                $lev = levenshtein($name, $key);
                if ($lev <= \strlen($name) / 3 || false !== strpos($key, $name)) {
                    $alternatives[] = $key;
                }
            }

            $nonNestedAlternative = null;
            if (!\count($alternatives) && false !== strpos($name, '.')) {
                $namePartsLength = array_map('strlen', explode('.', $name));
                $key = substr($name, 0, -1 * (1 + array_pop($namePartsLength)));
                while (\count($namePartsLength)) {
                    if ($this->has($key)) {
                        if (\is_array($this->get($key))) {
                            $nonNestedAlternative = $key;
                        }
                        break;
                    }

                    $key = substr($key, 0, -1 * (1 + array_pop($namePartsLength)));
                }
            }

            throw new DataNotFoundException($name, null, null, null, $alternatives, $nonNestedAlternative);
        }

        return $this->data[$name];
    }

    /**
     * Sets a service container data.
     *
     * @param string $name  The data name
     * @param mixed  $value The data value
     */
    public function set(string $name, $value)
    {
        $this->data[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name)
    {
        return \array_key_exists((string) $name, $this->data);
    }

    /**
     * Removes a data.
     *
     * @param string $name The data name
     */
    public function remove(string $name)
    {
        unset($this->data[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve()
    {
        if ($this->resolved) {
            return;
        }

        $data = [];
        foreach ($this->data as $key => $value) {
            try {
                $value = $this->resolveValue($value);
                $data[$key] = $this->unescapeValue($value);
            } catch (DataNotFoundException $e) {
                $e->setSourceKey($key);

                throw $e;
            }
        }

        $this->data = $data;
        $this->resolved = true;
    }

    /**
     * Replaces data placeholders (%name%) by their values.
     *
     * @param mixed $value     A value
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved value
     *
     * @throws DataNotFoundException          if a placeholder references a data that does not exist
     * @throws DataCircularReferenceException if a circular reference if detected
     * @throws RuntimeException               when a given data has a type problem
     */
    public function resolveValue($value, array $resolving = [])
    {
        if (\is_array($value)) {
            $args = [];
            foreach ($value as $k => $v) {
                $args[\is_string($k) ? $this->resolveValue($k, $resolving) : $k] = $this->resolveValue($v, $resolving);
            }

            return $args;
        }

        if (!\is_string($value) || 2 > \strlen($value)) {
            return $value;
        }

        return $this->resolveString($value, $resolving);
    }

    /**
     * Resolves data inside a string.
     *
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved string
     *
     * @throws DataNotFoundException          if a placeholder references a data that does not exist
     * @throws DataCircularReferenceException if a circular reference if detected
     * @throws RuntimeException               when a given data has a type problem
     */
    public function resolveString(string $value, array $resolving = [])
    {
        // we do this to deal with non string values (Boolean, integer, ...)
        // as the preg_replace_callback throw an exception when trying
        // a non-string in a data value
        if (preg_match('/^%([^%\s]+)%$/', $value, $match)) {
            $key = $match[1];

            if (isset($resolving[$key])) {
                throw new DataCircularReferenceException(array_keys($resolving));
            }

            $resolving[$key] = true;

            return $this->resolved ? $this->get($key) : $this->resolveValue($this->get($key), $resolving);
        }

        return preg_replace_callback('/%%|%([^%\s]+)%/', function ($match) use ($resolving, $value) {
            // skip %%
            if (!isset($match[1])) {
                return '%%';
            }

            $key = $match[1];
            if (isset($resolving[$key])) {
                throw new DataCircularReferenceException(array_keys($resolving));
            }

            $resolved = $this->get($key);

            if (!\is_string($resolved) && !is_numeric($resolved)) {
                throw new \RuntimeException(sprintf('A string value must be composed of strings and/or numbers, but found data "%s" of type "%s" inside string value "%s".', $key, \gettype($resolved), $value));
            }

            $resolved = (string) $resolved;
            $resolving[$key] = true;

            return $this->isResolved() ? $resolved : $this->resolveString($resolved, $resolving);
        }, $value);
    }

    public function isResolved()
    {
        return $this->resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function escapeValue($value)
    {
        if (\is_string($value)) {
            return str_replace('%', '%%', $value);
        }

        if (\is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->escapeValue($v);
            }

            return $result;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function unescapeValue($value)
    {
        if (\is_string($value)) {
            return str_replace('%%', '%', $value);
        }

        if (\is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->unescapeValue($v);
            }

            return $result;
        }

        return $value;
    }
}
