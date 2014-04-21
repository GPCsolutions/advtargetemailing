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


$res=@include("../../main.inc.php");				// For root directory
if (! $res) $res=@include("../../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';

dol_include_once('/advtargetemailing/class/advtargetemailing.class.php');
dol_include_once('/advtargetemailing/class/html.formadvtargetemailing.class.php');
dol_include_once('/advtargetemailing/core/modules/mailings/advthirdparties.modules.php');
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formother.class.php';


// Translations
$langs->load("mails");
$langs->load("advtargetemailing@advtargetemailing");
$langs->load("companies");

// Security check
if (! $user->rights->mailing->lire || $user->societe_id > 0) accessforbidden();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="email";


$id=GETPOST('id','int');
$rowid=GETPOST('rowid','int');
$action=GETPOST("action");
$search_nom=GETPOST("search_nom");
$search_prenom=GETPOST("search_prenom");
$search_email=GETPOST("search_email");


// Do we click on purge search criteria ?
if (GETPOST ( "button_removefilter_x" )) {
	$search_nom='';
	$search_prenom='';
	$search_email='';
}

$array_query = array();

$object = new Mailing($db);
$advTarget = new AdvanceTargetingMailing($db);
$advTarget->fk_mailing=$id;

$result=$advTarget->fetch_by_mailing();
if ($result<0) {
	setEventMessage($advTarget->error, 'errors');
} else {
	if (!empty($advTarget->id)) {
		$array_query=json_decode($advTarget->filtervalue,true);
	}
}

/*
 * Action
*/

if ($action=='addcontactandsave') {
	$action='add';
	$saveordelete='save';
}
if ($action=='addcontactanddelete') {
	$action='add';
	$saveordelete='delete';
}

if ($action=='add') {

	$user_contact_query=false;
	
	$array_query = array();

	// Get extra fields

	foreach($_POST as $key => $value)
	{
		//print '$key='.$key.' $value='.$value.'<BR>';
		if (preg_match("/^options_/",$key))	{
			//Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/",$key)) {
				$dtarr=array();
				$dtarr=explode('_',$key);
				if (!array_key_exists('options_'.$dtarr[1].'_st_dt',$array_query)) {
					$array_query['options_'.$dtarr[1].'_st_dt']=dol_mktime(0, 0, 0, GETPOST('options_'.$dtarr[1].'_st_dtmonth','int'), GETPOST('options_'.$dtarr[1].'_st_dtday','int'), GETPOST('options_'.$dtarr[1].'_st_dtyear','int'));
				}
			}elseif (preg_match("/end_dt/",$key)) {
				//Special case for end date come with 3 inputs day, month, year
				$dtarr=array();
				$dtarr=explode('_',$key);
				if (!array_key_exists('options_'.$dtarr[1].'_end_dt',$array_query)) {
					$array_query['options_'.$dtarr[1].'_end_dt']=dol_mktime(0, 0, 0, GETPOST('options_'.$dtarr[1].'_end_dtmonth','int'), GETPOST('options_'.$dtarr[1].'_end_dtday','int'), GETPOST('options_'.$dtarr[1].'_end_dtyear','int'));
					//print $array_query['options_'.$dtarr[1].'_end_dt'];
					//01/02/1013=1361228400
				}
			}else {
				$array_query[$key]=GETPOST($key);
			}
		}
		if (preg_match("/^cnct_options_/",$key))	{
			$user_contact_query=true;
			//Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/",$key)) {
				$dtarr=array();
				$dtarr=explode('_',$key);
				if (!array_key_exists('cnct_options_'.$dtarr[1].'_st_dt',$array_query)) {
					$array_query['cnct_options_'.$dtarr[1].'_st_dt']=dol_mktime(0, 0, 0, GETPOST('cnct_options_'.$dtarr[1].'_st_dtmonth','int'), GETPOST('cnct_options_'.$dtarr[1].'_st_dtday','int'), GETPOST('cnct_options_'.$dtarr[1].'_st_dtyear','int'));
				}
			}elseif (preg_match("/end_dt/",$key)) {
				//Special case for end date come with 3 inputs day, month, year
				$dtarr=array();
				$dtarr=explode('_',$key);
				if (!array_key_exists('cnct_options_'.$dtarr[1].'_end_dt',$array_query)) {
					$array_query['cnct_options_'.$dtarr[1].'_end_dt']=dol_mktime(0, 0, 0, GETPOST('cnct_options_'.$dtarr[1].'_end_dtmonth','int'), GETPOST('cnct_options_'.$dtarr[1].'_end_dtday','int'), GETPOST('cnct_options_'.$dtarr[1].'_end_dtyear','int'));
					//print $array_query['cnct_options_'.$dtarr[1].'_end_dt'];
					//01/02/1013=1361228400
				}
			}else {
				$array_query[$key]=GETPOST($key);
			}
		}
		
		if (preg_match("/^cust_/",$key)) {
			//Special case for this selector (O or '' is the samein php...)
			if ($key=='cust_typecust' ) {
				$val=GETPOST($key);
				if ($val=='') {
					$array_query[$key]=-1;
				}else {
					$array_query[$key]=$val;
				}
			} else {
				$array_query[$key]=GETPOST($key);
			}
		}
		
		if (preg_match("/^contact_/",$key)) {
			
			$array_query[$key]=GETPOST($key);
			
			$specials_date_key=array('contact_update_st_dt','contact_update_end_dt','contact_create_st_dt','contact_create_end_dt');
			foreach($specials_date_key as $date_key) {
				if ($key==$date_key) {
					$dt=GETPOST($date_key);
					if (!empty($dt)) {
						$array_query[$key]=dol_mktime(0, 0, 0, GETPOST($date_key.'month','int'), GETPOST($date_key.'day','int'), GETPOST($date_key.'year','int'));
					} else {
						$array_query[$key]='';
					}
				}
			}
			
			if ($array_query[$key]!='') {
				$user_contact_query=true;
			}
		}
		
		if (preg_match("/^type_of_target/",$key)) {
			$array_query[$key]=GETPOST($key);
		}
	}

	//var_dump($array_query);
	//exit;
	if ($saveordelete=='save') {
		$result=$advTarget->savequery($user,$array_query);
		if ($result<0) {
			setEventMessage($advTarget->error, 'errors');
		}
	}
	if ($saveordelete=='delete') {
		$result=$advTarget->delete($user);
		if ($result<0) {
			setEventMessage($advTarget->error, 'errors');
		}
	}

	$result=$advTarget->query_thirdparty($array_query);
	if ($result<0) {
		setEventMessage($advTarget->error, 'errors');
	}
	if ($user_contact_query) {
		$result=$advTarget->query_contact($array_query);
		if ($result<0) {
			setEventMessage($advTarget->error, 'errors');
		}
		//If use contact but no result use artefact to so not use socid into add_to_target
		if (count($advTarget->contact_lines)==0) {
			$advTarget->contact_lines=array(0);
		}
	} else {
		$advTarget->contact_lines=array();
	}

	if ((count($advTarget->thirdparty_lines)>0) || (count($advTarget->contact_lines)>0)) {
		// Add targets into database
		$obj = new mailing_advthirdparties($db);
		$result=$obj->add_to_target($id,$advTarget->thirdparty_lines,$array_query['type_of_target'],$advTarget->contact_lines);
	}else {$result=0;}
	
	
	if ($result > 0)
	{
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
		exit;
	}
	if ($result == 0)
	{
		setEventMessage($langs->trans("WarningNoEMailsAdded"),'warnings');
	}
	if ($result < 0)
	{
		setEventMessage($obj->error,'errors');
	}
}

