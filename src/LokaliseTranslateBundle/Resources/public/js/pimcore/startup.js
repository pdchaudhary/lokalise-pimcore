pimcore.registerNS("pimcore.plugin.LokaliseTranslateBundle");

pimcore.plugin.LokaliseTranslateBundle = Class.create(pimcore.plugin.admin, {
    getClassName: function () {
        return "pimcore.plugin.LokaliseTranslateBundle";
    },

    initialize: function () {
        pimcore.plugin.broker.registerPlugin(this);
    },

    pimcoreReady: function (params, broker) {
        var settingsItems = [];
        var lokaliseLanguage = {
            text: 'Lokalise Language Syncs',
            iconCls: "pimcore_icon_language",
            handler:this.lokaliseLanguage
           
           
        };
        settingsItems.push(lokaliseLanguage);
        layoutToolbar.settingsMenu.add(settingsItems);
    },

    postOpenObject: function (object, type) {
        /* add quickTranslate icon to objects with localizedfields */
        if (type === "object") {
            if (object.data.data.hasOwnProperty("localizedfields")) {
                menuParent = object.toolbar;

                Ext.Ajax.request({
                    url: '/admin/lokalise/object/alowed-update',
                    method: 'GET',
                    params: {
                        objectId:object.id,
                    },
                    success: function (response) {

                        var isAllowedResponse = JSON.parse(response.responseText, true);
                        var items = [
                            { text: 'Create' }
                        ]
                        if(isAllowedResponse.status){
                            items.push({ text: 'Update' });
                        }

                        var menu =   {
                            xtype: 'button',
                            text: t('Lokalise Translate'),
                            iconCls: 'lokalise-translate-icon',
                            scale: 'small',
                            menu: {
                                xtype: 'menu',
                                items:items,
                                listeners: {
                                    click: function( menu, item, e, eOpts ) {
                                        if(item.text == "Create"){
                                            singleLokaliseObject(object);
                                        }
                                        if(item.text == "Update"){
                                            updateLokaliseObject(object);
                                        }
                                    }
                                }
                            }
                        };

                        menuParent.add(menu);
                        if(isAllowedResponse.status){
                           /* menuParent.add({
                                text: t('Sync All'),
                                iconCls: 'lokalise-translate-icon',
                                scale: 'small',
                                handler: function () {
                                    syncAllObjects()
                                }
                            });*/
                        }

                    }
                });

                object.tabbar.add(new pimcore.element.lokaliseobjectFieldsStatus(object, "object").getLayout());
            
            }
        }
    },


    postOpenDocument: function (document, type) {
        /* add quicktranslate button to specific document type */
        if (type == "page" || type == "snippet" || type == "printpage") {
         

            
            if (!Ext.isIE) {
                if("en" == document.data.properties["language"]["data"] ){
                    this.docBtn(document);
                }
            }


        }

    },

    docBtn: function (document) {

        var menuParent;
        menuParent = document.toolbar;


        Ext.Ajax.request({
            url: '/admin/lokalise/document/alowed-update',
            method: 'GET',
            params: {
                documentId:document.id,
            },
            success: function (response) {
                var isAllowedResponse = JSON.parse(response.responseText, true);
                var items = [
                    { text: 'Create Single' },
                    { text: 'Create All' }
                ]
                if(isAllowedResponse.status){
                    items.push({ text: 'Update' });
                }

                var menu =   {
                    xtype: 'button',
                    text: t('Lokalise Translate'),
                    iconCls: 'lokalise-translate-icon',
                    scale: 'small',
                    menu: {
                        xtype: 'menu',
                        items: items,
                        listeners: {
                            click: function( menu, item, e, eOpts ) {
                                console.log(item.text);
                                if(item.text == "Create Single"){
                                    createSingleTranslateLocaliseDocument(document);
                                }
                                if(item.text == "Create All"){
                                    createTranslateLocaliseDocument(document);
                                }
                                if(item.text == "Update"){
                                    updateLokaliseDocument(document);
                                }
                            }
                        }
                    }
                };


            
                menuParent.add(menu);
                if(isAllowedResponse.status){
                    /* menuParent.add({
                        text: t('Sync All'),
                        iconCls: 'lokalise-translate-icon',
                        scale: 'small',
                        handler: function () {
                            syncAllDocuments()
                        }
                    });*/
                }

            }
        });

       
  
    },

    lokaliseLanguage: function () {
        Ext.Ajax.request({
            url: '/admin/lokalise/document/validate-lang',
            method: "GET",
            success: function (response) {
                var responseData = Ext.decode(response.responseText);
                if (responseData.status) {
                    Ext.MessageBox.alert(t("Message"), "Languages from Pimcore have been validated and synced on Lokalise.");
                }
            }
        });
    },
});

var LokaliseTranslateBundlePlugin = new pimcore.plugin.LokaliseTranslateBundle();
