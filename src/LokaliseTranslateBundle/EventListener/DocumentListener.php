<?php
namespace Pdchaudhary\LokaliseTranslateBundle\EventListener;

use Exception;
use Pimcore\Db;
use Pimcore\Event\Model\ElementEventInterface;
use Pimcore\Event\Model\DocumentEvent;
use Pimcore\Model\Document;
use Pdchaudhary\LokaliseTranslateBundle\Controller\DocumentController;
use Pdchaudhary\LokaliseTranslateBundle\Model\LocaliseKeys;
use Pdchaudhary\LokaliseTranslateBundle\Model\TranslateDocument;
use Pdchaudhary\LokaliseTranslateBundle\Model\TranslateKeys;
use Pdchaudhary\LokaliseTranslateBundle\Service\WorkflowHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\DocumentHelper;
use Pdchaudhary\LokaliseTranslateBundle\Service\KeyApiService;
use Pdchaudhary\LokaliseTranslateBundle\Service\ProjectApiService;
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
        if (!$e instanceof DocumentEvent) {
            return;
        }

        $document = $e->getDocument();
        try {
            $this->pushTranslatedDocumentMasterUpdate($document);
        } catch(\Exception $e) {
            throw new \Exception("<b>Lokalise Language Master Push Status</b><br><b> Message: </b>".$e->getMessage());
        }

        $isAutoPushEnabled = $this->utils->isAutoPushEnabled();
        if ($isAutoPushEnabled) {
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

    private function pushTranslatedDocumentMasterUpdate(Document $document): void
    {
        if (!\Pimcore\Tool\Admin::getCurrentUser()) {
            return;
        }

        $language = (string) $document->getProperty('language');
        if ($language === '' || $language === 'en') {
            return;
        }

        $documentService = new Document\Service();
        $sourceDocumentId = (int) $documentService->getTranslationSourceId($document);
        if ($sourceDocumentId <= 0) {
            return;
        }

        $translateDocuments = new TranslateDocument\Listing();
        $translateDocuments->setCondition(
            'parentDocumentId = ? AND language = ? AND parentId = ? AND dockey = ?',
            [$sourceDocumentId, $language, $document->getParentId(), $document->getKey()]
        );
        $translateDocumentItems = $translateDocuments->load();
        $translateDocument = $translateDocumentItems[0] ?? null;
        if (!$translateDocument) {
            return;
        }

        $editableValues = $this->getDocumentEditableValues($document);
        if (empty($editableValues)) {
            return;
        }

        $lokaliseKeyUpdates = [];
        $updatedTranslateKeys = [];
        foreach ($editableValues as $editableName => $editableData) {
            $localiseKey = LocaliseKeys::getByKeyName($sourceDocumentId . '||' . $editableName);
            if (!$localiseKey) {
                continue;
            }

            $translateKeys = new TranslateKeys\Listing();
            $translateKeys->setCondition(
                'translate_document_id = ? AND localise_key_id = ? AND language = ?',
                [$translateDocument->getId(), $localiseKey->getId(), $language]
            );
            $translateKeyItems = $translateKeys->load();
            $translateKey = $translateKeyItems[0] ?? new TranslateKeys();
            $translateKey->setTranslate_document_id($translateDocument->getId());
            $translateKey->setLanguage($language);
            $translateKey->setLocalise_key_id($localiseKey->getId());
            $translateKey->setValueData($editableData['data']);
            $translateKey->setIs_reviewed(1);
            $translateKey->setModified_at_timestamp(time());
            $translateKey->setIs_pushed(0);
            $translateKey->save();
            $updatedTranslateKeys[] = $translateKey;

            $localiseKey->setFieldType($editableData['type']);
            $localiseKey->save();

            $lokaliseKeyUpdates[] = [
                'key_id' => $localiseKey->getKeyId(),
                'translations' => [
                    [
                        'language_iso' => $language,
                        'translation' => $editableData['data'],
                    ],
                ],
            ];
        }

        if (empty($lokaliseKeyUpdates)) {
            return;
        }

        $projectId = ProjectApiService::getProjectIdByName('Documents');
        $this->keyApiService->updateKeys($projectId, $lokaliseKeyUpdates);

        foreach ($updatedTranslateKeys as $translateKey) {
            $translateKey->setIs_pushed(1);
            $translateKey->save();
        }

        $translateDocument->setStatus('done');
        $translateDocument->setIsCreated(1);
        $translateDocument->save();
    }

    private function getDocumentEditableValues(Document $document): array
    {
        $db = Db::get();
        $rows = $db->fetchAllAssociative(
            "SELECT name, type, data FROM documents_editables WHERE documentId = ? AND (type = 'input' OR type = 'textarea' OR type = 'wysiwyg') AND name NOT LIKE '%style%'",
            [$document->getId()]
        );

        $editableValues = [];
        foreach ($rows as $row) {
            $editableValues[$row['name']] = [
                'type' => $row['type'],
                'data' => (string) $row['data'],
            ];
        }

        if (!empty($editableValues) || !method_exists($document, 'getEditables')) {
            return $editableValues;
        }

        foreach ($document->getEditables() as $editableName => $editable) {
            if (
                !method_exists($editable, 'getType')
                || !method_exists($editable, 'getData')
                || !in_array($editable->getType(), ['input', 'textarea', 'wysiwyg'], true)
                || str_contains((string) $editableName, 'style')
                || (method_exists($editable, 'getInherited') && $editable->getInherited())
            ) {
                continue;
            }

            $editableValues[$editableName] = [
                'type' => $editable->getType(),
                'data' => (string) $editable->getData(),
            ];
        }

        return $editableValues;
    }

    public function onPreDelete(ElementEventInterface $e)
    {
        if (!$e instanceof DocumentEvent) {
            return;
        }

        $document = $e->getDocument();
        $language = (string) $document->getProperty('language');
        if ($language === '' || $language === 'en') {
            return;
        }

        $documentService = new Document\Service();
        $sourceDocumentId = (int) $documentService->getTranslationSourceId($document);
        if ($sourceDocumentId <= 0) {
            return;
        }

        $translateDocuments = new TranslateDocument\Listing();
        $translateDocuments->setCondition(
            'parentDocumentId = ? AND language = ? AND parentId = ? AND dockey = ?',
            [$sourceDocumentId, $language, $document->getParentId(), $document->getKey()]
        );

        foreach ($translateDocuments->load() as $translateDocument) {
            $translateKeys = new TranslateKeys\Listing();
            $translateKeys->setCondition('translate_document_id = ?', [$translateDocument->getId()]);

            foreach ($translateKeys->load() as $translateKey) {
                $translateKey->delete();
            }

            $translateDocument->delete();
        }
    }
}
