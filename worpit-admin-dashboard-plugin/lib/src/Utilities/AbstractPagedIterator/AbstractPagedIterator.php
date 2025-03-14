<?php

namespace FernleafSystems\Wordpress\Plugin\iControlWP\Utilities\AbstractPagedIterator;

abstract class AbstractPagedIterator implements \Countable, \ArrayAccess, \Iterator
{
    /**
     * @var array
     */
    protected $cachedPages = [];

    /**
     * @var array
     */
    protected $currentPage;

    /**
     * @var int
     */
    protected $currentPageNumber;

    /**
     * @var bool
     */
    protected $useCache = true;

    /**
     * @var int
     */
    protected $index = 0;

    /**
     * @return int
     */
    abstract public function getPageSize();

    /**
     * @return int
     */
    abstract public function getTotalSize();

    /**
     * @param int $pageNumber
     * @return array
     */
    abstract public function getPage($pageNumber);

    /**
     * @return int
     */
	#[\ReturnTypeWillChange]
    public function count()
    {
        return $this->getTotalSize();
    }

    /**
     * @param int $offset
     * @return bool
     */
	#[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $offset >= 0 && $offset < $this->getTotalSize();
    }

    /**
     * @param int $offset
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \OutOfBoundsException
     */
	#[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        if (!is_int($offset)) {
            throw new \InvalidArgumentException("Index must be a positive integer: $offset");
        }
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException("Index out of bounds: $offset");
        }

        $page = (int) ($offset / $this->getPageSize());
        if ($this->useCache) {
            if (!array_key_exists($page, $this->cachedPages)) {
                $this->cachedPages[$page] = $this->getPage($page);
            }
            return $this->cachedPages[$page][$offset % $this->getPageSize()];
        }

        if ($page !== $this->currentPageNumber) {
            $this->currentPageNumber = $page;
            $this->currentPage = $this->getPage($page);
        }
        return $this->currentPage[$offset % $this->getPageSize()];
    }

    /**
     * @param int $offset
     * @param mixed $value
     * @throws \LogicException
     */
	#[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        throw new \LogicException("Setting values is not allowed.");
    }

    /**
     * @param int $offset
     */
	#[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        throw new \LogicException("Unsetting values is not allowed.");
    }

	#[\ReturnTypeWillChange]
    public function current()
    {
        return $this->offsetGet($this->index);
    }

	#[\ReturnTypeWillChange]
    public function key()
    {
        return $this->index;
    }

	#[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->index;
    }

	#[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->index = 0;
    }

	#[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->offsetExists($this->index);
    }
}
