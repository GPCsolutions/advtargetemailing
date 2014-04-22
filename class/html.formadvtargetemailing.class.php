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
	 * @param int $selected_array à preselectionner
	 * @param string $htmlname select field
	 * @param int $showempty empty field
	 * @return string select field
	 */
	function multiselect_prospection_status($selected_array, $htmlname = 'cust_prospect_status') {
	
		global $conf, $langs;
		$options_array=array();
	
		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY sortorder";
		dol_syslog(get_class($this).'::multiselect_prospection_status sql='.$sql,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
	
				$level=$langs->trans($obj->code);
				if ($level == $obj->code) $level=$langs->trans($obj->label);
				$options_array[$obj->code]=$level;

				$i++;
			}
		}else {
			dol_print_error($this->db);
		}
		return $this->multiselectarray($htmlname,$options_array,$selected_array);
	}
	
	/**
	 * Return multiselect list of entities.
	 *
	 * @param string $htmlname select
	 * @param array $options_array to manage
	 * @param array $selected_array to manage
	 * @param int   $showempty show empty
	 * @return void
	 */
	function multiselectarray($htmlname, $options_array=array(), $selected_array=array(),$showempty=0) {
	
		global $conf, $langs;

		$return =  '<script type="text/javascript" language="javascript">
						$(document).ready(function() {
							$.extend($.ui.multiselect.locale, {
								addAll:\'' . $langs->transnoentities ( "AddAll" ) . '\',
								removeAll:\'' . $langs->transnoentities ( "RemoveAll" ) . '\',
								itemsCount:\'' . $langs->transnoentities ( "ItemsCount" ) . '\'
							});
											
							$(function(){
								$("#'.$htmlname.'").addClass("'.$htmlname.'").attr("multiple","multiple").attr("name","'.$htmlname.'[]");
								$(".multiselect").multiselect({sortable: false, searchable: false});
							});
						});
					</script>';
	
		$return .= '<select id="' . $htmlname . '" class="multiselect" multiple="multiple" name="' . $htmlname . '[]" style="display: none;">';
		if ($showempty) $return .= '<option value="">&nbsp;</option>';
		
		//Find if keys is in selected array value
		$intersect_array=array_intersect_key($options_array, array_flip($selected_array));
		
		if (count($options_array)>0) {
			foreach($options_array as $keyoption=>$valoption){
				//If key is in intersect table then it have to e selected
				if (count($intersect_array)>0) {
					if (array_key_exists($keyoption, $intersect_array)) {
						$selected=' selected="selected" ';
					} else {
						$selected='';
					}
				}	

				$return .='<option '.$selected.' value="'.$keyoption.'">'.$valoption.'</option>';
			}
		}
		
		$return .= '</select>';
	
		return $return;
	}

}