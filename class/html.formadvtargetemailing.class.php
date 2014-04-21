<?php
/* Copyright (C) 2014  Florian Henry   <florian.henry@open-concept.pro>
*
*
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * \file advtargetemailing/class/html.formadvtragetemaling.class.php
 * \brief Fichier de la classe des fonctions predefinie de composants html advtargetemaling
 */

/**
 * Class to manage building of HTML components
 */
class FormAdvTargetEmailing extends Form {
	var $db;
	var $error;
	

	/**
	 * Constructor
	 *
	 * @param DoliDB $db handler
	 */
	function __construct($db) {

		global $langs;
		
		
		$this->db = $db;
		
		
		
		return 1;
	}

	/**
	 * Affiche un champs select contenant une liste
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param int $showempty empty field
	 * @return string select field
	 */
	function select_prospection_status($selected, $htmlname = 'cust_prospect_status', $showempty = 0) {

		global $conf, $langs;
		
		$out = '<select class="flat" name="'.$htmlname.'">';
		if ($showempty) $out .= '<option value="">&nbsp;</option>';
		
		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY sortorder";
		dol_syslog(get_class($this).'::select_prospection_status sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
		
				$out .= '<option value="'.$obj->code.'"';
				if ($selected == $obj->code) $out .= ' selected="selected"';
				$out .= '>';
				$level=$langs->trans($obj->code);
				if ($level == $obj->code) $level=$langs->trans($obj->label);
				$out .= $level;
				$out .= '</option>';
		
				$i++;
			}
		}else {
			dol_print_error($this->db);
		}
		
		$out .= '</select>';
		
		return $out;
	}
	
	
	/**
	 * Affiche un champs select contenant une liste
	 *
	 * @param int $selectid à preselectionner
	 * @param string $htmlname select field
	 * @param int $showempty empty field
	 * @return string select field
	 */
	function multiselect_select($selected, $htmlname = 'cust_prospect_status', $showempty = 0) {
	
		global $conf, $langs;
	
		$out = '<select class="flat" name="'.$htmlname.'">';
		if ($showempty) $out .= '<option value="">&nbsp;</option>';
	
		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY sortorder";
		dol_syslog(get_class($this).'::select_prospection_status sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
	
				$out .= '<option value="'.$obj->code.'"';
				if ($selected == $obj->code) $out .= ' selected="selected"';
				$out .= '>';
				$level=$langs->trans($obj->code);
				if ($level == $obj->code) $level=$langs->trans($obj->label);
				$out .= $level;
				$out .= '</option>';
	
				$i++;
			}
		}else {
			dol_print_error($this->db);
		}
	
		$out .= '</select>';
	
		return $out;
	}

}