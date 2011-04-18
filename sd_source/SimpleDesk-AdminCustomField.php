<?php
###############################################################
#         Simple Desk Project - www.simpledesk.net            #
###############################################################
#       An advanced help desk modifcation built on SMF        #
###############################################################
#                                                             #
#         * Copyright 2010 - SimpleDesk.net                   #
#                                                             #
#   This file and its contents are subject to the license     #
#   included with this distribution, license.txt, which       #
#   states that this software is New BSD Licensed.            #
#   Any questions, please contact SimpleDesk.net              #
#                                                             #
###############################################################
# SimpleDesk Version: 1.0 Felidae                             #
# File Info: SimpleDesk-AdminCustomField.php / 1.0 Felidae    #
###############################################################

/**
 *	This file handles the core of SimpleDesk's custom ticket fields interface and code.
 *
 *	@package source
 *	@since 1.1
*/
if (!defined('SMF'))
	die('Hacking attempt...');

/**
 *	The start point for all interaction with the SimpleDesk custom field administration.
 *
 *	Directed here from the main administration centre, after permission checks and a few dependencies loaded, this deals solely with managing custom fields.
 *
 *	@since 1.1
*/
function shd_admin_custom()
{
	global $context, $scripturl, $sourcedir, $settings, $txt, $modSettings;

	loadTemplate('sd_template/SimpleDesk-AdminCustomField');

	$subactions = array(
		'main' => 'shd_admin_custom_main',
		'new' => 'shd_admin_custom_new',
		'edit' => 'shd_admin_custom_edit',
		'move' => 'shd_admin_custom_move',
		'save' => 'shd_admin_custom_save',
	);

	$_REQUEST['sa'] = isset($_REQUEST['sa']) && isset($subactions[$_REQUEST['sa']]) ? $_REQUEST['sa'] : 'main';

	$context['field_types'] = array(
		CFIELD_TYPE_TEXT => array($txt['shd_admin_custom_fields_ui_text'], 'text'),
		CFIELD_TYPE_LARGETEXT => array($txt['shd_admin_custom_fields_ui_largetext'], 'largetext'),
		CFIELD_TYPE_INT => array($txt['shd_admin_custom_fields_ui_int'], 'int'),
		CFIELD_TYPE_FLOAT => array($txt['shd_admin_custom_fields_ui_float'], 'float'),
		CFIELD_TYPE_SELECT => array($txt['shd_admin_custom_fields_ui_select'], 'select'),
		CFIELD_TYPE_CHECKBOX => array($txt['shd_admin_custom_fields_ui_checkbox'], 'checkbox'),
		CFIELD_TYPE_RADIO => array($txt['shd_admin_custom_fields_ui_radio'], 'radio'),
	);

	$subactions[$_REQUEST['sa']]();
}

/**
 *	Display all the custom fields, including new/edit/save/delete UI hooks
 *
 *	@since 1.1
*/
function shd_admin_custom_main()
{
	global $context, $smcFunc, $modSettings, $txt;

	$context['custom_fields'] = array();

	$query = shd_db_query('', '
		SELECT id_field, active, field_order, field_name, field_desc, field_loc, icon, field_type, can_see, can_edit
		FROM {db_prefix}helpdesk_custom_fields',
		array()
	);

	while ($row = $smcFunc['db_fetch_assoc']($query))
	{
		$row['active_string'] = empty($row['active']) ? 'inactive' : 'active';
		$row['field_type'] = $context['field_types'][$row['field_type']][1]; // convert the integer in the DB into the string for language + image uses
		$row['can_see'] = explode(',',$row['can_see']);
		$row['can_edit'] = explode(',',$row['can_edit']);
		$context['custom_fields'][] = $row;
	}

	ksort($context['custom_fields']);

	if (!empty($context['custom_fields']))
	{
		$context['custom_fields'][0]['is_first'] = true;
		$context['custom_fields'][count($context['custom_fields']) - 1]['is_last'] = true;
	}

	// Final stuff before we go.
	$context['page_title'] = $txt['shd_admin_custom_fields'];
	$context['sub_template'] = 'shd_custom_field_home';
}

