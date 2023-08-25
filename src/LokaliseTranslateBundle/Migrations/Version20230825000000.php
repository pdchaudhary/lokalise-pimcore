<?php

namespace Pdchaudhary\LokaliseTranslateBundle\Migrations;

use Pdchaudhary\LokaliseTranslateBundle\LokaliseTranslateBundle;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Migrations\BundleAwareMigration;


class Version20230825000000 extends BundleAwareMigration
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
        $this->addSql("ALTER TABLE localise_translate_document
        CHANGE COLUMN `key` dockey VARCHAR(255);");
      
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE localise_translate_document
        CHANGE COLUMN `dockey` key VARCHAR(255);");
    }
}
