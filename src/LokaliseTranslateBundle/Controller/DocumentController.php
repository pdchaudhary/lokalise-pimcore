<?php

namespace Pdchaudhary\LokaliseTranslateBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Pimcore\Db;
use Pimcore\Document as PimcoreDocument;
use Pimcore\Model\Document;
use Pimcore\Model\DataObject;
use Pdchaudhary\LokaliseTranslateBundle\Model\TranslateDocument;
use Pdchaudhary\LokaliseTranslateBundle\Model\TranslateKeys;
use Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseKeys;
use Pimcore\Model\Translation;
use Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseTranslateObject;
use Pimcore\Tool\Admin;
use Pdchaudhary\LokaliseTranslateBundle\Service\KeyApiService;
use Pdchaudhary\LokaliseTranslateBundle\Service\ProjectApiService;
use Pdchaudhary\LokaliseTranslateBundle\Service\DocumentHelper;
use Pimcore\Logger;
use Pdchaudhary\LokaliseTranslateBundle\Service\Languages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pdchaudhary\LokaliseTranslateBundle\Service\WorkflowHelper;

class DocumentController extends FrontendController
{
  
    /**
     * @Route("/admin/lokalise/document/create-key")
     */
    public function multiLanguageGeneration(Request $request, KeyApiService $keyApiService, WorkflowHelper $workflowHelper){
        set_time_limit (1000);
        ini_set("default_socket_timeout", 1000); 
        $projectId = ProjectApiService::getProjectIdByName("Documents");
        $data = $request->get("data");
        $documentDetails = $request->get("documentData");
        $documentDetails = json_decode($documentDetails, true);
        $languages = $documentDetails['language'];
        $documentData = $documentDetails['documentData'];
        $keys = json_decode($data, true);
        $isExistKeys = [];
        $newKeys = [];
       
        foreach($keys['keys'] as $key){
            $keyItem  = LocaliseKeys::getByKeyName($key['key_name']);
            if(NULL != $keyItem){
                $key['key_id'] = $keyItem->getKeyId();
                $isExistKeys[] = $key;
            }else{
                $newKeys[] = $key;
            }
        }


        if(!empty($newKeys)){
            $newcontent = $keyApiService->createKeys($projectId,$newKeys);
        

        
            $keysResponse = $newcontent->keys;
            if(!empty($keysResponse)){
                foreach($keysResponse as $keyItem){
                    $keyItemObject  = LocaliseKeys::getByKeyName($keyItem->key_name->web,1 );
                    
                    if(NULL === $keyItemObject){
                        $localiseKeys = new LocaliseKeys();
                        $localiseKeys->setElementId($documentData['translateDocId']); 
                        $localiseKeys->setKeyName($keyItem->key_name->web); 
                        $localiseKeys->setKeyId($keyItem->key_id);
                        $localiseKeys->setType(LocaliseKeys::$docType);
                        $localiseKeys->setKeyValue("");
                        $localiseKeys->setFieldType("");
                        $localiseKeys->save();
                        
                    }
                
                }
            }
        }
        
        if(!empty($isExistKeys)){
            $oldContent = $keyApiService->updateKeys($projectId,$isExistKeys); 
        }


        $this->deleteOlderLinkedDocument($documentData['translateDocId']);

        foreach( $languages as $language){
            $lang = $language[0];
            $parentPath = $documentData['parent'.$lang]; 
            $parentEl = Document\Service::getByUrl($parentPath);
            $translateDocument = new TranslateDocument();
            $translateDocument->setParentDocumentId($documentData['translateDocId']);
            $translateDocument->setLanguage($lang);
            $translateDocument->setParentId($parentEl->getId());
            $translateDocument->setKey($documentData['key'.$lang]);
            $translateDocument->setNavigation($documentData['name'.$lang]);
            $translateDocument->setTitle($documentData['title'.$lang]);
            $translateDocument->setStatus('new');
            $translateDocument->setIsCreated(0);
            $translateDocument->save();

            foreach($keys['keys'] as $key){
                $keyItem  = LocaliseKeys::getByKeyName($key['key_name']);
                $dataValue = array_filter($key['translations'], function ($var) use ($lang) {
                    return ($var['language_iso'] == $lang);
                });
                $valueData = $this->getLangTranslationValue($key['translations'],$lang);
                
                if(NULL == $valueData){
                    $valueData = "";
                }

                if(NULL != $keyItem){
                    $engValueData = $this->getLangTranslationValue($key['translations'],"en");
                    $keyItem->setFieldType($key['custom_attributes']['type']);
                    $keyItem->setKeyValue($engValueData);
                    $keyItem->save();
                }
                if(NULL != $keyItem){
                    $translateKeys = new TranslateKeys();
                    $translateKeys->setTranslate_document_id($translateDocument->getId());
                    $translateKeys->setLanguage($lang);
                    $translateKeys->setLocalise_key_id($keyItem->getId());
                    $translateKeys->setValueData($valueData);
                    $translateKeys->setIs_reviewed(0);
                    $translateKeys->setModified_at_timestamp(0);
                    $translateKeys->setIs_pushed(0);
                    $translateKeys->save();
                }
            }

        }
        $objectItem = Document::getById((int)$documentData['translateDocId']);
        if($objectItem){
            $workflowHelper->applyWorkFlow(DocumentHelper::WORKFLOWNAME, $objectItem, 'Sent');
        }
      
        return new Response('okay');
    }


