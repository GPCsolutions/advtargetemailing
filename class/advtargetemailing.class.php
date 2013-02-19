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
 * 	\file		class/advtargetemailing.class.php
 * 	\ingroup	advtargetemailing
 * 	\brief		This file is an example CRUD class file (Create/Read/Update/Delete)

 */


/**
 * Put your class' description here
 */
class AdvanceTargetingMailing // extends CommonObject
{

	var $db; //!< To store db handler
	var $error; //!< To return error code (or message)
	var $errors = array(); //!< To return several error codes (or messages)

	var $lines;


	/**
	 * Constructor
	 *
	 * 	@param	DoliDb		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
		return 1;
	}

	/**
	 * Load object in memory from database
	 *
	 * 	@param		array		$arrayquery	All element to Query
	 * 	@return		int			<0 if KO, >0 if OK
	 */
	function query($arrayquery)
	{
		global $langs,$conf;

		$sql = "SELECT";
		$sql.= " t.rowid";
		$sql.= " FROM " . MAIN_DB_PREFIX . "societe as t";
		$sql.= " LEFT OUTER JOIN " . MAIN_DB_PREFIX . "societe_extrafields as te ON te.fk_object=t.rowid ";

		if (count($arrayquery)>0) {
				
			$sqlwhere=array();
				
			//Standard Extrafield feature
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
				// fetch optionals attributes and labels
				dol_include_once('/core/class/extrafields.class.php');
				$extrafields = new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label('company');
				
				foreach($extralabels as $key=>$val) {

					if (($extrafields->attribute_type[$key] == 'varchar') ||
						($extrafields->attribute_type[$key] == 'text')) {
						if (!empty($arrayquery['options_'.$key])) {
							$sqlwhere[]= " (te.".$key." LIKE '".$arrayquery['options_'.$key]."')";
						}
					}

					if (($extrafields->attribute_type[$key] == 'int') ||
						($extrafields->attribute_type[$key] == 'double')) {
						if (!empty($arrayquery['options_'.$key.'_max'])) {
							$sqlwhere[]= " (te.".$key." >= ".$arrayquery['options_'.$key.'_max']." AND te.".$key." <= ".$arrayquery['options_'.$key.'_min'].")";
						}
					}

					if (($extrafields->attribute_type[$key] == 'date') ||
						($extrafields->attribute_type[$key] == 'datetime')) {
						if (!empty($arrayquery['options_'.$key.'_end_dt'])){
							$sqlwhere[]= " (te.".$key." >= '".$this->db->idate($arrayquery['options_'.$key.'_end_dt'])."' AND te.".$key." <= '".$this->db->idate($arrayquery['options_'.$key.'_st_dt'])."')";
						}
					}

				}


			}else {
				//non standard extrafield
				dol_include_once('/extrafields/class/actions_extrafields.class.php');
				$extrafields = new ActionsExtrafields($this->db);
				$complexstrucutre=$extrafields->loadExtraFields('company','');
				
				$extralabels=json_decode($complexstrucutre['form_structure'],true);
				
				foreach($extralabels as $extrafield) {
					if ($extrafield['active'] == 'true') {
						$fieldname= preg_replace('/^options_/','',$extrafield['code']);
					
						if ($extrafield['type'] == 'select') {
							if (!empty($arrayquery[$extrafield['code']])) {
								$sqlwhere[]= " (te.".$fieldname."='".$arrayquery[$extrafield['code']]."') ";
							}
						}
						
						if ($extrafield['type'] == 'input_text' || $extrafield['type'] == 'textarea') {
							if (!empty($arrayquery[$extrafield['code']])) {
								$sqlwhere[]= " (te.".$fieldname." LIKE '".$arrayquery[$extrafield['code']]."') ";
							}
						}
						
						if ($extrafield['type'] == 'select_date') {
							if (!empty($arrayquery[$extrafield['code'].'_from_timestamp'])) {
								$sqlwhere[]= " (te.".$fieldname." >= '".$this->db->idate($arrayquery[$extrafield['code'].'_to_timestamp']/1000)."' AND te.".$fieldname." <= '".$this->db->idate($arrayquery[$extrafield['code'].'_from_timestamp']/1000)."')";
							}
						}
						
						if ($extrafield['type'] == 'checkbox') {
							foreach ($extrafield['values'] as $value) {
								if (!empty($arrayquery[$extrafield['code']])) {
									if (in_array($value['id'],$arrayquery[$extrafield['code']])) {
										$sqlwhere[]= " (te.".$fieldname."_".$value['id']."=1) ";
									}
								}
							}
						}
						
						if ($extrafield['type'] == 'radio') {
							if (!empty($arrayquery[$extrafield['code']])) {
								foreach ($extrafield['values'] as $value) {
									if ($value['id']==$arrayquery[$extrafield['code']]) {
										$sqlwhere[]= " (te.".$fieldname."='".$arrayquery[$extrafield['code']]."') ";
									}
								}
							}
						}
						
					}
				}
			}

			if (count($sqlwhere)>0)	$sql.= " WHERE ".implode(" AND ",$sqlwhere);

		}


		dol_syslog(get_class($this) . "::fetch sql=" . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->lines = array();
			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num)
			{
				while( $i < $num)
				{
					$obj = $this->db->fetch_object($resql);
						
					$this->lines[$i] = $obj->rowid;

					$i++;
				}
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::fetch " . $this->error, LOG_ERR);
			return -1;
		}
	}


}

?>
