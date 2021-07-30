<?php

$board_name = basename(__FILE__, '.php'); //board name in Wekan and stream (channel) name in Zulip must be all small letters and be the same with the file name

/** GET DATA FROM WEBHOOK POST: https://stackoverflow.com/questions/47565321/how-to-get-webhook-response-data */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// fetch RAW input
	$json = file_get_contents('php://input');

	// decode json
	$object = json_decode($json);

	// expecting valid json
	if (json_last_error() !== JSON_ERROR_NONE) {
		die(header('HTTP/1.0 415 Unsupported Media Type'));
	}

	$text = $object->text;
	$wekan_action = $object->description;

	// dump to file so you can see for testing
	file_put_contents('callback.test.txt', print_r($object, true));
}


/** FORMAT DATA */

if ($wekan_action == 'act-moveCard') {

	$break = explode("at board \"" . $board_name . "\" ", $text); //remove board info
	$out = $break[0] . $break[1];

	$break2 = explode("at swimlane \"Default\"", $out); //remove swimlane info
	$output = $break2[0] . ' ' . $break2[1] . ' ' . $break2[2];

	$board = 'board main';
} elseif (
	$wekan_action == 'act-createCard' || $wekan_action == 'act-addComment' || $wekan_action == 'act-addChecklist' || $wekan_action == 'act-checkedItem' || $wekan_action == 'act-addChecklistItem'
	|| $wekan_action == 'act-uncheckedItem' || $wekan_action == 'act-joinMember' || $wekan_action == 'act-unjoinMember'
) {
	$break = explode("at swimlane \"Default\" at board \"" . $board_name . "\"", $text); //remove board and swimlane info
	$output = $break[0] . $break[1];

	if ($wekan_action == 'act-uncheckedItem' || $wekan_action == 'act-joinMember' || $wekan_action == 'act-unjoinMember') {
		$board = 'board secondary';
	} else $board = 'board main';
} elseif ($wekan_action == 'act-a-dueAt') {
	$break = explode(" to", $text);
	$break2 = explode("Where: ", $break[1]);
	$break3 = explode("previous due was __timeOldValue__", $break2[1]);
	$output = $break[0] . ' at card ' . $break3[0] . $break3[1];

	$board = 'board secondary';
} elseif ($wekan_action == 'act-addedLabel' || $wekan_action == 'act-removedLabel') {
	$break = explode("__label__ ", $text);
	$output = $break[0] . $break[1];
	$break = explode("at swimlane \"Default\" at board \"" . $board_name . "\"", $output); //remove board and swimlane info
	$output = $break[0] . $break[1];

	$board = 'board secondary';
} elseif ($wekan_action == 'act-archivedCard') {
	$break = explode("Card ", $text);
	$break2 = explode(" at swimlane \"Default\" at board \"" . $board_name . "\" moved to Archive", $break[1]);
	$output = $break[0] . 'deleted/archived card ' . $break2[0] . ' ' . $break2[1];

	$board = 'board secondary';
} else {

	$output = $text;

	$board = 'board secondary';
}

/** SEND DATA TO ZULIP*/

$curl = curl_init();
// Set some options - we are passing in a useragent too here
curl_setopt_array($curl, [
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => 'https://slack.crewnew.com/api/v1/messages',
	CURLOPT_USERAGENT => 'Codular Sample cURL Request',
	CURLOPT_POST => 1,
	CURLOPT_USERPWD => 'board-bot@slack.crewnew.com:eRsSjks7FpRn3apuge4LRev98xDsbpdx',
	CURLOPT_POSTFIELDS => [
		'type' => 'stream',
		'to' => $board_name,
		'subject' => $board,
		'content' => $output
	]
]);
// Send the request & save response to $resp
$resp = curl_exec($curl);
// Close request to clear up some resources
curl_close($curl);
