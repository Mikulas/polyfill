<?php
namespace Ds\Traits;

use Error;
use OutOfRangeException;
use Traversable;
use UnderflowException;

/**
 * Sequence
 *
 * @package Ds\Traits
 */
trait Sequence
{
    /**
     * @var array
     */
    private $internal = [];

    /**
     * @inheritDoc
     */
    public function __construct($values = null)
    {
        if (func_num_args()) {
            $this->push(...$values);
        }
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return $this->internal;
    }

    /**
     * @inheritdoc
     */
    public function apply(callable $callback)
    {
        foreach ($this->internal as &$value) {
            $value = $callback($value);
        }
    }

    /**
     * @inheritdoc
     */
    public function merge($values): \Ds\Sequence
    {
        if ( ! is_array($values)) {
            $values = iterator_to_array($values);
        }

        return new self(array_merge($this->internal, $values));
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return count($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function contains(...$values): bool
    {
        foreach ($values as $value) {
            if ($this->find($value) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function filter(callable $callback = null): \Ds\Sequence
    {
        return new self(array_filter($this->internal, $callback ?: 'boolval'));
    }

    /**
     * @inheritDoc
     */
    public function find($value)
    {
        return array_search($value, $this->internal, true);
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        if (empty($this->internal)) {
            throw new UnderflowException();
        }

        return $this->internal[0];
    }

    /**
     * @inheritDoc
     */
    public function get(int $index)
    {
        $this->checkRange($index);

        return $this->internal[$index];
    }

    /**
     * @inheritDoc
     */
    public function insert(int $index, ...$values)
    {
        if ($index < 0 || $index > count($this->internal)) {
            throw new OutOfRangeException();
        }

        array_splice($this->internal, $index, 0, $values);
    }

    /**
     * @inheritDoc
     */
    public function join(string $glue = null): string
    {
        return implode($glue, $this->internal);
    }

    /**
     * @inheritDoc
     */
    public function last()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        return end($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function map(callable $callback): \Ds\Sequence
    {
        return new self(array_map($callback, $this->internal));
    }

    /**
     * @inheritDoc
     */
    public function pop()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $value = array_pop($this->internal);
        $this->adjustCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function push(...$values)
    {
        if ($values) {
            array_push($this->internal, ...$values);
            $this->adjustCapacity();
        }
    }

    /**
     * @inheritDoc
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->internal, $callback, $initial);
    }

    /**
     * @inheritDoc
     */
    public function remove(int $index)
    {
        $this->checkRange($index);

        $value = array_splice($this->internal, $index, 1, null)[0];
        $this->adjustCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function reverse()
    {
        $this->internal = array_reverse($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function reversed(): \Ds\Sequence
    {
        return new self(array_reverse($this->internal));
    }

    private function normalizeRotations(int $rotations, int $count)
    {
        if ($rotations < 0) {
            return $count - (abs($rotations) % $count);
        }

        return $rotations % $count;
    }

    /**
     * @inheritDoc
     */
    public function rotate(int $rotations)
    {
        if (count($this) < 2) {
            return;
        }

        $rotations = $this->normalizeRotations($rotations, count($this));

        while ($rotations--) {
            $this->push($this->shift());
        }
    }

    /**
     * @inheritDoc
     */
    public function set(int $index, $value)
    {
        $this->checkRange($index);
        $this->internal[$index] = $value;
    }

    /**
     * @inheritDoc
     */
    public function shift()
    {
        if ($this->isEmpty()) {
            throw new UnderflowException();
        }

        $value = array_shift($this->internal);
        $this->adjustCapacity();

        return $value;
    }

    /**
     * @inheritDoc
     */
    public function slice(int $offset, int $length = null): \Ds\Sequence
    {
        if (func_num_args() === 1) {
            $length = count($this);
        }

        return new self(array_slice($this->internal, $offset, $length));
    }

    /**
     * @inheritDoc
     */
    public function sort(callable $comparator = null)
    {
        if ($comparator) {
            usort($this->internal, $comparator);
        } else {
            sort($this->internal);
        }
    }

    /**
     * @inheritDoc
     */
    public function sorted(callable $comparator = null): \Ds\Sequence
    {
        $internal = $this->internal;

        if ($comparator) {
            usort($internal, $comparator);
        } else {
            sort($internal);
        }

        return new self($internal);
    }

    /**
     * @inheritDoc
     */
    public function sum()
    {
        return array_sum($this->internal);
    }

    /**
     * @inheritDoc
     */
    public function unshift(...$values)
    {
        if ($values) {
            array_unshift($this->internal, ...$values);
            $this->adjustCapacity();
        }
    }

    /**
     *
     *
     * @param int $index
     */
    private function checkRange(int $index)
    {
        if ($index < 0 || $index >= count($this->internal)) {
            throw new OutOfRangeException();
        }
    }

    /**
     *
     */
    public function getIterator()
    {
        foreach ($this->internal as $value) {
            yield $value;
        }
    }

    /**
     * @inheritdoc
     */
    public function clear()
    {
        $this->internal = [];
        $this->capacity = self::MIN_CAPACITY;
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->push($value);
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     * @inheritdoc
     */
    public function &offsetGet($offset)
    {
        $this->checkRange($offset);
        return $this->internal[$offset];
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        // Unset should be quiet, so we shouldn't allow 'remove' to throw.
        if (is_integer($offset) && $offset >= 0 && $offset < count($this)) {
            $this->remove($offset);
        }
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        if ($offset < 0 || $offset >= count($this)) {
            return false;
        }

        return $this->get($offset) !== null;
    }
}
