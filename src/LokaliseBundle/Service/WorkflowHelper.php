<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Service;

use Symfony\Component\Workflow\Registry;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete as ConcreteObject;
use Pimcore\Model\Document;

class WorkflowHelper {

    public function __construct(Registry $workflowRegistry)
    {
        $this->workflowRegistry = $workflowRegistry;
    }

    public function applyWorkFlow($workflowName, $item, $label){
        $workflow = $this->workflowRegistry->get($item, $workflowName);
        $transitions = $workflow->getEnabledTransitions($item);
        $transitionName = "";
        foreach($transitions as  $transition){
            $transitionLabel = $transition->getOptions()['label'];
            if($label == $transitionLabel){
                $transitionName = $transition->getName();
                break;
            }
        }
        if($workflow->can($item, $transitionName)) {
            $workflow->apply($item, $transitionName);
            $item->save(); 
        }
    }

 
    public function getLatestVersion($element)
    {
        if (
            $element instanceof Document\Folder
            || $element instanceof Asset\Folder
            || $element instanceof DataObject\Folder
            || $element instanceof Document\Hardlink
            || $element instanceof Document\Link
        ) {
            return $element;
        }

        //TODO move this maybe to a service method, since this is also used in DataObjectController and DocumentControllers
        if ($element instanceof Document\PageSnippet) {
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestDoc = $latestVersion->loadData();
                if ($latestDoc instanceof Document\PageSnippet) {
                    $element = $latestDoc;
                }
            }
        }

        if ($element instanceof DataObject\Concrete) {
            $latestVersion = $element->getLatestVersion();
            if ($latestVersion) {
                $latestObj = $latestVersion->loadData();
                if ($latestObj instanceof ConcreteObject) {
                    $element = $latestObj;
                }
            }
        }

        return $element;
    }
}