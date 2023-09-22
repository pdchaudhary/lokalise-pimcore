<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Service;

use Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseTranslateObject;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\Db;

class ObjectHelper {

    const WORKFLOWNAME = 'lokalise_translation_object';

    public function __construct(WorkflowHelper $workflowHelper)
    {
        $this->workflowHelper = $workflowHelper;
    }

    public function isObjectFullyVerified($objectId){

        $counter =  LocaliseTranslateObject\Listing::isObjectReviewed($objectId);
        if(0 == $counter){
            return true;
        }else{
            return false;
        }
        
    }

    public function syncWorkFlowForObjects($objectIds){

        foreach ($objectIds as $key => $value) {
            $objectItem = ConcreteObject::getById((int)$value);
            if($this->isObjectFullyVerified($value)){
                $this->workflowHelper->applyWorkFlow(self::WORKFLOWNAME, $objectItem, 'Verified');
            }else{
                $this->workflowHelper->applyWorkFlow(self::WORKFLOWNAME, $objectItem, 'Awaiting Pull');
            }
        }
        
    }


    public function getObjectsIds(){
        $db = Db::get();
        $elems = $db->fetchFirstColumn("SELECT id FROM `objects` where published = 1 and type != 'folder'
        ");

        return $elems;
    }

}