<?php

namespace Pdchaudhary\LokaliseTranslateBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use PackageVersions\Versions;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class LokaliseTranslateBundle extends AbstractPimcoreBundle implements PimcoreBundleInterface
{

    use PackageVersionTrait;
    const PACKAGE_NAME = 'pdchaudhary/lokalise-pimcore';
    const BUNDLE_NAME = 'LokaliseTranslateBundle';
    
    public function getJsPaths()
    {
        return [
            '/bundles/lokalisetranslate/js/pimcore/startup.js',
            '/bundles/lokalisetranslate/js/translation/lokaliseobjectFieldsStatus.js',
            '/bundles/lokalisetranslate/js/translation/translateDocumentAll.js',
            '/bundles/lokalisetranslate/js/translation/singleTranslateObject.js',
            '/bundles/lokalisetranslate/js/translation/objectUtill.js',
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
            '/bundles/lokalisetranslate/css/index.css'
        ];
    }

    public function getVersion(){
        return "2.0";
    }

    public static function getSolutionVersion(){
        //code duplication from PackageVersionTrait... sorry
        $version = Versions::getVersion(self::PACKAGE_NAME);

        // normalizes v2.3.0@9e016f4898c464f5c895c17993416c551f1697d3 to 2.3.0
        $version = preg_replace('/^v/', '', $version);
        $version = preg_replace('/@(.+)$/', '', $version);

        return $version;
    }

    /**
     * Returns the composer package name used to resolve the version
     *
     * @return string
     */
    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }
   
}