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
use Symfony\Component\HttpFoundation\JsonResponse;
use Pdchaudhary\LokaliseTranslateBundle\Service\ProjectApiService;
use Pdchaudhary\LokaliseTranslateBundle\Service\KeyApiService;
use \Pimcore\Model\WebsiteSetting;
use Pdchaudhary\LokaliseTranslateBundle\Service\Languages;
use Pdchaudhary\LokaliseTranslateBundle\Service\ObjectHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\WorkflowHelper;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;

class ObjectController extends FrontendController
{
  

     /**
     * @Route("/admin/lokalise/object/create-key")
     */
    public function multiLanguageObjectGeneration(Request $request, KeyApiService $keyApiService,WorkflowHelper $workflowHelper){
        set_time_limit (1000);
        ini_set("default_socket_timeout", 1000); 
        $objectId = $request->get("objectId");
        $this->pushToLokalise($objectId, $keyApiService, $workflowHelper);
        return new Response('okay');
    }

    function pushToLokalise($objectId, $keyApiService,$workflowHelper){
        $projectId = ProjectApiService::getProjectIdByName("Objects");
        $object = DataObject::getById($objectId);
        $isEligible = method_exists($object,'getLocalizedfields');
        if($isEligible){
            $localiseFields = $object->getLocalizedfields();
            $fields = $localiseFields->getInternalData()['en'];
            $languages = Languages::getLanguages();
            $languageTranslations = [];
            foreach($languages as $key => $language){
                if("en" != $language){
                    $languageTranslations[] = [
                        "language_iso" => $language,
                        "translation" =>  ""
                    ];
                }
            }
            $locFields = [];
            $objectId = $object->getId();
            $tag =  $object->getKey();
            foreach($fields as $key => $field){
                if($field){
                    $totalTransaltion = [];
                    $engTransaltion = [
                        "language_iso" => "en",
                        "translation" =>  $field
                    ];
                    $totalTransaltion[] = $engTransaltion;
                    $locFields[] = [
                        "key_name" =>$objectId.'||'.$key,
                        "description" => "",
                        "platforms" => [
                            "web"
                        ],
                        "tags" => [$tag],
                        "custom_attributes" => [
                            "type" => "",
                            "elementId" => $objectId ,
                            "mainType" => 'object',
                        
                        ],
                        "translations" => $totalTransaltion
                    ];
                }
            }
            
        
            $isExistKeys = [];
            $newKeys = [];
            foreach($locFields as $key){
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
                            $localiseKeys->setElementId($objectId); 
                            $localiseKeys->setKeyName($keyItem->key_name->web); 
                            $localiseKeys->setKeyId($keyItem->key_id);
                            $localiseKeys->setType(LocaliseKeys::$objectType);
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
        
        

            $this->deleteOlderLinkedObjectKeys($objectId);
        
            foreach( $languages as $language){
                $lang = $language;
                foreach($locFields as $key){
                    $keyItem  = LocaliseKeys::getByKeyName($key['key_name']);
                    $dataValue = array_filter($key['translations'], function ($var) use ($lang) {
                        return ($var['language_iso'] == $lang);
                    });
                    $valueData = $this->getLangTranslationValue($key['translations'],$lang);
                    
                    if(NULL == $valueData){
                        $valueData = "";
                    }
                    if(NULL!= $keyItem && "en" == $lang){
                
                        $keyItem->setKeyValue($valueData);
                        $keyItem->save();
                    }

                
                    if(NULL != $keyItem){
                        $translateKeys = new LocaliseTranslateObject();
                        $translateKeys->setObject_id($objectId);
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
            $objectItem = ConcreteObject::getById((int)$objectId);
            if($objectItem){
                $workflowHelper->applyWorkFlow(ObjectHelper::WORKFLOWNAME, $objectItem, 'Sent');
            }
        }
    }






    public function deleteOlderLinkedObjectKeys($objectId){

        $list = new LocaliseTranslateObject\Listing();
        $list->setCondition("object_id = ?", $objectId);
        $objects = $list->load();
    
        if($objects){
            foreach($objects as $object){
                $object->delete();
            }
        }
        
    }




     /**
     * @Route("/admin/lokalise/object/sync-key")
     */
    public function objectTranslationSync(ObjectHelper $objectHelper){
        $keyApiService  = new KeyApiService();
      
        $projectId = ProjectApiService::getProjectIdByName("Objects");
        $translations = $keyApiService->getReviewedTranslation($projectId);
        $objectsIds = [];
        if(!empty($translations)){
            foreach($translations as $keyItem){
                $is_unverified =(int) $keyItem->is_unverified;
                $is_reviewed = (int) $keyItem->is_reviewed;
                if(1 == $is_reviewed && 0 == $is_unverified){
                    $keyId = $keyItem->key_id;
                    $lang = $keyItem->language_iso;
                    $translation = $keyItem->translation;
                    $keyData =  LocaliseKeys::getByKeyId($keyId);
                    if($keyData){
                        $objectKeyId = $keyData->getId();
                        $localiseTranslateObject = LocaliseTranslateObject::getByKeyIdAndLang($objectKeyId,$lang);               
                        
                        $keyNameObject =  $keyData->getKeyName();
                        if($localiseTranslateObject){
                            $keyNameArray = explode('||',$keyNameObject);
                            $keyName = $keyNameArray[1];
                            $itemId = $keyData->getElementId();
                            $item = DataObject::getById($itemId);
                            if($item){
                                if(!in_array($itemId,$objectsIds)){
                                    $objectsIds[] =  $itemId;
                                }
                                $item->set($keyName, $translation, $lang);
                                $item->save();
                            }
                           

                            $localiseTranslateObject->setIs_pushed(true);
                            $localiseTranslateObject->setIs_reviewed(true);
                            $localiseTranslateObject->setModified_at_timestamp($keyItem->modified_at_timestamp);
                            $localiseTranslateObject->save();
                        }
                    }
                }
            }
        }
        $objectHelper->syncWorkFlowForObjects($objectsIds);
        return new Response('okay');
    }


     /**
     * @Route("/admin/lokalise/object/updated-key")
     */
    public function multiLanguageObjectUpdate(Request $request, KeyApiService $keyApiService,WorkflowHelper $workflowHelper){
        set_time_limit (600);
        $projectId = ProjectApiService::getProjectIdByName("Objects");
        $objectId = $request->get("objectId");
        $object = DataObject::getById($objectId);
        $localiseFields = $object->getLocalizedfields();
        $fields = $localiseFields->getInternalData()['en'];
        $languages = Languages::getLanguages();
        $languageTranslations = [];
        foreach($languages as $key => $language){
            if("en" != $language){
                $languageTranslations[] = [
                    "language_iso" => $language,
                    "translation" =>  ""
                ];
            }
        }
        $locFields = [];
        $objectId = $object->getId();
        $tag =  $object->getKey();

        $isExistKeys = [];
        $newKeys = [];


        foreach($fields as $key => $field){
            if($field){
                $totalTransaltion = [];
                $keyObject = [
                    "key_name" =>$objectId.'||'.$key,
                    "description" => "",
                    "platforms" => [
                        "web"
                    ],
                    "tags" => [$tag],
                    "custom_attributes" => [
                        "type" => "",
                        "elementId" => $objectId ,
                        "mainType" => 'object'
                    ]
                ];
              

                $keyItem  = LocaliseKeys::getByKeyName($keyObject['key_name']);
                if($keyItem != NULL){
                    $keyObject['key_id'] = $keyItem->getKeyId();
                    if($keyItem->getKeyValue() != $field){
                        $totalTransaltion = $languageTranslations;
                        $engTransaltion = [
                            "language_iso" => "en",
                            "translation" =>  $field
                        ];
                        $totalTransaltion[] = $engTransaltion;
                        $keyObject['translations'] = $totalTransaltion;
                        $isExistKeys[] = $keyObject;
                        $locFields[] = $keyObject;
                    }
                }else{
                    $totalTransaltion = $languageTranslations;
                    $engTransaltion = [
                        "language_iso" => "en",
                        "translation" =>  $field
                    ];
                    $totalTransaltion[] = $engTransaltion;
                    $keyObject['translations'] = $totalTransaltion;
                    $newKeys[] = $keyObject;
                    $locFields[] = $keyObject;
                }

            
            }
        }

        if(!empty($newKeys)){
            $newcontent = $keyApiService->createKeys($projectId,$newKeys);
        }
      
        
        $keysResponse = $newcontent->keys;
        if(!empty($keysResponse)){
            foreach($keysResponse as $keyItem){
                $keyItemObject  = LocaliseKeys::getByKeyName($keyItem->key_name->web,1 );
                
                if(NULL ===  $keyItemObject){
                    $localiseKeys = new LocaliseKeys();
                    $localiseKeys->setElementId($objectId); 
                    $localiseKeys->setKeyName($keyItem->key_name->web); 
                    $localiseKeys->setKeyId($keyItem->key_id);
                    $localiseKeys->setType(LocaliseKeys::$objectType);
                    $localiseKeys->setFieldType("");
                    $localiseKeys->setKeyValue("");
                    $localiseKeys->save();
                }
            
            }
        }

        if(!empty($isExistKeys)){
            $oldContent = $keyApiService->updateKeys($projectId,$isExistKeys);
        }
    
       
        foreach( $languages as $language){
            $lang = $language;
            foreach($newKeys as $key){
                if(NULL  != $key ){
                    
                    $keyItem  = LocaliseKeys::getByKeyName($key['key_name']);
                    $valueData = $this->getLangTranslationValue($key['translations'],$lang);
                    if(NULL  == $valueData){
                        $valueData = "";
                    }
                    if(NULL != $keyItem && "en" == $lang){
                  
                        $keyItem->setKeyValue($valueData);
                        $keyItem->save();
                    }
                   
                    if(NULL != $keyItem){
                        $translateKeys = new LocaliseTranslateObject();
                        $translateKeys->setObject_id($objectId);
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

        foreach( $languages as $language){
            $lang = $language;
            foreach($isExistKeys as $key){
                if(NULL !=  $key){
                    
                    $keyItem  = LocaliseKeys::getByKeyName($key['key_name']);
                    $valueData = $this->getLangTranslationValue($key['translations'],$lang);
                    if(NULL == $valueData){
                        $valueData = "";
                    }
                    if(NULL != $keyItem && "en" == $lang ){
                  
                        $keyItem->setKeyValue($valueData);
                        $keyItem->save();
                    }
                   
                    if(NULL != $keyItem ){
                        $translateKeys =  LocaliseTranslateObject::getByKeyIdAndLang( $keyItem->getId(), $lang);
                        if(NULL == $translateKeys){
                            $translateKeys = new LocaliseTranslateObject();
                        }
                        $translateKeys->setObject_id($objectId);
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
        $objectItem = ConcreteObject::getById((int)$objectId);
        if($objectItem){
            $workflowHelper->applyWorkFlow(ObjectHelper::WORKFLOWNAME, $objectItem, 'Updated');
        }

        return new Response('okay');
    }


   



    function getLangTranslationValue($items,$lang){
        
        foreach($items as $index => $item) {
            if($item['language_iso'] == $lang){
                return $item['translation'];
            } 
        }
    }


    /**
     * @Route("/admin/lokalise/object/status", name="pimcore_admin_localise_admin_object_field_status")
     */
    public function objectFieldStatus(Request $request){
        $objectId = $request->get("objectId");
        $list = new LocaliseTranslateObject\Listing();
        $list->setCondition("object_id = ?", $objectId);
        $objectTranslations = $list->load();
        $keyIds = [];
        $statement = LocaliseTranslateObject\Listing::getDataByObjectIdandGroupBy($objectId);
     
        while ($row = $statement->fetch(\PDO::FETCH_NUM)) {
            $keyIds[$row[3]] = [ ] ;
        }

        foreach($objectTranslations as $objectTranslation){
            $keyId = $objectTranslation->getLocalise_key_id();
            $keyItem  = LocaliseKeys::getById($keyId);
            if(NULL != $keyItem){
                $keyName =  $keyItem->getKeyName();
                $keyNameArray = explode("||", $keyName);
                $keyIds[$keyId]['fieldName'] = $keyNameArray[1];
                $isPushed = " Pending ";
                if($objectTranslation->getIs_pushed() == true){
                    $isPushed = " Completed ";
                }
                $keyIds[$keyId][$objectTranslation->getLanguage()] = $isPushed;
            }

        }
   
        $keyIds = array_values($keyIds); 
    
        return JsonResponse::create([
            "success"=>true,
            "total" => count($keyIds),
            "data" =>  $keyIds
        ]);

    }


    /** 
     * @Route("/admin/lokalise/object/alowed-update")
    */
    public function isAllowedToUpdate(Request $request){
        $objectId = $request->get("objectId");
        $list = new LocaliseTranslateObject\Listing();
        $list->setCondition("object_id = ?", $objectId);
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