    public function deleteOlderLinkedDocument($parentId){

        $list = new TranslateDocument\Listing();
        $list->setCondition("parentDocumentId = ?", $parentId);
        $documents = $list->load();
        foreach($documents as $document){
            $keys = new TranslateKeys\Listing();
            $keys->setCondition("translate_document_id = ?", $document->getId());
            $keysData = $keys->load();
            foreach($keysData as $key){
                $key->delete();
            }
            $document->delete();
        }
    }

    function getLangTranslationValue($items,$lang){
        
        foreach($items as $index => $item) {
            if($item['language_iso'] == $lang){
                return $item['translation'];
            } 
        }
        return "";
    }


      /**
     * @Route("/admin/lokalise/document/sync-key")
     */
    public function documentTranslationSync(WorkflowHelper $workflowHelper,DocumentHelper $documentHelper){
        
        $keyApiService = new KeyApiService();
        $projectId = ProjectApiService::getProjectIdByName("Documents");
        $translations = $keyApiService->getReviewedTranslation($projectId);
        if(!empty($translations)){
            foreach($translations as $keyItem){
                $is_unverified =(int) $keyItem->is_unverified;
                $is_reviewed = (int) $keyItem->is_reviewed;
                if(1 == $is_reviewed && 0 == $is_unverified){
                    $keyId = $keyItem->key_id;
                    $lang = $keyItem->language_iso;
                    $translation = $keyItem->translation;
                    $keyData =  LocaliseKeys::getByKeyId($keyId);
                    if(NULL != $keyData){
                        $keyObjectId = $keyData->getId();
                        $localiseTranslateObject = TranslateKeys::getByKeyIdAndLang($keyObjectId,$lang); 
                        if($localiseTranslateObject){
                            $localiseTranslateObject->setIs_pushed(false);
                            $localiseTranslateObject->setIs_reviewed(true);
                            $localiseTranslateObject->setValueData($translation);
                            $localiseTranslateObject->setModified_at_timestamp($keyItem->modified_at_timestamp);
                            $localiseTranslateObject->save();
                        }
                    }
                }
            }
        }
       
        $this->createDocuments( $workflowHelper );
        $this->updateDocuments( $workflowHelper );
        $documentHelper->syncParentWorkFlow( $workflowHelper );
        return new Response('okay');
    }


    public function createDocuments($workflowHelper){
        $list = new TranslateDocument\Listing();
        $list->setCondition("isCreated = ?", 0);
        $translateDocuments = $list->load();
        foreach($translateDocuments as $translateDocument){
            $translateDocumentId = $translateDocument->getId();
            $isDocumentReviewed = $this->isDocumentReviewed($translateDocumentId);
          
            if( $isDocumentReviewed ){
                $translatedKeys = $this->getDocumentKeys($translateDocumentId);
                $data = $this->saveDocument($translateDocument, $translatedKeys, $workflowHelper);
                if($data['success']){
                    $translateDocument->setIsCreated(1);
                    $translateDocument->setStatus("done");
                    $translateDocument->save();
                }
            }
        }
    }