if ($action == 'clear')
{
	// Chargement de la classe
	$classname = "MailingTargets";
	$obj = new $classname($db);
	$obj->clear_target($id);

	header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	exit;
}

if ($action == 'savetemplate') {

	$array_query=array();
	
	// Get extra fields
	foreach($_POST as $key => $value)
	{
		if (preg_match("/^options_/",$key))	{
			//Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/",$key)) {
				$dtarr=array();
				$dtarr=explode('_',$key);
				if (!array_key_exists('options_'.$dtarr[1].'_st_dt',$array_query)) {
					$array_query['options_'.$dtarr[1].'_st_dt']=dol_mktime(0, 0, 0, GETPOST('options_'.$dtarr[1].'_st_dtmonth','int'), GETPOST('options_'.$dtarr[1].'_st_dtday','int'), GETPOST('options_'.$dtarr[1].'_st_dtyear','int'));
				}
			}elseif (preg_match("/end_dt/",$key)) {
				//Special case for end date come with 3 inputs day, month, year
				$dtarr=array();
				$dtarr=explode('_',$key);
				if (!array_key_exists('options_'.$dtarr[1].'_end_dt',$array_query)) {
					$array_query['options_'.$dtarr[1].'_end_dt']=dol_mktime(0, 0, 0, GETPOST('options_'.$dtarr[1].'_end_dtmonth','int'), GETPOST('options_'.$dtarr[1].'_end_dtday','int'), GETPOST('options_'.$dtarr[1].'_end_dtyear','int'));
					//print $array_query['options_'.$dtarr[1].'_end_dt'];
					//01/02/1013=1361228400
				}
			}else {
				$array_query[$key]=GETPOST($key);
			}
		}
		if (preg_match("/^cnct_options_/",$key))	{
			//Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/",$key)) {
				$dtarr=array();
				$dtarr=explode('_',$key);
				if (!array_key_exists('cnct_options_'.$dtarr[1].'_st_dt',$array_query)) {
					$array_query['cnct_options_'.$dtarr[1].'_st_dt']=dol_mktime(0, 0, 0, GETPOST('cnct_options_'.$dtarr[1].'_st_dtmonth','int'), GETPOST('cnct_options_'.$dtarr[1].'_st_dtday','int'), GETPOST('cnct_options_'.$dtarr[1].'_st_dtyear','int'));
				}
			}elseif (preg_match("/end_dt/",$key)) {
				//Special case for end date come with 3 inputs day, month, year
				$dtarr=array();
				$dtarr=explode('_',$key);
				if (!array_key_exists('cnct_options_'.$dtarr[1].'_end_dt',$array_query)) {
					$array_query['cnct_options_'.$dtarr[1].'_end_dt']=dol_mktime(0, 0, 0, GETPOST('cnct_options_'.$dtarr[1].'_end_dtmonth','int'), GETPOST('cnct_options_'.$dtarr[1].'_end_dtday','int'), GETPOST('cnct_options_'.$dtarr[1].'_end_dtyear','int'));
					//print $array_query['cnct_options_'.$dtarr[1].'_end_dt'];
					//01/02/1013=1361228400
				}
			}else {
				$array_query[$key]=GETPOST($key);
			}
		}

		if (preg_match("/^cust_/",$key)) {	
			//Special case for this selector (O or '' is the samein php...)
			if ($key=='cust_typecust' ) {
				$val=GETPOST($key);
				if ($val=='') {
					$array_query[$key]=-1;
				}else {
					$array_query[$key]=$val;
				}
			} else {
				$array_query[$key]=GETPOST($key);
			}
		}
		
		if (preg_match("/^contact_/",$key)) {
			
			$array_query[$key]=GETPOST($key);
			
			$specials_date_key=array('contact_update_st_dt','contact_update_end_dt','contact_create_st_dt','contact_create_end_dt');
			foreach($specials_date_key as $date_key) {
				if ($key==$date_key) {
					$dt=GETPOST($date_key);
					if (!empty($dt)) {
						$array_query[$key]=dol_mktime(0, 0, 0, GETPOST($date_key.'month','int'), GETPOST($date_key.'day','int'), GETPOST($date_key.'year','int'));
					}else {
						$array_query[$key]='';
					}
				}
			}

		}
		
		if (preg_match("/^type_of_target/",$key)) {
			$array_query[$key]=GETPOST($key);
		}
	}	
	
	$result=$advTarget->savequery($user,$array_query);
	if ($result<0) {
		setEventMessage($advTarget->error, 'errors');
	}
}

