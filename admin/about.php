<?php
/* Advance Targeting Emailling for mass emailing module
 * Copyright (C) 2013  Florian Henry <florian.henry@open-concept.pro>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/about.php
 * 	\ingroup	mymodule
 * 	\brief		This file is an example about page
 * 				Put some comments here
 */
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if ( ! $res)
		$res = @include("../../../main.inc.php"); // From "custom" directory


// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once "../lib/advtargetemailing.lib.php";

dol_include_once('/advtargetemailing/lib/PHP_Markdown_1.0.1o/markdown.php');

// Translations
$langs->load("advtargetemailing@advtargetemailing");

// Access control
if ( ! $user->admin) accessforbidden();


/*
 * Actions
 */

/*
 * View
 */
$page_name = "AdvTgtAbout";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
	. $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = advtargetemailingadmin_prepare_head();
dol_fiche_head($head, 'about', $langs->trans("Module103117Name"), 0,
	"advtargetemailing@advtargetemailing");

// About page goes here
echo $langs->trans("AdvTgtAboutPage");

print '<br>';

$buffer = file_get_contents(dol_buildpath('/advtargetemailing/README.md',0));
print Markdown($buffer);

print '<br>';

print '<a href="'.dol_buildpath('/advtargetemailing/COPYING',1).'">';

print '<img src="'.dol_buildpath('/advtargetemailing/img/gplv3.png',1).'"/>';

print '</a>';

llxFooter();

$db->close();
?>
