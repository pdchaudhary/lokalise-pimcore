<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Service;

use Pimcore\Tool\Admin;
use Pimcore\Tool\Session;

class Languages {

    public static $mappingWithLokalise = [
        "zh" => "zh_CN"
    ];

    public static function getLanguages(){

        $languages = \Pimcore\Tool::getValidLanguages();
        return $languages;

    }

    public static function getMappedLanguages(){

        $languages = self::getLanguages();
        $mappingWithLokalise = self::$mappingWithLokalise;
        foreach ($languages as $key => $value) {
            $lang_iso = $value;
            if( array_key_exists($value,$mappingWithLokalise) ){
                $lang_iso = $mappingWithLokalise[$value];
            }
            $langApiBlock[] = [
                "lang_iso" => $lang_iso,
                "custom_iso" => $value,
            ];
        }
        return $langApiBlock;
    }


}