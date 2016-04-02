<?php
// User Agent Display
// This file will create all the necessary columns in your database, if they do not exist already.

if (!defined('ELK'))
	die('No access...');

$db = database();
$db_table = db_table();

$db_table->db_add_column('{db_prefix}messages',
	array(
		'name' => 'user_agent',
		'type' => 'varchar(255)',
		'null' => false,
	),
	array(),
	'do_nothing',
	''
);

$db_table->db_add_column('{db_prefix}messages',
	array(
		'name' => 'ua_os',
		'type' => 'varchar(255)',
		'null' => false,
	),
	array(),
	'do_nothing',
	''
);

$db_table->db_add_column('{db_prefix}messages',
	array(
		'name' => 'ua_browser',
		'type' => 'varchar(255)',
		'null' => false,
	),
	array(),
	'do_nothing',
	''
);

$db_table->db_add_column('{db_prefix}messages',
	array(
		'name' => 'ua_os_icon',
		'type' => 'varchar(255)',
		'null' => false,
	),
	array(),
	'do_nothing',
	''
);

$db_table->db_add_column('{db_prefix}messages',
	array(
		'name' => 'ua_browser_icon',
		'type' => 'varchar(255)',
		'null' => false,
	),
	array(),
	'do_nothing',
	''
);

// set default permissions

// Do it for 'ungrouped members' (ID: 0) & guests (ID: -1).
$db->insert('ignore',
	'{db_prefix}permissions',
	array(
		'permission' => 'text',
		'id_group' => 'int',
		'add_deny' => 'int',
	),
	array(
		'view_uad',
		-1,
		1,
	),
	array()
);
	
$db->insert('ignore',
	'{db_prefix}permissions',
	array(
		'permission' => 'text',
		'id_group' => 'int',
		'add_deny' => 'int',
	),
	array(
		'view_uad',
		0,
		1,
	),
	array()
);

// Get all the non-postcount based groups.
$request = $db->query('', '
	SELECT id_group
	FROM {db_prefix}membergroups
	WHERE min_posts = -1',
	array()
);

// Give them all their new permission.
while ($row = $db->fetch_assoc($request)) {
	$db->insert('ignore',
		'{db_prefix}permissions',
		array(
			'permission' => 'text',
		'id_group' => 'int',
		'add_deny' => 'int',
		),
		array(
			'view_uad',
			$row['id_group'],
			1,
		),
		array()
	);
}

?>
