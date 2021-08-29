<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseKeys;
 
use Pimcore\Model;
use Pimcore\Model\Paginator\PaginateListingInterface;
 
class Listing extends Model\Listing\AbstractListing implements PaginateListingInterface
{
    /**
     * List of LocaliseKeys.
     *
     * @var array
     */
    public $data = null;
 
    /**
     * @var string
     */
    public $locale;
 
    /**
     * @return array
     */
    public function getData()
    {
        if ($this->data === null) {
            $this->load();
        }
 
        return $this->data;
    }
 
    /**
     * @param array|null $data
     *
     * @return static
     */
    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }
 
    /**
     * get total count.
     *
     * @return mixed
     */
    public function count()
    {
        return $this->getTotalCount();
    }
 
    /**
     * get all items.
     *
     * @param int $offset
     * @param int $itemCountPerPage
     *
     * @return mixed
     */
    public function getItems($offset, $itemCountPerPage)
    {
        $this->setOffset($offset);
        $this->setLimit($itemCountPerPage);
 
        return $this->load();
    }
 
    /**
     * Get Paginator Adapter.
     *
     * @return $this
     */
    public function getPaginatorAdapter()
    {
        return $this;
    }
 
    /**
     * Set Locale.
     *
     * @param mixed $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
 
    /**
     * Get Locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }
     
    /**
     * Methods for Iterator.
     */
 
    /**
     * Rewind.
     */
    public function rewind()
    {
        $this->getData();
        reset($this->data);
    }
 
    /**
     * current.
     *
     * @return mixed
     */
    public function current()
    {
        $this->getData();
        $var = current($this->data);
 
        return $var;
    }
 
    /**
     * key.
     *
     * @return mixed
     */
    public function key()
    {
        $this->getData();
        $var = key($this->data);
 
        return $var;
    }
 
    /**
     * next.
     *
     * @return mixed
     */
    public function next()
    {
        $this->getData();
        $var = next($this->data);
 
        return $var;
    }
 
    /**
     * valid.
     *
     * @return bool
     */
    public function valid()
    {
        $this->getData();
        $var = $this->current() !== false;
 
        return $var;
    }
}