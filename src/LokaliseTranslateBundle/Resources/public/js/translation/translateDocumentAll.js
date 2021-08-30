

function createTranslateLocaliseDocument(documentTranslate) {

    var languagestore = [];
    var languageItems = {};
    
    var websiteLanguages = pimcore.settings.websiteLanguages;
    var selectContent = "";
   
    for (var i = 0; i < websiteLanguages.length; i++) {
        if (documentTranslate.data.properties["language"]["data"] != websiteLanguages[i] ) {
            selectContent = pimcore.available_languages[websiteLanguages[i]] + " [" + websiteLanguages[i] + "]";
            languagestore.push([websiteLanguages[i], selectContent]);

        }
    }
    var promises = [];
    Ext.Ajax.request({
        url: "/admin/translate/translate_get_auth_key",
        success: function (response) {
            var authKey = Ext.decode(response.responseText);

            if (authKey.exists) {

                var key = authKey.authKey;

               
                /* settings for checker request to deepl */
                for (let index = 0; index < languagestore.length; index++) {
                    var lang = languagestore[index][0];
                    var deeplLanguages = [
                        "en",
                        "de",
                        "fr",
                        "es",
                        "pt",
                        "it",
                        "nl",
                        "pl",
                        "ru",
                        "ja",
                        "zh"
                    ];
                    if(deeplLanguages.includes(lang)){
                        var  request = $.ajax({
                            url: "https://api.deepl.com/v2/translate?auth_key="+key+"&text="+documentTranslate.data.title+"&target_lang="+lang,
                            data:{target_lang:lang,text:documentTranslate.data.title,auth_key:key},
                            method: 'GET'}).done(function (response) {
                            var url = new URL($(this)[0].url);
                            var target_lang = url.searchParams.get("target_lang");
                            languageItems = {...languageItems,[target_lang]:response.translations[0].text};
                        });
                        promises.push( request);
                    }else{
                        languageItems = {...languageItems,[lang]:''};
                    }
                   
                }
           
               $.when.apply(null, promises).done(function(){

                    var allLanguageWiseItems = [];

                    var lineconfig = {
                        xtype: 'box',
                        autoEl:{
                            tag: 'hr',
                            style:'line-height:1px; font-size: 1px;margin-bottom:4px',
                        }
                    };

                    for (let index = 0; index < languagestore.length; index++) {
                        
                        allLanguageWiseItems = [...allLanguageWiseItems,...[{
                            xtype: "combo",
                            name: "language"+languagestore[index][0],
                            itemId: "language-" + documentTranslate.id+languagestore[index][0],
                            store: languagestore,
                            editable: false,
                            readOnly:true,
                            triggerAction: 'all',
                            mode: "local",
                            value:languagestore[index],
                            fieldLabel: t('language'),
                            listeners: {
                                render: function (el) {
                                    pageForm.getComponent("parent"+languagestore[index][0]).disable();
                                    
                                    Ext.Ajax.request({
                                        url: "/admin/document/translation-determine-parent",
                                        params: {
                                            language: el.getValue(),
                                            id: documentTranslate.id
                                        },
                                        success: function (response) {
                                            var data = Ext.decode(response.responseText);
                                            if (data["success"]) {
                                                pageForm.getComponent("parent"+languagestore[index][0]).setValue(data["targetPath"]);
                                            }else{
                                                pageForm.getComponent("parent"+languagestore[index][0]).setValue('/'+el.getValue());
                                            }
                                            pageForm.getComponent("parent"+languagestore[index][0]).enable();
                                        }
                                    });
                                }.bind(this)
                            }
                        },{
                            xtype: "textfield",
                            name: "parent"+languagestore[index][0],
                            itemId: "parent"+languagestore[index][0],
                            width: "100%",
                            fieldCls: "input_drop_target",
                            fieldLabel: t("parent"),
                            listeners: {
                                "render": function (el) {
                                    new Ext.dd.DropZone(el.getEl(), {
                                        reference: this,
                                        ddGroup: "element",
                                        getTargetFromEvent: function (e) {
                                            return this.getEl();
                                        }.bind(el),

                                        onNodeOver: function (target, dd, e, data) {
                                            if (data.records.length === 1 && data.records[0].data.elementType === "document") {
                                                return Ext.dd.DropZone.prototype.dropAllowed;
                                            }
                                        },

                                        onNodeDrop: function (target, dd, e, data) {

                                            if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                                return false;
                                            }

                                            data = data.records[0].data;
                                            if (data.elementType === "document") {
                                                this.setValue(data.path);
                                                return true;
                                            }
                                            return false;
                                        }.bind(el)
                                    });
                                }
                            }
                        }, {
                            xtype: "textfield",
                            width: "100%",
                            fieldLabel: t('key'),
                            itemId: "key"+languagestore[index][0],
                            name: 'key'+languagestore[index][0],
                            enableKeyEvents: true,
                            value:documentTranslate.data.key,
                            listeners: {
                                keyup: function (el) {
                                    // pageForm.getComponent("name"+languagestore[index][0]).setValue(el.getValue());
                                }
                            }
                        }, {
                            xtype: "textfield",
                            itemId: "name"+languagestore[index][0],
                            fieldLabel: t('navigation'),
                            name: 'name'+languagestore[index][0],
                            width: "100%",
                            value:languageItems[languagestore[index][0]]
                        }, {
                            xtype: "textfield",
                            itemId: "title"+languagestore[index][0],
                            fieldLabel: t('title'),
                            name: 'title'+languagestore[index][0],
                            width: "100%",
                            value:languageItems[languagestore[index][0]]
                        },
                        lineconfig]];
                    }

                    var pageForm = new Ext.form.FormPanel({
                        border: false,
                        defaults: {
                            labelWidth: 170
                        },
                        items: allLanguageWiseItems
                    });

                    var win = new Ext.Window({
                        title: "Translate Document",
                        width: 600,
                        height: 600,
                        autoScroll: true,
                        autoShow: true,
                        bodyStyle: "padding:10px",
                        items: [pageForm],
                        buttons: [{
                            text: t("cancel"),
                            iconCls: "pimcore_icon_delete",
                            handler: function () {
                                win.close();
                            }
                        }, {
                            text: t("apply"),
                            iconCls: "pimcore_icon_apply",
                            handler: function () {

                                var params = pageForm.getForm().getFieldValues();
                                var validateDocument = {language:languagestore,documentData:params};
                                Ext.Ajax.request({
                                    url: "/admin/lokalise/document/validate-document",
                                    method: 'post',
                                    params: {
                                        data: JSON.stringify(validateDocument)
                                    },
                                    success: function (response) {
                                        var validations = JSON.parse(response.responseText, true);
                                        if(validations.status){

                                        
                                            Ext.Ajax.request({
                                                url: "/admin/lokalise/document/get_document_elements",
                                                method: 'GET',
                                                params: {
                                                    id: documentTranslate.id
                                                },
                                                success: function (response) {
                                                    var elements = JSON.parse(response.responseText, true);
                                                    console.log(elements);
                                                    if (elements.elements != null) {

                                                        var xml = "";
                                                        params["translateDocId"] = documentTranslate.id;
                                                        var objectKeys = [];
                                                        var xmlModified = '';
                                                        Object.keys(elements.elements).forEach(function (key) {
                                                            xmlModified= '<' + key + ' quick-t-tag="'+ key +'"  quick-t-type="' + elements.elements[key]["type"] + '">' + elements.elements[key]["data"] + '</' + key + '>';
                                                            xml +=xmlModified;
                                                            objectKeys.push({documentId:documentTranslate.id,key:documentTranslate.id+'||'+key,value:elements.elements[key]["data"],type:elements.elements[key]["type"]});
                                                        });
                                                        var tagText = "";
                                                        if(documentTranslate.data.title){
                                                            tagText = documentTranslate.data.title;
                                                        }else{
                                                            tagText = documentTranslate.data.key;
                                                        }
                                                        var tags = [tagText];
                                                        xml = xmlRegReplaceUtil(xml);

                                                        var tempWrapper = document.createElement("tempWrapper");

                                                        tempWrapper.innerHTML = xml;

                                                        var srcSet = [];
                                                        Array.from(tempWrapper.getElementsByTagName("img")).forEach(function (image) {
                                                            srcSet.push(image.src);
                                                            image.src = "";
                                                        });


                                                        var settings ={};
                                                        var otherData = {language:languagestore,documentData:params}
                                                        settings = createLokaliseapiPostAll(settings, objectKeys, tags, otherData);
                                                        var elementsWindow = appTranslatecreateWindow("Processing", "Generating document's keys in lokalise.. ");
                                                        function deeplAjax(settings) {

                                                            
                                                            Ext.Ajax.request({
                                                                url: settings.url,
                                                                method: 'POST',
                                                                params: {
                                                                    data:JSON.stringify(settings.data),
                                                                    documentData:JSON.stringify(settings.documentData)
                                                                
                                                                },
                                                                success: function (response) {
                                                                    elementsWindow.destroy();
                                                                    appTranslatecreateWindow("Successfully", "Document's keys has been generated successfully in lokalise.");
                                                                    documentTranslate.reload();
                                                                }
                                                            });
                            
                                                        };

                                                        deeplAjax(settings);
                                                        

                                                        tempWrapper.remove();


                                                    } else {
                                                        
                                                        appTranslatecreateWindow("Empty document", "We couldn't create your document beacuse document does not have text type fields[No local keys]!");
                                                    }
                                                    win.destroy();

                                                },

                                                failure: function () {
                                                    elementsWindow.destroy();
                                                    appTranslatecreateWindow("Error", "We encountered an error while processing your content for translation. Internal server error.");
                                                }

                                            });
                                        }else{
                                            appTranslatecreateWindow("Already Exists", "We couldn't save your document beacuse a document with the same path + key already exists!");
                                        }

                                    },

                                    failure: function () {
                                        elementsWindow.destroy();
                                        appTranslatecreateWindow("Error", "We encountered an error while processing your content for translation. Internal server error.");
                                    }

                                });


                            }.bind(this)
                        }]
                    });

                    win.show();
                        
                });
                


} else {
 
    var allLanguageWiseItems = [];

                    var lineconfig = {
                        xtype: 'box',
                        autoEl:{
                            tag: 'hr',
                            style:'line-height:1px; font-size: 1px;margin-bottom:4px',
                        }
                    };

                    for (let index = 0; index < languagestore.length; index++) {
                        
                        allLanguageWiseItems = [...allLanguageWiseItems,...[{
                            xtype: "combo",
                            name: "language"+languagestore[index][0],
                            itemId: "language-" + documentTranslate.id+languagestore[index][0],
                            store: languagestore,
                            editable: false,
                            readOnly:true,
                            triggerAction: 'all',
                            mode: "local",
                            value:languagestore[index],
                            fieldLabel: t('language'),
                            listeners: {
                                render: function (el) {
                                    pageForm.getComponent("parent"+languagestore[index][0]).disable();
                                    
                                    Ext.Ajax.request({
                                        url: "/admin/document/translation-determine-parent",
                                        params: {
                                            language: el.getValue(),
                                            id: documentTranslate.id
                                        },
                                        success: function (response) {
                                            var data = Ext.decode(response.responseText);
                                            if (data["success"]) {
                                                pageForm.getComponent("parent"+languagestore[index][0]).setValue(data["targetPath"]);
                                            }else{
                                                pageForm.getComponent("parent"+languagestore[index][0]).setValue('/'+el.getValue());
                                            }
                                            pageForm.getComponent("parent"+languagestore[index][0]).enable();
                                        }
                                    });
                                }.bind(this)
                            }
                        },{
                            xtype: "textfield",
                            name: "parent"+languagestore[index][0],
                            itemId: "parent"+languagestore[index][0],
                            width: "100%",
                            fieldCls: "input_drop_target",
                            fieldLabel: t("parent"),
                            listeners: {
                                "render": function (el) {
                                    new Ext.dd.DropZone(el.getEl(), {
                                        reference: this,
                                        ddGroup: "element",
                                        getTargetFromEvent: function (e) {
                                            return this.getEl();
                                        }.bind(el),

                                        onNodeOver: function (target, dd, e, data) {
                                            if (data.records.length === 1 && data.records[0].data.elementType === "document") {
                                                return Ext.dd.DropZone.prototype.dropAllowed;
                                            }
                                        },

                                        onNodeDrop: function (target, dd, e, data) {

                                            if (!pimcore.helpers.dragAndDropValidateSingleItem(data)) {
                                                return false;
                                            }

                                            data = data.records[0].data;
                                            if (data.elementType === "document") {
                                                this.setValue(data.path);
                                                return true;
                                            }
                                            return false;
                                        }.bind(el)
                                    });
                                }
                            }
                        }, {
                            xtype: "textfield",
                            width: "100%",
                            fieldLabel: t('key'),
                            itemId: "key"+languagestore[index][0],
                            name: 'key'+languagestore[index][0],
                            enableKeyEvents: true,
                            value:documentTranslate.data.key,
                            listeners: {
                                keyup: function (el) {
                                    // pageForm.getComponent("name"+languagestore[index][0]).setValue(el.getValue());
                                }
                            }
                        }, {
                            xtype: "textfield",
                            itemId: "name"+languagestore[index][0],
                            fieldLabel: t('navigation'),
                            name: 'name'+languagestore[index][0],
                            width: "100%",
                            value:""
                        }, {
                            xtype: "textfield",
                            itemId: "title"+languagestore[index][0],
                            fieldLabel: t('title'),
                            name: 'title'+languagestore[index][0],
                            width: "100%",
                            value:""
                        },
                        lineconfig]];
                    }

                    var pageForm = new Ext.form.FormPanel({
                        border: false,
                        defaults: {
                            labelWidth: 170
                        },
                        items: allLanguageWiseItems
                    });

                    var win = new Ext.Window({
                        title: "Translate Document",
                        width: 600,
                        height: 600,
                        autoScroll: true,
                        autoShow: true,
                        bodyStyle: "padding:10px",
                        items: [pageForm],
                        buttons: [{
                            text: t("cancel"),
                            iconCls: "pimcore_icon_delete",
                            handler: function () {
                                win.close();
                            }
                        }, {
                            text: t("apply"),
                            iconCls: "pimcore_icon_apply",
                            handler: function () {

                                var params = pageForm.getForm().getFieldValues();
                                var validateDocument = {language:languagestore,documentData:params};
                                Ext.Ajax.request({
                                    url: "/admin/lokalise/document/validate-document",
                                    method: 'post',
                                    params: {
                                        data: JSON.stringify(validateDocument)
                                    },
                                    success: function (response) {
                                        var validations = JSON.parse(response.responseText, true);
                                        if(validations.status){

                                        
                                            Ext.Ajax.request({
                                                url: "/admin/lokalise/document/get_document_elements",
                                                method: 'GET',
                                                params: {
                                                    id: documentTranslate.id
                                                },
                                                success: function (response) {
                                                    var elements = JSON.parse(response.responseText, true);
                                                    console.log(elements);
                                                    if (elements.elements != null) {

                                                        var xml = "";
                                                        params["translateDocId"] = documentTranslate.id;
                                                        var objectKeys = [];
                                                        var xmlModified = '';
                                                        Object.keys(elements.elements).forEach(function (key) {
                                                            xmlModified= '<' + key + ' quick-t-tag="'+ key +'"  quick-t-type="' + elements.elements[key]["type"] + '">' + elements.elements[key]["data"] + '</' + key + '>';
                                                            xml +=xmlModified;
                                                            objectKeys.push({documentId:documentTranslate.id,key:documentTranslate.id+'||'+key,value:elements.elements[key]["data"],type:elements.elements[key]["type"]});
                                                        });
                                                        var tagText = "";
                                                        if(documentTranslate.data.title){
                                                            tagText = documentTranslate.data.title;
                                                        }else{
                                                            tagText = documentTranslate.data.key;
                                                        }
                                                        var tags = [tagText];
                                                        xml = xmlRegReplaceUtil(xml);

                                                        var tempWrapper = document.createElement("tempWrapper");

                                                        tempWrapper.innerHTML = xml;

                                                        var srcSet = [];
                                                        Array.from(tempWrapper.getElementsByTagName("img")).forEach(function (image) {
                                                            srcSet.push(image.src);
                                                            image.src = "";
                                                        });


                                                        var settings ={};
                                                        var otherData = {language:languagestore,documentData:params}
                                                        settings = createLokaliseapiPostAll(settings, objectKeys, tags, otherData);
                                                        var elementsWindow = appTranslatecreateWindow("Processing", "Generating document's keys in lokalise.. ");
                                                        function deeplAjax(settings) {

                                                            
                                                            Ext.Ajax.request({
                                                                url: settings.url,
                                                                method: 'POST',
                                                                params: {
                                                                    data:JSON.stringify(settings.data),
                                                                    documentData:JSON.stringify(settings.documentData)
                                                                
                                                                },
                                                                success: function (response) {
                                                                    elementsWindow.destroy();
                                                                    appTranslatecreateWindow("Successfully", "Document's keys has been generated successfully in lokalise.");
                                                                    documentTranslate.reload();
                                                                }
                                                            });
                            
                                                        };

                                                        deeplAjax(settings);
                                                        

                                                        tempWrapper.remove();


                                                    } else {
                                                        
                                                        appTranslatecreateWindow("Empty document", "We couldn't create your document beacuse document does not have text type fields[No local keys]!");
                                                    }
                                                    win.destroy();

                                                },

                                                failure: function () {
                                                    elementsWindow.destroy();
                                                    appTranslatecreateWindow("Error", "We encountered an error while processing your content for translation. Internal server error.");
                                                }

                                            });
                                        }else{
                                            appTranslatecreateWindow("Already Exists", "We couldn't save your document beacuse a document with the same path + key already exists!");
                                        }

                                    },

                                    failure: function () {
                                        elementsWindow.destroy();
                                        appTranslatecreateWindow("Error", "We encountered an error while processing your content for translation. Internal server error.");
                                    }

                                });


                            }.bind(this)
                        }]
                    });

                    win.show();
}


},

failure: function () {
appTranslatecreateWindow("Connection error", "We encountered an error while checking for your DeepL authentication key. Internal server error.");
}
});  
  
                


            
   
};



