<?php

namespace Pdchaudhary\LokaliseTranslateBundle\Migrations;

use Pdchaudhary\LokaliseTranslateBundle\LokaliseTranslateBundle;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\BundleAwareMigration;


class Version20210829000000 extends BundleAwareMigration
{
    protected function getBundleName(): string
    {
        return LokaliseTranslateBundle::BUNDLE_NAME;
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {   

      
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
 
    }
}
