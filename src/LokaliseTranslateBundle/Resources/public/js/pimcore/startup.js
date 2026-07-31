pimcore.registerNS("pimcore.plugin.LokaliseTranslateBundle");

pimcore.plugin.LokaliseTranslateBundle = Class.create({
    getClassName: function () {
        return "pimcore.plugin.LokaliseTranslateBundle";
    },

    initialize: function () {
        if(pimcore.events){
            document.addEventListener(pimcore.events.pimcoreReady, this.pimcoreReady.bind(this));
            document.addEventListener(pimcore.events.postOpenObject, this.postOpenObject.bind(this));
            document.addEventListener(pimcore.events.postOpenDocument, this.postOpenDocument.bind(this));
        }
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

    postOpenObject: function (e) {
        var { object, type } = e.detail;
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
                            items.push({ text: 'Sync' });

                        }

                        var menu =   {
                            xtype: 'button',
                            text: t('Lokalise'),
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

                                        if(item.text == "Sync"){
                                            syncLokaliseObject(object);
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


    postOpenDocument: function (e) {
        var { document, type } = e.detail;
        this.addLinkedParentsTab(document, type);
        this.addTranslationKeysTab(document, type);
        /* add quicktranslate button to specific document type */
        if (type == "page" || type == "snippet" || type == "printpage") {
      
            if (!Ext.isIE) {
                if("en" == document.data.properties["language"]["data"] ){
                    this.docBtn(document);
                }else{
                    this.syncDocBtn(document);
                }
            }


        }

    },

    addLinkedParentsTab: function (document, type) {
        if (!(type == "page" || type == "snippet" || type == "printpage")) {
            return;
        }

        var tabPanel = document.tabbar;
        if (!tabPanel || typeof tabPanel.add !== "function") {
            return;
        }

        var tabId = "lokalise-linked-parents-tab-" + document.id;
        if (Ext.getCmp(tabId)) {
            return;
        }

        var store = new Ext.data.JsonStore({
            autoLoad: false,
            fields: [
                "recordType",
                "documentId",
                "language",
                "status",
                "documentPath",
                "parentId",
                "parentPath",
                "isCurrentDocument"
            ],
            proxy: {
                type: "ajax",
                url: "/admin/lokalise/document/parent-links",
                extraParams: {
                    id: document.id
                },
                reader: {
                    type: "json",
                    rootProperty: "rows"
                }
            }
        });

        var tab = new Ext.Panel({
            id: tabId,
            title: t("Linked Parents"),
            iconCls: "pimcore_material_icon_account_tree",
            layout: "fit",
            border: false,
            items: [{
                xtype: "grid",
                border: false,
                store: store,
                columns: [
                    {
                        xtype: "actioncolumn",
                        text: "Open",
                        width: 70,
                        align: "center",
                        items: [{
                            iconCls: "pimcore_icon_open",
                            tooltip: t("Open document"),
                            isDisabled: function (view, rowIndex, colIndex, item, record) {
                                return !record.get("documentId");
                            },
                            handler: function (grid, rowIndex) {
                                var record = grid.getStore().getAt(rowIndex);
                                var docId = record.get("documentId");
                                if (docId) {
                                    pimcore.helpers.openDocument(docId);
                                }
                            }
                        }]
                    },
                    { text: "Type", dataIndex: "recordType", width: 90 },
                    { text: "Document ID", dataIndex: "documentId", width: 110 },
                    { text: "Language", dataIndex: "language", width: 90 },
                    { text: "Status", dataIndex: "status", width: 110 },
                    { text: "Document Path", dataIndex: "documentPath", flex: 1 },
                    { text: "Parent Path", dataIndex: "parentPath", flex: 1 },
                    {
                        text: "Current",
                        dataIndex: "isCurrentDocument",
                        width: 80,
                        renderer: function (value) {
                            return value ? "Yes" : "No";
                        }
                    }
                ],
                listeners: {
                    itemdblclick: function (grid, record) {
                        var docId = record.get("documentId");
                        if (docId) {
                            pimcore.helpers.openDocument(docId);
                        }
                    }
                }
            }],
            listeners: {
                activate: function () {
                    if (!store._isLoadedOnce) {
                        store.load({
                            callback: function () {
                                store._isLoadedOnce = true;
                            }
                        });
                    }
                }
            }
        });

        tabPanel.add(tab);
        tabPanel.updateLayout();
    },

    addTranslationKeysTab: function (document, type) {
        if (!(type == "page" || type == "snippet" || type == "printpage")) {
            return;
        }

        var tabPanel = document.tabbar;
        if (!tabPanel || typeof tabPanel.add !== "function") {
            return;
        }

        var tabId = "lokalise-review-keys-tab-" + document.id;
        if (Ext.getCmp(tabId)) {
            return;
        }

        var summaryStore = new Ext.data.JsonStore({
            autoLoad: false,
            fields: ["language", "documentStatus", "isLinked", "totalKeys", "pendingReview"],
            proxy: {
                type: "ajax",
                url: "/admin/lokalise/document/keys-status",
                extraParams: {
                    id: document.id
                },
                reader: {
                    type: "json",
                    rootProperty: "summary"
                }
            }
        });

        var keysStore = new Ext.data.JsonStore({
            autoLoad: false,
            fields: [
                "language",
                "fieldName",
                "sourceValue",
                "translatedValue",
                "isReviewed",
                "isPushed",
                "documentStatus",
                "isLinked"
            ],
            proxy: {
                type: "ajax",
                url: "/admin/lokalise/document/keys-status",
                extraParams: {
                    id: document.id
                },
                reader: {
                    type: "json",
                    rootProperty: "rows"
                }
            }
        });

        var yesNoRenderer = function (value) {
            return value ? '<span style="color:#2e7d32;font-weight:bold;">' + t("yes") + '</span>' : '<span style="color:#c62828;font-weight:bold;">' + t("no") + '</span>';
        };

        var summaryGrid = new Ext.grid.GridPanel({
            title: t("Languages"),
            region: "north",
            height: 150,
            split: true,
            store: summaryStore,
            border: false,
            columns: [
                { text: "Language", dataIndex: "language", width: 90 },
                { text: "Document Status", dataIndex: "documentStatus", width: 130 },
                { text: "Linked", dataIndex: "isLinked", width: 80, renderer: yesNoRenderer },
                { text: "Total Keys", dataIndex: "totalKeys", width: 90 },
                {
                    text: "Pending Review",
                    dataIndex: "pendingReview",
                    width: 110,
                    renderer: function (value) {
                        if (value > 0) {
                            return '<span style="color:#c62828;font-weight:bold;">' + value + '</span>';
                        }
                        return value;
                    }
                }
            ],
            viewConfig: {
                forceFit: true
            }
        });

        var keysGrid = new Ext.grid.GridPanel({
            title: t("Keys"),
            region: "center",
            flex: 1,
            store: keysStore,
            border: false,
            columnLines: true,
            stripeRows: true,
            autoScroll: true,
            columns: [
                { text: "Language", dataIndex: "language", width: 90 },
                { text: "Field", dataIndex: "fieldName", width: 150 },
                { text: "Source (EN)", dataIndex: "sourceValue", flex: 1 },
                { text: "Translation", dataIndex: "translatedValue", flex: 1 },
                { text: "Reviewed", dataIndex: "isReviewed", width: 90, renderer: yesNoRenderer },
                { text: "Pushed", dataIndex: "isPushed", width: 90, renderer: yesNoRenderer }
            ],
            viewConfig: {
                forceFit: false
            }
        });

        var tab = new Ext.Panel({
            id: tabId,
            title: t("Review Keys"),
            iconCls: "lokalise-translate-icon",
            layout: {
                type: "vbox",
                align: "stretch"
            },
            border: false,
            items: [summaryGrid, keysGrid],
            listeners: {
                activate: function () {
                    if (!keysStore._isLoadedOnce) {
                        summaryStore.load();
                        keysStore.load({
                            callback: function () {
                                keysStore._isLoadedOnce = true;
                            }
                        });
                    }
                }
            }
        });

        tabPanel.add(tab);
        tabPanel.updateLayout();
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
                    { text: 'Create All' },
                    
                ]
                if(isAllowedResponse.status){
                    items.push({ text: 'Update' });
                    items.push({ text: 'Sync' }); 
                }

                var menu =   {
                    xtype: 'button',
                    text: t('Lokalise'),
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
                                if(item.text == "Sync"){
                                    syncLokaliseDocument(document);
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

    syncDocBtn: function (document) {

        var menuParent;
        menuParent = document.toolbar;

        var items = [
      
            
        ]
        var isAllowedResponse; 
        Ext.Ajax.request({
            url: '/admin/lokalise/document/source-document',
            method: 'GET',
            params: {
                documentId:document.id,
            },
            success: function (response) {
                isAllowedResponse = JSON.parse(response.responseText, true);
              
                if(isAllowedResponse.status){
             
                    items.push({ text: 'Sync' }); 
                }
           
                
                var menu =   {
                    xtype: 'button',
                    text: t('Lokalise'),
                    iconCls: 'lokalise-translate-icon',
                    scale: 'small',
                    menu: {
                        xtype: 'menu',
                        items: items,
                        listeners: {
                            click: function( menu, item, e, eOpts ) {
                              
                                if(item.text == "Sync"){
                                 
                                    syncLokaliseDocument(document,isAllowedResponse.sourceDocId);
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
