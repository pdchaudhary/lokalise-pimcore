<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Model;
 
use Pimcore\Model\AbstractModel;
 
class TranslateKeys extends AbstractModel {
 
    /**
     * @var int
     */
    public $id;
 
    /**
     * @var int
     */
    public $translate_document_id;
 
    /**
     * @var string
     */
    public $language;


    /**
     * @var int
     */
    public $localise_key_id;

    /**
     * @var string
     */
    public $valueData;


    /**
     * @var int
     */
    public $is_reviewed;

    /**
     * @var string
    */
    public $modified_at_timestamp;

    /**
     * @var int
    */
    public $is_pushed;


 
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
     * get by keyId and lang
     *
     * @param int $id
     * @return null|self
     */
    public static function getByKeyIdAndLang($keyId, $lang) {
        try {
            $obj = new self;
            $obj->getDao()->getByKeyIdAndLang($keyId, $lang);
            return $obj;
        }
        catch (\Exception $ex) {
            \Pimcore\Logger::warn("Lokalise document with id $keyId not found");
        }
 
        return null;
    }

    
  

    
 
    /**
     * @param int $translate_document_id
     */
    public function setTranslate_document_id($translate_document_id) {
        $this->translate_document_id = $translate_document_id;
    }
 
    /**
     * @return int
     */
    public function getTranslate_document_id() {
        return $this->translate_document_id;
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
     * @param int $localise_key_id
     */
    public function setLocalise_key_id($localise_key_id) {
        $this->localise_key_id = $localise_key_id;
    }
 
    /**
     * @return int
     */
    public function getLocalise_key_id() {
        return $this->localise_key_id;
    }

     /**
     * @param string $valueData
     */
    public function setValueData($valueData) {
        $this->valueData = $valueData;
    }
 
    /**
     * @return string
     */
    public function getValueData() {
        return $this->valueData;
    }


    /**
     * @param int $is_reviewed
     */
    public function setIs_reviewed($is_reviewed) {
        $this->is_reviewed = $is_reviewed;
    }
 
    /**
     * @return int
     */
    public function getIs_reviewed() {
        return $this->is_reviewed;
    }

     /**
     * @param string $modified_at_timestamp
     */
    public function setModified_at_timestamp($modified_at_timestamp) {
        $this->modified_at_timestamp = $modified_at_timestamp;
    }
 
    /**
     * @return string
     */
    public function getModified_at_timestamp() {
        return $this->modified_at_timestamp;
    }

      /**
     * @param string $is_pushed
     */
    public function setIs_pushed($is_pushed) {
        $this->is_pushed = $is_pushed;
    }
 
    /**
     * @return string
     */
    public function getIs_pushed() {
        return $this->is_pushed;
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