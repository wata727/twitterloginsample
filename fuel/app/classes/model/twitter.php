<?php

namespace Model;

class Twitter extends BaseModel
{
	const API_KEY = "YOUR_API_KEY";
	const API_SECRET = "YOUR_API_SECRET_KEY";
	const CALLBACK_URL = "https://twitterloginsample.herokuapp.com/login/callback";

	/* Public Request Token Method
	   リクエストトークンの取得、セットなどを行う。コールバック前に実行されるメソッド群
	*/
	public static function getRequestToken()
	{
		$url = "https://api.twitter.com/oauth/request_token";
		$method = "POST";
		$params = Twitter::getRequestParams();
		$params["oauth_signature"] = Twitter::makeRequestSignature($url, $method, $params);

		$header_params = http_build_query($params, "", ",");
		$token = Twitter::execHttpRequest($url, $method, $header_params);

		if ( empty($token) ) {
			throw new \Exception("リクエストトークン取得エラーです");
		} else {
			return $token;
		}
	}

	public static function setRequestToken($token)
	{
		$query = array();
		$params = explode("&", $token);
		foreach($params as $param){
			$key_value = explode("=", $param);
			$query[$key_value[0]] = $key_value[1];
		}
		Twitter::set('request_token', $query['oauth_token']);
		Twitter::set('request_token_secret', $query['oauth_token_secret']);
	}

	/* Public Access Token Method
	    アクセストークンの取得、セットなどを行う。コールバック後に実行されるメソッド群
	    アクセストークン取得時にユーザコンフィグ情報も取得されるため、それをセットするメソッドも実装する
	*/
	public static function getAccessToken($oauth_token, $oauth_verifier, $request_token_secret)
	{
		$url = "https://api.twitter.com/oauth/access_token";
		$method = "POST";
		$params = Twitter::getAccessParams($oauth_token, $oauth_verifier);
		$params["oauth_signature"] = Twitter::makeAccessSignature($url, $method, $params, $request_token_secret);

		$header_params = http_build_query($params, "", ",");
		$token = Twitter::execHttpRequest($url, $method, $header_params);

		if ( empty($token) ) {
			throw new \Exception("アクセストークン取得エラーです");
		} else {
			return $token;
		}
	}

	public static function setAccessToken($token)
	{
		$query = array();
		$params = explode("&", $token);
		foreach($params as $param){
			$key_value = explode("=", $param);
			$query[$key_value[0]] = $key_value[1];
		}
		Twitter::set('access_token', $query["oauth_token"]);
		Twitter::set('access_token_secret', $query["oauth_token_secret"]);
	}

	public static function setUserConfig($token)
	{
		$query = array();
		$params = explode("&", $token);
		foreach($params as $param){
			$key_value = explode("=", $param);
			$query[$key_value[0]] = $key_value[1];
		}
		Twitter::set('user_id',$query["user_id"]);
		Twitter::set('screen_name',$query["screen_name"]);
	}

	/* Public API Method
	   API実行用パラメータの取得、APIのリクエストなどを行う。
	   リクエストトークン、アクセストークンの取得が正常に完了している場合のみ実行可能なメソッド群
	*/
	public static function getApiResponse($url, $method, $api_params, $access_token, $access_token_secret)
	{
		$api_token_params = Twitter::getApiTokenParams($access_token);
		$params = array_merge($api_params,$api_token_params);
		ksort($params);
		$params["oauth_signature"] = Twitter::makeApiSignature($url, $method, $params, $access_token_secret);

		$header_params = http_build_query($params, "", ",");
		if($api_params && $method == "GET"){
			$url .= "?".http_build_query($api_params, "", "&");
		}

		$response = Twitter::execHttpRequest($url, $method, $header_params);

		if ( empty($response) ) {
			throw new \Exception("APIリクエストエラーです");
		} else {
			return $response;
		}
	}

	public static function getHomeTimelineParams($count = 50)
	{
		$params = array(
			"count" => $count,
		);

		return $params;
	}