/**
 *	Display the new field UI
 *
 *	@since 1.1
*/
function shd_admin_custom_new()
{
	global $context, $smcFunc, $modSettings, $txt, $scripturl;

	$context = array_merge($context, array(
		'sub_template' => 'shd_custom_field_edit',
		'page_title' => $txt['shd_admin_new_custom_field'],
		'section_title' => $txt['shd_admin_new_custom_field'],
		'section_desc' => $txt['shd_admin_new_custom_field_desc'],
		'field_type_value' => CFIELD_TYPE_TEXT,
		'field_icons' => shd_admin_cf_icons(),
		'field_icon_value' => '',
		'new_field' => true,
		'field_loc' => CFIELD_TICKET,
		'field_active' => ' checked="checked"',
		'placement' => CFIELD_PLACE_DETAILS,
	));

	$context['custom_field']['options'] = array(1 => '', '', '');
	$context['custom_field']['default_value'] = false;

	// Get the list of departments, and whether a field is required in each department.
	$context['dept_fields'] = array();
	$query = $smcFunc['db_query']('', '
		SELECT hdd.id_dept, hdd.dept_name, 0 AS present, 0 AS required
		FROM {db_prefix}helpdesk_depts AS hdd
		ORDER BY hdd.dept_order',
		array()
	);
	while ($row = $smcFunc['db_fetch_assoc']($query))
		$context['dept_fields'][$row['id_dept']] = $row;
	$smcFunc['db_free_result']($query);
}

