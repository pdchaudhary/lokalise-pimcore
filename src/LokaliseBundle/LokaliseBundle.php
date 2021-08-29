<?php

namespace Pdchaudhary\LokaliseTranslateBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;

class LokaliseBundle extends AbstractPimcoreBundle implements PimcoreBundleInterface
{
    public function getJsPaths()
    {
        return [
            '/bundles/lokalise/js/pimcore/startup.js',
            '/bundles/lokalise/js/translation/lokaliseobjectFieldsStatus.js',
            '/bundles/lokalise/js/translation/translateDocumentAll.js',
            '/bundles/lokalise/js/translation/singleTranslateObject.js',
            '/bundles/lokalise/js/translation/objectUtill.js',
        ];
    }

    public function getInstaller()
    {
        return $this->container->get(Installer::class);
    }

    public function getNiceName()
    {
        return 'Lokalise Translate Bundle';
    }


    public function getEditmodeJsPaths(){
        return [
      
        ];
    }

    public function getCssPaths()
    {
        return [
            '/bundles/lokalise/css/index.css'
        ];
    }

    public function getVersion(){
        return "1.0";
    }
   
}