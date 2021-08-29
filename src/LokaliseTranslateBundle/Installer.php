<?php

namespace Pdchaudhary\LokaliseTranslateBundle;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Pdchaudhary\LokaliseTranslateBundle\Service\ProjectApiService;

class Installer extends MigrationInstaller
{
    public function migrateInstall(Schema $schema, Version $version)
    {

        $projectApiService =  new ProjectApiService();
        $projectApiService->createProjectOnLokalise();

        $table = $schema->createTable('localise_translate_document');
        $table->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);

        $table->addColumn('parentDocumentId', 'integer');
        $table->addColumn('language', 'string');
        $table->addColumn('parentId', 'integer');
        $table->addColumn('key', 'string');
        $table->addColumn('navigation', 'string');
        $table->addColumn('title', 'string');
        $table->addColumn('status', 'string');
        $table->addColumn('isCreated', 'string');
        $table->setPrimaryKey(['id']);

        $table1 = $schema->createTable('localise_translate_keys');

        $table1->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $table1->addColumn('translate_document_id', 'integer');
        $table1->addColumn('language', 'string');
        $table1->addColumn('localise_key_id', 'integer');
        $table1->addColumn('valueData', 'text');
        $table1->addColumn('is_reviewed', 'integer');


        $table1->addColumn('modified_at_timestamp', 'string');
        $table1->addColumn('is_pushed', 'integer');
        $table1->setPrimaryKey(['id']);


        $table1 = $schema->createTable('localise_keys');

        $table1->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $table1->addColumn('elementId', 'string');
        $table1->addColumn('keyName', 'string');
        $table1->addColumn('keyId', 'string');
        $table1->addColumn('keyValue', 'text');
        $table1->addColumn('type', 'string');
        $table1->addColumn('fieldType', 'string');
        $table1->setPrimaryKey(['id']);


        $table1 = $schema->createTable('localise_translate_object');
        
        $table1->addColumn('id', 'integer', [
            'autoincrement' => true,
        ]);
        $table1->addColumn('object_id', 'integer');
        $table1->addColumn('language', 'string');
        $table1->addColumn('localise_key_id', 'integer');
        $table1->addColumn('valueData', 'text');
        $table1->addColumn('is_reviewed', 'integer');
        $table1->addColumn('modified_at_timestamp', 'string');
        $table1->addColumn('is_pushed', 'integer');
        $table1->setPrimaryKey(['id']);
        // or
        // $version->addSql('CREATE TABLE my_bundle ...');
    }

    public function migrateUninstall(Schema $schema, Version $version)
    {
        $schema->dropTable('localise_translate_document');
        $schema->dropTable('localise_translate_keys');
        $schema->dropTable('localise_keys');
        $schema->dropTable('localise_translate_object');
        // or
        // $version->addSql('DROP TABLE my_bundle');
    }
}