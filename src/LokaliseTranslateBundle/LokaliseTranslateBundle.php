<?php

namespace Pdchaudhary\LokaliseTranslateBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\PimcoreBundleInterface;
use PackageVersions\Versions;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;

class LokaliseTranslateBundle extends AbstractPimcoreBundle implements PimcoreBundleInterface, PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;
    use PackageVersionTrait;
    const PACKAGE_NAME = 'pdchaudhary/lokalise-pimcore';
    const BUNDLE_NAME = 'LokaliseTranslateBundle';
    
    public function getJsPaths(): array
    {
        return [
            '/bundles/lokalisetranslate/js/pimcore/startup.js',
            '/bundles/lokalisetranslate/js/translation/lokaliseobjectFieldsStatus.js',
            '/bundles/lokalisetranslate/js/translation/translateDocumentAll.js',
            '/bundles/lokalisetranslate/js/translation/translateDocumentSingle.js',
            '/bundles/lokalisetranslate/js/translation/singleTranslateObject.js',
            '/bundles/lokalisetranslate/js/translation/objectUtill.js',
        ];
    }

    public function getInstaller(): ?InstallerInterface
    {
        return $this->container->get(Installer::class);
    }

    public function getNiceName(): string
    {
        return 'Lokalise Translate Bundle';
    }


    public function getEditmodeJsPaths(): array
    {
        return [
            '/bundles/lokalisetranslate/js/pimcore/startup.js',
            '/bundles/lokalisetranslate/js/translation/lokaliseobjectFieldsStatus.js',
            '/bundles/lokalisetranslate/js/translation/translateDocumentAll.js',
            '/bundles/lokalisetranslate/js/translation/translateDocumentSingle.js',
            '/bundles/lokalisetranslate/js/translation/singleTranslateObject.js',
            '/bundles/lokalisetranslate/js/translation/objectUtill.js',
        ];
    }

    public function getCssPaths(): array
    {
        return [
            '/bundles/lokalisetranslate/css/index.css'
        ];
    }

    public function getVersion(): string 
    {
        return "3.0";
    }

    public static function getSolutionVersion(): string 
    {
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