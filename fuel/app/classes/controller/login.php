<?php

use \Model\Twitter as Twitter;

class Controller_Login extends Controller
{
	public function action_index()
	{
		Twitter::set('request_token', Session::get('request_token') );
		Twitter::set('request_token_secret', Session::get('request_token_secret') );
		Twitter::set('access_token', Session::get('access_token') );
		Twitter::set('access_token_secret', Session::get('access_token_secret') );

		if ( !empty( Twitter::get('access_token') ) && !empty( Twitter::get('access_token_secret') ) ){
			try {
				$tweet = Twitter::getApiResponse("https://api.twitter.com/1.1/statuses/home_timeline.json", "GET", Twitter::getHometimelineParams(10), Twitter::get('access_token'), Twitter::get('access_token_secret') );
			} catch (\Exception $e) {
				Session::destroy();
				return View::forge('error', array( 'message' => $e->getMessage() ) );
			}
			Debug::dump( Format::forge($tweet, 'json')->to_array() );
			return View::forge('logout');
		} else {
			return View::forge('home');
		}
	}

	public function action_twitter()
	{
		try {
			$token = Twitter::getRequestToken();
		} catch (\Exception $e) {
			return View::forge('error', array( 'message' => $e->getMessage() ) );
		}
		Twitter::setRequestToken($token);

		Session::create();
		Session::set('request_token', Twitter::get('request_token') );
		Session::set('request_token_secret', Twitter::get('request_token_secret') );

		$url = "https://api.twitter.com/oauth/authenticate?oauth_token=".Twitter::get('request_token');
		Response::redirect($url, 'location');
	}

	public function action_callback()
	{
		Twitter::set('request_token', Session::get('request_token') );
		Twitter::set('request_token_secret', Session::get('request_token_secret') );

		if ( Twitter::isAuthenticated($_GET["oauth_token"], $_GET["oauth_verifier"]) ){
			try {
				$token = Twitter::getAccessToken($_GET["oauth_token"], $_GET["oauth_verifier"], Twitter::get('request_token_secret') );
			} catch (\Exception $e) {
				Session::destroy();
				return View::forge('error', array( 'message' => $e->getMessage() ) );
			}
			Twitter::setAccessToken($token);
			Twitter::setUserConfig($token);

			Session::set('access_token', Twitter::get('access_token') );
			Session::set('access_token_secret', Twitter::get('access_token_secret') );

			Response::redirect('login/index','location');
		} elseif ( Twitter::isRejected($_GET["denied"]) ) {
			Session::destroy();
			return View::forge('error', array( 'message' => "Twitterで認証が拒否されました" ) );
		} else {
			Session::destroy();
			return View::forge('error', array( 'message' => "予期しないエラーが発生しました" ) );
		}
	}

	public function action_logout()
	{
		Session::destroy();
		Response::redirect('login','location');
	}
}