#!/usr/bin/php
<?php
/**
 * Twitter Auto Responder Bot
 * Based on 'Twitter Autoresponder Bot' by Daniel15 (https://gist.github.com/820281)
 */

require '/oauth/twitteroauth.php';
require '/config.php';

class TwitterAutoResponder {

	private $_replies = array();
	private $_connection;

	public function __construct() {

		$this->_connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

	}

	public function run() {

		echo '========= '.date('Y-m-d g:i:s A')." - Started =========\n";

		// Get the last ID we replied to
		$since_id = @file_get_contents('since_id');
		if ($since_id == null) {
			$since_id = 0;
		}

		// Store the ID of the last tweet
		$max_id = $since_id;

		// Verify the Twitter account exists
		if (!$this->verify()) {
			$this->_auth();
			die();
		}

		// Loop through the replies
		foreach ($this->_replies as $term => $reply) {
			echo 'Performing search for '.$term.'... ';
			//$search = $this->search($term, $since_id);
			$search = $this->_connection->get('search/tweets', array('q' => $term, 'since_id' => $since_id /*'count' => 15*/));
			echo 'Done, '.count($search->statuses).' results.';

			// Store the max ID
			if ($search->search_metadata->max_id_str > $max_id) {
				$max_id = $search->search_metadata->max_id_str;
			}

			// Loop through the results
			foreach ($search->statuses as $tweet) {
				$this->reply($tweet, $reply);
			}
		}
		file_put_contents('since_id', $max_id);
		echo '========= '.date('Y-m-d g:i:s A')." - Finished =========\n";
	}

	private function reply($tweet, $reply) {

		try {
			echo '@'.$tweet->user->screen_name.' said: '.$tweet->text."\n";
			$this->_connection->post('status/update', array(
				'status' => '@'.$tweet->from_user.' '.$reply,
				'in_reply_to_status_id' => $tweet->id_str
			);
		}
		catch (OAuthException $e) {
				echo 'ERROR: '.$e->message;
				die();
		}
	}

	private function verify() {

		try {
			$this->_connection->get('account/verify_credentials');
			return true;
		} catch (OAuthException $e) {
			return false;
		}
	}

	private function _auth() {

		// First get a request token, then prompt them to go to the URL
		echo 'OAuth Verification Needed. Retrieving request token...';
		$request_token = $this->_connection->getRequestToken();
		$redirect_url = $this->_connection->getAuthorizeUrl($request_token);
		echo 'Please navigate to this URL for authentication: '.$redirect_url;
		echo 'Once done, and you have a PIN Number, press ENTER.';
		fread(STDIN, 10);

		echo 'PIN Number: ';
		$pin = trim(fread(STDIN, 10));

		// Swap the PIN for an access token
		//!!!
	}

	public function test() {

		$this->_connection->post('statuses/update', array('status' => 'Test tweet from TwitterBot'));

	}

}
