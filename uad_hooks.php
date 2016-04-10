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

class UAD_integrate
{
	public static function general_mod_settings(&$config_vars)
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
	public static function before_create_post(&$msgOptions, &$topicOptions, &$posterOptions, &$message_columns, &$message_parameters)
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

	public static function load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
	{
		loadLanguage('UAD');

		// Add to permissions list
		$permissionList['membergroup']['view_uad'] = array(false, 'general', 'view_uad');
	}


	// Load from DB
	public static function message_query(&$msg_selects, &$msg_tables, &$msg_parameters)
	{
		$msg_selects += array('user_agent, ua_os, ua_browser, ua_os_icon, ua_browser_icon');
	}

	protected static function ua_icon_img($url, $agent) {
		if (!empty($url)) {
			return '<img src="'.$url.'" align="top" alt=""'.((!empty($agent))?' title="'.$agent.'"':'').' />';
		}
		else {
			return NULL;
		}
	}

	// Add to $output['member']['custom_fields'] array
	public static function prepare_display_context(&$output, &$message)
	{
		global $settings, $modSettings;

		$UAD_location = $modSettings['UAD_location'];

		// Don't display if disabled and just collecting data or if not allowed to view
		if ($UAD_location == 'disabled' || !allowedTo('view_uad'))
			return;

		$user_agent = (!empty($message['user_agent'])) ? htmlspecialchars($message['user_agent']) : NULL;
		$uad_sep = !empty($user_agent) ? ' <span class="uad_sep">•</span> ' : NULL; // used for seperating between icons and user agent
		$images_url = $settings['default_images_url'] . '/user_agent_display/icons/';

		$ua_browser; $ua_browser_icon_url; $ua_browser_icon_img;
		$ua_os; $ua_os_icon_url; $ua_os_icon_img;

		$ua_browser = !empty($message['ua_browser']) ? $message['ua_browser'] : NULL;
		if (!empty($message['ua_browser_icon']))
		{
			$ua_browser_icon_url = $images_url.'browsers/'.$message['ua_browser_icon'].'.png';
			$ua_browser_icon_img = self::ua_icon_img($ua_browser_icon_url, $user_agent);
		}
		$ua_os = !empty($message['ua_os']) ? $message['ua_os'] : NULL;
		if (!empty($message['ua_os_icon']))
		{
			$ua_os_icon_url = $images_url.'operatingsystems/'.$message['ua_os_icon'].'.png';
			$ua_os_icon_img = self::ua_icon_img($ua_os_icon_url, $user_agent);
		}

		// Start by cleanup after last run
		unset($output['member']['custom_fields']['uad_browser']);
		unset($output['member']['custom_fields']['uad_os']);

		// Sanity checks
		$no_browser = $no_os = NULL;

		if (empty($ua_browser_icon_img) && empty($ua_browser))
			$no_browser = TRUE;
		if (empty($ua_os_icon_img) && empty($ua_os))
			$no_os = TRUE;
		// Move on if nothing's there
		if ($no_browser == TRUE && $no_os == TRUE && empty($user_agent))
			return;

		// Ideally we'd abuse templates somehow, or maybe language with sprintf?
		// PHP arrays give me a headache
		// Apparently we can't store a reference but need to write out the whole thing every time, yay
		// Anyway, although Elk doesn't custom_fields array stuff by default by doing it here we can overwrite it easily on a per-post basis
		// We have to take great care to unset() instead of value = NULL because otherwise the default template sticks some stuff in position 0
		if (empty($no_browser))
		{
			$output['member']['custom_fields']['uad_browser'] = array(
				'title' => 'Browser',
				'placement' => $UAD_location,
			);
			// 0 is under user info, 1 is as icon, 2 is below post above signature, 3 is top of user info popup (1.1 only)
			switch ($UAD_location) {
				case 0:
					$output['member']['custom_fields']['uad_browser']['value'] = '<br>' . $ua_browser_icon_img .' '. $ua_browser;
					break;
				case 1: // as icon
					if (!empty($ua_browser_icon_img))
						$output['member']['custom_fields']['uad_browser']['value'] = $ua_browser_icon_img;
					else
						unset($output['member']['custom_fields']['uad_browser']);
					break;
				case 2: // below post above signature
					$output['member']['custom_fields']['uad_browser']['value'] = '<small>'.$ua_browser_icon_img.' '.$ua_browser.' '.$ua_os_icon_img.' '.$ua_os.$uad_sep.$user_agent.'</small>';
					break;
				case 3: // top of user info popup (1.1 only)
					$output['member']['custom_fields']['uad_browser']['value'] = $ua_browser_icon_img .' '. $ua_browser;
					break;
			}
		}
		if (empty($no_os))
		{
			$output['member']['custom_fields']['uad_os'] = array(
				'title' => 'OS',
				'placement' => $UAD_location,
			);
			// 0 is under user info, 1 is as icon, 2 is below post above signature, 3 is top of user info popup (1.1 only)
			switch ($UAD_location) {
				case 0:
					$output['member']['custom_fields']['uad_os']['value'] = '<br>' . $ua_os_icon_img .' '. $ua_os;
					break;
				case 1: // as icon
					if (!empty($ua_os_icon_img))
						$output['member']['custom_fields']['uad_os']['value'] = $ua_os_icon_img;
					else
						unset($output['member']['custom_fields']['uad_os']);
					break;
				case 2: // below post above signature
					unset($output['member']['custom_fields']['uad_os']); // don't display OS above sig 'cause we stick it all in one line
					break;
				case 3: // top of user info popup (1.1 only)
					$output['member']['custom_fields']['uad_os']['value'] = $ua_os_icon_img .' '. $ua_os;
					break;
			}
		}
	}
}
?>