function createLokaliseapiPostAll(settings,data,tags,otherData={}){
   
    languages = otherData.language;
    var langTranslations = [];
    for (let index = 0; index < languages.length; index++) {
        var lang = languages[index][0];
        langTranslations.push({
            "language_iso": lang,
            "translation": ""
        });
        
    }
    settings.method="POST";
    settings.url = '/admin/lokalise/document/create-key';
    let keys = [];
    let keyObjectData ={};
    data.forEach(function(entry) {
        
        totalLangTranslations = langTranslations.concat([{
            "language_iso": "en",
            "translation": entry.value
        }]);
     
        keyObjectData =  {
            "key_name": entry.key,
            "description": "",
            "platforms": [
                "web"
            ],
            "tags":tags,
            "custom_attributes":{
                type:entry.type,
                elementId: entry.documentId,
                mainType:'document'
            },
            "translations": [{
                "language_iso": "en",
                "translation": entry.value
            }]
        };
        totalLangTranslations = [];
        keys.push(keyObjectData);
    });
   
    settings.data = {"keys":keys};
    settings.documentData=otherData;
   
    return settings;

}

function updateLokaliseapiPostDocumentAll(settings){
    
    settings.method="POST";
    settings.url = '/admin/lokalise/document/update-key';
   
    return settings;

}



function updateLokaliseDocument(document){
    this.element = document;
    var settings = {};
    var documentId  = this.element.id;
    settings  = this.updateLokaliseapiPostDocumentAll(settings);
    var elementsWindow = appTranslatecreateWindow("Processing", "Updating document's keys in lokalise.. ");
    function deeplAjax(settings) {

     
        Ext.Ajax.request({
            url: settings.url,
            method: 'POST',
            params: {
                documentId:documentId,
            },
            success: function (response) {
                console.log(response);
                elementsWindow.destroy();
             
                appTranslatecreateWindow("Successfully", "Document's keys has been updated successfully in lokalise.");
                document.reload();
            }
        });

    };
    
    deeplAjax(settings);
        
}

function syncAllDocuments(){

    var url  = '/admin/lokalise/document/sync-key' ;
    var elementsWindow = appTranslatecreateWindow("Processing", "Pulling verified data from lokalise into pimcore");
    function deeplAjax(url) {

     
        Ext.Ajax.request({
            url: url,
            method: 'GET',
            success: function (response) {
                console.log(response);
                elementsWindow.destroy();
                appTranslatecreateWindow("Successfully", "Document's keys has been fetched successfully from lokalise.");
            }
        });

    };
    
    deeplAjax(url);
}