/**
 *	Display the edit field UI
 *
 *	@since 1.1
*/
function shd_admin_custom_edit()
{
	global $context, $smcFunc, $modSettings, $txt;

	$_REQUEST['field'] = isset($_REQUEST['field']) ? (int) $_REQUEST['field'] : 0;

	$query = shd_db_query('', '
		SELECT id_field, active, field_order, field_name, field_desc, field_loc, icon, field_type,
		field_length, field_options, bbc, default_value, can_see, can_edit, display_empty, placement
		FROM {db_prefix}helpdesk_custom_fields
		WHERE id_field = {int:field}',
		array(
			'field' => $_REQUEST['field'],
		)
	);

	if ($row = $smcFunc['db_fetch_assoc']($query))
	{
		$smcFunc['db_free_result']($query);
		$context['custom_field'] = $row;
		$context['section_title'] = $txt['shd_admin_edit_custom_field'];
		$context['section_desc'] = $txt['shd_admin_edit_custom_field_desc'];
		$context['page_title'] = $txt['shd_admin_edit_custom_field'];
		$context['sub_template'] = 'shd_custom_field_edit';
		$context['custom_field']['options'] = !empty($row['field_options']) ? unserialize($row['field_options']) : array(1 => '', '', '');

		// If this is a textarea, we need to get its dimensions too.
		if ($context['custom_field']['field_type'] == CFIELD_TYPE_LARGETEXT)
			$context['custom_field']['dimensions'] = explode(',',$context['custom_field']['default_value']);

		$context['custom_field']['can_see'] = explode(',',$context['custom_field']['can_see']);
		$context['custom_field']['can_edit'] = explode(',',$context['custom_field']['can_edit']);

		$context = array_merge($context, array(
			'field_type_value' => $context['custom_field']['field_type'],
			'field_icons' => shd_admin_cf_icons(),
			'field_icon_value' => $context['custom_field']['icon'],
			'field_loc' => $context['custom_field']['field_loc'],
			'field_active' => $context['custom_field']['active'] == 1 ? ' checked="checked"' : '',
			'placement' => $context['custom_field']['placement'],
		));

		// Get the list of departments, and whether a field is required in each department.
		$context['dept_fields'] = array();
		$query = $smcFunc['db_query']('', '
			SELECT hdd.id_dept, hdd.dept_name, cfd.id_dept AS present, cfd.required
			FROM {db_prefix}helpdesk_depts AS hdd
				LEFT JOIN {db_prefix}helpdesk_custom_fields_depts AS cfd ON (cfd.id_field = {int:field} AND hdd.id_dept = cfd.id_dept)
			ORDER BY hdd.dept_order',
			array(
				'field' => $_REQUEST['field'],
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($query))
			$context['dept_fields'][$row['id_dept']] = $row;
		$smcFunc['db_free_result']($query);
	}
	else
	{
		$smcFunc['db_free_result']($query);
		fatal_lang_error('shd_admin_cannot_edit_custom_field', false);
	}
}

/**
 *	Handle saving a field
 *
 *	@since 1.1
*/
function shd_admin_custom_save()
{
	global $context, $smcFunc, $modSettings;

	checkSession('request');

	// Deletifyingistuffithingi?
	if (isset($_REQUEST['delete']) && !empty($_REQUEST['field']))
	{
		$_REQUEST['field'] = (int) $_REQUEST['field'];
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}helpdesk_custom_fields
			WHERE id_field = {int:field}',
			array(
				'field' => $_REQUEST['field'],
			)
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}helpdesk_custom_fields_values
			WHERE id_field = {int:field}',
			array(
				'field' => $_REQUEST['field'],
			)
		);

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}helpdesk_custom_fields_depts
			WHERE id_field = {int:field}',
			array(
				'field' => $_REQUEST['field'],
			)
		);

		// End of the road
		redirectexit('action=admin;area=helpdesk_customfield;' . $context['session_var'] . '=' . $context['session_id']);
	}

	// Aborting mission!
	if (isset($_POST['cancel']))
	{
		redirectexit('action=admin;area=helpdesk_customfield;' . $context['session_var'] . '=' . $context['session_id']);
	}

	// Fix all the input
	if (trim($_POST['field_name']) == '')
		fatal_lang_error('shd_admin_no_fieldname', false);
	$_POST['field_name'] = $smcFunc['htmlspecialchars']($_POST['field_name']);
	$_POST['description'] = $smcFunc['htmlspecialchars'](isset($_POST['description']) ? $_POST['description'] : '');
	$_POST['bbc'] = isset($_POST['bbc']) && in_array($_POST['field_type'], array(CFIELD_TYPE_TEXT, CFIELD_TYPE_LARGETEXT)) ? 1 : 0;
	$_POST['display_empty'] = isset($_POST['display_empty']) && $_POST['field_type'] != CFIELD_TYPE_CHECKBOX ? 1 : 0;

	$_POST['active'] = isset($_POST['active']) ? 1 : 0;
	$_POST['field_length'] = isset($_POST['field_length']) ? (int) $_POST['field_length'] : 255;
	$_POST['default_check'] = isset($_POST['default_check']) && $_POST['field_type'] == CFIELD_TYPE_CHECKBOX ? 1 : '';
	if ($_POST['field_type'] == CFIELD_TYPE_LARGETEXT)
		$_POST['default_check'] = (int) $_POST['rows'] . ',' . (int) $_POST['cols'];
	if (!isset($_POST['placement']) || !in_array($_POST['placement'], array(CFIELD_PLACE_DETAILS, CFIELD_PLACE_INFO, CFIELD_PLACE_PREFIX)))
		$_POST['placement'] = CFIELD_PLACE_DETAILS;
	$_POST['field_icon'] = isset($_POST['field_icon']) && preg_match('~^[A-Za-z0-9.-_]+$~', $_POST['field_icon']) ? $_POST['field_icon'] : '';
	$options = '';

	$users_see = !empty($_POST['see_users']) ? '1' : '0';
	$users_edit = $users_see == '1' && !empty($_POST['edit_users']) ? '1' : '0';

	$staff_see = !empty($_POST['see_staff']) ? '1' : '0';
	$staff_edit = $staff_see == '1' && !empty($_POST['edit_staff']) ? '1' : '0';

	$can_see = $users_see . ',' . $staff_see;
	$can_edit = $users_edit . ',' . $staff_edit;

	// Get the list of departments, and whether a field is required in each department.
	$context['dept_fields'] = array();
	$query = $smcFunc['db_query']('', '
		SELECT hdd.id_dept, hdd.dept_name
		FROM {db_prefix}helpdesk_depts AS hdd
		ORDER BY hdd.dept_order',
		array(
			'field' => $_REQUEST['field'],
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($query))
	{
		// Is the field meant to be in this department?
		if (empty($_POST['present_dept' . $row['id_dept']]))
			continue;
		$context['dept_fields'][$row['id_dept']] = $row;
		$context['dept_fields'][$row['id_dept']]['required'] = isset($_POST['required_dept' . $row['id_dept']]) ? 1 : 0;
	}
	$smcFunc['db_free_result']($query);

	// Select options?
	$newOptions = array();
	if (!empty($_POST['select_option']) && ($_POST['field_type'] == CFIELD_TYPE_SELECT || $_POST['field_type'] == CFIELD_TYPE_RADIO))
	{
		foreach ($_POST['select_option'] as $k => $v)
		{
			// Clean, clean, clean...
			$v = $smcFunc['htmlspecialchars']($v);

			// Nada, zip, etc...
			if (trim($v) == '')
				continue;

			// This is just for working out what happened with old options...
			$newOptions[$k] = $v;

			// Is it default?
			if (isset($_POST['default_select']) && $_POST['default_select'] == $k)
				$_POST['default_check'] = $k;
		}
		$options = serialize($newOptions);
	}

	// Do I feel a new field being born?
	if (isset($_REQUEST['new']))
	{
		// Order??
		$count_query = shd_db_query('', '
			SELECT COUNT(id_field) AS count
			FROM {db_prefix}helpdesk_custom_fields',
			array()
		);

		$row = $smcFunc['db_fetch_assoc']($count_query);

		$smcFunc['db_insert']('insert',
			'{db_prefix}helpdesk_custom_fields',
			array(
				'active' => 'int', 'field_order' => 'int', 'field_name' => 'string', 'field_desc' => 'string',
				'field_loc' => 'int', 'icon' => 'string', 'field_type' => 'int', 'field_length' => 'int',
				'field_options' => 'string', 'bbc' => 'int', 'default_value' => 'string', 'can_see' => 'string',
				'can_edit' => 'string', 'display_empty' => 'int', 'placement' => 'int',
			),
			array(
				$_POST['active'], $row['count'], $_POST['field_name'], $_POST['description'],
				$_POST['field_visible'],$_POST['field_icon'], $_POST['field_type'], $_POST['field_length'],
				$options, $_POST['bbc'], $_POST['default_check'], $can_see,
				$can_edit, $_POST['display_empty'], $_POST['placement'],
			),
			array(
				'id_field',
			)
		);

		$new_field = $smcFunc['db_insert_id']('{db_prefix}helpdesk_custom_fields', 'id_field');
		if (empty($new_field))
			fatal_lang_error('shd_admin_could_not_create_field', false);

		// Also update fields
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}helpdesk_custom_fields_depts
			WHERE id_field = {int:field}',
			array(
				'field' => $new_field,
			)
		);
		$fields = array();
		foreach ($context['dept_fields'] as $id => $row)
			$fields[] = array($new_field, $id, $row['required']);

		$smcFunc['db_insert']('replace',
			'{db_prefix}helpdesk_custom_fields_depts',
			array(
				'id_field' => 'int', 'id_dept' => 'int', 'required' => 'int',
			),
			$fields,
			array(
				'id_field', 'id_dept',
			)
		);

		redirectexit('action=admin;area=helpdesk_customfield;' . $context['session_var'] . '=' . $context['session_id']);
	}
	// No? Meh. Update it is then.
	else
	{
		shd_db_query('', '
			UPDATE {db_prefix}helpdesk_custom_fields
			SET
				active = {int:active}, field_name = {string:field_name},
				field_desc = {string:field_desc}, field_loc = {int:field_visible},
				icon = {string:field_icon}, field_type = {int:field_type},
				field_length = {int:field_length}, field_options = {string:field_options},
				bbc = {int:bbc}, default_value = {string:default_value}, can_see = {string:can_see},
				can_edit = {string:can_edit}, display_empty = {int:display_empty}, placement = {int:placement}
			WHERE id_field = {int:id_field}',
			array(
				'id_field' => $_REQUEST['field'],
				'active' => $_POST['active'],
				'field_name' => $_POST['field_name'],
				'field_desc' => $_POST['description'],
				'field_visible' => $_POST['field_visible'],
				'field_icon' => $_POST['field_icon'],
				'field_type' => $_POST['field_type'],
				'field_length' => $_POST['field_length'],
				'field_options' => $options,
				'bbc' => $_POST['bbc'],
				'default_value' => $_POST['default_check'],
				'can_see' => $can_see,
				'can_edit' => $can_edit,
				'display_empty' => $_POST['display_empty'],
				'placement' => $_POST['placement'],
			)
		);

		// Also update fields
		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}helpdesk_custom_fields_depts
			WHERE id_field = {int:field}',
			array(
				'field' => $_REQUEST['field'],
			)
		);
		$fields = array();
		foreach ($context['dept_fields'] as $id => $row)
			$fields[] = array($_REQUEST['field'], $id, $row['required']);

		$smcFunc['db_insert']('replace',
			'{db_prefix}helpdesk_custom_fields_depts',
			array(
				'id_field' => 'int', 'id_dept' => 'int', 'required' => 'int',
			),
			$fields,
			array(
				'id_field', 'id_dept',
			)
		);

		redirectexit('action=admin;area=helpdesk_customfield;' . $context['session_var'] . '=' . $context['session_id']);
	}
}

