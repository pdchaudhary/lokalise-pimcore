<?php
namespace Pdchaudhary\LokaliseTranslateBundle\Model\TranslateDocument;
 
use Pimcore\Model\Dao\AbstractDao;
 
class Dao extends AbstractDao {
 
    protected $tableName = 'localise_translate_document';
 
    /**
     * get TranslateDocument by id
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
     * get by parentDocumentId and language
     *
     * @param int|null $parentDocumentId
     * @param string|null $language
     * @throws \Exception
     */
    public function getByParentDocumentIdAndLang($parentDocumentId, $language) {
 
        if ($parentDocumentId != null){
            $this->model->setParentDocumentId($parentDocumentId);
        }
        if ($language != null){
            $this->model->setLanguage($language);
        }
 
        $data = $this->db->fetchRow('SELECT * FROM '.$this->tableName.' WHERE parentDocumentId = ? and language = ?', [$this->model->getParentDocumentId(), $this->model->getLanguage()]);
 
        if(!$data["id"])
            throw new \Exception("Doesn't exists");
 
        $this->assignVariablesToModel($data);
    }



 
    /**
     * save TranslateDocument
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
     * delete TranslateDocument
     */
    public function delete() {
        $this->db->delete($this->tableName, ["id" => $this->model->getId()]);
    }


 



     
 
}