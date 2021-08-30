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