    public function updateDocuments($workflowHelper){
        $translateDocuments = TranslateDocument\Listing::getDocumentList(1,'update');
        foreach($translateDocuments as $translateDocument){
            $translateDocumentId = $translateDocument->getId();
            $isDocumentReviewed = $this->isDocumentReviewed($translateDocumentId);
            if( $isDocumentReviewed ){
                $translatedKeys = $this->getDocumentKeys($translateDocumentId);
                $data = $this->fetchAndUpdateDocument($translateDocument, $translatedKeys,$workflowHelper);
                if($data){
                    $translateDocument->setIsCreated(1);
                    $translateDocument->setStatus("done");
                    $translateDocument->save();
                }
            }else{
                $this->setWorkflowAwaitingPull($translateDocument,$workflowHelper);
            }
        }
    }

    public function setWorkflowAwaitingPull($translateDocument,$workflowHelper){
        $parentDocument = Document::getById(intval($translateDocument->getParentId()));
        $intendedPath = $parentDocument->getRealFullPath() . '/' . $translateDocument->getKey();
        $document =  Document::getByPath($intendedPath);
        if($document){
            $workflowHelper->applyWorkFlow(DocumentHelper::WORKFLOWNAME, $document, 'Awaiting Pull');
        }
    }

    public function fetchAndUpdateDocument($translateDocument, $translatedKeys,$workflowHelper){
        $parentDocument = Document::getById(intval($translateDocument->getParentId()));
        $intendedPath = $parentDocument->getRealFullPath() . '/' . $translateDocument->getKey();
        $document =  Document::getByPath($intendedPath);
        if(NULL != $document && $translatedKeys){
            $newDoc = Document::getById($document->getId());
            $translateDoc = Document::getById($translateDocument->getParentDocumentId());
            $newDoc->setEditables($translateDoc->getEditables());

            foreach ($translatedKeys as $key => $element) {
                         
                $keyItem  = LocaliseKeys::getById($element->getLocalise_key_id());

                if(null != $keyItem){
                    $fieldType = $keyItem->getFieldType();
                    $keyNameObject =  $keyItem->getKeyName();
                    $keyNameArray = explode('||',$keyNameObject);
                    $keyName = $keyNameArray[1];
                    if(null != $fieldType){
                      
                        $newDoc->setRawEditable($keyName, $fieldType, $element->getValueData());
                    }
                }else{
                    
                    $errorMessage = "Key is not found in table";
                    Logger::debug($errorMessage);
                }
            }
            $newDoc->save();
            $workflowHelper->applyWorkFlow(DocumentHelper::WORKFLOWNAME, $newDoc, 'Verified');
            

        }
        return true;

    }

    public function isDocumentReviewed($translateDocumentId){
        $counter = TranslateKeys\Listing::isDocumentReviewed($translateDocumentId);
        if(0 == $counter){
            return true;
        }else{
            return false;
        }
    }

    public function getDocumentKeys($translateDocumentId){
        $list = new TranslateKeys\Listing();
        $list->setCondition("translate_document_id   = ?", $translateDocumentId);
        $list = $list->load();
        return $list;
    }

