<?php
  define('USERNAME_POSTFIX', '-upce.cz');

  require_once(dirname(__FILE__) . '/../config.php');
  $db = mysqli_connect($dbhost, $dbuser, $dbpasswd, $dbname);
  
  if (isset($authenticate) && ($authenticate)) {
    require_once(dirname(__FILE__) . '/phpCAS/source/CAS.php');
    phpCAS::client(CAS_VERSION_2_0, 'idp.upce.cz', 443, '/jasig');
    phpCAS::setNoCasServerValidation();
    phpCAS::forceAuthentication();
    $user = phpCAS::getUser();
  }

  function sendResponse($response) {
    $callback = $_GET['callback'] ? $_GET['callback'] : 'callback';
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $callback)) {
      $callback = 'callback';
    }
    header('Content-Type: application/javascript');
    echo $callback . '(' . json_encode($response) . ');';
    exit;
  }
?>