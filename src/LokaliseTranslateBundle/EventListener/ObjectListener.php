<?php
namespace Pdchaudhary\LokaliseTranslateBundle\EventListener;

use Exception;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DataObjectEvent;
use Pdchaudhary\LokaliseTranslateBundle\Controller\ObjectController;
use Pdchaudhary\LokaliseTranslateBundle\Service\WorkflowHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\ObjectHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\KeyApiService;
use Pdchaudhary\LokaliseTranslateBundle\Service\Utils;

class ObjectListener {

    public function __construct(WorkflowHelper $workflowHelper, ObjectHelper $objectHelper,KeyApiService $keyApiService,Utils $utils)
    {
  
        $this->workflowHelper = $workflowHelper;
        $this->objectHelper = $objectHelper;
        $this->keyApiService = $keyApiService;
        $this->utils = $utils;
    }
     
    public function onPostUpdate (ElementEventInterface $e) {
       
        $isAutoPushEnabled = $this->utils->isAutoPushEnabled();
        if ($e instanceof DataObjectEvent && $isAutoPushEnabled) {
            $object = $e->getObject();
            $objectController = new ObjectController();
            try{
                $objectController->pushToLokalise($object->getId(), $this->keyApiService,$this->workflowHelper);
            }catch(\Exception $e){
                throw new \Exception("<b>Lokalise Auto Push Status</b><br><b> Message: </b>".$e->getMessage());
            }
            
        }
    }
}