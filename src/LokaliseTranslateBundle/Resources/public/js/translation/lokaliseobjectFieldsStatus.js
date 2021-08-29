/*
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

pimcore.registerNS("pimcore.element.lokaliseobjectFieldsStatus");
pimcore.element.lokaliseobjectFieldsStatus = Class.create({

    initialize: function (element) {

        this.element = element;

    },


    getLayout: function () {

        if (this.layout == null) {

            

            var languagestore = getLanguages();
            var columns = [
                {text: t("Field name"), sortable: false, dataIndex: 'fieldName', flex: 20},
            ];
            var fields = ["fieldName"];
            for (let index = 0; index < languagestore.length; index++) {
                columns.push({text: t(languagestore[index][1]), sortable: false, dataIndex: languagestore[index][0], flex: 20});
                fields.push(languagestore[index][0]);
            }
            var objectId = this.element.id;
            var itemsPerPage = 100000000;
            store = pimcore.helpers.grid.buildDefaultStore(
                '/admin/lokalise/object/status?objectId='+objectId,
                itemsPerPage
            );

            var grid = new Ext.grid.GridPanel({
                store: store,
                region: "center",
                columns: columns,
                columnLines: true,
                autoExpandColumn: "description",
                stripeRows: true,
                autoScroll: true,
                viewConfig: {
                    forceFit: true
                }
            });
          
            var layoutConf = {
                tabConfig: {
                    tooltip: 'Quick Translate'
                },
                id: 'lokalise-panel' + this.element.id,
                items: [grid],
                layout: "border",
                iconCls: 'lokalise-translate-icon pimcore_material_icon',
            };

            this.layout = new Ext.Panel(layoutConf);

            this.layout.on("activate", function () {
                store.load();
            }.bind(this));

        }

        return this.layout;


    },


    /* get all available languages configured in admin panel that are supported by deepl*/
    getLanguages: function () {


        var locales = pimcore.settings.websiteLanguages;

        var languages = [];

        for (var i = 0; i < locales.length; i++) {
            var langText = pimcore.available_languages[locales[i]] + " [" + locales[i] + "]";
            languages.push([locales[i], langText]);
        }
        ;

        return languages;

    },

   


});