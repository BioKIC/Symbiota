<?php
/*
------------------
Language: English
------------------
*/

include_once($SERVER_ROOT.'/content/lang/checklists/checklistadmin.en.php');

$LANG['NEED_NAME'] = 'Checklist name field must have a value';
$LANG['NEED_LONG'] = 'If latitude has a value, longitude must also have a value';
$LANG['LAT_NUMERIC'] = 'Latitude must be strictly numeric (decimal format: e.g. 34.2343)';
$LANG['NO_NINETY'] = 'Latitude values can not be greater than 90 or less than -90';
$LANG['NEED_LAT'] = 'If longitude has a value, latitude must also have a value';
$LANG['LONG_NUMERIC'] = 'Longitude must be strictly numeric (decimal format: e.g. -112.2343)';
$LANG['NO_ONE_EIGHTY'] = 'Longitude values can not be greater than 180 or less than -180';
$LANG['NUMERIC_RADIUS'] = 'Point radius must be a numeric value only';
$LANG['NEED_STATE'] = 'Rare species checklists must have a state value entered into the locality field';
$LANG['NEED_PARENT'] = 'You need to select a parent checklist to create an Exclude Species Checklist';
$LANG['CREATE_CHECKLIST'] = 'Create a New Checklist';
$LANG['SELECT_PARENT'] = 'Select a parent checklist';
$LANG['REFERENCE_CHECK'] = 'More Inclusive Reference Checklist';
$LANG['NONE'] = 'None Selected';
$LANG['ASSIGNED_CHECKLISTS'] = 'Checklists assigned to your account';
$LANG['NO_CHECKLISTS'] = 'You have no personal checklists';
$LANG['CLICK_TO_CREATE'] = 'Click here to create a new checklist';
$LANG['PROJ_ADMIN'] = 'Inventory Project Administration';
$LANG['EDIT_PROJECT'] = 'Edit Project';
$LANG['NO_PROJECTS'] = 'There are no Projects for which you have administrative permissions';
$LANG['INATURALIST'] = 'iNaturalist';

?>