if ($action == 'delete')
{
	// Ici, rowid indique le destinataire et id le mailing
	$sql="DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid=".$rowid;
	$resql=$db->query($sql);
	if ($resql)
	{
		if (!empty($id))
		{
			$classname = "MailingTargets";
			$obj = new $classname($db);
			$obj->update_nb($id);

			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit;
		}
		else
		{
			header("Location: liste.php");
			exit;
		}
	}
	else
	{
		dol_print_error($db);
	}
}

if ($_POST["button_removefilter"])
{
	$search_nom='';
	$search_prenom='';
	$search_email='';
}


/*
 * View
*/
$extrajs = array (
		'/agefodd/includes/multiselect/js/ui.multiselect.js'
);
$extracss = array (
		'/agefodd/includes/multiselect/css/ui.multiselect.css','/agefodd/css/agefodd.css'
);

llxHeader('',$langs->trans("AdvTgtTabsTarget"), '', '', '', '', $extrajs, $extracss );

print '<script type="text/javascript" language="javascript">
	$(document).ready(function() {
		$.extend($.ui.multiselect.locale, {
			addAll:\'' . $langs->transnoentities ( "AddAll" ) . '\',
			removeAll:\'' . $langs->transnoentities ( "RemoveAll" ) . '\',
			itemsCount:\'' . $langs->transnoentities ( "ItemsCount" ) . '\'
		});
	
		$(function(){
				$("#receiver").addClass("multiselect").attr("multiple","multiple").attr("name","receiver[]");
				$(".multiselect").multiselect({sortable: false, searchable: false});
		});
					
		// Click Function
		$(":button[name=addcontactandsave]").click(function() {
				$(":hidden[name=action]").val("addcontactandsave");
				$("#find_customer").submit();
		});
					
		$(":button[name=addcontactanddelete]").click(function() {
				$(":hidden[name=action]").val("addcontactanddelete");
				$("#find_customer").submit();
		});
	
		$(":button[name=savetemplate]").click(function() {
				$(":hidden[name=action]").val("savetemplate");
				$("#find_customer").submit();
		});
	});
