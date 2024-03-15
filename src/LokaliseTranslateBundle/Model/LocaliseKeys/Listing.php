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
    public ?array $data = null;
 
    /**
     * @var string
     */
    public $locale;
 
    /**
     * @return array
     */
    public function getData(): ?array
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
    public function setData(?array $data): static
    {
        $this->data = $data;

        return $this;
    }
 
    /**
     * get total count.
     *
     * @return mixed
     */
    public function count(): int
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
    public function getItems($offset, $itemCountPerPage): array
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
    public function rewind(): void
    {
        $this->getData();
        reset($this->data);
    }
 
    /**
     * current.
     *
     * @return mixed
     */
    public function current(): mixed
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
    public function key(): string|int|null
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
    public function next(): void
    {
        $this->getData();
        next($this->data);
    }
 
    /**
     * valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        $this->getData();
        $var = $this->current() !== false;
 
        return $var;
    }

    public static function getLokaliseKeyIds($elementId, $type){

        $obj = new self;
        $data = $obj->getDao()->getLokaliseKeyIds($elementId, $type);
        return $data ;
    }
}