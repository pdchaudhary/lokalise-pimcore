function appTranslatecreateWindow(title, text, success = false, objectID = null) {
    var window = new Ext.window.Window({
        minHeight: 150,
        minWidth: 350,
        maxWidth: 700,
        modal: true,
        layout: 'fit',
        bodyStyle: "padding: 10px;",
        title: title,
        html: text,
        buttons: success == false && objectID == null ? "" : [
            {
                text    : 'Reload',
                handler : function () {
                    window.destroy();
                    pimcore.helpers.closeObject(objectID);
                    pimcore.helpers.openObject(objectID);
                }
            }
        ]
    });

    window.show();

    return window;
}


function appTranslate(key, data,srcSet = null,langFrom, langTo, id,successCallback, failCallback) {

    var url = createDeeplApi(key, data, langFrom, langTo);

    var settings = {
        "async": true,
        "crossDomain": true,
        "url": url,
        "method": "GET",
        "headers": {},
    };

    var translatingWindow = appTranslatecreateWindow("Translating", "Translating your content, please wait...");

    $.ajax(settings).done(function (response) {

        translatingWindow.destroy();
        var translatedData = xmlToJson(response.translations[0].text, srcSet);
        successCallback(id, langTo, translatedData);

    }).fail(function () {
        translatingWindow.destroy();
        failCallback("Failed", "There was a problem connecting to the DeepL translations service! Check your internet connection and that you haven't exceeded the maximum amount of translatable characters!");
    });

};

function appMultiTranslate(key, data,srcSet = null,langFrom, langTo, id,successCallback, failCallback, nextAjaxCallObject,paramsAjax,length) {

    var url = createDeeplApi(key, data, langFrom, langTo);

    var settings = {
        "async": true,
        "crossDomain": true,
        "url": url,
        "method": "GET",
        "headers": {},
    };

    var translatingWindow = appTranslatecreateWindow("Translating", "Translating your content, please wait...");

    $.ajax(settings).done(function (response) {

        translatingWindow.destroy();
        var translatedData = xmlToJson(response.translations[0].text, srcSet);
        successCallback(id, langTo, translatedData,nextAjaxCallObject,paramsAjax,length);
      
    }).fail(function () {
        translatingWindow.destroy();
        failCallback("Failed", "There was a problem connecting to the DeepL translations service! Check your internet connection and that you haven't exceeded the maximum amount of translatable characters!");
    });

};

function appTranslateProgressBar() {
    var progressBar = new Ext.ProgressBar({
        text: "Translating"
    });

    var progressWindow = new Ext.window.Window({
        minWidth: 550,
        modal: true,
        layout: 'fit',
        title: "Translating",
        items: [progressBar],
        bodyStyle: "padding: 10px;"
    });

    progressWindow.show();

    return[progressBar, progressWindow];

}

function xmlRegReplaceUtil(xml, replaceBack = false) {
    if (replaceBack) {
        return xml.replace(/<notrans>\(GT12\)<\/notrans>/g, "&gt;")
            .replace(/<notrans>\(Am1PnBs1P\)<\/notrans>/g, "&nbsp;")
            .replace(/<notrans>\(BR1\)<\/notrans>/g, '<br />')
            .replace(/<notrans>\(A1mP1\)<\/notrans>/g, " &amp;")
            .replace(/\(W1HIT1AM1P\)/g, "&amp;")
            .replace(/\(Ha1ShTaG1\)/g, "#");
            
    }

    return xml.replace(/\s\s+/g, "")
        .replace(/\r?\n|\r/g, " ")
        .replace(/&gt;/g, "<notrans>(GT12)</notrans>")
        .replace(/&nbsp;/g, "<notrans>(Am1PnBs1P)</notrans>")
        .replace(/<br( \/)?>/g, "<notrans>(BR1)</notrans>")
        .replace(/ &amp;/g, "<notrans>(A1mP1)</notrans>")
        .replace(/&amp;/g, "(W1HIT1AM1P)")
        .replace(/ & /g, "<notrans>(A1mP1)</notrans>")
        .replace(/#/g, "(Ha1ShTaG1)");
        
};


function xmlToJsonUtils(xml, srcSet = null, isDocument = false) {
   
    var translation = xmlRegReplaceUtil(xml, true);
    var div = document.createElement('temporaryWrapper');

    div.innerHTML = translation;
   
    if (srcSet != null) {
        Array.from(div.getElementsByTagName("img")).forEach(function (image, key) {
            image.src = srcSet[key];
        }, srcSet);
    }

    var dataToSave = {};

    if (isDocument) {
       
        div.childNodes.forEach(function (child) {
            try {
                
                dataToSave[child.getAttribute("quick-t-tag")] = {
                    "type": child.getAttribute("quick-t-type"),
                    "data": child.innerHTML
                }
              } catch (e) {
                    console.log(e);
                    return 0;
              }
            
        });
    } else {
        div.childNodes.forEach(function (child) {
            if (child.getAttribute("quick-t-type")) {

                dataToSave[child.getAttribute("quick-t-tag")] = child.innerHTML.split("|").map(function (row) {
                    return row.split(",").map(function (cell) {
                        return (cell == " " ? "" : cell);
                    });
                });;

            } else {
                dataToSave[child.getAttribute("quick-t-tag")] = child.innerHTML;
            }
        });
    }

    div.remove();

    return JSON.stringify(dataToSave);
};
