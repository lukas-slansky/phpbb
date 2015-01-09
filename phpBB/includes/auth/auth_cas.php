<?PHP
    if (!defined('IN_PHPBB')) {
	exit;
    }
    
    function cas_init() {
  global $phpbb_root_path;
    require_once($phpbb_root_path . '/includes/auth/CAS/source/CAS.php');
	$cas_host = 'idp.upce.cz';
	$cas_port = 443;
	$cas_context = '/jasig';
	static $casInitialized = false;
	if (!$casInitialized) {
	    phpCAS::client(SAML_VERSION_1_1, $cas_host, $cas_port, $cas_context);
	    phpCAS::setNoCasServerValidation();
	    phpCAS::forceAuthentication();
	    $casInitialized = true;
	}
    }
    
    function autologin_cas() {
	cas_init();
	global $db;
	$user = phpCAS::getUser();
	set_var($user, $user, 'string', true);
	$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE username="' . $db->sql_escape($user) . '"';
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if ($row) {
	    return ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE) ? array() : $row;
	}

	if (!function_exists('user_add')) {
	    global $phpbb_root_path, $phpEx;
	    include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
	}

	// create the user if he does not exist yet
	user_add(user_row_cas($user), user_cp_data_cas());
	$sql = 'SELECT * FROM ' . USERS_TABLE . " WHERE username_clean = '" . $db->sql_escape(utf8_clean_string($user)) . "'";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);
	if ($row) {
	    return $row;
	}
	return array();
    }
    
    function login_cas($username, $password) {
	global $db;
	cas_init();
	if (!$username) {
	    return array(
		'status'    => LOGIN_ERROR_USERNAME,
		'error_msg' => 'LOGIN_ERROR_USERNAME',
		'user_row'  => array('user_id' => ANONYMOUS),
	    );
        }

	$user = phpCAS::getUser();
	if (!empty($user)) {
	    if ($user !== $username) {
		return array(
		    'status'    => LOGIN_ERROR_USERNAME,
		    'error_msg' => 'LOGIN_ERROR_USERNAME',
		    'user_row'  => array('user_id' => ANONYMOUS),
		);
	    }

	    $sql = 'SELECT user_id, username, user_password, user_passchg, user_email, user_type FROM ' . USERS_TABLE . " WHERE username = '" . $db->sql_escape($user) . "'";
	    $result = $db->sql_query($sql);
	    $row = $db->sql_fetchrow($result);
	    $db->sql_freeresult($result);
	    if ($row) {
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
	}

	// Not logged into apache
	return array(
	    'status'    => LOGIN_ERROR_EXTERNAL_AUTH,
	    'error_msg' => 'LOGIN_ERROR_EXTERNAL_AUTH_APACHE',
	    'user_row'  => array('user_id' => ANONYMOUS),
	);
    }

    function user_row_cas($username) {
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
	$attributes = phpCAS::getAttributes();
	$ret = array(
	    'username'      => phpCAS::getUser(),
	    'user_password' => phpbb_hash(rand()),
	    'user_email'    => $attributes['Email'],
	    'group_id'      => (int) $row['group_id'],
	    'user_type'     => USER_NORMAL,
	    'user_ip'       => $user->ip,
	    'user_new'      => 0
	);
	return $ret;
    }
    
    function user_cp_data_cas() {
	$attributes = phpCAS::getAttributes();
	return array(
	    'pf_jmeno'=>$attributes['CommonName']
	);
    }
?>
