<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Service;

use Pimcore\Model\WebsiteSetting;

class Utils {

    public function isAutoPushEnabled(){
        $autoPushObject = WebsiteSetting::getByName("lokalise_auto_push");
        $autoPush = $autoPushObject ? $autoPushObject->getData() : null;
        return $autoPush;
    }
}