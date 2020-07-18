<?php

namespace App\Http\Controllers;

use Goutte\Client as GoutteClient;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Http;

class ScrapeController extends Controller
{
    private $url;
    private $addressApiUrl;
    private $token;
    private $sessionId;
    private $cookieDomain;
    public function __construct()
    {
        $this->url = "https://starex.az/account/login/";
        $this->addressApiUrl = "https://starex.az/api/v2/countries/";
        $this->cookieDomain = "starex.az";
    }

    // getting data from starex.az with "Goutta" library
    private function get($url=null,$callback){
        $callback = $callback ?? function(){};
        if($url){
            $goutteClient = new GoutteClient();
            $guzzleClient = new GuzzleClient(array(
                'timeout' => 60,
                'verify' => false
            ));
            $goutteClient->setClient($guzzleClient);
            $response = $goutteClient->request('GET', $url);
            if($url){
                $callback($response);
            }else return "Error: enter url!";
        }
        return $this;
    }

    // scrape data got from starex.az
    public function scrape(){
        $this->get($this->url,function($response){
            $response->filter('[name="csrfmiddlewaretoken"]')->each(function ($node) {
                $token = $node->attr("value");
                $this->token = $token;
            });

        });
        return $this;
    }

    // get token from accessor which gained token from "scrape" function
    public function getToken(){
        return $this->token;
    }

    // signing in in order to get session
    public function login($username,$password,$callback){
        $callback = $callback ?? function(){};
        $this->scrape();
        $response = Http::asForm()
            ->withHeaders(["Referer" => $this->url])
            ->withCookies(["csrftoken" => $this->getToken()],$this->cookieDomain)
            ->post($this->url,[
                'csrfmiddlewaretoken' => $this->getToken(),
                "username" => $username,
                "password" => $password
        ]);

        // checking "sessionid" cookie to determine whether signed in or not
        $sessionId = $response->cookies()->getCookieByName("sessionid");
        if(!$sessionId){
            $callback("failed");
        }else{
            $callback("succeed");
            $this->sessionId = $sessionId->getValue();
        }
        return $this;
    }

    // getting addresses by session id which we got above
    public function getAddresses(){
        $addresses = Http::withCookies(["sessionid" => $this->sessionId],$this->cookieDomain)->get($this->addressApiUrl)->json();
        return $this->parseAddresses($addresses);
    }

    // finally parse addresses to output for console
    public function parseAddresses($addresses){
        $addressesOutput = "";
        foreach ($addresses as $index => $address){
            $addressesOutput.= "\n --- <fg=blue;bold>$address[display_name]</> ---\n\n";
            foreach ($address['user_addresses'][0]["data"] as $addressInfo){
                $addressesOutput.= "$addressInfo[title]: <fg=red>$addressInfo[value]</>\n";
            }

            $addressesOutput.= $index!==count($addresses)-1?"\n\n":"";
        }
        return $addressesOutput;
    }

}
