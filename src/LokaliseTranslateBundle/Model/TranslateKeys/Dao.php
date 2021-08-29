<?php
namespace Pdchaudhary\LokaliseTranslateBundle\Model\TranslateKeys;
 
use Pimcore\Model\Dao\AbstractDao;
 
class Dao extends AbstractDao {
 
    protected $tableName = 'localise_translate_keys';
 
    /**
     * get TranslateKeys by id
     *
     * @param int|null $id
     * @throws \Exception
     */
    public function getById($id = null) {
 
        if ($id != null)
            $this->model->setId($id);
 
        $data = $this->db->fetchRow('SELECT * FROM '.$this->tableName.' WHERE id = ?', $this->model->getId());
 
        if(!$data["id"])
            throw new \Exception("Object with the ID " . $this->model->getId() . " doesn't exists");
 
        $this->assignVariablesToModel($data);
    }

      /**
     * get LocaliseTranslateObject by keyId and lang
     *
     * @param int|null $keyId
     * @param int|null $lang
     * @throws \Exception
     */
    public function getByKeyIdAndLang($keyId, $lang) {
 
        if ($keyId != null){
            $this->model->setLocalise_key_id($keyId);
        }
        if ($lang != null){
            $this->model->setLanguage($lang);
        }
 
        $data = $this->db->fetchRow('SELECT * FROM '.$this->tableName.' WHERE localise_key_id = ? and language = ?', [$this->model->getLocalise_key_id(), $this->model->getLanguage()]);
 
        if(!$data["id"])
            throw new \Exception("Object with the ID " . $this->model->getLocalise_key_id() . " doesn't exists");
 
        $this->assignVariablesToModel($data);
    }
 
    /**
     * save TranslateKeys
     */
    public function save() {
        $vars = get_object_vars($this->model);
 
        $buffer = [];
 
        $validColumns = $this->getValidTableColumns($this->tableName);
 
        if(count($vars))
            foreach ($vars as $k => $v) {
 
                if(!in_array($k, $validColumns))
                    continue;
 
                $getter = "get" . ucfirst($k);
 
                if(!is_callable([$this->model, $getter]))
                    continue;
 
                $value = $this->model->$getter();
 
                if(is_bool($value))
                    $value = (int)$value;
 
                $buffer[$k] = $value;
            }
        
        if($this->model->getId() !== null) {
            $this->db->update($this->tableName, $buffer, ["id" => $this->model->getId()]);
            return;
        }
 
        $this->db->insert($this->tableName, $buffer);
        $this->model->setId($this->db->lastInsertId());
    }
 
    /**
     * delete TranslateKeys
     */
    public function delete() {
        $this->db->delete($this->tableName, ["id" => $this->model->getId()]);
    }

     /**
     * delete TranslateKeys
     */
    public function deleteByDocID($docID) {
        $this->db->delete($this->tableName, ["translate_document_id" => $docID]);
    }
 
}