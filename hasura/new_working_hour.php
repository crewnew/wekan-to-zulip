<?php

// New working hour inserted

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

$status_string = $obj->event->data->new->status;  // eg. published

//if ($status == 'published') { //TOTO: when status 'archived' then message 'archived'
  $operation = $obj->event->op;  // eg. INSERT
  $working_hour_id = $obj->event->data->new->id;
  $user_id = $obj->event->data->new->user_created;  // Directus user ID
  $start_hour = $obj->event->data->new->start_hour;
  $minimum_hrs = $obj->event->data->new->minimum_hrs;
  $maximum_hrs = $obj->event->data->new->maximum_hrs;
  $type = $obj->event->data->new->type;
  $comment = $obj->event->data->new->comment;
  $days = $obj->event->data->new->days;
  $username_string = $obj->event->data->new->username;

  $username = $user_id;
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

  if ($type == 'w') $type = 'work ';
  if ($type == 'a') $type = 'be available ';
  if ($type == 'o') $type = 'be in the office ';

  $days_string = strtoupper($days[0]);
  if (isset($days[1])) $days_string = $days_string . ', ' . strtoupper($days[1]);
  if (isset($days[2])) $days_string = $days_string . ', ' . strtoupper($days[2]);
  if (isset($days[3])) $days_string = $days_string . ', ' . strtoupper($days[3]);
  if (isset($days[4])) $days_string = $days_string . ', ' . strtoupper($days[4]);
  if (isset($days[5])) $days_string = $days_string . ', ' . strtoupper($days[5]);
  if (isset($days[6])) $days_string = $days_string . ', ' . strtoupper($days[6]);

  if (isset($comment)) $comment_string = '(' . $comment . ')';
  else $comment_string = '';

  if ($operation == 'UPDATE') {
    $op = 'updated';
    $username = $username_string; // temporarly needed as query to GraphQL not done
  } else $op = 'added new';

  // make username to @**username**
  $username = '@**' . $username . '**';

  $message = $username . ' ' . $op . ' working hours for ' . $days_string . '. Start ' . $start_hour . ' and will ' . $type . $minimum_hrs . ' - ' . $maximum_hrs . 'h ' . $comment_string;

  // dump to file so you can see for testing
  file_put_contents('new_working_hour-test.txt', print_r($obj, true));

  require_once('sendZulip.php');
  sendZulip('checkinout-standup', 'working hours', $message, 'stream'); // to/stream/channel, topic/subject, content, type
//}