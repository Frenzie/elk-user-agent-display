<?php

if (!defined('ELK'))
	die('No access...');

function parse_user_agent($user_agent)
{
	global $settings;
	
	$client_data = array(
		'system' => '',
		'system_icon' => '',
		'browser' => '',
		'browser_icon' => ''
	);

	$browser = user_agent_detect($user_agent, parse_ini_file(SOURCEDIR .'/addons/user_agent_display/browsers.ini', TRUE));
	$browser_icon = str_replace(' ', '', strtolower($browser[1]));
	$system = user_agent_detect($user_agent, parse_ini_file(SOURCEDIR .'/addons/user_agent_display/operating-systems.ini', TRUE));
	
	$client_data['browser'] = htmlspecialchars($browser[0]);
	$client_data['browser_icon'] = (file_exists($settings['default_theme_dir'] . '/images/user_agent_display/icons/browsers/' . $browser_icon . '.png')) ? $browser_icon : '';
	$client_data['system'] = htmlspecialchars($system[0]);
	$client_data['system_icon'] = strtolower($system[1]);

	return $client_data;;
}

// Courtesy of Emdek
// https://github.com/Emdek/eStats
function user_agent_detect($string, $rules)
{
	foreach ($rules as $key => $value)
	{
		$version = 0;

		if (isset($value['rules']))
		{
			if (strstr($key, '.'))
			{
				$version = explode('.', $key);
				$key = $version[0];
			}

			for ($i = 0, $c = count($value['rules']); $i < $c; ++$i)
			{
				if (($version && preg_match('#'.$value['rules'][$i].'#i', $string)) || preg_match('#'.$value['rules'][$i].'#i', $string, $version))
				{
					return array($key.(isset($version[1]) ? ' '.$version[1] : ''), (isset($value['icon']) ? $value['icon'] : $key));
				}
			}
		}
		else if (stristr($string, $key))
		{
			return array($key, (isset($value['icon']) ? $value['icon'] : $key));
		}
	}

	return NULL;
}

?>
