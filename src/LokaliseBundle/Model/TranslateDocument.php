<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Model;
 
use Pimcore\Model\AbstractModel;
 
class TranslateDocument extends AbstractModel {
 
    /**
     * @var int
     */
    public $id;
 
    /**
     * @var int
     */
    public $parentDocumentId;
 
    /**
     * @var string
     */
    public $language;

    /**
     * @var int
     */
    public $parentId;

    /**
     * @var string
     */
    public $key;

    /**
     * @var string
     */
    public $navigation;


    /**
     * @var string
     */
    public $title;

    /**
     * @var string
    */
    public $status;

    /**
     * @var string
    */
    public $isCreated;


 
    /**
     * get score by id
     *
     * @param int $id
     * @return null|self
     */
    public static function getById($id) {
        try {
            $obj = new self;
            $obj->getDao()->getById($id);
            return $obj;
        }
        catch (\Exception $ex) {
            \Pimcore\Logger::warn("Lokalise document with id $id not found");
        }
 
        return null;
    }

         /**
     * get by parentDocumentId and lang
     *
     * @param int $id
     * @return null|self
     */
    public static function getByParentDocumentIdAndLang($parentDocumentId, $language) {
        try {
            $obj = new self;
            $obj->getDao()->getByParentDocumentIdAndLang($parentDocumentId, $language);
            return $obj;
        }
        catch (\Exception $ex) {
            \Pimcore\Logger::warn("Lokalise document with id $parentDocumentId not found");
        }
 
        return null;
    }
    
 
    /**
     * @param int $parentDocumentId
     */
    public function setParentDocumentId($parentDocumentId) {
        $this->parentDocumentId = $parentDocumentId;
    }
 
    /**
     * @return int
     */
    public function getParentDocumentId() {
        return $this->parentDocumentId;
    }
 
    /**
     * @param string $language
     */
    public function setLanguage($language) {
        $this->language = $language;
    }
 
    /**
     * @return string
     */
    public function getLanguage() {
        return $this->language;
    }

    /**
     * @param int $parentId
     */
    public function setParentId($parentId) {
        $this->parentId = $parentId;
    }
 
    /**
     * @return int
     */
    public function getParentId() {
        return $this->parentId;
    }
    
     /**
     * @param string $key
     */
    public function setKey($key) {
        $this->key = $key;
    }
 
    /**
     * @return string
     */
    public function getKey() {
        return $this->key;
    }

     /**
     * @param string $navigation
     */
    public function setNavigation($navigation) {
        $this->navigation = $navigation;
    }
 
    /**
     * @return string
     */
    public function getNavigation() {
        return $this->navigation;
    }


    /**
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }
 
    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }

     /**
     * @param string $status
     */
    public function setStatus($status) {
        $this->status = $status;
    }
 
    /**
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

      /**
     * @param string $isCreated
     */
    public function setIsCreated($isCreated) {
        $this->isCreated = $isCreated;
    }
 
    /**
     * @return string
     */
    public function getIsCreated() {
        return $this->isCreated;
    }

 
    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }
 
    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }
}