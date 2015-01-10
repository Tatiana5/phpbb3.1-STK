<?php
/**
 *
 * @package Support Toolkit - Database Cleaner
 * @copyright (c) 2009 phpBB Group
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit;
}

/**
* Collect all configiration data.
*/
function get_config_rows(&$phpbb_config, &$config_rows, &$existing_config)
{
	global $db;

	$existing_config = array();
	$sql_ary = array(
		'SELECT'	=> 'c.config_name',
		'FROM'		=> array(
			CONFIG_TABLE => 'c',
		),
		'ORDER_BY'	=> 'config_name ASC',
	);
	$sql = $db->sql_build_query('SELECT', $sql_ary);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$existing_config[] = $row['config_name'];
	}
	$db->sql_freeresult($result);

	$config_rows = array_unique(array_merge(array_keys($phpbb_config), $existing_config));
	sort($config_rows);
}

/**
* Collect all extension groups
*/
function get_extension_groups_rows(&$extension_groups_data, &$extension_groups_rows, &$existing_extension_groups)
{
	global $db;

	$existing_extension_groups = array();
	$sql_ary = array(
		'SELECT'	=> 'eg.group_name',
		'FROM'		=> array(
			EXTENSION_GROUPS_TABLE => 'eg',
		),
	);
	$sql = $db->sql_build_query('SELECT', $sql_ary);
	$result	= $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$existing_extension_groups[] = $row['group_name'];
	}
	$db->sql_freeresult($result);

	$extension_groups_rows = array_unique(array_merge(array_keys($extension_groups_data), $existing_extension_groups));
	sort($extension_groups_rows);
}

/**
* Collect the extensions for a given group
*/
function get_extensions($group, &$group_id)
{
	global $db;

	$sql_ary = array(
		'SELECT'	=> 'e.extension, eg.group_id',
		'FROM'		=> array(
			EXTENSIONS_TABLE		=> 'e',
			EXTENSION_GROUPS_TABLE	=> 'eg',
		),
		'WHERE'		=> "e.group_id = eg.group_id
							AND eg.group_name = '" . $db->sql_escape($group) . "'",
	);
	$sql = $db->sql_build_query('SELECT', $sql_ary);
	$result	= $db->sql_query($sql);
	$set	= array();
	while ($row = $db->sql_fetchrow($result))
	{
		$set[] = $row['extension'];

		if (empty($group_id))
		{
			$group_id = $row['group_id'];
		}
	}
	$db->sql_freeresult($result);

	return $set;
}

function get_permission_rows(&$permission_data, &$permission_rows, &$existing_permissions)
{
	global $db;

	$existing_permissions = array();
	$sql_ary = array(
		'SELECT'	=> 'ao.auth_option',
		'FROM'		=> array(
			ACL_OPTIONS_TABLE => 'ao',
		),
	);
	$sql = $db->sql_build_query('SELECT', $sql_ary);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$existing_permissions[] = $row['auth_option'];
	}
	$db->sql_freeresult($result);

	$permission_rows = array_unique(array_merge(array_keys($permission_data), $existing_permissions));
	sort($permission_rows);
}

function get_role_rows(&$roles_data, &$role_rows, &$existing_roles)
{
	global $db;

	$existing_roles = array();
	$sql_ary = array(
		'SELECT'	=> 'ar.role_name',
		'FROM'		=> array(
			ACL_ROLES_TABLE => 'ar',
		),
	);
	$sql = $db->sql_build_query('SELECT', $sql_ary);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$existing_roles[] = $row['role_name'];
	}
	$db->sql_freeresult($result);

	$role_rows = array_unique(array_merge(array_keys($roles_data), $existing_roles));
	sort($role_rows);
}

/**
* Get all the phpBB system groups
*/
function get_group_rows(&$group_data, &$group_rows, &$existing_groups)
{
	global $db;

	$existing_groups = array();
	$sql_ary = array(
		'SELECT'	=> 'g.group_name',
		'FROM'		=> array(
			GROUPS_TABLE => 'g',
		),
		'WHERE'		=> 'group_type = 3',
	);
	$sql = $db->sql_build_query('SELECT', $sql_ary);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$existing_groups[] = $row['group_name'];
	}
	$db->sql_freeresult($result);

	$group_rows = array_unique(array_merge(array_keys($group_data), $existing_groups));
	sort($group_rows);
}

