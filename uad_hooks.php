<?php

/**
 *
 * @author  Frans de Jonge http://fransdejonge.com
 * @license ...
 * @mod     User Agent Display
 *
 */

if (!defined('ELK'))
	die('No access...');

function UAD_integrate_general_mod_settings(&$config_vars)
{
	global $txt; // need $txt 'cause loadLanguage only fills in the setting's name
	loadLanguage('UAD');

	$config_vars = array_merge($config_vars, array(
		array(
			'select',
			'UAD_location',
			'name' => 'UAD_location',
			array(
				'0' => $txt['UAD_bottom_user_info'],
				'3' => $txt['UAD_top_user_info'],
				'2' => $txt['UAD_below_post'],
				'1' => $txt['UAD_as_icon'],
				'disabled' => $txt['UAD_disabled'],
			),
		),
		'',
	));
}

// Parses the user agent string and preps it for DB insertion
function UAD_integrate_before_create_post(&$msgOptions, &$topicOptions, &$posterOptions, &$message_columns, &$message_parameters)
{
	require_once(SOURCEDIR .'/addons/user_agent_display/user_agent_detect.php');
	$db = database();

	$safe_user_agent = $db->escape_string($_SERVER['HTTP_USER_AGENT']);
	$os_browser_detected = parse_user_agent($safe_user_agent);
	$posterOptions += array(
		'user_agent' => $safe_user_agent,
		'ua_os' => $os_browser_detected['system'],
		'ua_browser' => $os_browser_detected['browser'],
		'ua_os_icon' => $os_browser_detected['system_icon'],
		'ua_browser_icon' => $os_browser_detected['browser_icon'],
	);

	$message_columns += array(
		'user_agent' => 'string',
		'ua_os' => 'string',
		'ua_browser' => 'string',
		'ua_os_icon' => 'string',
		'ua_browser_icon' => 'string',
	);

	$message_parameters += array(
		'user_agent' => isset($posterOptions['user_agent']) ? $posterOptions['user_agent'] : '',
		'ua_os' => isset($posterOptions['ua_os']) ? $posterOptions['ua_os'] : '',
		'ua_browser' => isset($posterOptions['ua_browser']) ? $posterOptions['ua_browser'] : '',
		'ua_os_icon' => isset($posterOptions['ua_os_icon']) ? $posterOptions['ua_os_icon'] : 'unknown',
		'ua_browser_icon' => isset($posterOptions['ua_browser_icon']) ? $posterOptions['ua_browser_icon'] : 'unknown',
	);
}

function UAD_integrate_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	loadLanguage('UAD');

	// Add to permissions list
	$permissionList['membergroup']['view_uad'] = array(false, 'general', 'view_uad');
}


// Load from DB
function UAD_integrate_message_query(&$msg_selects, &$msg_tables, &$msg_parameters)
{
	$msg_selects += array('user_agent, ua_os, ua_browser, ua_os_icon, ua_browser_icon');
}

// Add to $output['member']['custom_fields'] array
function UAD_integrate_prepare_display_context(&$output, &$message)
{
	global $settings, $modSettings;

	// Don't display if disabled and just collecting data or if not allowed to view
	if ($modSettings['UAD_location'] == 'disabled' || !allowedTo('view_uad'))
		return;

	// Ideally we'd abuse templates somehow

	// 0 is under user info, 1 is as icon, 2 is below post above signature, 3 is top of user info popup (1.1 only)
	$ua_browser = array(
		0 => '<br>' . (($message['ua_browser_icon']) ? '<img src="'.$settings['default_images_url'].'/user_agent_display/icons/browsers/'.$message['ua_browser_icon'].'.png" align="top" alt="" title="'.htmlspecialchars($message['user_agent']).'" /> ' : '') . $message['ua_browser'],
		1 => '<img src="'.$settings['default_images_url'].'/user_agent_display/icons/browsers/'.$message['ua_browser_icon'].'.png" align="top" alt="" title="'.htmlspecialchars($message['user_agent']).'" />',
		2 => '<small>' . (($message['ua_browser_icon']) ? '<img src="'.$settings['default_images_url'].'/user_agent_display/icons/browsers/'.$message['ua_browser_icon'].'.png" align="top" alt="" title="'.htmlspecialchars($message['user_agent']).'" /> ' : '') . $message['ua_browser'] . ' <img src="' . $settings['default_images_url'] . '/user_agent_display/icons/operatingsystems/' . $message['ua_os_icon'] . '.png" align="top" alt="' . $message['ua_os'] . '" title="' . htmlspecialchars($message['user_agent']) . '" /> ' . $message['ua_os'] . ' • ' . htmlspecialchars($message['user_agent']) . '</small>',
		3 => (($message['ua_browser_icon']) ? '<img src="'.$settings['default_images_url'].'/user_agent_display/icons/browsers/'.$message['ua_browser_icon'].'.png" align="top" alt="" title="'.htmlspecialchars($message['user_agent']).'" /> ' : '') . $message['ua_browser'],
	);
	$ua_os = array(
		0 => '<br>' . '<img src="' . $settings['default_images_url'] . '/user_agent_display/icons/operatingsystems/' . $message['ua_os_icon'] . '.png" align="top" alt="' . $message['ua_os'] . '" title="' . htmlspecialchars($message['user_agent']) . '" /> ' . $message['ua_os'],
			'placement' => $modSettings['UAD_location'],
		1 => '<img src="' . $settings['default_images_url'] . '/user_agent_display/icons/operatingsystems/' . $message['ua_os_icon'] . '.png" align="top" alt="' . $message['ua_os'] . '" title="' . htmlspecialchars($message['user_agent']) . '" /> ',
		2 => 'nope', // can't be null or placement defaults to 0
		3 => '<img src="' . $settings['default_images_url'] . '/user_agent_display/icons/operatingsystems/' . $message['ua_os_icon'] . '.png" align="top" alt="' . $message['ua_os'] . '" title="' . htmlspecialchars($message['user_agent']) . '" /> ' . $message['ua_os'],
			'placement' => $modSettings['UAD_location'],
	);

	// There must be a better way to do this
	$arr = array_search('Browser', $output['member']['custom_fields'][count($output['member']['custom_fields']) - 2]);

	if (empty($arr))
	{
		array_push($output['member']['custom_fields'], array(
			'title' => 'Browser',
			'value' => $ua_browser[$modSettings['UAD_location']],
			'placement' => $modSettings['UAD_location'],
		));
		array_push($output['member']['custom_fields'], array(
			'title' => 'OS',
			'value' => $ua_os[$modSettings['UAD_location']],
			'placement' => ($modSettings['UAD_location'] != 2 ? $modSettings['UAD_location'] : -1), // don't display above sig
		));
	}
	else
	{
		$output['member']['custom_fields'][count($output['member']['custom_fields']) - 2] = array(
			'title' => 'Browser',
			'value' => $ua_browser[$modSettings['UAD_location']],
			'placement' => $modSettings['UAD_location'],
		);
		$output['member']['custom_fields'][count($output['member']['custom_fields']) - 1] = array(
			'title' => 'OS',
			'value' => $ua_os[$modSettings['UAD_location']],
			'placement' => ($modSettings['UAD_location'] != 2 ? $modSettings['UAD_location'] : -1), // don't display above sig
		);
	}
}
?>
