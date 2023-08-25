<?php
namespace Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseKeys;
 
use Pimcore\Model\Dao\AbstractDao;
 
class Dao extends AbstractDao {
 
    protected $tableName = 'localise_keys';
 
    /**
     * get LocaliseKeys by id
     *
     * @param int|null $id
     * @throws \Exception
     */
    public function getById($id = null) {
 
        if ($id != null)
            $this->model->setId($id);
 
        $data = $this->db->fetchAssociative('SELECT * FROM '.$this->tableName.' WHERE id = ?', [$this->model->getId()]);
 
        if(!$data["id"])
            throw new \Exception("Object with the ID " . $this->model->getId() . " doesn't exists");
 
        $this->assignVariablesToModel($data);
    }


      /**
     * get LocaliseKeys by keyName
     *
     * @param string|null $keyName
     * @throws \Exception
     */
    public function getByKeyName($keyName = null) {
       // 
        if ($keyName != null)
            $this->model->setKeyName($keyName);
    
           
        $data = $this->db->fetchAssociative('SELECT * FROM '.$this->tableName.' WHERE keyName = ?', [$this->model->getKeyName()]);
      
        if(!$data["id"])
            throw new \Exception("Object with the keyname " . $this->model->getKeyName() . " doesn't exists");
 
        $this->assignVariablesToModel($data);
    }

       /**
     * get LocaliseKeys by keyName
     *
     * @param string|null $keyName
     * @throws \Exception
     */
    public function getByKeyId($keyId = null) {
        // 
         if ($keyId != null)
             $this->model->setKeyId($keyId);
     
            
         $data = $this->db->fetchAssociative('SELECT * FROM '.$this->tableName.' WHERE keyId = ?',[ $this->model->getKeyId()]);
         
         if(!$data["id"])
             throw new \Exception("Object with the keyname " . $this->model->getKeyId() . " doesn't exists");
  
         $this->assignVariablesToModel($data);
     }
 
    /**
     * save LocaliseKeys
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
     * delete LocaliseKeys
     */
    public function delete() {
        $this->db->delete($this->tableName, ["id" => $this->model->getId()]);
    }
 
}