/**
 *	Handle moving a custom field up or down
 *
 *	@since 1.1
*/
function shd_admin_custom_move()
{
	global $context, $smcFunc, $modSettings;

	checkSession('get');

	$_REQUEST['field'] = isset($_REQUEST['field']) ? (int) $_REQUEST['field'] : 0;
	$_REQUEST['direction'] = isset($_REQUEST['direction']) && in_array($_REQUEST['direction'], array('up', 'down')) ? $_REQUEST['direction'] : '';

	$query = shd_db_query('', '
		SELECT id_field, field_order
		FROM {db_prefix}helpdesk_custom_fields',
		array()
	);

	if ($smcFunc['db_num_rows']($query) == 0 || empty($_REQUEST['direction']))
	{
		$smcFunc['db_free_result']($query);
		fatal_lang_error('shd_admin_cannot_move_custom_field', false);
	}

	$fields = array();
	while ($row = $smcFunc['db_fetch_assoc']($query))
		$fields[$row['field_order']] = $row['id_field'];

	ksort($fields);

	$fields_map = array_flip($fields);
	if (empty($fields_map[$_REQUEST['field']]))
		fatal_lang_error('shd_admin_cannot_move_custom_field', false);

	$current_pos = $fields_map[$_REQUEST['field']];
	$destination = $current_pos + ($_REQUEST['direction'] == 'up' ? -1 : 1);

	if (empty($fields[$destination]))
		fatal_lang_error('shd_admin_cannot_move_custom_field_' . $_REQUEST['direction'], false);

	$other_field = $fields[$destination];

	shd_db_query('', '
		UPDATE {db_prefix}helpdesk_custom_fields
		SET field_order = {int:new_pos}
		WHERE id_field = {int:field}',
		array(
			'new_pos' => $destination,
			'field' => $_REQUEST['field'],
		)
	);

	shd_db_query('', '
		UPDATE {db_prefix}helpdesk_custom_fields
		SET field_order = {int:old_pos}
		WHERE id_field = {int:other_field}',
		array(
			'old_pos' => $current_pos,
			'other_field' => $other_field,
		)
	);

	redirectexit('action=admin;area=helpdesk_customfield;' . $context['session_var'] . '=' . $context['session_id']);
}

