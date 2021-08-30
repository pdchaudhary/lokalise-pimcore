<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Service;

use Pimcore\Db;
use Pimcore\Model\Document;
use Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseKeys;
use Pdchaudhary\LokaliseTranslateBundle\Model\TranslateKeys;

class DocumentHelper {

    const WORKFLOWNAME = 'lokalise_translation_document';

    public function getAllKeys($documentId){
        $db = Db::get();
        $document = Document::getById($documentId);
    
        $elems = $db->fetchAll("SELECT name, type, data
                                    FROM documents_editables
                                    WHERE documentId=" . $documentId . " AND (type='input' OR type='textarea' OR type='wysiwyg' ) and data!=''  AND name not LIKE '%style%'");

        if ($elems == null && $document->getContentMasterDocumentId() != null) {
            $elems = $db->fetchAll("SELECT name, type, data
                                    FROM documents_editables
                                    WHERE documentId=" . $document->getContentMasterDocumentId() . " AND (type='input' OR type='textarea' OR type='wysiwyg' ) and data!='' AND name not LIKE '%style%'");
        }
        
        
        foreach ($elems as $element) {
          
            $elements[$element["name"]] = (object)[
                "type" => $element["type"],
                "data" => $element["data"]
            ];
        }
        return $elements;
    }

    public function getApiKeyRequestById($documentId){
        $elements = $this->getAllKeys($documentId);
        $document = Document::getById($documentId);
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
        $documentKeys = [];
        foreach($elements as $key=>$element){
            $totalTransaltion = [];
            $totalTransaltion = $languageTranslations;
            $engTransaltion = [
                "language_iso" => "en",
                "translation" =>  $element->data
            ];
            $totalTransaltion[] = $engTransaltion;
            $tagText = "";
            if($document->getTitle()){
                $tagText = $document->getTitle();
            }else{
                $tagText = $document->getKey();
            }
            $keyObject = [
                "key_name" =>$documentId.'||'.$key,
                "description" => "",
                "platforms" => [
                    "web"
                ],
                "tags" => [$tagText],
                "custom_attributes" => [
                    "type" => $element->type,
                    "elementId" => $documentId ,
                    'mainType' => 'document'
                    
                ]
            ];
            $keyObject['translations'] = $totalTransaltion;
            $documentKeys[] = $keyObject;
        }

        return $documentKeys;
    }

    public function syncParentWorkFlow($workflowHelper){
        $db = Db::get();
        $data = $db->fetchAll("SELECT count(*) as count , parentDocumentId FROM `localise_translate_document` WHERE isCreated = 1 and status = 'done' group by parentDocumentId");
        $countData = $db->fetchAll("SELECT count(*) as count , parentDocumentId FROM `localise_translate_document` group by parentDocumentId");
        if($data){
            foreach($data as $value){
                if($value["count"] == $countData[0]['count']){
                    $documentId = $value["parentDocumentId"];
                    $document =  Document::getById($documentId);
                    if($document){
                        $workflowHelper->applyWorkFlow(self::WORKFLOWNAME, $document, 'Generated');
                    }
                }
            }
        }
    }

    public function getExistingDbKeys($documentId){
        $list = new LocaliseKeys\Listing();
        $list->setCondition("elementId = ?", $documentId);
        $keys = $list->load();
        $data = [];
        foreach($keys as $key){
            $keyName = $key->getKeyName();
            $elementId = $documentId;
            $keyId = $key->getKeyId();
            $id = $key->getId();
            $data[$keyName] = [
                "keyName" => $keyName,
                'elementId' => $elementId,
                'keyId' => $keyId,
                'id' => $id
            ];
        }
        return $data;

    }

    public function removeKeys($existingDbKeys,$projectId,$keyApiService){
        $keyIds = [];
        foreach($existingDbKeys as $key){
            $id = $key['id'];
            $keyIds[] = $key['keyId'];
            $keyObject = LocaliseKeys::getById($id);
            $list = new TranslateKeys\Listing();
            $list->setCondition("localise_key_id = ?", $id);
            $translateKeys = $list->load();
            foreach($translateKeys as $item){
                $item->delete();
            }
            $keyObject->delete();
        }
        if(!empty($keyIds)){
            $keyApiService->deleteKeys($projectId,$keyIds);
        }
        

    }

    public function validateDocumentList($languages,$documentData){
        foreach( $languages as $language){
            $lang = $language[0];
            $parentPath = $documentData['parent'.$lang]; 
            $key = $documentData['key'.$lang];
            $doc =  Document\Service::pathExists($parentPath.'/'.$key);
            if($doc){
                return false;
            }
        }
        return true;
    }

}