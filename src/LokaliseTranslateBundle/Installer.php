<?php

namespace Pdchaudhary\LokaliseTranslateBundle;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pdchaudhary\LokaliseTranslateBundle\Migrations\Version20210829000000;
use Pdchaudhary\LokaliseTranslateBundle\Service\ProjectApiService;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;


class Installer extends SettingsStoreAwareInstaller
{

    public function install()
    {
        $projectApiService =  new ProjectApiService();
        $projectApiService->createProjectOnLokalise();
        $this->createTables();
        parent::install();
    }

    public function uninstall()
    {
        $tables = [
            'localise_translate_document',
            'localise_translate_keys',
            'localise_keys',
            'localise_translate_object'
        ];
        foreach ($tables as $table) {
            $this->getDb()->query("DROP TABLE IF EXISTS " . $table);
        }

        parent::uninstall();
    }

     /**
     * {@inheritdoc}
     */
    public function needsReloadAfterInstall()
    {
        return true;
    }


    protected function createTables()
    {
        $db = $this->getDb();

        $db->query(
            "CREATE TABLE IF NOT EXISTS `localise_translate_document` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `parentDocumentId` int(11) NOT NULL,
                `language` varchar(255) NOT NULL,
                `parentId` int(11) NOT NULL,
                `key` varchar(255) NOT NULL,
                `navigation` varchar(255) NOT NULL,
                `title` varchar(255) NOT NULL,
                `status` varchar(255) NOT NULL,
                `isCreated` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $db->query(
            "CREATE TABLE IF NOT EXISTS `localise_translate_keys` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `translate_document_id` int(11) NOT NULL,
                `language` varchar(255) NOT NULL,
                `localise_key_id` int(11) NOT NULL,
                `valueData` longtext NOT NULL,
                `is_reviewed` int(11) NOT NULL,
                `modified_at_timestamp` varchar(255) NOT NULL,
                `is_pushed` int(11) NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `localise_keys` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `elementId` varchar(255) NOT NULL,
                `keyName` varchar(255) NOT NULL,
                `keyId` varchar(255) NOT NULL,
                `keyValue` longtext NOT NULL,
                `type` varchar(255) NOT NULL,
                `fieldType` varchar(255) NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );

        $db->query(
            "CREATE TABLE IF NOT EXISTS `localise_translate_object` (
                `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `object_id` int(11) NOT NULL,
                `language` varchar(255) NOT NULL,
                `localise_key_id` int(11) NOT NULL,
                `valueData` longtext NOT NULL,
                `is_reviewed` int(11) NOT NULL,
                `modified_at_timestamp` varchar(255) NOT NULL,
                `is_pushed` int(11) NOT NULL,
                PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );

    }


    public function getLastMigrationVersionClassName(): ?string
    {
        return Version20210829000000::class;
    }

     /**
     * @return \Pimcore\Db\Connection|\Pimcore\Db\ConnectionInterface
     */
    protected function getDb(){
        return \Pimcore\Db::get();
    }
    

   
}