function singleLokaliseObject(object){
    this.element = object;
    var settings = {};
    var objectId  = this.element.id;
    settings  = this.createLokaliseapiPostObjectAll(settings);
    var elementsWindow = appTranslatecreateWindow("Processing", "Generating object's keys in lokalise.. ");
    function deeplAjax(settings) {

     
        Ext.Ajax.request({
            url: settings.url,
            method: 'POST',
            params: {
                objectId:objectId,
            },
            success: function (response) {
                console.log(response);
                elementsWindow.destroy();
                appTranslatecreateWindow("Successfully", "Object's keys has been generated successfully in lokalise.");
                object.reload();
            }
        });

    };
    
    deeplAjax(settings);
        
}

function updateLokaliseObject(object){
    this.element = object;
    var settings = {};
    var objectId  = this.element.id;
    settings  = this.updateLokaliseapiPostObjectAll(settings);
    var elementsWindow = appTranslatecreateWindow("Processing", "Updating object's keys in lokalise.. ");
    function deeplAjax(settings) {

     
        Ext.Ajax.request({
            url: settings.url,
            method: 'POST',
            params: {
                objectId:objectId,
            },
            success: function (response) {
                console.log(response);
                elementsWindow.destroy();
                appTranslatecreateWindow("Successfully", "Object's keys has been updated successfully in lokalise.");
                object.reload();
            }
        });

    };
    
    deeplAjax(settings);
        
}


function syncLokaliseObject (object){

    this.element = object;
    var settings = {};
    var objectId  = this.element.id;
    settings  = this.syncLokaliseapiPostObjectAll(settings);
 
    var elementsWindow = appTranslatecreateWindow("Processing", "Processing.. ");
    function deeplAjax(settings) {

     
        Ext.Ajax.request({
            url: settings.url,
            method: 'POST',
            params: {
                objectId:objectId,
            },
            success: function (response) {
                console.log(response);
                elementsWindow.destroy();
                appTranslatecreateWindow("Successfully", "Sync initiated! 🚀 Keep your eyes on the process manager for updates.");
                object.reload();
            }
        });

    };
    
    deeplAjax(settings);

}



function createLokaliseapiPostObjectAll(settings){
    
    settings.method="POST";
    settings.url = '/admin/lokalise/object/create-key';
   
    return settings;

}

function updateLokaliseapiPostObjectAll(settings){
    
    settings.method="POST";
    settings.url = '/admin/lokalise/object/updated-key';
   
    return settings;
}

function syncLokaliseapiPostObjectAll(settings){
    
        settings.method="POST";
        settings.url = '/admin/lokalise/object/individual-sync';
       
        return settings;
    
   
}


function syncAllObjects(){

    var url  = '/admin/lokalise/object/sync-key' ;
    var elementsWindow = appTranslatecreateWindow("Processing", "Pulling verified data from lokalise into pimcore");
    function deeplAjax(url) {

     
        Ext.Ajax.request({
            url: url,
            method: 'GET',
            success: function (response) {
                console.log(response);
                elementsWindow.destroy();
                appTranslatecreateWindow("Successfully", "Object's keys has been fetched successfully from lokalise.");
            }
        });

    };
    
    deeplAjax(url);
}