# lokalise-pimcore
Automatically translate and review your content via Lokalise

# Overview
This extension will work as a bridge between Pimcore and Lokalise for the purpose of automating the whole translation workflow. Thus eliminating most of the manual steps in the task along with availing quality translation-review service from Lokalise.

# Requirements
* Pimcore 5.8 or 6.x

```bash
composer require pdchaudhary/lokalise-pimcore:1.0.7
``` 
* Pimcore X (since Version 2.0)

```bash
composer require pdchaudhary/lokalise-pimcore
``` 

# Installation


1. Open Pimcore admin panel
2. Go to Setting and click on Bundles
3. Click on plus icon(+) to enable the plugin
4. Then add “lokalise_api_key” in website setting from (Setting -> Website settings)
5. Generate the api key from Lokalise with read and write access. To learn how to generate Lokalise api key please refer to Lokalise documentation.
6. Now that the Lokalise bundle is enabled and configured, to install go to Settings -> Bundles -> Install(+ icon) against the Lokalise bundle. 
7. The installation process involves creating 3 projects within Lokalise one each for Documents, Objects and Shared Translations. The projects in Lokalise would be created to have the same number of languages as defined in Pimcore settings. Post creation of projects in Lokalise, their Lokalise projectids would be added to websitesettings. If in case the projects already exist in Lokalise, their projectids would be synced to websitesettings.
8. The installation is now successfully completed.


# Document translation

There are two flows in the document translation.

# Create Document:-

1. After opening a document. Lokalise Translate Dropdown will be available on the toolbar. A “Create” option is available in the dropdown for new documents.
![image](https://user-images.githubusercontent.com/30948231/132936423-78907fc6-0662-4d4c-976f-4555a6dc0595.png)
2. Clicking on the “Create” button, it will open the modal form for language-navigation-title configuration. After filing this form click on the “Apply” button.
![image](https://user-images.githubusercontent.com/30948231/132936458-9467d3ed-8e43-4338-8f10-5e2f93949cd9.png)
3. After clicking on the “Apply” button, it will generate keys into Lokalise and then it will change the status for the parent document to “Sent”.
![image](https://user-images.githubusercontent.com/30948231/132936477-36c1710c-1673-4264-9ce6-914b2c9d1e08.png)
4. Then to get the translations of document keys from Lokalise by the translation would have to be verified & reviewed.
![image](https://user-images.githubusercontent.com/30948231/132936489-c140fde2-3370-4bd7-9f95-e1fdb018a712.png)
5. After that you need to run document sync from proccess manager. a child document for that language is automatically generated with the received translation keys and also it will be set verified for that child document or you can also run.
![image](https://user-images.githubusercontent.com/30948231/132936521-55105fde-c65d-43d0-bbbd-ac9b3dec6056.png)
![image](https://user-images.githubusercontent.com/30948231/132936544-e8707aba-fccf-4654-91d0-61aea46db862.png)

# Update Document:-

1. After updating the main document, click on the “Update” button in the Lokalise dropdown.
2. Note that **Update option is only available when their child documents are already created from Lokalise.**
![image](https://user-images.githubusercontent.com/30948231/132936580-5ce43b81-311b-4ee6-9914-00e87e637a84.png)
3. After clicking on the “Update” button, It will update keys in Lokalise. The document state will change to “Updated”.
4. Only the updated component/keys would be sent for the update in translation. Those keys would be set to unverified at Lokalise end, and would be required to be verified in order to get them updated in Pimcore.
![image](https://user-images.githubusercontent.com/30948231/132936643-289bedf9-bed7-46b5-81cd-83b2167755eb.png)
5. on Click Document sync it will check full document translation in a certain language is done. If so, a child document for that language is automatically updated with the received translation keys and also it will be set verified for that child document.




