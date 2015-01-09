<?php
  $authenticate = true;
  require_once(dirname(__FILE__) . '/config.php');

  if ($_SERVER['REQUEST_METHOD'] != 'GET') {
    sendResponse(array('error'=>'Method not allowed'));
  }
  $uri = $_SERVER['PATH_INFO'];
  $forum_id = preg_replace('|^/|', '', $uri);
  if (!is_numeric($forum_id)) { 
    sendResponse(array('error'=>'Wrong forum ID format'));
  }
  
  $sql = 'SELECT forum_last_post_time FROM ' . $table_prefix . 'forums WHERE forum_id=' . mysqli_real_escape_string($db, $forum_id);
  $ret = mysqli_query($db, $sql);
  if ($row = mysqli_fetch_assoc($ret)) {
    $lastPost = $row['forum_last_post_time'];
  } else {
    sendResponse(array('dirty'=>false));
  }

  $sql = 'SELECT la.last_access FROM
          ' . $table_prefix . 'last_access la
          RIGHT JOIN ' . $table_prefix . 'users u ON (la.user_id=u.user_id)
      WHERE la.forum_id=' . mysqli_real_escape_string($db, $forum_id) . ' and u.username="' . mysqli_real_escape_string($db, $user) . USERNAME_POSTFIX . '"';
  $ret = mysqli_query($db, $sql);
  $lastSeen = 0;
  if ($row = mysqli_fetch_assoc($ret)) {
    $lastSeen = $row['last_access'];
  }
  if ($lastPost <= $lastSeen) {
    sendResponse(array('dirty'=>false));
  } 

  $sql = 'SELECT COUNT(*) unread FROM ' . $table_prefix . 'posts WHERE forum_id=' . mysqli_real_escape_string($db, $forum_id) . ' AND post_time>' . $lastSeen;
  $ret = mysqli_query($db, $sql);
  $unread = -1;
  if ($row = mysqli_fetch_assoc($ret)) {
    $unread = $row['unread'];
  }
  sendResponse(array('dirty'=>($lastPost > $lastSeen), 'unread'=>$unread));
?>