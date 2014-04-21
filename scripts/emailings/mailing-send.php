#!/usr/bin/php
<?php
/*
 * Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013		Florian Henry  <florian.henry@open-concept.pro>
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
 *      \file       advtargetemailing/scripts/emailings/mailing-send.php
 *      \ingroup    mailing
 *      \brief      Script d'envoi d'un mailing prepare et valide
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
    exit;
}

if (! isset($argv[1]) || ! $argv[1]) {
	print "Usage: ".$script_file." ID_MAILING ClearAndAddContact(0/1) UseMailjetCampaign(mailjet/normal) userlogin \n";
	exit;
}
$id=$argv[1];


$res=@include("../../master.inc.php");				// For root directory
if (! $res) $res=@include("../../../master.inc.php");	// For "custom" directory
if (! $res) die("Include of master.inc.php fails");
require_once (DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php");



if (! isset($argv[2]) || ! $argv[2]) {
	print "Usage: ".$script_file." ID_MAILING ClearAndAddContact(0/1) UseMailjetCampaign(mailjet/normal) userlogin \n";
	exit;
} else {
	$clearandadd=$argv[2];
}
if (! isset($argv[3]) || ! $argv[3]) {
	print "Usage: ".$script_file." ID_MAILING ClearAndAddContact(0/1) UseMailjetCampaign(mailjet/normal) userlogin \n";
	exit;
}
else {
	if ($argv[3]=='mailjet')  {
		if (!empty($conf->global->MAILJET_ACTIVE)) {
			$usemailjet=$argv[3];
		} else {
			print $script_file."  You ask for mailjet but module mailjet is not activated \n";
			exit;
		}
	}
}

if (! isset($argv[4]) || ! $argv[4]) {
	print "Usage: ".$script_file." ID_MAILING ClearAndAddContact(0/1) UseMailjetCampaign(mailjet/normal) userlogin \n";
	exit;
} else {
	$userlogin=$argv[4];
}

require_once (DOL_DOCUMENT_ROOT_ALT."/advtargetemailing/class/advtargetemailing.class.php");
require_once (DOL_DOCUMENT_ROOT_ALT."/advtargetemailing/core/modules/mailings/advthirdparties.modules.php");
if ($usemailjet=='mailjet')  {
	require_once (DOL_DOCUMENT_ROOT_ALT."/mailjet/class/dolmailjet.class.php");
	require_once (DOL_DOCUMENT_ROOT."/user/class/user.class.php");
	require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
}


$error = 0;


//We clear and added the contact if asked
$mailing = new mailing_advthirdparties($db);
$advTarget = new AdvanceTargetingMailing($db);
if (!empty($clearandadd)) {
	
	$mailing->clear_target($id);
	
	$result=$advTarget->fetch_by_mailing($id);
	if ($result<0) {
		$mesg=$script_file." fetch_by_mailing Error:".$advTarget->error." \n";
		print $mesg."\n";
		dol_syslog($mesg,LOG_ERR);
	}else {
	
		if (!empty($advTarget->id)) {
			//$mesg=$script_file." fetch_by_mailing advTarget->filtervalue:".$advTarget->filtervalue." \n";
			//print $mesg."\n";
			$array_query=json_decode($advTarget->filtervalue,true);
		}
	
		$result=$advTarget->query($array_query);
		if ($result<0) {
			$mesg=$script_file." query Error".$advTarget->error." \n";
			print $mesg."\n";
			dol_syslog($mesg,LOG_ERR);
		}
		
		if (count($advTarget->lines)>0) {
			// Add targets into database
			$obj = new mailing_advthirdparties($db);
			$result=$obj->add_to_target($id,$advTarget->lines);
		}else {
			$mesg="No contact found for mailing ".$id." found";
			print $mesg."\n";
			dol_syslog($mesg,LOG_ERR);
		}
	}
}

if ($usemailjet!='mailjet')  {
	// We read data of email
	$sql = "SELECT m.rowid, m.titre, m.sujet, m.body,";
	$sql.= " m.email_from, m.email_replyto, m.email_errorsto";
	$sql.= " FROM ".MAIN_DB_PREFIX."mailing as m";
	$sql.= " WHERE m.statut >= 1";
	$sql.= " AND m.rowid= ".$id;
	$sql.= " LIMIT 1";
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
	
		if ($num == 1)
		{
			$obj = $db->fetch_object($resql);
	
			dol_syslog("mailing ".$id);
	
			$id       = $obj->rowid;
			$subject  = $obj->sujet;
			$message  = $obj->body;
			$from     = $obj->email_from;
			$replyto  = $obj->email_replyto;
			$errorsto = $obj->email_errorsto;
	        // Le message est-il en html
	        $msgishtml=-1;  // Unknown by default
	        if (preg_match('/[\s\t]*<html>/i',$message)) $msgishtml=1;
	
			$i++;
		}
		else
		{
			$mesg="Emailing with id ".$id." not found";
			print $mesg."\n";
			dol_syslog($mesg,LOG_ERR);
		}
	}
	
	
	$nbok=0; $nbko=0;
	
	// On choisit les mails non deja envoyes pour ce mailing (statut=0)
	// ou envoyes en erreur (statut=-1)
	$sql = "SELECT mc.rowid, mc.nom as lastname, mc.prenom as firstname, mc.email, mc.other, mc.source_url, mc.source_id, mc.source_type, mc.tag";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE mc.statut < 1 AND mc.fk_mailing = ".$id;
	
	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
	
		if ($num)
		{
			dol_syslog("nb of targets = ".$num, LOG_DEBUG);
	
			$now=dol_now();
	
			// Positionne date debut envoi
			$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET date_envoi='".$db->idate($now)."' WHERE rowid=".$id;
	
			$resql2=$db->query($sql);
			if (! $resql2)
			{
				dol_print_error($db);
			}
	
			// Look on each email and sent message
			$i = 0;
			while ($i < $num)
			{
				$res=1;
				$now=dol_now();
	
				$obj = $db->fetch_object($resql);
	
				// sendto en RFC2822
				$sendto = str_replace(',',' ',$obj->firstname." ".$obj->lastname) ." <".$obj->email.">";
	
				// Make subtsitutions on topic and body
				$other=explode(';',$obj->other);
				$other1=$other[0];
				$other2=$other[1];
				$other3=$other[2];
				$other4=$other[3];
				$other5=$other[4];
				$substitutionarray=array(
					'__ID__' => $obj->source_id,
					'__EMAIL__' => $obj->email,
					'__CHECK_READ__' => '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$obj->tag.'" width="1" height="1" style="width:1px;height:1px" border="0"/>',
					'__UNSUBSCRIBE__' => '<a href="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-unsubscribe.php?tag='.$obj->tag.'&unsuscrib=1" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>',
					'__MAILTOEMAIL__' => '<a href="mailto:'.$obj->email.'">'.$obj->email.'</a>',
					'__LASTNAME__' => $obj->lastname,
					'__FIRSTNAME__' => $obj->firstname,
					'__OTHER1__' => $other1,
					'__OTHER2__' => $other2,
					'__OTHER3__' => $other3,
					'__OTHER4__' => $other4,
					'__OTHER5__' => $other5
				);
	
				complete_substitutions_array($substitutionarray,$langs);
				$newsubject=make_substitutions($subject,$substitutionarray);
				$newmessage=make_substitutions($message,$substitutionarray);
	
	            $substitutionisok=true;
	
	            // Fabrication du mail
				$mail = new CMailFile(
				    $newsubject,
				    $sendto,
				    $from,
				    $newmessage,
				    array(),
				    array(),
				    array(),
				    '',
				    '',
				    0,
				    $msgishtml,
				    $errorsto
				);
	
				if ($mail->error)
				{
					$res=0;
				}
				if (! $substitutionisok)
				{
					$mail->error='Some substitution failed';
					$res=0;
				}
	
				// Send Email
				if ($res)
				{
					//$res=$mail->sendfile();
				}
	
				if ($res)
				{
					// Mail successful
					$nbok++;
	
					dol_syslog("ok for #".$i.($mail->error?' - '.$mail->error:''), LOG_DEBUG);
	
					$sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
					$sql.=" SET statut=1, date_envoi=".$db->idate($now)." WHERE rowid=".$obj->rowid;
					$resql2=$db->query($sql);
					if (! $resql2)
					{
						dol_print_error($db);
					}
					else
					{
						//if cheack read is use then update prospect contact status
						if (strpos($message, '__CHECK_READ__') !== false)
						{
							//Update status communication of thirdparty prospect
							$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=2 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid=".$obj->rowid.")";
							dol_syslog("fiche.php: set prospect thirdparty status sql=".$sql, LOG_DEBUG);
							$resql2=$db->query($sql);
							if (! $resql2)
							{
								dol_print_error($db);
							}
	
						    //Update status communication of contact prospect
							$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=2 WHERE rowid IN (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."socpeople AS sc INNER JOIN ".MAIN_DB_PREFIX."mailing_cibles AS mc ON mc.rowid=".$obj->rowid." AND mc.source_type = 'contact' AND mc.source_id = sc.rowid)";
							dol_syslog("fiche.php: set prospect contact status sql=".$sql, LOG_DEBUG);
	
							$resql2=$db->query($sql);
							if (! $resql2)
							{
								dol_print_error($db);
							}
						}
					}
				}
				else
				{
					// Mail failed
					$nbko++;
	
					dol_syslog("error for #".$i.($mail->error?' - '.$mail->error:''), LOG_DEBUG);
	
					$sql="UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
					$sql.=" SET statut=-1, date_envoi=".$db->idate($now)." WHERE rowid=".$obj->rowid;
					$resql2=$db->query($sql);
					if (! $resql2)
					{
						dol_print_error($db);
					}
				}
	
				$i++;
			}
		}
	
		// Loop finished, set global statut of mail
		$statut=2;
		if (! $nbko) $statut=3;
	
		$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$statut." WHERE rowid=".$id;
		dol_syslog("update global status sql=".$sql, LOG_DEBUG);
		$resql2=$db->query($sql);
		if (! $resql2)
		{
			dol_print_error($db);
		}
	}
	else
	{
		dol_print_error($db);
	}
}elseif ($usemailjet=='mailjet'){
	
	$user=new User($db);
	$result=$user->fetch('',$userlogin);
	if ($result<0) {
		$mesg=$script_file." User Error:".$user->error." \n";
		print $mesg."\n";
		dol_syslog($mesg,LOG_ERR);
		exit;
	}else {
		if (empty($user->id)) {
			$mesg=$script_file." User user login:".$userlogin." do not exists \n";
			print $mesg."\n";
			dol_syslog($mesg,LOG_ERR);
			exit;
		}
	}
	
	$mailing=new Mailing($db);
	$result=$mailing->fetch($id);
	if ($result<0) {
		$mesg=$script_file." Mailing Error:".$mailing->error." \n";
		print $mesg."\n";
		dol_syslog($mesg,LOG_ERR);
		exit;
	}
	
	$mailjet= new DolMailjet($db);
	$result=$mailjet->fetch_by_mailing($id);
	if ($result<0) {
		$mesg=$script_file." mailjet Error:".$mailjet->error." \n";
		print $mesg."\n";
		dol_syslog($mesg,LOG_ERR);
		exit;
	}
	
	$mailjet->currentmailing=$mailing;
	
	$result=$mailjet->createMailJetCampaign($user);
	if ($result<0) {
		$mesg=$script_file." Mailjet createMailJetCampaign Error:".$mailjet->error." \n";
		print $mesg."\n";
		dol_syslog($mesg,LOG_ERR);
		exit;
	}
	
	
	//Send campaign
	$result=$mailjet->sendMailJetCampaign($user);
	if ($result<0) {
		$mesg=$script_file." Mailjet sendMailJetCampaign Error:".$mailjet->error." \n";
		print $mesg."\n";
		dol_syslog($mesg,LOG_ERR);
	} else {
		//Update mailing general status
		$mailing->statut=3;
		$sql="UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$mailing->statut." WHERE rowid=".$mailing->id;
		dol_syslog("update global status sql=".$sql, LOG_DEBUG);
		$resql2=$db->query($sql);
		if (! $resql2)	{
			$mesg=$script_file." update global status Error:".$db->lasterror()." \n";
			print $mesg."\n";
			dol_syslog($mesg,LOG_ERR);
		}
		
		//Wait a little bit before update mailing status
		sleep(2);
	
		//Update inforamtion from mailjet
		$result=$mailjet->updateMailJetCampaignAttr($user);
		if ($result<0) {
			$mesg=$script_file." Mailjet updateMailJetCampaignAttr Error:".$mailjet->error." \n";
			print $mesg."\n";
			dol_syslog($mesg,LOG_ERR);
		}
	}
}