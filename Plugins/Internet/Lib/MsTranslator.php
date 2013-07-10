<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Kacper
 * Date: 07.07.13
 * Time: 15:46
 * To change this template use File | Settings | File Templates.
 */

namespace XPBot\Plugins\Internet\Lib;

class MsTranslator {
    private $applicationId;
    private $secret;
    private $token;
    private $tokenTime;

    public function __construct($appId, $secret) {
        $this->applicationId = $appId;
        $this->secret = $secret;
        $this->token = $this->getToken();
    }

    /**
     * Get the access token.
     * @throws \Exception
     * @return string
     */
    public function getToken() {
        $ch = curl_init();
        $paramArr = array (
            'grant_type'    => 'client_credentials',
            'scope'         => 'http://api.microsofttranslator.com',
            'client_id'     => $this->applicationId,
            'client_secret' => $this->secret
        );

        $paramArr = http_build_query($paramArr);
        curl_setopt($ch, CURLOPT_URL, 'https://datamarket.accesscontrol.windows.net/v2/OAuth2-13/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $paramArr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $strResponse = curl_exec($ch);

        if(curl_errno($ch))
            throw new \Exception( curl_error($ch));

        curl_close($ch);
        $objResponse = json_decode($strResponse);
        if(isset($objResponse->error))
            throw new \Exception($objResponse->error_description);

        $this->tokenTime = time();

        return $objResponse->access_token;
    }

    public function translate($text, $to, $from = '') {
        if(time() - $this->tokenTime > 15 * 60)
            $this->token = $this->getToken();

        $url = 'http://api.microsofttranslator.com/v2/Http.svc/Translate?';
        $url .= 'text='.urlencode($text);
        $url .= '&to='.$to;
        if(!empty($from)) $url .= '&from='.$from;

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer {$this->token}",
            'Content-Type: text/xml'
        ));
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, False);
        $response = simplexml_load_string(curl_exec($ch));
        if(curl_errno($ch))
            throw new \Exception(curl_error($ch));

        curl_close($ch);
        return (string)$response;
    }
}