    public function saveDocument($translateDocument, $translatedKeys,$workflowHelper)
    {
        $success = false;
        $errorMessage = '';
       
        $mainDocument =  Document::getById(intval($translateDocument->getParentDocumentId()));
        $parentDocument = Document::getById(intval($translateDocument->getParentId()));

        $intendedPath = $parentDocument->getRealFullPath() . '/' . $translateDocument->getKey();

        if (!Document\Service::pathExists($intendedPath)) {
            $createValues = [
                'userOwner' => 1,
                'userModification' => 1,
                'published' => false
            ];

            $createValues['key'] = \Pimcore\Model\Element\Service::getValidKey($translateDocument->getKey(), 'document');
            $translationsBaseDocument = Document::getById($translateDocument->getParentDocumentId());
            if ($translateDocument->getParentDocumentId() && $translationsBaseDocument) {
                
                $createValues['template'] = $translationsBaseDocument->getTemplate();
                $createValues['controller'] = $translationsBaseDocument->getController();
              
               
            } elseif ($mainDocument && ($mainDocument->getType() == 'page' || $mainDocument->getType() == 'snippet' || $mainDocument->getType() == 'email')) {
                $createValues += Tool::getRoutingDefaults();
            }
            if($mainDocument){
                switch ($mainDocument->getType()) {
                    case 'page':
                        $document = Document\Page::create($translateDocument->getParentId(), $createValues, false);
                        $document->setTitle($translateDocument->getTitle());
                        $document->setProperty('navigation_name', 'text', $translateDocument->getNavigation(), false);
                        $document->save();
                        $success = true;
                        break;
                    case 'snippet':
                        $document = Document\Snippet::create($translateDocument->getParentId(), $createValues);
                        $success = true;
                        break;
                    case 'printpage':
                        $document = Document\Printpage::create($translateDocument->getParentId(), $createValues);
                        $success = true;
                        break;

                    default:
                        $classname = '\\Pimcore\\Model\\Document\\' . ucfirst($mainDocument->getType());

                        // this is the fallback for custom document types using prefixes
                        // so we need to check if the class exists first
                        if (!\Pimcore\Tool::classExists($classname)) {
                            $oldStyleClass = '\\Document_' . ucfirst($mainDocument->getType());
                            if (\Pimcore\Tool::classExists($oldStyleClass)) {
                                $classname = $oldStyleClass;
                            }
                        }

                        if (Tool::classExists($classname)) {
                            $document = $classname::create($translateDocument->getParentId(), $createValues);
                            try {
                                $document->save();
                                $success = true;
                            } catch (\Exception $e) {
                                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
                            }
                            break;
                        } else {
                            Logger::debug("Unknown document type, can't add [ " . $mainDocument->getType() . ' ] ');
                        }
                        break;
                }
            } else {
                $errorMessage = "prevented adding a document because base document not found. ID:".$translateDocument->getParentDocumentId();
                Logger::debug($errorMessage);
            }
        } else {
            $errorMessage = "prevented adding a document because document with same path+key [ $intendedPath ] already exists";
            Logger::debug($errorMessage);
        }

        if ($success) {
            if ($translateDocument->getParentDocumentId()) {
                $translationsBaseDocument = Document::getById($translateDocument->getParentDocumentId());

                $properties = $translationsBaseDocument->getProperties();
                $properties = array_merge($properties, $document->getProperties());
                $document->setProperties($properties);
                $document->setProperty('language', 'text', $translateDocument->getLanguage());
                $document->save();

                $service = new Document\Service();
                $service->addTranslation($translationsBaseDocument, $document);

                if ($translatedKeys) {

                    $newDoc = Document::getById($document->getId());
                    $translateDoc = Document::getById($translateDocument->getParentDocumentId());


                    $newDoc->setEditables($translateDoc->getEditables());

                   

                    foreach ($translatedKeys as $key => $element) {
                         
                        $keyItem  = LocaliseKeys::getById($element->getLocalise_key_id());
                        
                        if(null != $keyItem){
                            $fieldType = $keyItem->getFieldType();
                            $keyNameObject =  $keyItem->getKeyName();
                            $keyNameArray = explode('||',$keyNameObject);
                            $keyName = $keyNameArray[1];
                            if(null != $fieldType){
                                $newDoc->setRawEditable($keyName, $fieldType, $element->getValueData());
                            }
                        }else{
                            $errorMessage = "Key is not found in table";
                            Logger::debug($errorMessage);
                        }
                    }

                    $newDoc->save();
                    $workflowHelper->applyWorkFlow(DocumentHelper::WORKFLOWNAME, $newDoc, 'Verified');

                }
            }
            
            return [
                'success' => $success,
                'id' => $document->getId(),
                'type' => $document->getType(),
                'parentId' => $document->getParentId()
            ];

        } else {
            return [
                'success' => $success,
                'message' => $errorMessage,
              
            ];
        }
    }


