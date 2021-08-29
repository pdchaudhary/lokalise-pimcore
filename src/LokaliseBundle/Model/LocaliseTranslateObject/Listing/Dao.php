<?php
namespace Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseTranslateObject\Listing;
  
 use Pimcore\Model\Listing;
 use Pdchaudhary\LokaliseTranslateBundle\Model;
 use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
 use Pimcore\Model\Listing\Dao\QueryBuilderHelperTrait;
  
 class Dao extends Listing\Dao\AbstractDao
 {
     use QueryBuilderHelperTrait;
     
     /**
      * @var string
      */
     protected $tableName = 'localise_translate_object';
  
     /**
      * Get tableName, either for localized or non-localized data.
      *
      * @return string
      *
      * @throws \Exception
      */
     protected function getTableName()
     {
         return $this->tableName;
     }
  
     /**
      * @param string|string[]|null $columns
      *
      * @return DoctrineQueryBuilder
      */
     public function getQueryBuilder(...$columns): DoctrineQueryBuilder
     {
         $queryBuilder = $this->db->createQueryBuilder();
         $queryBuilder->select(...$columns)->from($this->tableName);
 
         $this->applyListingParametersToQueryBuilder($queryBuilder);
 
         return $queryBuilder;
     }
  
     /**
      * Loads objects from the database.
      *
      * @return Model\LocaliseTranslateObject[]
      */
     public function load()
     {
         // load id's
         $list = $this->loadIdList();
  
         $objects = array();
         foreach ($list as $o_id) {
             if ($object = Model\LocaliseTranslateObject::getById($o_id)) {
                 $objects[] = $object;
             }
         }
  
         $this->model->setData($objects);
  
         return $objects;
     }
  
     /**
      * Loads a list for the specicifies parameters, returns an array of ids.
      *
      * @return int[]
      * @throws \Exception
      */
     public function loadIdList()
     {
         try {
            $objectIds = $this->db->fetchCol(
                'SELECT id FROM '.$this->getTableName().' '.$this->getCondition().$this->getOrder().$this->getOffsetLimit(),
                $this->model->getConditionVariables()
            );
             $this->totalCount = (int) $this->db->fetchOne('SELECT FOUND_ROWS()');
  
             return array_map('intval', $objectIds);
         } catch (\Exception $e) {
             throw $e;
         }
     }
  
     /**
      * Get Count.
      *
      * @return int
      *
      * @throws \Exception
      */
     public function getCount()
     {
         $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM '.$this->getTableName().$this->getCondition().$this->getOffsetLimit(), $this->model->getConditionVariables());
  
         return $amount;
     }
  
     /**
      * Get Total Count.
      *
      * @return int
      *
      * @throws \Exception
      */
     public function getTotalCount()
     {
         $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM '.$this->getTableName().$this->getCondition(), $this->model->getConditionVariables());
  
         return $amount;
     }

     
    public function getDataByObjectIdandGroupBy($objectId) {
 
        $data = $this->db->executeQuery('SELECT * FROM '.$this->tableName.' WHERE object_id = ? group by localise_key_id ', [$objectId]);
        return $data;
    }

    public function isObjectReviewed($objectId){

        $amount = (int) $this->db->fetchOne('SELECT COUNT(*) as amount FROM '.$this->getTableName().' WHERE object_id = ? and is_pushed = 0', [$objectId]);
        return $amount;
     }

 }