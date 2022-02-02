<?php
/** SEND DATA TO ZULIP*/
function sendZulip($to, $subject, $content, $type)
{
	$curl = curl_init();
	// Set some options - we are passing in a useragent too here
	curl_setopt_array($curl, [
		CURLOPT_RETURNTRANSFER => 1,
		CURLOPT_URL => 'https://slack.crewnew.com/api/v1/messages',
		CURLOPT_USERAGENT => 'Codular Sample cURL Request',
		CURLOPT_POST => 1,
		CURLOPT_USERPWD => 'boards-bot@slack.crewnew.com:4e7AsLEhiaefFSxYFnNKzuI0QaMOIJz1',
		CURLOPT_POSTFIELDS => [
			'type' => $type,
			'to' => $to,
			'subject' => $subject,
			'content' => $content
		]
	]);
	// Send the request & save response to $resp
	$resp = curl_exec($curl);
	// Close request to clear up some resources
	curl_close($curl);
}