/**
 *	Get possible icons
 *
 *	@return array A list of possible images for the icon selector (everything in Themes/default/images/simpledesk/cf/ that's an image). Each item in the principle array is an array of value/caption pairs.
 *	@since 1.1
*/
function shd_admin_cf_icons()
{
	global $context, $settings, $txt;

	static $iconlist = null;

	if ($iconlist !== null)
		return $iconlist;

	$iconlist = array(
		array('', $txt['shd_admin_custom_fields_none']),
	);

	// Open the directory..
	$dir = dir($settings['default_theme_dir'] . '/images/simpledesk/cf/');
	$files = array();

	if (!$dir)
		return $iconlist;

	while ($line = $dir->read())
		$files[] = $line;
	$dir->close();

	// Sort the results...
	natcasesort($files);

	foreach ($files as $line)
	{
		$filename = substr($line, 0, (strlen($line) - strlen(strrchr($line, '.'))));
		$extension = substr(strrchr($line, '.'), 1);

		// Make sure it is an image.
		if (strcasecmp($extension, 'gif') != 0 && strcasecmp($extension, 'jpg') != 0 && strcasecmp($extension, 'jpeg') != 0 && strcasecmp($extension, 'png') != 0 && strcasecmp($extension, 'bmp') != 0)
			continue;

		$iconlist[] = array(htmlspecialchars($filename. '.' .$extension),htmlspecialchars(str_replace('_', ' ', $filename)));
	}

	return $iconlist;
}

?>