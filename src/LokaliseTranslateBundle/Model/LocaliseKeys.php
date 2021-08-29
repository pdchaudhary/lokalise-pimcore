<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Model;
 
use Pimcore\Model\AbstractModel;
 
class LocaliseKeys extends AbstractModel {


    public static $docType = "document";
    public static $objectType = "object";
    public static $sharedType = "Shared translation";


 
    /**
     * @var int
     */
    public $id;
 

    /**
     * @var string
    */
    public $elementId;

 
    /**
     * @var string
     */
    public $keyName;


    /**
     * @var string
     */
    public $keyId;

     /**
     * @var string
     */
    public $keyValue;

    /**
     * @var type
     */
    public $type;

    /**
     * @var fieldType
    */
    public $fieldType;


    


 
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
     * get score by id
     *
     * @param int $id
     * @return null|self
     */
    public static function getByKeyName($keyName) {
        try {
            $obj = new self;
            $obj->getDao()->getByKeyName($keyName);
            return $obj;
        }
        catch (\Exception $ex) {
            \Pimcore\Logger::warn("Lokalise document with id $keyName not found");
        }
 
        return null;
    }

    /**
     * get score by id
     *
     * @param int $id
     * @return null|self
     */
    public static function getByKeyId($keyId) {
        try {
            $obj = new self;
            $obj->getDao()->getByKeyId($keyId);
            return $obj;
        }
        catch (\Exception $ex) {
            \Pimcore\Logger::warn("Lokalise document with id $keyId not found");
        }
 
        return null;
    }

    
 
    
 
    /**
     * @param string $keyName
     */
    public function setKeyName($keyName) {
        $this->keyName = $keyName;
    }
 
    /**
     * @return string
     */
    public function getKeyName() {
        return $this->keyName;
    }

    /**
     * @param string $elementId
     */
    public function setElementId($elementId) {
        $this->elementId = $elementId;
    }
 
    /**
     * @return string
     */
    public function getElementId() {
        return $this->elementId;
    }
    
   

    
     /**
     * @param string $keyId
     */
    public function setKeyId($keyId) {
        $this->keyId = $keyId;
    }
 
    /**
     * @return string
     */
    public function getKeyId() {
        return $this->keyId;
    }

    /**
     * @param string $keyValue
     */
    public function setKeyValue($keyValue) {
        $this->keyValue = $keyValue;
    }
 
    /**
     * @return string
     */
    public function getKeyValue() {
        return $this->keyValue;
    }

    
    /**
     * @param string $type
    */
    public function setType($type) {
        $this->type = $type;
    }
 
    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }


    /**
     * @param string $fieldType
    */
    public function setFieldType($fieldType) {
        $this->fieldType = $fieldType;
    }
 
    /**
     * @return string
     */
    public function getFieldType() {
        return $this->fieldType;
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