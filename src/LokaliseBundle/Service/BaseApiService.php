<?php
 
namespace Pdchaudhary\LokaliseTranslateBundle\Service;

use Pimcore\Model\WebsiteSetting;
use Symfony\Component\HttpClient\HttpClient;

class BaseApiService {

   public $apiUrl;

   public $authKey;

   public $httpClient;

   public function __construct()
   {
       $this->apiUrl = "https://api.lokalise.com/api2";
       $apiKeyObject = WebsiteSetting::getByName("lokalise_api_key");
       $this->authKey = $apiKeyObject ? $apiKeyObject->getData() : null;
       $this->httpClient = HttpClient::create();
       if($this->authKey == null){
            throw new \Exception("Please add 'lokalise_api_key' in website settings");
       }

   }

   public function callPostApi($apiPath, $body=null){

        $url = $this->apiUrl.$apiPath;
        $response = $this->httpClient->request('POST',$url, [
            'timeout' => 1000,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Token' => $this->authKey
            ],
            "body" => $body
        ]);
        $this->validateReponse($response);
        return $response;
   }

    public function callGetApi($apiPath, $body = null,$validation = true){

        $url = $this->apiUrl.$apiPath;
        $response = $this->httpClient->request('GET',$url, [
            'timeout' => 1000,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Token' => $this->authKey
            ],
            "body" => $body
        ]);
        if($validation){
            $this->validateReponse($response);   
        }   
        return $response;
    }

    public function callPutApi($apiPath, $body = null){
     
        $url = $this->apiUrl.$apiPath;
        $response = $this->httpClient->request('PUT',$url, [
            'timeout' => 1000,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Token' => $this->authKey
            ],
            "body" => $body
        ]);
        $this->validateReponse($response); 
        return $response;
    }

    public function callDeleteApi($apiPath, $body = null){
      
        $url = $this->apiUrl.$apiPath;
        $response = $this->httpClient->request('DELETE',$url, [
            'timeout' => 1000,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Api-Token' => $this->authKey
            ],
            "body" => $body
        ]);
        $this->validateReponse($response);    
        return $response;
    }

    public function validateReponse($response){
        $result = json_decode($response->getContent(false));

        if(!empty($result->error)){
            throw new \Exception($result->error->message);
        }
        if(!empty($result->errors)){
            $keyApiService = new KeyApiService();
            $keyApiService->syncUpAllKeysWithDb();
            throw new \Exception("Some keys were not found in database. Database is now synced, please try again.");
        }
    }




   
 

}