<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Service;

use Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseKeys;
use Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseTranslateObject;
use Pdchaudhary\LokaliseTranslateBundle\Model\TranslateKeys;
use Pimcore\Db;

class KeyApiService extends BaseApiService{

    const PAGINATIONLIMIT = 100;

    public function getKeyApiPath($projectId){
        return '/projects/'.$projectId.'/keys';
    }

    public function createKeys($projectId,$keys){
        $body = json_encode( [ 'keys' => $keys ]);
        $apiPath = $this->getKeyApiPath($projectId);
        $response = $this->callPostApi($apiPath, $body);
        $result = json_decode($response->getContent(false));
        return $result;
    }

    public function updateKeys($projectId,$keys){
        $body = json_encode( [ 'keys' => $keys ]);
        $apiPath = $this->getKeyApiPath($projectId);
        $response = $this->callPutApi($apiPath, $body);
        $result = json_decode($response->getContent(false));
        return $result;
    }

    public function deleteKeys($projectId,$keyIds){
        $body = json_encode( [ 'keys' => $keyIds ]);
        $apiPath = $this->getKeyApiPath($projectId);
        $response = $this->callDeleteApi($apiPath, $body);
        $result = json_decode($response->getContent(false));
        return $result;
    }


    public function getKeySingle($projectId,$keyId){
        $apiPath = $this->getKeyApiPath($projectId);
        $apiPath = '/projects/'.$projectId.'/keys/'.$keyId;
        $response = $this->callGetApi($apiPath,null,false);
        return $response;
    }

    public function getKeysPaginate($projectId,$page,$limit){
        $apiPath = $this->getKeyApiPath($projectId);
        $apiPath = '/projects/'.$projectId.'/keys?include_translations=1&limit='.$limit.'&page='.$page;
        $response = $this->callGetApi($apiPath);
        return $response;
    }

    public function getReviewedTranslationBylimit($projectId,$page,$limit){
        $apiPath = '/projects/'.$projectId.'/translations?filter_is_reviewed=1&
        filter_unverified=0&disable_references=0&limit='.$limit.'&page='.$page;
        $response = $this->callGetApi($apiPath);
        return $response;
    }

    public function getReviewedTranslation($projectId){
        $transaltions = [];
        $keyLimit = self::PAGINATIONLIMIT;
        $response = $this->getReviewedTranslationBylimit($projectId,1,$keyLimit);
        $result = json_decode($response->getContent(false));
        $transaltions = array_merge($transaltions,$result->translations);
        $pageLimit = $response->getHeaders()['x-pagination-page-count'][0];
        if($pageLimit > 1 ){
            for ($page= 2; $page<=$pageLimit;$page++) {
                $response = $this->getReviewedTranslationBylimit($projectId,$page,$keyLimit);
                $result = json_decode($response->getContent(false));
                $transaltions = array_merge($transaltions,$result->translations);
            }
        }
        return $transaltions;
    }


    public function getAllkeys($projectId){
        $keys = [];
        $keyLimit = self::PAGINATIONLIMIT;
        $response = $this->getKeysPaginate($projectId,1,$keyLimit);
        $result = json_decode($response->getContent(false));
        $keys = array_merge($keys,$result->keys);
        $pageLimit = $response->getHeaders()['x-pagination-page-count'][0];
        if($pageLimit > 1 ){
            for ($page= 2; $page<=$pageLimit;$page++) {
                $response = $this->getKeysPaginate($projectId,$page,$keyLimit);
                $result = json_decode($response->getContent(false));
                $keys = array_merge($keys,$result->keys);
            }
        }
        return $keys;
    }


    public function getAllkeysById($projectId,$objectId){
        $keys = [];
        $keyLimit = self::PAGINATIONLIMIT;
        $lokalisKeyIds = LocaliseKeys\Listing::getLokaliseKeyIds($objectId,'object');
        $transaltions = [];
        if(!empty($lokalisKeyIds)){
            $lokalisKeyIdString = implode(',',$lokalisKeyIds);
            $response = $this->getKeysByName($projectId,$lokalisKeyIdString,1,$keyLimit);
            $result = json_decode($response->getContent(false));
            $keys = array_merge($keys,$result->keys);
            $pageLimit = $response->getHeaders()['x-pagination-page-count'][0];
            if($pageLimit > 1 ){
                for ($page= 2; $page<=$pageLimit;$page++) {
                    $response = $this->getKeysByName($projectId,$lokalisKeyIdString,$page,$keyLimit);
                    $result = json_decode($response->getContent(false));
                    $keys = array_merge($keys,$result->keys);
                }
            }
            $transaltions =  $this->getTranslationFromKeys($keys);
        }
        
        return $transaltions;
    }

