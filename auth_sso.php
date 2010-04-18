<?php
/**
* SSO Plugin by Benjamin Rice
* http://www.benrice.org/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

function user_row_sso($username, $password)
{
	global $db, $config, $user;
	// first retrieve default group id
	$sql = 'SELECT group_id
		FROM ' . GROUPS_TABLE . "
		WHERE group_name = '" . $db->sql_escape('REGISTERED') . "'
			AND group_type = " . GROUP_SPECIAL;
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if (!$row)
	{
		trigger_error('NO_GROUP');
	}

	// generate user account data
	return array(
		'username'		=> $username,
		'user_password'	=> phpbb_hash($password),
		'user_email'	=> '',
		'group_id'		=> (int) $row['group_id'],
		'user_type'		=> USER_NORMAL,
		'user_ip'		=> $user->ip,
	);
}


function login_sso($username, $password)
{
	global $db, $config, $user;

	$username = $_SERVER['REMOTE_USER'];
	$password = "doesntmatter";


	if (!preg_match("/@" . $config['sso_domain'] . "$/i", $username)) {
		unset($username);
	}
	else {
		$username = preg_replace("/@.*/", "", $_SERVER['REMOTE_USER']);
		$password = "doesntmatter";
	}
	// do not allow empty password
	if (!$password)
	{
		return array(
			'status'	=> LOGIN_ERROR_PASSWORD,
			'error_msg'	=> 'NO_PASSWORD_SUPPLIED',
			'user_row'	=> array('user_id' => ANONYMOUS),
		);
	}

	if (!$username)
	{
		return array(
			'status'	=> LOGIN_ERROR_USERNAME,
			'error_msg'	=> 'LOGIN_ERROR_USERNAME',
			'user_row'	=> array('user_id' => ANONYMOUS),
		);
	}

	$sql = 'SELECT user_id, username, user_password, user_passchg, user_email, user_type
		FROM ' . USERS_TABLE . "
		WHERE username = '" . $db->sql_escape($username) . "'";
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);
	$db->sql_freeresult($result);

	if ($row)
	{
		// User inactive...
		if ($row['user_type'] == USER_INACTIVE || $row['user_type'] == USER_IGNORE)
		{
			return array(
				'status'		=> LOGIN_ERROR_ACTIVE,
				'error_msg'		=> 'ACTIVE_ERROR',
				'user_row'		=> $row,
			);
		}

		return array(
			'status'		=> LOGIN_SUCCESS,
			'error_msg'		=> false,
			'user_row'		=> $row,
		);
	}

	return array(
		'status'		=> LOGIN_SUCCESS_CREATE_PROFILE,
		'error_msg'		=> false,
		'user_row'		=> user_row_remote_user($username, $password),
	);
}

function acp_sso(&$new)
{
   global $user;

   $tpl = '

   <dl>
      <dt><label for="sso_domain">SSO Domain:</label><br /><span>ie. domain.com</span></dt>
      <dd><input type="text" id="sso_domain" size="40" name="config[sso_domain]" value="' . $new['sso_domain'] . '" /></dd>
   </dl>
   <dl>
	';

   return array(
      'tpl'    => $tpl,
      'config' => array('sso_domain')
   );
}
?>