/**
* Get the columns of a given database table
* @param String $table The name of the table
*/
function get_columns($table)
{
	global $db;

	// Set the query and column for each dbms
	static $sql = '';
	static $column_name = '';
	$dbms = $db->get_sql_layer();

	if (empty($sql))
	{
		switch ($db->get_sql_layer())
		{
			// MySQL
			case 'mysql'	:
			case 'mysqli'	:
			case 'mysql4'	:
			case 'mysql_40'	:
			case 'mysql_41'	:
				$sql = "SHOW COLUMNS FROM %s";
				$column_name = 'Field';
			break;

			// PostgreSQL
			case 'postgres'	:
				$sql = "SELECT a.attname
					FROM pg_class c, pg_attribute a
					WHERE c.relname = '%s'
						AND a.attnum > 0
						AND a.attrelid = c.oid";
				$column_name = 'attname';
			break;

			// MsSQL
			case 'mssql'		:
			case 'mssqlnative'	:
				$sql = "SELECT c.name
					FROM syscolumns c
					LEFT JOIN sysobjects o ON c.id = o.id
					WHERE o.name = '%s'";
				$column_name = 'name';
			break;

			// Oracle
			case 'oracle'	:
				$sql = "SELECT column_name
					FROM user_tab_columns
					WHERE table_name = '%s'";
				$column_name = 'column_name';
			break;

			// SQLite
			case 'sqlite'	:
			case 'sqlite3'	:
				$sql = "SELECT sql
					FROM sqlite_master
					WHERE type = 'table'
						AND name = '%s'";
				$column_name = 'sql';
			break;
		}
	}

	// Run the query
	$result = $db->sql_query(sprintf($sql, $table));

	// Get the columns
	$columns = array();

	if ($db->get_sql_layer() != 'sqlite' || $db->get_sql_layer() != 'sqlite3')
	{
		while ($row = $db->sql_fetchrow($result))
		{
			array_push($columns, $row[$column_name]);
		}
	}
	else
	{
		// Unfortunately SQLite doen't play as nice as the others
		$col_ary = $entities = $matches = array();
		$cols = $declaration = '';

		while ($row = $db->sql_fetchrow($result))
		{
			preg_match('#\((.*)\)#s', $row[$column_name], $matches);

			$cols = trim($matches[1]);
			$col_ary = preg_split('/,(?![\s\w]+\))/m', $cols);

			foreach ($col_ary as $declaration)
			{
				$entities = preg_split('#\s+#', trim($declaration));
				if ($entities[0] == 'PRIMARY')
				{
					continue;
				}

				array_push($columns, $entities[0]);
			}
		}
	}

	$db->sql_freeresult($result);
	return $columns;
}

/**
* Get all tables used by phpBB
*/
function get_phpbb_tables()
{
	global $db, $table_prefix;

	static $_tables = array();
	if (!empty($_tables))
	{
		return $_tables;
	}

	if (!function_exists('get_tables'))
	{
		include PHPBB_ROOT_PATH . 'includes/functions_install.' . PHP_EXT;
	}

	// Function returns all tables in the database
	$all_tables = get_tables($db);

	// @TODO: tprefix, uppercase voor firebird/oracle!

	// Only get tables using the phpBB prefix
	if (!empty($table_prefix))
	{
		foreach ($all_tables as $table)
		{
			// Use `stripos` for Oracle and Firebird support. (#62821)
			if (stripos($table, $table_prefix) === 0)
			{
				$_tables[] = $table;
			}
		}
	}
	else
	{
		// Use is using an empty table prefix (Bug #62537)
		// no way to determine the phpBB tables, in this case
		// we'll show everything with a warning that the tool
		// most likely want to trash a lot of tables '-,-
		global $template;

		$template->assign_vars(array(
			'ERROR_MESSAGE' => user_lang('EMPTY_PREFIX_EXPLAIN'),
			'ERROR_TITLE'	=> user_lang('EMPTY_PREFIX'),
		));

		$_tables = $all_tables;
	}

	sort($_tables);

	return $_tables;
}