    public function getTranslationFromKeys($keys){
        $transaltions = [];

        foreach($keys as $key){
            $transaltions = array_merge($transaltions,$key->translations); 
        }
        return $transaltions;
    }


    public function getKeysByName($projectId,$name,$page,$keyLimit){
        $apiPath = $this->getKeyApiPath($projectId);
        $apiPath = '/projects/'.$projectId.'/keys?include_translations=1&filter_key_ids='.$name.'&limit='.$keyLimit.'&page='.$page;
        $response = $this->callGetApi($apiPath);
        return $response;
    }


    public function syncAllKeys($projectId,$projectType){
        $keys = $this->getAllkeys($projectId);
     
        foreach($keys as $key){
            $keyId = $key->key_id;
            $custom_attributes = json_decode($key->custom_attributes);
            $mainType = $elementId = $fieldType = '';
            if($custom_attributes){
                $mainType = $custom_attributes->mainType;
                $elementId = $custom_attributes->elementId;
                $fieldType = $custom_attributes->type;
            }
            $transaltions = $key->translations;
            if(NULL == $fieldType){
                $fieldType = "";
            }
            $keyData =  LocaliseKeys::getByKeyId($keyId);
            if(NULL == $keyData ) {
                $enTransaltionValue = "";
                if($transaltions){
                    $enTransaltion = array_filter($transaltions, function ($var)  {
                        return ($var->language_iso == "en");
                    });
                    if(0 != count($enTransaltion)){
                        $enTransaltion = array_values($enTransaltion); 
                        $enTransaltionValue = $enTransaltion[0]->translation;
                    }  
                }
                $localiseKeys = new LocaliseKeys();
                if(empty($elementId)){
                    $elementId = 0;
                }
                if(empty($mainType)){
                    $mainType = $projectType;
                }
                $localiseKeys->setElementId($elementId); 
                $localiseKeys->setKeyName($key->key_name->web); 
                $localiseKeys->setKeyId($keyId);
                $localiseKeys->setKeyValue($enTransaltionValue);
                $localiseKeys->setFieldType($fieldType);

                $localiseKeys->setType($mainType);
                $localiseKeys->save();
            }
        }
        $list = new LocaliseKeys\Listing();
        $list->setCondition("type = ?", $projectType);
        $dbkeys = $list->load();
        foreach($dbkeys as $key){
            $response = $this->getKeySingle($projectId,$key->getKeyId());
            $result = json_decode($response->getContent(false));
            if(!empty($result->error)){
                $localiseKeyId = $key->getId();

                $docKeys = new TranslateKeys\Listing();
                $docKeys->setCondition("localise_key_id = ?", $localiseKeyId);
                $docKeysData = $docKeys->load();
                if($docKeysData ){
                    foreach($docKeysData as $docObject){
                       
                        $docObject->delete();
                    }
                }

                $docKeys = new LocaliseTranslateObject\Listing();
                $docKeys->setCondition("localise_key_id = ?", $localiseKeyId);
                $docKeysData = $docKeys->load();
                if($docKeysData ){
                    foreach($docKeysData as $docObject){

                        $docObject->delete();
                    }
                }
                
                $key->delete();

            }
           
        }

        return true;
        
    }

    public function clearDumpData(){
        $db = Db::get();
        $db->executeQuery("DELETE  FROM `localise_translate_keys` WHERE localise_key_id not in (select id from localise_keys);");
        $db->executeQuery("DELETE  FROM `localise_translate_object` WHERE localise_key_id not in (select id from localise_keys);");
        
    }

    public function syncUpAllKeysWithDb(){
        $this->clearDumpData();
        $projectDocId = ProjectApiService::getProjectIdByName("Documents");
        $projectObjectId = ProjectApiService::getProjectIdByName("Objects");
        $projectSharedId = ProjectApiService::getProjectIdByName("Shared translation");
        $keyApiService = new KeyApiService();
        $keyApiService->syncAllKeys($projectDocId,LocaliseKeys::$docType);
        $keyApiService->syncAllKeys($projectObjectId,LocaliseKeys::$objectType);
        $keyApiService->syncAllKeys($projectSharedId,LocaliseKeys::$sharedType);
    }

}