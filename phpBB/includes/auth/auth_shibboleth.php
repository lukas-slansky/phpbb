<?PHP
if (!defined('IN_PHPBB')) {
  exit;
}

function shibboleth_init() {
  global $phpbb_root_path, $shibboleth_authentication;
  require_once($phpbb_root_path . '/simplesamlphp/lib/_autoload.php');
  $shibboleth_authentication = new SimpleSAML_Auth_Simple('default-sp');
}

function login_shibboleth($username, $password, $shouldAdd = true) {
  shibboleth_init();
  global $db, $shibboleth_authentication;
  $shibboleth_authentication->requireAuth();
  $attributes = $shibboleth_authentication->getAttributes();
  $user = strtr($attributes['eduPersonPrincipalName'][0], '@', '-');

  if (!$user) {
    return array(
      'status'    => LOGIN_ERROR_USERNAME,
      'error_msg' => 'LOGIN_ERROR_USERNAME',
      'user_row'  => array('user_id' => ANONYMOUS),
    );
  }
  

  if (!empty($user)) {
    $sql = 'SELECT user_id, username, user_password, user_passchg, user_email, user_type FROM ' . USERS_TABLE . " WHERE username = '" . $db->sql_escape($user) . "'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    if ($row) {
      update_user_shibboleth($row, $attributes);
      // User inactive...
      if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE) {
        return array(
          'status'    => LOGIN_ERROR_ACTIVE,
          'error_msg' => 'ACTIVE_ERROR',
          'user_row'  => $row,
        );
      }

      // Successful login...
      return array(
        'status'    => LOGIN_SUCCESS,
        'error_msg' => false,
        'user_row'  => $row,
      );
    }

    if ($shouldAdd) {    
      if (!function_exists('user_add')) {
        global $phpbb_root_path, $phpEx;
        include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
      }
      user_add(user_row_shibboleth($user, $attributes));
      return login_shibboleth($username, $password, false);
    }
  }

  // Not logged into apache
  return array(
    'status'    => LOGIN_ERROR_EXTERNAL_AUTH,
    'error_msg' => 'LOGIN_ERROR_EXTERNAL_AUTH_SHIBBOLETH',
    'user_row'  => array('user_id' => ANONYMOUS),
  );
}

function logout_shibboleth($data, $newSession) {
  shibboleth_init();
  global $shibboleth_authentication;
  $shibboleth_authentication->logout();
}

function user_row_shibboleth($username, $attributes) {
  global $db, $config, $user;
  // first retrieve default group id
  $sql = 'SELECT group_id FROM ' . GROUPS_TABLE . " WHERE group_name = '" . $db->sql_escape('REGISTERED') . "' AND group_type = " . GROUP_SPECIAL;
  $result = $db->sql_query($sql);
  $row = $db->sql_fetchrow($result);
  $db->sql_freeresult($result);

  if (!$row) {
    trigger_error('NO_GROUP');
  }

  // generate user account data
  $ret = array(
    'username'      => $username,
    'user_password' => phpbb_hash(rand()),
    'user_email'    => $attributes['mail'][0],
    'group_id'      => (int) $row['group_id'],
    'user_type'     => USER_NORMAL,
    'user_ip'       => $user->ip,
    'user_new'      => 0
  );
  return $ret;
}

function update_user_shibboleth($userRow, $attributes) {
  global $phpbb_root_path, $phpEx, $db;
  if (!class_exists('custom_profile')) {
    include_once($phpbb_root_path . 'includes/functions_profile_fields.' . $phpEx);
  }
  if (!function_exists('get_group_name')) {
    include_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);
  }

  $sql = 'SELECT ug.group_id FROM ' . USER_GROUP_TABLE . ' ug INNER JOIN ' . GROUPS_TABLE . ' g ON (ug.group_id=g.group_id) WHERE group_type=2 AND user_id=' . $userRow['user_id'];
  $result = $db->sql_query($sql);
  while ($row = $db->sql_fetchrow($result)) {
    $inGroup = get_group_name($row['group_id']);
    if ($inGroup && !in_array($inGroup, $attributes['groups'])) {
      group_user_del($row['group_id'], array($userRow['user_id']));
    }
  }
	$db->sql_freeresult($result);
  
  foreach ($attributes['groups'] as $group) {
    $sql = 'SELECT group_id FROM ' . GROUPS_TABLE . " WHERE group_name = '" . $db->sql_escape($group) . "'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
    $group_id = $row['group_id'];
    if (!$row) {
      $group_id = 0;
      group_create($group_id, GROUP_HIDDEN, $group, '', array());
    }
    group_user_add($group_id, $userRow['user_id']);
  }

  /* Mapování atributů Shibboleth na atributy uživatele */
  $jmeno = $attributes['https://idp.upce.cz/celeJmenoSTituly'][0];
  if (!$jmeno) {
    $jmeno = $attributes['cn'][0];
  }
  if (!$jmeno) {
    $jmeno = strtr($attributes['eduPersonPrincipalName'][0], '@', '-');
  }
  $cp_data = array(
    'user_id'=>$userRow['user_id'],
    'pf_jmeno'=>$jmeno,
	);

  $sql = 'DELETE FROM ' . PROFILE_FIELDS_DATA_TABLE . ' WHERE user_id=' . $userRow['user_id'];
	$db->sql_query($sql);
	$sql = 'INSERT INTO ' . PROFILE_FIELDS_DATA_TABLE . ' ' .
		$db->sql_build_array('INSERT', custom_profile::build_insert_sql_array($cp_data));
	$db->sql_query($sql);
}
?>