/**
* Compile the cleaner data
* @param database_cleaner_data The database cleaner data object
* @param String The version
*/
function fetch_cleaner_data(&$data, $phpbb_version)
{
	global $config;

	// Fetch all the files
	if (!function_exists('filelist'))
	{
		include PHPBB_ROOT_PATH . 'includes/functions_admin.' . PHP_EXT;
	}
	$filelist = array_shift(filelist(STK_ROOT_PATH . 'includes/database_cleaner/', 'data/', PHP_EXT));
	usort($filelist, 'version_compare');

	// Add the data
	foreach ($filelist as $file)
	{
		$version	= pathinfo_filename($file);
		$class		= 'datafile_' . $version;
		if (!class_exists($class))
		{
			include STK_ROOT_PATH . "includes/database_cleaner/data/{$version}." . PHP_EXT;
		}
		$_datafile = new $class();

		// Set the data
		$data->bots					= array_merge($data->bots, $_datafile->bots);
		$data->config				= array_merge($data->config, $_datafile->config);
		$data->acl_options			= array_merge($data->acl_options, $_datafile->acl_options);
		$data->acl_roles			= array_merge($data->acl_roles, $_datafile->acl_roles);
		$data->acl_role_data		= array_merge_recursive($data->acl_role_data, $_datafile->acl_role_data);
		$data->extension_groups		= array_merge($data->extension_groups, $_datafile->extension_groups);
		$data->extensions			= array_merge($data->extensions, $_datafile->extensions);
		$data->module_categories	= array_merge($data->module_categories, $_datafile->module_categories);
		$data->module_extras		= array_merge($data->module_extras, $_datafile->module_extras);
		$data->groups				= array_merge($data->groups, $_datafile->groups);
		$data->report_reasons		= array_merge($data->report_reasons, $_datafile->report_reasons);
		$data->acp_modules			= array_merge($data->acp_modules, $_datafile->acp_modules);
		$data->module_categories_basenames			= array_merge($data->module_categories_basenames, $_datafile->module_categories_basenames);

		$_datafile->get_schema_struct($data->schema_data);

		// Just make sure that nothing sticks
		unset($_datafile);

		// Break after our version
		if (version_compare($version, $phpbb_version, 'eq'))
		{
			break;
		}
	}

	// Perform some actions that only have to be done on given versions or on all
	switch($phpbb_version)
	{
		case '3_1_2'	:
			// The extension group names have been changed, remove the old ones
			foreach ($data->extension_groups as $key => $null)
			{
				if (strpos($key, 'EXT_') === 0)
				{
					unset($data->extension_groups[$key]);
				}
			}

			// Same for the extensions
			foreach ($data->extensions as $key => $null)
			{
				if (strpos($key, 'EXT_') === 0)
				{
					unset($data->extensions[$key]);
				}
			}

			// If $config['questionnaire_unique_id] exists add it to the config data array
			if (isset($config['questionnaire_unique_id']))
			{
				$data->config['questionnaire_unique_id'] = array('config_value' => $config['questionnaire_unique_id'], 'is_dynamic' => '0');
			}

			// Need to force do some ordering on $module_extras
			$extra_add = array('ACP_FORUM_PERMISSIONS_COPY');
			array_splice($data->module_extras['acp']['ACP_FORUM_BASED_PERMISSIONS'], 1, 0, $extra_add);

			$data->config['version'] = $phpbb_version;		// We always need to set the version afterwards
		break;
	}

	// Call init
	$data->init();

/**
* Find any non standart modules in database
*/
	function get_acp_modules($acp_modules, &$acp_rows)
	{
		global $db, $user, $phpEx, $phpbb_root_path;

		$main_modules = array('acp', 'ucp', 'mcp');
		$i = 0;
		foreach($main_modules as $main)
		{
			$sql = 'SELECT *
				FROM ' . MODULES_TABLE . '
				WHERE module_class = \''. $main .'\'';
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				if(!array_find($acp_modules, $row['module_langname']))
				{
					$module = $row['module_langname'];
					$module_id = $row['module_id'];
					$sql = 'SELECT *
						FROM ' . MODULES_TABLE . '
						WHERE module_id = ' . $row['parent_id'] . '';
					$res = $db->sql_query($sql);
					$row = $db->sql_fetchrow($res);
					$parent = $row['module_langname'];
					$parent_id = $row['module_id'];
					if ($parent)
					{
						$link = append_sid("{$phpbb_root_path}adm/index.$phpEx", 'i=acp_modules&amp;sid=' . $user->data['session_id'] .'&amp;mode=' . $main . '&parent_id='. $parent_id .'');
						$module_mame = (isset($user->lang[$parent])) ? '<b>' . $user->lang[$parent] . '</b>' : '<i>' . $user->lang['UNDEFINED'] . '</i>';

					}
					else
					{
						$link = append_sid("{$phpbb_root_path}adm/index.$phpEx", 'i=acp_modules&amp;sid=' . $user->data['session_id'] .'&amp;mode=' . $main . '');
						$module_mame = '';
					}
					$acp_rows[$i]['class'] = $main;
					$acp_rows[$i]['name'] = $module_id;
					$acp_rows[$i]['acp_name'] = $module;
					$acp_rows[$i]['module_name'] = $module_mame;
					$acp_rows[$i]['link'] = $link;
					$acp_rows[$i]['parent_id'] = $parent_id;
					$acp_rows[$i]['parent_module_name'] = $parent;
					$db->sql_freeresult($res);
					$i++;

				}
			}
			$db->sql_freeresult($result);
		}
	}

/**
* Find element in multidimensional associative array
*/
	function array_find($array, $needle)
	{
		foreach($array as $cat)
		{
			if (array_key_exists($needle, $cat))
			{
				return true;
			}
			if (in_array($needle, $cat))
			{
				return true;
			}

			foreach($cat as $main_module)
			{
				if (array_key_exists($needle, $main_module))
				{
					return true;
				}
				if (in_array($needle, $main_module))
				{
					return true;
				}
				foreach($main_module as $module)
				{
					if(is_array($module))
					{
						if (array_key_exists($needle, $module))
						{
							return true;
						}
						if (in_array($needle, $module))
						{
							return true;
						}
					}
				}
			}
		}
		return false;
	}
}