</script>';


$form = new Form($db);
$formadvtargetemaling = new FormAdvTargetEmailing($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);

if ($object->fetch($id) >= 0) {

	$head = emailing_prepare_head($object);

	dol_fiche_head($head, 'tabAdvTgtTabsTarget', $langs->trans("Mailing"), 0, 'email');
	

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/liste.php">'.$langs->trans("BackToList").'</a>';

	print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($object,'id', $linkback);
	print '</td></tr>';

	print '<tr><td width="25%">'.$langs->trans("MailTitle").'</td><td colspan="3">'.$object->titre.'</td></tr>';

	print '<tr><td width="25%">'.$langs->trans("MailFrom").'</td><td colspan="3">'.dol_print_email($object->email_from,0,0,0,0,1).'</td></tr>';

	// Errors to
	print '<tr><td width="25%">'.$langs->trans("MailErrorsTo").'</td><td colspan="3">'.dol_print_email($object->email_errorsto,0,0,0,0,1);
	print '</td></tr>';

	// Status
	print '<tr><td width="25%">'.$langs->trans("Status").'</td><td colspan="3">'.$object->getLibStatut(4).'</td></tr>';

	// Nb of distinct emails
	print '<tr><td width="25%">';
	print $langs->trans("TotalNbOfDistinctRecipients");
	print '</td><td colspan="3">';
	$nbemail = ($object->nbemail?$object->nbemail:'0');
	if (!empty($conf->global->MAILING_LIMIT_SENDBYWEB) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
	{
		$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
		print $form->textwithpicto($nbemail,$text,1,'warning');
	}
	else
	{
		print $nbemail;
	}
	print '</td></tr>';

	print '</table>';

	print "</div>";

	// Show email selectors
	if ($object->statut == 0 && $user->rights->mailing->creer)	{
		print_fiche_titre($langs->trans("AdvTgtTitle"));

		print '<div class="tabBar">'."\n";
		print '<form name="find_customer" id="find_customer" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'"  method="POST">'."\n";
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";
		print '<input type="hidden" name="action" value="">'."\n";
		print '<table class="border" width="100%">'."\n";
		
		print '<tr><td>'.$langs->trans('AdvTgtTypeOfIncude').'</td><td>';
		
		print $form->selectarray('type_of_target', $advTarget->select_target_type,$array_query['type_of_target']);
		print '</td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtTypeOfIncudeHelp"),1,'help');
		print '</td></tr>'."\n";

		//Customer name
		print '<tr><td>'.$langs->trans('ThirdPartyName').'</td><td><input type="text" name="cust_name" value="'.$array_query['cust_name'].'"/></td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
		print '</td></tr>'."\n";

		//Code Client
		print '<tr><td>'.$langs->trans('CustomerCode').'</td><td><input type="text" name="cust_code" value="'.$array_query['cust_code'].'"/></td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
		print '</td></tr>'."\n";

		//Address Client
		print '<tr><td>'.$langs->trans('Address').'</td><td><input type="text" name="cust_adress" value="'.$array_query['cust_adress'].'"/></td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
		print '</td></tr>'."\n";

		//Zip Client
		print '<tr><td>'.$langs->trans('Zip').'</td><td><input type="text" name="cust_zip" value="'.$array_query['cust_zip'].'"/></td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
		print '</td></tr>'."\n";

		//City Client
		print '<tr><td>'.$langs->trans('Town').'</td><td><input type="text" name="cust_city" value="'.$array_query['cust_city'].'"/></td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
		print '</td></tr>'."\n";
		
		//Customer Country
		print '<tr><td>'.$langs->trans("Country").'</td><td>'."\n";
		
		print $form->select_country($array_query['cust_country'],'cust_country');
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//State Customer
		print '<tr><td>'.$langs->trans('Status').' '.$langs->trans('ThirdParty').'</td><td>';
		print $form->selectarray('cust_status', array(''=>'','0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$array_query['cust_status']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Prospect/Customer
		$selected=$array_query['cust_typecust'];
		print '<tr><td>'.$langs->trans('ProspectCustomer').' '.$langs->trans('ThirdParty').'</td><td><select class="flat" name="cust_typecust">';
		print '<option value=""'.($selected==-1?' selected="selected"':'').'></option>';
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2"'.($selected==2?' selected="selected"':'').'>'.$langs->trans('Prospect').'</option>';
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="3"'.($selected==3?' selected="selected"':'').'>'.$langs->trans('ProspectCustomer').'</option>';
        if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1"'.($selected==1?' selected="selected"':'').'>'.$langs->trans('Customer').'</option>';
        print '<option value="0"'.($selected==0?' selected="selected"':'').'>'.$langs->trans('NorProspectNorCustomer').'</option></select>';
       
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Prospection status
		print '<tr><td>'.$langs->trans('ProspectLevel').'</td><td>';
		print $formadvtargetemaling->multiselect_prospection_status($array_query['cust_prospect_status'],'cust_prospect_status', 1);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Prospection comm status
		print '<tr><td>'.$langs->trans('StatusProsp').'</td><td>';
		print $form->selectarray('cust_comm_status', $advTarget->type_statuscommprospect,$array_query['cust_comm_status']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Customer Type
		print '<tr><td>'.$langs->trans("ThirdPartyType").'</td><td>'."\n";
		print $form->selectarray('cust_typeent',$formcompany->typent_array(0), $array_query['cust_typeent']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Staff number
		print '<td>'.$langs->trans("Staff").'</td><td>';
		print $form->selectarray("cust_effectif_id",$formcompany->effectif_array(0), $array_query['cust_effectif_id']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Sales manager
		print '<tr><td>'.$langs->trans("SalesRepresentatives").'</td><td>'."\n";
		print $formother->select_salesrepresentatives ( $array_query['cust_sale'], 'cust_saleman', $user );
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		//Standard Extrafield feature
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
			// fetch optionals attributes and labels
			dol_include_once('/core/class/extrafields.class.php');
			$extrafields = new ExtraFields($db);
			$extralabels=$extrafields->fetch_name_optionals_label('societe');
			foreach($extralabels as $key=>$val) {
				print '<tr><td>'.$extrafields->attribute_label[$key].'</td><td>';
				if (($extrafields->attribute_type[$key] == 'varchar') ||
					($extrafields->attribute_type[$key] == 'text')) {
					print '<input type="text" name="options_'.$key.'"/></td><td>'."\n";
					print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
				}elseif (($extrafields->attribute_type[$key] == 'int') ||
					($extrafields->attribute_type[$key] == 'double')){
					print $langs->trans("AdvTgtMinVal").'<input type="text" name="options'.$key.'_min"/>';
					print $langs->trans("AdvTgtMaxVal").'<input type="text" name="options'.$key.'_max"/>';
					print '</td><td>'."\n";
					print $form->textwithpicto('',$langs->trans("AdvTgtSearchIntHelp"),1,'help');
				}elseif (($extrafields->attribute_type[$key] == 'date') ||
					($extrafields->attribute_type[$key] == 'datetime')){
						
					print '<table class="nobordernopadding"><tr>';
					print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
					print $form->select_date('','options_'.$key.'_st_dt');
					print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
					print $form->select_date('','options_'.$key.'_end_dt');
					print '</td></tr></table>';
						
					print '</td><td>'."\n";
					print $form->textwithpicto('',$langs->trans("AdvTgtSearchDtHelp"),1,'help');
				}elseif (($extrafields->attribute_type[$key] == 'boolean')){
					print $form->selectarray('options_'.$key, array(''=>'','1'=>$langs->trans('Yes'),'0'=>$langs->trans('No')),$array_query['options_'.$key]);
					print '</td><td>'."\n";
				}else{
					
					print '<table class="nobordernopadding"><tr>';
					print '<td></td><td>';
					if (is_array($array_query['options_'.$key])) {
						print $extrafields->showInputField($key,implode(',',$array_query['options_'.$key]));
					} else {
						print $extrafields->showInputField($key,$array_query['options_'.$key]);
					}
					print '</td></tr></table>';
						
					print '</td><td>'."\n";
				}
				print '</td></tr>'."\n";
			}
		}else {
			$std_soc=new Societe($db);
			$action_search='query';
			// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($db);
			$hookmanager->initHooks(array('thirdpartycard'));
			if (!empty($advTarget->id)) {
				$parameters=array('array_query'=>$advTarget->filtervalue);
			}
			//Module extrafield feature
			$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$std_soc,$action_search);
		}
		
		//State Contact
		print '<tr><td>'.$langs->trans('Status').' '.$langs->trans('Contact').'</td><td>';
		print $form->selectarray('contact_status', array(''=>'','0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),$array_query['contact_status']);
		print '</td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtContactHelp"),1,'help');
		print '</td></tr>'."\n";
		
		// Civility
		print '<tr><td width="15%">'.$langs->trans("UserTitle").'</td><td>';
		print $formcompany->select_civility($array_query['contact_civility'],'contact_civility');
		print '</td></tr>';
		
		//contact name
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans('Lastname').'</td><td><input type="text" name="contact_lastname" value="'.$array_query['contact_lastname'].'"/></td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
		print '</td></tr>'."\n";
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans('Firstname').'</td><td><input type="text" name="contact_firstname" value="'.$array_query['contact_firstname'].'"/></td><td>'."\n";
		print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
		print '</td></tr>'."\n";
		
		//Contact Country
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("Country").'</td><td>'."\n";
		print $form->select_country($array_query['contact_country'],'contact_country');
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Never send mass mailing
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("No_Email").'</td><td>'."\n";
		print $form->selectarray('contact_no_email', array(''=>'','1'=>$langs->trans('Yes'),'0'=>$langs->trans('No')),$array_query['contact_no_email']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Contact Date Create
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("DateCreation").'</td><td>'."\n";
		print '<table class="nobordernopadding"><tr>';
		print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
		print $form->select_date($array_query['contact_create_st_dt'],'contact_create_st_dt',0,0,1,'find_customer',1,1);
		print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
		print $form->select_date($array_query['contact_create_end_dt'],'contact_create_end_dt',0,0,1,'find_customer',1,1);
		print '</td></tr></table>';
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		//Contact update Create
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("DateLastModification").'</td><td>'."\n";
		print '<table class="nobordernopadding"><tr>';
		print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
		print $form->select_date($array_query['contact_update_st_dt'],'contact_update_st_dt',0,0,1,'find_customer',1,1);
		print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
		print $form->select_date($array_query['contact_update_end_dt'],'contact_update_end_dt',0,0,1,'find_customer',1,1);
		print '</td></tr></table>';
		print '</td><td>'."\n";
		print '</td></tr>'."\n";
		
		
		//Standard Extrafield feature
		if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
			// fetch optionals attributes and labels
			dol_include_once('/core/class/extrafields.class.php');
			$extrafields = new ExtraFields($db);
			$extralabels=$extrafields->fetch_name_optionals_label('socpeople');
			foreach($extralabels as $key=>$val) {
				print '<tr><td>'.$extrafields->attribute_label[$key].'</td><td>';
				if (($extrafields->attribute_type[$key] == 'varchar') ||
				($extrafields->attribute_type[$key] == 'text')) {
					print '<input type="text" name="cnct_options_'.$key.'"/></td><td>'."\n";
					print $form->textwithpicto('',$langs->trans("AdvTgtSearchTextHelp"),1,'help');
				}elseif (($extrafields->attribute_type[$key] == 'int') ||
					($extrafields->attribute_type[$key] == 'double')){
					print $langs->trans("AdvTgtMinVal").'<input type="text" name="cnct_options'.$key.'_min"/>';
					print $langs->trans("AdvTgtMaxVal").'<input type="text" name="cnct_options'.$key.'_max"/>';
					print '</td><td>'."\n";
					print $form->textwithpicto('',$langs->trans("AdvTgtSearchIntHelp"),1,'help');
				}elseif (($extrafields->attribute_type[$key] == 'date') ||
					($extrafields->attribute_type[$key] == 'datetime')){
		
					print '<table class="nobordernopadding"><tr>';
					print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
					print $form->select_date('','cnct_options_'.$key.'_st_dt');
					print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
					print $form->select_date('','cnct_options_'.$key.'_end_dt');
					print '</td></tr></table>';
		
					print '</td><td>'."\n";
					print $form->textwithpicto('',$langs->trans("AdvTgtSearchDtHelp"),1,'help');
				}elseif (($extrafields->attribute_type[$key] == 'boolean')){
					print $form->selectarray('cnct_options_'.$key, array(''=>'','1'=>$langs->trans('Yes'),'0'=>$langs->trans('No')),$array_query['cnct_options_'.$key]);
					print '</td><td>'."\n";
				}else{
						
					print '<table class="nobordernopadding"><tr>';
					print '<td></td><td>';
					if (is_array($array_query['cnct_options_'.$key])) {
						print $extrafields->showInputField($key,implode(',',$array_query['cnct_options_'.$key]),'','cnct_');
					} else {
						print $extrafields->showInputField($key,$array_query['cnct_options_'.$key],'','cnct_');
					}
					print '</td></tr></table>';
		
					print '</td><td>'."\n";
				}
				print '</td></tr>'."\n";
			}
		}
		

		print '<tr>'."\n";
		print '<td colspan="3" align="right">'."\n";
		
		print '<input type="button" name="addcontactandsave" id="addcontactandsave" value="'.$langs->trans('AdvTgtSaveTemplateAndAdd').'"/>'."\n";
		print '<input type="button" name="addcontactanddelete" id="addcontactanddelete" value="'.$langs->trans('AdvTgtSaveTemplateAndDelete').'"/>'."\n";
		print '<input type="button" name="savetemplate" id="savetemplate" value="'.$langs->trans('AdvTgtSaveTemplate').'"/>'."\n";

		print '</td>'."\n";
		print '</tr>'."\n";
		print '</table>'."\n";
		print '</form>'."\n";
		print '</div>'."\n";

		print '<form action="'.$_SERVER['PHP_SELF'].'?action=clear&id='.$object->id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print_titre($langs->trans("ToClearAllRecipientsClickHere"));
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre" align="right"><input type="submit" class="button" value="'.$langs->trans("TargetsReset").'"></td>';
		print '</tr>';
		print '</table>';
		print '</form>';
		print '<br>';
	}

	// List of selected targets
	print "\n<!-- Liste destinataires selectionnes -->\n";
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	$sql  = "SELECT mc.rowid, mc.lastname, mc.firstname, mc.email, mc.other, mc.statut, mc.date_envoi, mc.source_url, mc.source_id, mc.source_type";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE mc.fk_mailing=".$object->id;
	if ($search_nom)    $sql.= " AND mc.lastname    LIKE '%".$db->escape($search_nom)."%'";
	if ($search_prenom) $sql.= " AND mc.firstname LIKE '%".$db->escape($search_prenom)."%'";
	if ($search_email)  $sql.= " AND mc.email  LIKE '%".$db->escape($search_email)."%'";
	$sql .= $db->order($sortfield,$sortorder);
	$sql .= $db->plimit($conf->liste_limit+1, $offset);

	dol_syslog('advtargetemailing.php:: sql='.$sql);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		$parm = "&amp;id=".$object->id;
		if ($search_nom)    $parm.= "&amp;search_nom=".urlencode($search_nom);
		if ($search_prenom) $parm.= "&amp;search_prenom=".urlencode($search_prenom);
		if ($search_email)  $parm.= "&amp;search_email=".urlencode($search_email);

		print_barre_liste($langs->trans("MailSelectedRecipients"),$page,$_SERVER["PHP_SELF"],$parm,$sortfield,$sortorder,"",$num,$object->nbemail,'');

		if ($page)			$parm.= "&amp;page=".$page;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("EMail"),$_SERVER["PHP_SELF"],"mc.email",$parm,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Lastname"),$_SERVER["PHP_SELF"],"mc.lastname",$parm,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Firstname"),$_SERVER["PHP_SELF"],"mc.firstname",$parm,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("OtherInformations"),$_SERVER["PHP_SELF"],"",$parm,"","",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Source"),$_SERVER["PHP_SELF"],"",$parm,"",'align="center"',$sortfield,$sortorder);

		// Date sendinf
		if ($object->statut < 2)
		{
			print '<td class="liste_titre">&nbsp;</td>';
		}
		else
		{
			print_liste_field_titre($langs->trans("DateSending"),$_SERVER["PHP_SELF"],"mc.date_envoi",$parm,'','align="center"',$sortfield,$sortorder);
		}

		// Statut
		print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"mc.statut",$parm,'','align="right"',$sortfield,$sortorder);

		print '</tr>';

		// Ligne des champs de filtres
		print '<tr class="liste_titre">';
		// EMail
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_email" size="14" value="'.$search_email.'">';
		print '</td>';
		// Name
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_nom" size="12" value="'.$search_nom.'">';
		print '</td>';
		// Firstname
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_prenom" size="10" value="'.$search_prenom.'">';
		print '</td>';
		// Other
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';
		// SendDate
		print '<td class="liste_titre">';
		print '&nbsp';
		print '</td>';
		// Source
		print '<td class="liste_titre" align="right" colspan="3">';
		print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
		print '&nbsp; ';
		print '<input type="image" class="liste_titre" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/searchclear.png" name="button_removefilter" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
		print '</td>';
		print '</tr>';

		$var = true;
		$i = 0;

		if ($num) {
			while ($i < min($num,$conf->liste_limit)) {
				$obj = $db->fetch_object($resql);
				$var=!$var;

				print "<tr $bc[$var]>";
				print '<td>'.$obj->email.'</td>';
				print '<td>'.$obj->lastname.'</td>';
				print '<td>'.$obj->firstname.'</td>';
				print '<td>'.$obj->other.'</td>';
				print '<td>';
				if (empty($obj->source_id) || empty($obj->source_type))	{
					print $obj->source_url; // For backward compatibility
				} else	{
						
					if ($obj->source_type == 'thirdparty')	{
						include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
						$m=new Societe($db);
						$m->fetch($obj->source_id);
						print $m->getNomUrl(1);
					}
					elseif ($obj->source_type == 'contact') {
						include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
						$m=new Contact($db);
						$m->fetch($obj->source_id);
						print $m->getNomUrl(1);
					}
				}
				print '</td>';

				// Statut pour l'email destinataire (Attentioon != statut du mailing)
				if ($obj->statut == 0)
				{
					print '<td>&nbsp;</td>';
					print '<td align="right" nowrap="nowrap">'.$langs->trans("MailingStatusNotSent");
					if ($user->rights->mailing->creer) {
						print '<a href="'.$_SERVER['PHP_SELF'].'?action=delete&rowid='.$obj->rowid.$parm.'">'.img_delete($langs->trans("RemoveRecipient"));
					}
					print '</td>';
				}
				else
				{
					print '<td align="center">'.$obj->date_envoi.'</td>';
					print '<td align="right" nowrap="nowrap">';
					if ($obj->statut==-1) print $langs->trans("MailingStatusError").' '.img_error();
					if ($obj->statut==1) print $langs->trans("MailingStatusSent").' '.img_picto($langs->trans("MailingStatusSent"),'statut4');
					if ($obj->statut==2) print $langs->trans("MailingStatusRead").' '.img_picto($langs->trans("MailingStatusRead"),'statut6');
					if ($obj->statut==3) print $langs->trans("MailingStatusNotContact").' '.img_picto($langs->trans("MailingStatusNotContact"),'statut8');
					print '</td>';
				}
				print '</tr>';

				$i++;
			}
		}
		else
		{
			print '<tr '.$bc[false].'><td colspan="7">'.$langs->trans("NoTargetYet").'</td></tr>';
		}
		print "</table><br>";

		$db->free($resql);
	}
	else
	{
		setEventMessage($db->lasterror(),'errors');
	}

	print '</form>';
}

llxFooter();
$db->close();