	/* Public Judge Method
	   各種判定用メソッド。基本的にはブーリアンを返す。
	*/
	public static function isAuthenticated($oauth_token, $oauth_verifier)
	{
		$token = Twitter::get('request_token_secret');
		if ( isset($oauth_token) && !empty($oauth_token) 
				&& isset($oauth_verifier) && !empty($oauth_verifier) 
				&& isset($token) && !empty($token) ) {
			return true;
		} else {
			return false;
		}
	}

	public static function isRejected($denied)
	{
		if ( isset($denied) && !empty($denied) ) {
			return true;
		} else {
			return false;
		}
	}

	/* Private Method
	   内部的に用いられる各種メソッド。
	*/
	private static function getRequestParams()
	{
		$params = array(
			"oauth_callback" => self::CALLBACK_URL,
			"oauth_consumer_key" => self::API_KEY,
			"oauth_signature_method" => "HMAC-SHA1",
			"oauth_timestamp" => time(),
			"oauth_nonce" => microtime(),
			"oauth_version" => "1.0"
		);

		foreach ($params as $key => $value) {
			if ($key == "oauth_callback"){
				continue;
			}
			$params[$key] = rawurlencode($value);
		}
		ksort($params);
		return $params;		
	}

	private static function makeRequestSignature($url, $method, $params)
	{
		$key = Twitter::makeSignatureKey(self::API_SECRET, "");
		$header_params = http_build_query($params, "", "&");
		$value = rawurlencode($method)."&".rawurlencode($url)."&".rawurlencode($header_params);

		$hash = hash_hmac("sha1", $value, $key, TRUE);
		$signature = base64_encode($hash);

		return $signature;
	}

	private static function getAccessParams($oauth_token, $oauth_verifier)
	{
		$params = array(
			"oauth_consumer_key" => self::API_KEY,
	        "oauth_token" => $oauth_token,
	        "oauth_signature_method" => "HMAC-SHA1",
	        "oauth_timestamp" => time(),
	        "oauth_verifier" => $oauth_verifier,
	        "oauth_nonce" => microtime(),
	        "oauth_version" => "1.0"
		);

		foreach($params as $key => $value){
			$params[$key] = rawurlencode($value);
		}
		ksort($params);
		return $params;
	}

	private static function makeAccessSignature($url, $method, $params, $request_token_secret)
	{
		$key = Twitter::makeSignatureKey(self::API_SECRET, $request_token_secret);
		$header_params = http_build_query($params, "", "&");
		$value = rawurlencode($method)."&".rawurlencode($url)."&".rawurlencode($header_params);

		$hash = hash_hmac("sha1", $value, $key, TRUE);
		$signature = base64_encode($hash);

		return $signature;
	}

	private static function getApiTokenParams($access_token)
	{
		$params = array(
			"oauth_consumer_key" => self::API_KEY,
			"oauth_token" => $access_token,
			"oauth_nonce" => microtime(),
			"oauth_signature_method" => "HMAC-SHA1",
			"oauth_timestamp" => time(),
			"oauth_version" => "1.0"
		);

		return $params;
	}

	private static function makeApiSignature($url, $method, $params, $access_token_secret)
	{
		$key = Twitter::makeSignatureKey(self::API_SECRET, $access_token_secret);
		$signature_params = str_replace(array("+","%7E"),array("%20","~"),http_build_query($params, "", "&"));
		$value = rawurlencode($method)."&".rawurlencode($url)."&".rawurlencode($signature_params);
		$signature = base64_encode( hash_hmac("sha1", $value, $key, TRUE) );

		return $signature;
	}

	private static function execHttpRequest($url, $method, $params)
	{
		$response = @file_get_contents(
		    $url,
 		    false,
   	        stream_context_create(
                array(
                    "http" => array(
                        "method" => $method,
                        "header" => array(
                            "Authorization: OAuth ".$params,
                        ),
                    )
                )
            )
        );

        return $response;
	}

	private static function makeSignatureKey($key1, $key2)
	{
		return rawurlencode($key1)."&".rawurlencode($key2);
	}
}
