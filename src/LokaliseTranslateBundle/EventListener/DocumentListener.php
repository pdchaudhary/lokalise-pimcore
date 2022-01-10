<?php
namespace Pdchaudhary\LokaliseTranslateBundle\EventListener;

use Exception;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DocumentEvent;
use Pdchaudhary\LokaliseTranslateBundle\Controller\DocumentController;
use Pdchaudhary\LokaliseTranslateBundle\Service\WorkflowHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\DocumentHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\KeyApiService;
use Pdchaudhary\LokaliseTranslateBundle\Service\Utils;

class DocumentListener {

    public function __construct(WorkflowHelper $workflowHelper, DocumentHelper $documentHelper,KeyApiService $keyApiService, Utils $utils)
    {
  
        $this->workflowHelper = $workflowHelper;
        $this->documentHelper = $documentHelper;
        $this->keyApiService = $keyApiService;
        $this->utils = $utils;
    }
     
    public function onPostUpdate (ElementEventInterface $e) {
       
        $isAutoPushEnabled = $this->utils->isAutoPushEnabled();
        if ($e instanceof DocumentEvent && $isAutoPushEnabled) {
            $document = $e->getDocument();
            $docId = $document->getId();
            $documentController = new DocumentController();
            try{
                if($documentController->toCheckAllowUpdate($docId)){
                    $documentController->pushDocumentKeyOnUpdate($docId, $this->keyApiService, $this->documentHelper, $this->workflowHelper);
                }   
            }catch(\Exception $e){
                throw new \Exception("<b>Lokalise Auto Push Status</b><br><b> Message: </b>".$e->getMessage());
            }
            
        }
    }
}