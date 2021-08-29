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


class SharedTranslationController extends FrontendController
{

 /**
     * @Route("/admin/lokalise/shared-translation/create-key")
     */
    public function sharedTranslationGeneration(){
        $keyApiService = new KeyApiService();
        $list = new Translation\Listing();
        $list = $list->load();
        $isExistKeys = [];
        $newKeys = [];
        foreach($list as $index => $item){
            $keyNamePim = $index."||".$item->getKey();
            $keyItem  = LocaliseKeys::getByKeyName($keyNamePim);
            $transaltions =$item->getTranslations();
            $translationsObject = [];
            foreach($transaltions as $key => $transaltion){
                if("en" === $key){
                    if(empty($transaltion)){
                        $transaltion = $item->getKey();
                    }
                    $translationsObject[] = [
                        "language_iso" => $key,
                        "translation" => $transaltion
                    ];
                }  
            }

            $keyObject = [
             
                'key_name' => $keyNamePim,
                "platforms" => ['web'],
                "translations" => $translationsObject,
                "tags"=> [LocaliseKeys::$sharedType],
                "custom_attributes"=>[
                   'mainType'=>LocaliseKeys::$sharedType,
                   'elementId'=> 0,
                   'type' =>  '',
                ]
            ];
            if($keyItem != NULL){
                $keyObject['key_id'] = $keyItem->getKeyId();
                $isExistKeys[] =  $keyObject;
            }else{
                $newKeys[] =  $keyObject;
            }
        }
        
        $limit = 10;
        if(count($newKeys) > $limit ){
            $total = count($newKeys);
            $totalPages = ceil( $total/ $limit );
            for ($i=1; $i <= $totalPages ; $i++) { 
                $offset = ($i - 1) * $limit;
                if( $offset < 0 ) $offset = 0;
                $newKeysData = array_slice( $newKeys, $offset, $limit );
                $this->createSharedKeys($newKeysData, $keyApiService);
            } 
        }else{
            $this->createSharedKeys($newKeys, $keyApiService);
        }

        if(count($isExistKeys) > $limit ){
            $total = count($isExistKeys);
            $totalPages = ceil( $total/ $limit );
            for ($i=1; $i <= $totalPages ; $i++) { 
                $offset = ($i - 1) * $limit;
                if( $offset < 0 ) $offset = 0;
                $isExistKeysData = array_slice( $isExistKeys, $offset, $limit );
                $this->updateSharedKeys($isExistKeysData,$keyApiService);
            } 
        }else{
            $this->updateSharedKeys($isExistKeys,$keyApiService);
        }
       

        
        return new Response('okay');
    }



    public function createSharedKeys($newKeys,$keyApiService){

        $projectId = ProjectApiService::getProjectIdByName("Shared translation");
        if(!empty($newKeys)){
            $newcontent = $keyApiService->createKeys($projectId,$newKeys);
        }
  
        $keysResponse = $newcontent->keys;

        if(!empty($keysResponse)){

            foreach($keysResponse as $keyItem){
                $keyItemObject  = LocaliseKeys::getByKeyName($keyItem->key_name->web,1 );
                
                if(NULL === $keyItemObject ){
                    $localiseKeys = new LocaliseKeys();
                    $localiseKeys->setElementId(0); 
                    $localiseKeys->setKeyName($keyItem->key_name->web); 
                    $localiseKeys->setKeyId($keyItem->key_id);
                    $localiseKeys->setKeyValue("");
                    $localiseKeys->setFieldType("");
                    $localiseKeys->setType(LocaliseKeys::$sharedType);
                    $localiseKeys->save();
                }
            
            }
        }

    }

    public function updateSharedKeys($isExistKeys, $keyApiService){

        $projectId = ProjectApiService::getProjectIdByName("Shared translation");
        if(!empty($isExistKeys)){
            $oldContent = $keyApiService->updateKeys($projectId,$isExistKeys);
        }
    }
    

     /**
     * @Route("/admin/lokalise/shared-translation/sync-key")
     */
    public function sharedTranslationSync(){
        $keyApiService = new KeyApiService();
        $projectId = ProjectApiService::getProjectIdByName("Shared translation");

        $transaltions = $keyApiService->getReviewedTranslation($projectId);
         
        if(!empty($transaltions)){
            foreach($transaltions as $keyItem){
                $is_unverified =(int) $keyItem->is_unverified;
                $is_reviewed = (int) $keyItem->is_reviewed;
                if(1 == $is_reviewed && 0 == $is_unverified){
                    $keyId = $keyItem->key_id;
                    $lang = $keyItem->language_iso;
                    $translation = $keyItem->translation;
                    $keyData =  LocaliseKeys::getByKeyId($keyId);
                    if( NULL != $keyData){
                        $keyNameObject = $keyData->getKeyName();
                        $keyNameArray = explode('||',$keyNameObject);
                        $keyName = $keyNameArray[1];
                        $transObject =  Translation::getByKey($keyName);
                        $transaltionsObject = $transObject->getTranslations();
                        $transaltionsObject[$lang] =  $translation;
                        $transObject->setTranslations($transaltionsObject);
                        $transObject->save();
                    }
                }
            }
        }

        return new Response('okay');
    }

    /**
     * @Route("/admin/lokalise/shared-translation/sync-allkey")
    */
    public function sharedTranslationSyncAll(){
        $projectApiService =  new ProjectApiService();
        $projectApiService->createProjectOnLokalise();
         return new Response('okay');
    }

    
}