    /**
     * @Route("/admin/lokalise/document/update-key")
     */
    public function multiLanguageUpdation(Request $request, KeyApiService $keyApiService,DocumentHelper $documentHelper,WorkflowHelper $workflowHelper){
        set_time_limit (600);
        $projectId = ProjectApiService::getProjectIdByName("Documents");
        $documentId = $request->get("documentId");
        $languages = Languages::getLanguages();
        $isExistKeys = [];
        $newKeys = [];
        $totalKeys = [];
        $keys = $documentHelper->getApiKeyRequestById($documentId);
        $existingDbKeys = $documentHelper->getExistingDbKeys($documentId);
       

        foreach($keys as $key){
            $keyItem  = LocaliseKeys::getByKeyName($key['key_name']);
            if(NULL != $keyItem ){
                $keyItemContent = $keyItem->getKeyValue();
                $dataValue = $this->getLangTranslationValue($key['translations'],"en");
                if($dataValue != $keyItemContent){
                    $key['key_id'] = $keyItem->getKeyId();
                    $isExistKeys[] = $key;
                    $totalKeys[] = $key;
                }
            }else{
                $newKeys[] = $key;
                $totalKeys[] = $key;
            }
            if(array_key_exists($key['key_name'],$existingDbKeys)){
                unset($existingDbKeys[$key['key_name']]);
            }
        }
    
        $documentHelper->removeKeys($existingDbKeys,$projectId,$keyApiService);

        if(!empty($newKeys)){
            $newcontent = $keyApiService->createKeys($projectId,$newKeys);
            $keysResponse = $newcontent->keys;
            if(!empty($keysResponse)){
                foreach($keysResponse as $keyItem){
                    $keyItemObject  = LocaliseKeys::getByKeyName($keyItem->key_name->web,1 );
                    
                    if($keyItemObject === NULL){
                        $localiseKeys = new LocaliseKeys();
                        $localiseKeys->setElementId($documentId); 
                        $localiseKeys->setKeyName($keyItem->key_name->web); 
                        $localiseKeys->setKeyId($keyItem->key_id);
                        $localiseKeys->setType(LocaliseKeys::$docType);
                        $localiseKeys->setKeyValue("");
                        $localiseKeys->setFieldType("");
                        $localiseKeys->save();
                        
                    }
                
                }
            }
        }

        if(!empty($isExistKeys)){
            $oldContent = $keyApiService->updateKeys($projectId,$isExistKeys); 
        }


        foreach( $languages as $lang){
       
            if("en"  == $lang){
                continue;
            }
            $translateDocument = TranslateDocument::getByParentDocumentIdAndLang($documentId,$lang);
            if(NULL != $translateDocument){
                $translateDocument->setStatus('update');
                $translateDocument->save();

                foreach($totalKeys as $key){

                    $keyItem  = LocaliseKeys::getByKeyName($key['key_name']);
                    $dataValue = array_filter($key['translations'], function ($var) use ($lang) {
                        return ($var['language_iso'] == $lang);
                    });
                    $valueData = $this->getLangTranslationValue($key['translations'],$lang);
                    
                    if(NULL !=$keyItem ){
                        $engValueData = $this->getLangTranslationValue($key['translations'],"en");
                        $keyItem->setFieldType($key['custom_attributes']['type']);
                        $keyItem->setKeyValue($engValueData);
                        $keyItem->save();

                        $translateKeys = TranslateKeys::getByKeyIdAndLang($keyItem->getId(),$lang); 
                        if(NULL == $translateKeys){
                            $translateKeys = new TranslateKeys();
                        }
                   
                        $translateKeys->setTranslate_document_id($translateDocument->getId());
                        $translateKeys->setLanguage($lang);
                        $translateKeys->setLocalise_key_id($keyItem->getId());
                        $translateKeys->setValueData($valueData);
                        $translateKeys->setIs_reviewed(0);
                        $translateKeys->setModified_at_timestamp(0);
                        $translateKeys->setIs_pushed(0);
                        $translateKeys->save();
                    }
                }
            }

        }
        $objectItem = Document::getById((int)$documentId);
        if($objectItem){
            $workflowHelper->applyWorkFlow(DocumentHelper::WORKFLOWNAME, $objectItem, 'Updated');
        }
        return new Response('okay');
    }


     /** 
     * @Route("/admin/lokalise/document/get_document_elements")
    */
    public function getDocumentElementsAction(Request $request, DocumentHelper $documentHelper)
    {
        $documentId = $request->get("id");
        $elements = $documentHelper->getAllKeys($documentId);
        return JsonResponse::create([
            "elements" => $elements,
        ]);
    }

     /** 
     * @Route("/admin/lokalise/document/validate-document")
    */
    public function validateDocument(Request $request, DocumentHelper $documentHelper)
    {
        $documentDetails = $request->get("data");
        $documentDetails = json_decode($documentDetails, true);
        $languages = $documentDetails['language'];
        $documentData = $documentDetails['documentData'];
        $data = $documentHelper->validateDocumentList($languages,$documentData);
        return JsonResponse::create([
            "status" => $data,
        ]);
        
    }

    /** 
     * @Route("/admin/lokalise/document/alowed-update")
    */
    public function isAllowedToUpdate(Request $request){
        $documentId = $request->get("documentId");
        $list = new TranslateDocument\Listing();
        $list->setCondition("parentDocumentId = ?", $documentId);
        $documents = $list->load();
        $status = false;
        if(count($documents) > 0){
            $status = true;
        }
        return JsonResponse::create([
            "status" => $status,
        ]);

    }

    
}
