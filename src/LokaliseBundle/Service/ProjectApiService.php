<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Service;

use \Pimcore\Model\WebsiteSetting;
use Pimcore\Tool\Admin;


class ProjectApiService extends BaseApiService{

    const PROJECT_API_PATH = "/projects";

    public static $pimcoreProjects = [
        "Objects", 'Shared translation', "Documents"
    ];


    public function createProject($project){

        $body = json_encode($project);
        $apiPath = self::PROJECT_API_PATH;
        $response = $this->callPostApi($apiPath, $body);
        $result = json_decode($response->getContent(false));
        return $result;
    }

    public function updateProject($projectId, $project){

        $body = json_encode($project);
        $apiPath = self::PROJECT_API_PATH."/".$projectId;
        $response = $this->callPutApi($apiPath, $body);
        $result = json_decode($response->getContent(false));
        return $result;

    }

    public function getProjects(){
        $apiPath = self::PROJECT_API_PATH;
        $response = $this->callGetApi($apiPath);
        $result = json_decode($response->getContent(false));
        return $result;
    }

   

    public function createProjectOnLokalise(){
        $projectResponse = $this->getProjects();
        $projects = $projectResponse->projects;
        $pimcoreProjects = self::$pimcoreProjects;
        foreach($pimcoreProjects as $proj){
            $filteredProject = array_filter($projects, function ($var) use($proj) {
                return ($var->name == $proj);
            });
            if(count($filteredProject) ==  0){
                $apiResponse = $this->createApiRequests($proj);
                $projectInfo = $this->createProject($apiResponse);
                $projectId = $projectInfo->project_id;
            }else{
                $filteredProject = array_values($filteredProject); 
                $projectId = $filteredProject[0]->project_id;
                $projLanguages = $filteredProject[0]->statistics->languages;
                $this->validateProjectLanguage($projLanguages,$projectId);
            }
            $this->createNewOrUpdateWebSettings($proj,$projectId);
        }   
        return true;
    }

    public function createApiRequests($projectName)
    {
        $langApiBlock = Languages::getMappedLanguages();
        return [
            "name" => $projectName,
            "description" => "",
            "languages" => $langApiBlock,
            "base_lang_iso" =>  "en"
        ];
    }

    public function createNewOrUpdateWebSettings($name,$value)
    {
        $webSiteSetting = WebsiteSetting::getByName($name);
        
        if(null == $webSiteSetting){
            $webSiteSetting = new WebsiteSetting();
            $webSiteSetting->setName($name);
        }
        $webSiteSetting->setType("text");
        $webSiteSetting->setData($value);
        $webSiteSetting->save();
    }

    public static function getProjectIdByName($name){
        $setting = WebsiteSetting::getByName($name);
        $projectId = null;
        if($setting){
            $projectId =  $setting->getData();
        }
        return $projectId;
    }

    public function validateProjectLanguage($projLangs,$projectId){
      
        $langApiBlock = Languages::getMappedLanguages();
        foreach($projLangs as $key => $value){
            foreach($langApiBlock as $apikey => $apivalue){
                if($apivalue['custom_iso'] == $value->language_iso ){
                    $projLangs[$key]->isCommon = true;
                    $langApiBlock[$apikey]['isCommon'] = true;
                }
            }
        }

        $addLanguages = array_filter($langApiBlock, function ($var) {
            if(!isset($var['isCommon'])){
                return true;
            }
        });

        $deleteLanguages = array_filter($projLangs, function ($var) {
            if(!isset($var->isCommon)){
                return true;
            }
        });
        $apiPath = self::PROJECT_API_PATH."/".$projectId."/languages";
        $addLanguages = array_values($addLanguages); 
        if($addLanguages){
            $body = json_encode(["languages"=>$addLanguages]);
            $this->callPostApi($apiPath,$body);
        }   
        if($deleteLanguages){
            foreach($deleteLanguages as $lang){
                $apiPath = self::PROJECT_API_PATH."/".$projectId."/languages/".$lang->language_id;
                $this->callDeleteApi($apiPath);
            }
        }

    }

    

}