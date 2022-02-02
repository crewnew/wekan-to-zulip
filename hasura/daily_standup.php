<?php

@$server = $_SERVER;
@$post = $_POST;
@$get = $_GET;
$data = file_get_contents('php://input');
if (empty($post)) {
  $post = "emptypost-";
}

if (empty($get)) {
  $get = "emptyget";
}

$server = json_encode($_SERVER);

echo "success";

$obj = json_decode($data); // put the second parameter as true if you want it to be a associative array

$id = $obj->event->data->new->id;
$status_string = $obj->event->data->new->status; // eg. published
$operation = $obj->event->op; // eg. INSERT
$user_id = $obj->event->data->new->user_created; // Directus user ID
$day = $obj->event->data->new->day; // eg. 2022-02-03
$project = $obj->event->data->new->project; // project ID
$task = $obj->event->data->new->task; // name of the task
$longer_text = $obj->event->data->new->longer_text; // task description
$min_hrs = $obj->event->data->new->min_hrs;
$max_hrs = $obj->event->data->new->max_hrs;
$deadline = $obj->event->data->new->deadline;
$url = $obj->event->data->new->url;
$completed_at = $obj->event->data->new->completed_at;
$type = $obj->event->data->new->type; // yesterday, today or blocker

$type_string = strtoupper($type[0]);
//if (isset($type[1])) $type_string += ', ' . strtoupper($type[1]);
//if (isset($type[2])) $type_string += ', ' . strtoupper($type[2]);

$timestamp = strtotime($day);
$new_date = date("D jS", $timestamp);
$timestamp_deadline = strtotime($deadline);
$new_deadline = date("D jS", $timestamp_deadline);
$timestamp_completed = strtotime($completed_at);
$new_completed_at = date("D jS", $timestamp_completed);

/**  TODO: get real username instead $user_id:
  query MyQuery {
    directus_users_by_pk(id: "$user_id") {
      first_name
    }
  }
  Response: https://i.imgur.com/44cWDp8.png

  And once we have the @username then create mutation:
  mutation MyMutation {
    update_working_hours_by_pk(pk_columns: {id: 52}, _set: {username: "$username"}) {
      id
    }
  }
  Response: https://i.imgur.com/YYuNAFU.png
 */

// dump to file so you can see for testing
file_put_contents('daily_standup-test.txt', print_r($obj, true));

if ($operation == 'INSERT' AND $status_string == 'published') {
  $message = $user_id . ' added for ' . $new_date . ' on project ['.$project.'](https://cms.crewnew.com/admin/content/projects/'.$project.'): ' . $type_string . ' - [' . $task . '](https://cms.crewnew.com/admin/content/dailystandup/'.$id.')';
  if (isset($longer_text)) $message .= ' ('.$longer_text.').';
  if (isset($min_hrs)) $message .= ' '.$min_hrs.'-' . $max_hrs . 'h.';
  if (isset($deadline)) $message .= ' Plan to complete on '.$new_deadline.'.';
  if (isset($url)) $message .= ' [URL>>]('.$url.').';
  if (isset($completed_at)) $message .= ' Completed already on '.$new_completed_at.').';
  require_once('sendZulip.php');
  sendZulip('test', 'daily standup', $message, 'stream'); // to/stream/channel, topic/subject, content, type
}
