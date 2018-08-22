<?php

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
//if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test
if (! defined('NOIPCHECK'))      define('NOIPCHECK','1');				// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOSTYLECHECK'))   define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');				    	// If this page is public (can be called outside logged session)
if (! defined("MAIN_LANG_DEFAULT")) define('MAIN_LANG_DEFAULT','auto');
if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','sellyoursaas');
if (! defined("MAIN_AUTHENTICATION_POST_METHOD")) define('MAIN_AUTHENTICATION_POST_METHOD','0');

// Load Dolibarr environment
include ('./mainmyaccount.inc.php');


// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
//require_once DOL_DOCUMENT_ROOT.'/website/class/website.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societeaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/companypaymentmode.class.php';
dol_include_once('/sellyoursaas/class/packages.class.php');
dol_include_once('/sellyoursaas/lib/sellyoursaas.lib.php');

$conf->global->SYSLOG_FILE_ONEPERSESSION=2;

// Which mode to get credit card (direct credit card data or token) ?
$conf->global->SELLYOURSAAS_STRIPE_USE_TOKEN = 1;


$welcomecid = GETPOST('welcomecid','alpha');
$mode = GETPOST('mode', 'alpha');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtourl = GETPOST('backtourl', 'alpha');
if (empty($mode) && empty($welcomecid)) $mode='dashboard';

$langs=new Translate('', $conf);
$langs->setDefaultLang(GETPOST('lang','aZ09')?GETPOST('lang','aZ09'):'auto');

$langsen=new Translate('', $conf);
$langsen->setDefaultLang('en_US');

$langs->loadLangs(array("main","companies","bills","sellyoursaas@sellyoursaas","other","errors",'mails','paypal','paybox','stripe','withdrawals','other'));
$langsen->loadLangs(array("main","companies","bills","sellyoursaas@sellyoursaas","other","errors",'mails','paypal','paybox','stripe','withdrawals','other'));

$mythirdpartyaccount = new Societe($db);

$service=GETPOST('service','int');
$firstrecord=GETPOST('firstrecord','int');
$lastrecord=GETPOST('lastrecord','int');
$search_instance_name=GETPOST('search_instance_name','alphanohtml');
$search_customer_name=GETPOST('search_customer_name','alphanohtml');

$MAXINSTANCEVIGNETTE = 4;

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):($mode == 'instance' ? $MAXINSTANCEVIGNETTE : 20);
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
//if (! $sortfield) $sortfield="p.date_fin";
//if (! $sortorder) $sortorder="DESC";

$firstrecord = GETPOSTISSET('firstrecord')?GETPOST('firstrecord','int'):($page * $limit) + 1;
$lastrecord = GETPOSTISSET('lastrecord')?GETPOST('lastrecord','int'):(($page+1)*$limit);
if ($firstrecord < 1) $firstrecord=1;
if (GETPOSTISSET('reset')) { $search_instance_name = ''; $search_customer_name = ''; }
$fromsocid=GETPOST('fromsocid','int');

// Id of connected thirdparty
$socid = GETPOST('socid','int')?GETPOST('socid','int'):$_SESSION['dol_loginsellyoursaas'];
$idforfetch = $fromsocid > 0 ? $fromsocid : $socid;
if ($idforfetch > 0)
{
	$result = $mythirdpartyaccount->fetch($idforfetch);					// fromid set if creation from reseller dashboard else we use socid
	if ($result <= 0)
	{
		dol_print_error($db, "Failed to load thirdparty for id=".($idforfetch));
		exit;
	}
}
if ($idforfetch <= 0 || empty($mythirdpartyaccount->status))
{
	$_SESSION=array();
	$_SESSION['dol_loginmesg']=$langs->trans("SorryAccountDeleted", $conf->global->SELLYOURSAAS_MAIN_EMAIL);
	//header("Location: index.php?username=".urlencode(GETPOST('username','alpha')));
	header("Location: index.php?usernamebis=".urlencode(GETPOST('username','alpha')));
	exit;
}

$langcode = 'en';
if ($langs->getDefaultLang(1) == 'es') $langcode = 'es';
if ($langs->getDefaultLang(1) == 'fr') $langcode = 'fr';

$urlfaq = '';
if (preg_match('/dolicloud\.com/', $conf->global->SELLYOURSAAS_MAIN_DOMAIN_NAME))
{
	$urlfaq='https://www.'.$conf->global->SELLYOURSAAS_MAIN_DOMAIN_NAME.'/'.$langcode.'/faq';
}

$urlstatus=$conf->global->SELLYOURSAAS_STATUS_URL;
$now =dol_now();
$tmp=dol_getdate($now);
$nowmonth = $tmp['mon'];
$nowyear = $tmp['year'];

require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
$documentstatic=new Contrat($db);
$documentstaticline=new ContratLigne($db);

$listofcontractid = array();
$sql = 'SELECT c.rowid as rowid';
$sql.= ' FROM '.MAIN_DB_PREFIX.'contrat as c LEFT JOIN '.MAIN_DB_PREFIX.'contrat_extrafields as ce ON ce.fk_object = c.rowid, '.MAIN_DB_PREFIX.'contratdet as d, '.MAIN_DB_PREFIX.'societe as s';
$sql.= " WHERE c.fk_soc = s.rowid AND s.rowid = ".$mythirdpartyaccount->id;
$sql.= " AND d.fk_contrat = c.rowid";
$sql.= " AND c.entity = ".$conf->entity;
$sql.= " AND ce.deployment_status IN ('processing', 'done', 'undeployed')";
$resql=$db->query($sql);
if ($resql)
{
	$num_rows = $db->num_rows($resql);
	$i = 0;
	while ($i < $num_rows)
	{
		$obj = $db->fetch_object($resql);
		if ($obj)
		{
			$contract=new Contrat($db);
			$contract->fetch($obj->rowid);					// This load also lines
			$listofcontractid[$obj->rowid]=$contract;
		}
		$i++;
	}
}

$mythirdpartyaccount->isareseller = 0;
if ($conf->global->SELLYOURSAAS_DEFAULT_RESELLER_CATEG > 0)
{
	$categorie=new Categorie($db);
	$categorie->fetch($conf->global->SELLYOURSAAS_DEFAULT_RESELLER_CATEG);
	if ($categorie->containsObject('supplier', $mythirdpartyaccount->id) > 0)
	{
		$mythirdpartyaccount->isareseller = 1;
	}
}

$listofcontractidresellerall = array();
$listofcontractidreseller = array();
$listofcustomeridreseller = array();

if ($mythirdpartyaccount->isareseller)
{
	$sql = 'SELECT DISTINCT c.rowid, c.fk_soc';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'contrat as c';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contrat_extrafields as ce ON ce.fk_object = c.rowid,';
	$sql.= ' '.MAIN_DB_PREFIX.'contratdet as d, '.MAIN_DB_PREFIX.'societe as s';
	$sql.= " WHERE c.fk_soc = s.rowid AND s.parent = ".$mythirdpartyaccount->id;
	$sql.= " AND d.fk_contrat = c.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND ce.deployment_status IN ('processing', 'done', 'undeployed')";
	if ($search_instance_name) $sql.=natural_search(array('c.ref_customer'), $search_instance_name);
	if ($search_customer_name) $sql.=natural_search(array('s.nom','s.email'), $search_customer_name);
	$resql=$db->query($sql);
	$num_rows = $db->num_rows($resql);
	$i=0;
	while($i < $num_rows)
	{
		$nbtotalofrecords++;
		$listofcontractidresellerall[$obj->rowid]=$obj->fk_soc;
		$listofcustomeridreseller[$obj->fk_soc]=1;
		$i++;
	}

	if (empty($lastrecord) || $lastrecord > $nbtotalofrecords) $lastrecord = $nbtotalofrecords;

	if ($lastrecord > 0) $sql.= " LIMIT ".($firstrecord?$firstrecord:1).", ".(($lastrecord >= $firstrecord) ? ($lastrecord - $firstrecord + 1) : 5);

	$sql = 'SELECT c.rowid as rowid, c.fk_soc';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'contrat as c';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'contrat_extrafields as ce ON ce.fk_object = c.rowid,';
	$sql.= ' '.MAIN_DB_PREFIX.'contratdet as d, '.MAIN_DB_PREFIX.'societe as s';
	$sql.= " WHERE c.fk_soc = s.rowid AND s.parent = ".$mythirdpartyaccount->id;
	$sql.= " AND d.fk_contrat = c.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND ce.deployment_status IN ('processing', 'done', 'undeployed')";
	if ($search_instance_name) $sql.=" AND c.ref_customer REGEXP '^[^\.]*".$db->escape($search_instance_name)."'";
	if ($search_customer_name) $sql.=natural_search(array('s.nom','s.email'), $search_customer_name);
	$resql=$db->query($sql);
	if ($resql)
	{
		$num_rows = $db->num_rows($resql);
		$i = 0;
		while ($i < $num_rows)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				if (empty($listofcontractidreseller[$obj->rowid]))
				{
					$contract=new Contrat($db);
					$contract->fetch($obj->rowid);					// This load also lines
					$listofcontractidreseller[$obj->rowid]=$contract;
				}
			}
			$i++;
		}
	}
	else dol_print_error($db);
}
//var_dump(array_keys($listofcontractidreseller));

// Define environment of payment modes
$servicestatusstripe = 0;
if (! empty($conf->stripe->enabled))
{
	$service = 'StripeTest';
	$servicestatusstripe = 0;
	if (! empty($conf->global->STRIPE_LIVE) && ! GETPOST('forcesandbox','alpha') && empty($conf->global->SELLYOURSAAS_FORCE_STRIPE_TEST))
	{
		$service = 'StripeLive';
		$servicestatusstripe = 1;
	}
}
$servicestatuspaypal = 0;
if (! empty($conf->paypal->enabled))
{
	$servicestatuspaypal = 0;
	if (! empty($conf->global->PAYPAL_LIVE) && ! GETPOST('forcesandbox','alpha') && empty($conf->global->SELLYOURSAAS_FORCE_PAYPAL_TEST))
	{
		$servicestatuspaypal = 1;
	}
}

$initialaction = $action;


/*
 * Action
 */

if (empty($welcomecid))
{
	dol_syslog('----- index.php mode='.$mode.' action='.$action.' cancel='.$cancel, LOG_DEBUG, 1);
}

if ($cancel)
{
	if ($action == 'sendbecomereseller') $backtourl = 'index.php?mode=dashboard';

	$action = '';
	if ($backtourl)
	{
		header("Location: ".$backtourl);
		exit;
	}
}

if (preg_match('/logout/', $mode))
{
	$mode = preg_replace('/logout_?/', '', $mode);

	session_destroy();
	$param='';
	if (GETPOSTISSET('username','alpha'))   $param.='&username='.urlencode(GETPOST('username','alpha'));
	if (GETPOSTISSET('password','alpha'))   $param.='&password='.urlencode(GETPOST('password','alpha'));
	if (GETPOSTISSET('login_hash','alpha')) $param.='&login_hash='.urlencode(GETPOST('login_hash','alpha'));
	if ($mode) $param.='&mode='.urlencode($mode);
	header("Location: /index.php".($param?'?'.$param:''));
	exit;
}

if ($action == 'updateurl')
{
	setEventMessages($langs->trans("FeatureNotYetAvailable").'.<br>'.$langs->trans("ContactUsByEmail", $conf->global->SELLYOURSAAS_MAIN_EMAIL), null, 'warnings');
}

if ($action == 'changeplan')
{
	setEventMessages($langs->trans("FeatureNotYetAvailable").'.<br>'.$langs->trans("ContactUsByEmail", $conf->global->SELLYOURSAAS_MAIN_EMAIL), null, 'warnings');
	$action = '';
}

// Send support ticket
if ($action == 'send')
{
	$emailfrom = $conf->global->SELLYOURSAAS_NOREPLY_EMAIL;
	$emailto = GETPOST('to','alpha');
	$replyto = GETPOST('from','alpha');
	$topic = GETPOST('subject','none');
	$content = GETPOST('content','none');

	$channel = GETPOST('supportchannel','alpha');
	$contractid = GETPOST('contractid','int');
	if ($contractid > 0)
	{
		$tmpcontract = $listofcontractid[$contractid];
		$topic = '[Ticket for '.$tmpcontract->ref_customer.'] '.$topic;
		$content .= "<br><br>\n";
		$content .= 'Date: '.dol_print_date($now, 'dayhour')."<br>\n";
		$content .= 'Instance: <a href="https://'.$tmpcontract->ref_customer.'">'.$tmpcontract->ref_customer."</a><br>\n";
		//$content .= 'Ref contract: <a href="xxx/contrat/card.php?id='.$tmpcontract->ref.">".$tmpcontract->ref."</a><br>\n"; 	// No link to backoffice as the mail is used with answer to.
		$content .= 'Ref contract: '.$tmpcontract->ref."<br>\n";
		$tmpcontract->fetch_thirdparty();
		if (is_object($tmpcontract->thirdparty))
		{
			$content .= 'Organization: '.$tmpcontract->thirdparty->name."<br>\n";
			$content .= $tmpcontract->thirdparty->array_options['options_lastname'].' '.$tmpcontract->thirdparty->array_options['options_firstname']."<br>\n";
		}
		// Add the support type
		foreach($tmpcontract->lines as $key => $val)
		{
			if ($val->fk_product > 0)
			{
				$product = new Product($db);
				$product->fetch($val->fk_product);
				$content .= '- '.$langs->trans("Service").' '.$product->ref.' - '.$langs->trans("Qty")." ".$val->qty;
				$content.=' ('.$product->array_options['options_app_or_option'].')';
				if ($product->array_options['options_app_or_option'] == 'app')
				{
					$content .= ' - Support type = '.$product->array_options['options_typesupport'];
				}
			}
			else
			{
				$content .= '- Service '.$val->label;
			}
			$content .= "<br>\n";;
		}
	}
	$trackid = 'sellyoursaas'.$contractid;

	$cmailfile = new CMailFile($topic, $emailto, $emailfrom, $content, array(), array(), array(), '', '', 0, 1, '', '', $trackid, '', 'standard', $replyto);
	$result = $cmailfile->sendfile();

	if ($result) setEventMessages($langs->trans("TicketSent"), null, 'warnings');
	else setEventMessages($langs->trans("FailedToSentTicketPleaseTryLater").' '.$cmailfile->error, $cmailfile->errors, 'errors');
	$action = '';
}

// Send reseller request
if ($action == 'sendbecomereseller')
{
	$emailfrom = $conf->global->SELLYOURSAAS_NOREPLY_EMAIL;
	$emailto = GETPOST('to','alpha');
	$replyto = GETPOST('from','alpha');
	$topic = '['.$conf->global->SELLYOURSAAS_NAME.'] - '.GETPOST('subject','none').' - '.$mythirdpartyaccount->name;
	$content = GETPOST('content','none');
	$content .= "<br><br>\n";
	$content .= 'Date: '.dol_print_date($now, 'dayhour')."<br>\n";
	$content .= 'Email: '.GETPOST('from','alpha')."<br>\n";

	$trackid = 'thi'.$mythirdpartyaccount->id;

	$cmailfile = new CMailFile($topic, $emailto, $emailfrom, $content, array(), array(), array(), '', '', 0, 1, '', '', $trackid, '', 'standard', $replyto);
	$result = $cmailfile->sendfile();

	if ($result) setEventMessages($langs->trans("TicketSent"), null, 'warnings');
	else setEventMessages($langs->trans("FailedToSentTicketPleaseTryLater").' '.$cmailfile->error, $cmailfile->errors, 'errors');
	$action = '';
}


if ($action == 'updatemythirdpartyaccount')
{
	$orgname = GETPOST('orgName','nohtml');
	$address = GETPOST('address','nohtml');
	$town = GETPOST('town','nohtml');
	$zip = GETPOST('zip','nohtml');
	$stateorcounty = GETPOST('stateorcounty','nohtml');
	$country_code = GETPOST('country_id','aZ09');
	$vatassuj = (GETPOST('vatassuj','alpha') == 'on' ? 1 : 0);
	$vatnumber = GETPOST('vatnumber','alpha');

	if (empty($orgname))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NameOfCompany")), null, 'errors');
		header("Location: ".$_SERVER['PHP_SELF']."?mode=myaccount#updatemythirdpartyaccount");
		exit;
	}
	if (empty($country_code))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Country")), null, 'errors');
		header("Location: ".$_SERVER['PHP_SELF']."?mode=myaccount#updatemythirdpartyaccount");
		exit;
	}

	$country_id = dol_getIdFromCode($db, $country_code, 'c_country', 'code', 'rowid');

	$db->begin();	// Start transaction

	$mythirdpartyaccount->oldcopy = dol_clone($mythirdpartyaccount);

	$mythirdpartyaccount->name = $orgname;
	$mythirdpartyaccount->address = $address;
	$mythirdpartyaccount->town = $town;
	$mythirdpartyaccount->zip = $zip;
	if ($country_id > 0)
	{
		$mythirdpartyaccount->country_id = $country_id;
		$mythirdpartyaccount->country_code = $country_code;
	}
	$mythirdpartyaccount->tva_assuj = $vatassuj;
	$mythirdpartyaccount->tva_intra = $vatnumber;

	$result = $mythirdpartyaccount->update($mythirdpartyaccount->id, $user);

	if ($result > 0)
	{
		$mythirdpartyaccount->country_code = $country_code;

		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		$db->commit();
	}
	else
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailedToSaveRecord'), null, 'errors');
		setEventMessages($mythirdpartyaccount->error, $mythirdpartyaccount->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'updatemythirdpartylogin')
{
	$email = GETPOST('email','nohtml');
	$firstname = GETPOST('firstName','nohtml');
	$lastname = GETPOST('lastName','nohtml');

	if (empty($email))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Email")), null, 'errors');
		header("Location: ".$_SERVER['PHP_SELF']."?mode=myaccount#updatemythirdpartylogin");
		exit;
	}
	if (! isValidEmail($email))
	{
		setEventMessages($langs->trans("ErrorBadValueForEmail"), null, 'errors');
		header("Location: ".$_SERVER['PHP_SELF']."?mode=myaccount#updatemythirdpartylogin");
		exit;
	}

	$db->begin();	// Start transaction

	$mythirdpartyaccount->email = $email;
	$mythirdpartyaccount->array_options['options_firstname'] = $firstname;
	$mythirdpartyaccount->array_options['options_lastname'] = $lastname;

	$result = $mythirdpartyaccount->update($mythirdpartyaccount->id, $user);

	if ($result > 0)
	{
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
		$db->commit();
	}
	else
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailedToSaveRecord'), null, 'errors');
		setEventMessages($mythirdpartyaccount->error, $mythirdpartyaccount->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'updatepassword')
{
	$password = GETPOST('password','nohtml');
	$password2 = GETPOST('password2','nohtml');

	if (empty($password) || empty($password2))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Password")), null, 'errors');
		header("Location: ".$_SERVER['PHP_SELF']."?mode=myaccount#updatepassword");
		exit;
	}
	if ($password != $password2)
	{
		setEventMessages($langs->trans("ErrorPasswordMismatch"), null, 'errors');
		header("Location: ".$_SERVER['PHP_SELF']."?mode=myaccount#updatepassword");
		exit;
	}

	$db->begin();	// Start transaction

	$mythirdpartyaccount->array_options['options_password'] = $password;

	$result = $mythirdpartyaccount->update($mythirdpartyaccount->id, $user);

	if ($result > 0)
	{
		setEventMessages($langs->trans("PasswordModified"), null, 'mesgs');
		$db->commit();
	}
	else
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorFailedToChangePassword'), null, 'errors');
		setEventMessages($mythirdpartyaccount->error, $mythirdpartyaccount->errors, 'errors');
		$db->rollback();
	}
}

if ($action == 'createpaymentmode')		// Create credit card stripe
{
	$stripeToken = GETPOST("stripeToken",'alpha');
	$label = 'Card '.dol_print_date($now, 'dayhourrfc');

	if (empty($conf->global->SELLYOURSAAS_STRIPE_USE_TOKEN))
	{
		if (! GETPOST('proprio','alpha') || ! GETPOST('cardnumber','alpha') || ! GETPOST('exp_date_month','alpha') || ! GETPOST('exp_date_year','alpha') || ! GETPOST('cvn','alpha'))
		{
			if (! GETPOST('proprio','alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NameOnCard")), null, 'errors');
			if (! GETPOST('cardnumber','alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CardNumber")), null, 'errors');
			if (! (GETPOST('exp_date_month','alpha') > 0) || ! (GETPOST('exp_date_year','alpha') > 0)) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ExpiryDate")), null, 'errors');
			if (! GETPOST('cvn','alpha')) setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CVN")), null, 'errors');
			$action='';
			$mode='registerpaymentmode';
			$error++;
		}
	}
	else
	{
		if (! $stripeToken)
		{
			setEventMessages($langs->trans("ErrorTokenWasNotProvidedByPreviousPage"), null, 'errors');
			$action='';
			$mode='registerpaymentmode';
			$error++;
		}
	}

	if (! $error)
	{
		// Ajout
		$companypaymentmode = new CompanyPaymentMode($db);

		$companypaymentmode->fk_soc          = $mythirdpartyaccount->id;
		$companypaymentmode->bank            = GETPOST('bank','alpha');
		$companypaymentmode->label           = $label;
		$companypaymentmode->number          = GETPOST('cardnumber','alpha');
		$companypaymentmode->last_four       = substr(GETPOST('cardnumber','alpha'), -4);
		$companypaymentmode->proprio         = GETPOST('proprio','alpha');
		$companypaymentmode->exp_date_month  = GETPOST('exp_date_month','int');
		$companypaymentmode->exp_date_year   = GETPOST('exp_date_year','int');
		$companypaymentmode->cvn             = GETPOST('cvn','alpha');
		$companypaymentmode->datec           = $now;
		$companypaymentmode->default_rib     = 1;
		$companypaymentmode->type            = 'card';
		$companypaymentmode->country_code    = $mythirdpartyaccount->country_code;
		$companypaymentmode->status          = $servicestatusstripe;
		// field $companypaymentmode->stripe_card_ref is filled later

		$db->begin();

		if (! $error)
		{
			$result = $companypaymentmode->create($user);
			if ($result < 0)
			{
				$error++;
				setEventMessages($companypaymentmode->error, $companypaymentmode->errors, 'errors');
				$action='createcard';     // Force chargement page création
			}

			if (! empty($conf->stripe->enabled) && class_exists('Stripe'))
			{
				$stripe = new Stripe($db);
				$stripeacc = $stripe->getStripeAccount($service);								// Get Stripe OAuth connect account if it exists (no network access here)

				// Create card on Stripe
				if (! $error)
				{
					// Get the Stripe customer and create if not linked
					$cu = $stripe->customerStripe($mythirdpartyaccount, $stripeacc, $servicestatusstripe, 1);
					if (! $cu)
					{
						$error++;
						setEventMessages($stripe->error, $stripe->errors, 'errors');
					}
					else
					{
						dol_syslog('--- Stripe customer retrieved or created, now we try to create card with mode SELLYOURSAAS_STRIPE_USE_TOKEN='.$conf->global->SELLYOURSAAS_STRIPE_USE_TOKEN);

						if (! empty($conf->global->SELLYOURSAAS_STRIPE_USE_TOKEN))
						{
							$metadata = array(
								'dol_version'=>DOL_VERSION,
								'dol_entity'=>$conf->entity,
								'ipaddress'=>(empty($_SERVER['REMOTE_ADDR'])?'':$_SERVER['REMOTE_ADDR'])
							);
							//if (! empty($dol_id))        			$metadata["dol_id"] = $dol_id;
							//if (! empty($dol_type))      			$metadata["dol_type"] = $dol_type;
							if (! empty($mythirdpartyaccount->id)) 	$metadata["dol_thirdparty_id"] = $mythirdpartyaccount->id;

							// Create Stripe card from Token
							try
							{
								$card = $cu->sources->create(array("source" => $stripeToken, "metadata" => $metadata));
							}
							catch(\Stripe\Error\Card $e) {
								// Since it's a decline, Stripe_CardError will be caught
								$body = $e->getJsonBody();
								$err  = $body['error'];

								$stripefailurecode = $err['code'];
								$stripefailuremessage = $err['message'];

								$error++;
								$errormsg = 'Code: '.$stripefailurecode.', '.$langs->trans("Message").': '.$stripefailuremessage;
								dol_syslog('--- FailedToCreateCardRecord Strip Error Card '.$errormsg, LOG_WARNING);
								setEventMessages($langs->trans('FailedToCreateCardRecord').($errormsg?'<br>'.$errormsg:''), null, 'errors');
								$action='';

								dol_syslog('--- FailedToCreateCardRecord '.json_encode($err), LOG_WARNING);
							}
							catch(Exception $e)
							{
								$error++;
								$errormsg = $e->getMessage();
								dol_syslog('--- FailedToCreateCardRecord Exception '.$errormsg, LOG_WARNING);
								setEventMessages($langs->trans('FailedToCreateCardRecord').($errormsg?'<br>'.$errormsg:''), null, 'errors');
								$action='';
							}

							if (! $error)
							{
								if (empty($card))
								{
									$error++;
									dol_syslog('--- FailedToCreateCardRecord', LOG_WARNING, 0);
									setEventMessages($langs->trans('FailedToCreateCardRecord', ''), null, 'errors');
									$action='';
								}
								else
								{
									$sql = "UPDATE " . MAIN_DB_PREFIX . "societe_rib";
									$sql.= " SET stripe_card_ref = '".$db->escape($card->id)."', card_type = '".$db->escape($card->brand)."',";
									$sql.= " country_code = '".$db->escape($card->country)."',";
									$sql.= " exp_date_month = '".$db->escape($card->exp_month)."',";
									$sql.= " exp_date_year = '".$db->escape($card->exp_year)."',";
									$sql.= " last_four = '".$db->escape($card->last4)."',";
									//$sql.= " cvn = '".$db->escape($card->???)."',";
									$sql.= " approved = ".($card->cvc_check == 'pass' ? 1 : 0);
									$sql.= " WHERE rowid = " . $companypaymentmode->id;
									$sql.= " AND type = 'card'";
									$resql = $db->query($sql);
									if (! $resql)
									{
										setEventMessages($db->lasterror(), null, 'errors');
									}

									$stripecard = $card->id;
								}
							}
						}
						else
						{
							// Create Stripe card from data in $companypaymentmode (societe_rib) created previsoulsy + update of societe_rib to save stripe_card_ref
							$card = $stripe->cardStripe($cu, $companypaymentmode, $stripeacc, $servicestatusstripe, 1);
							if (! $card)
							{
								$error++;
								setEventMessages($stripe->error, $stripe->errors, 'errors');
							}
							else
							{
								$stripecard = $card->id;
							}
						}
					}
				}
			}

			if (! $error)
			{
				$companypaymentmode->setAsDefault($idpayment, 1);
				dol_syslog("--- A credit card was recorded", LOG_DEBUG, 0);

				if ($mythirdpartyaccount->client == 2)
				{
					dol_syslog("--- Set status of thirdparty to prospect+client instead of only prospect", LOG_DEBUG, 0);
					$mythirdpartyaccount->set_as_client();
				}

				if (! $error)
				{
					include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
					// Create an event
					$actioncomm = new ActionComm($db);
					$actioncomm->type_code   = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
					$actioncomm->code        = 'AC_ADD_PAYMENT';
					$actioncomm->label       = 'Payment mode added by '.$_SERVER["REMOTE_ADDR"];
					$actioncomm->datep       = $now;
					$actioncomm->datef       = $now;
					$actioncomm->percentage  = -1;   // Not applicable
					$actioncomm->socid       = $mythirdpartyaccount->id;
					$actioncomm->authorid    = $user->id;   // User saving action
					$actioncomm->userownerid = $user->id;	// Owner of action
					//$actioncomm->fk_element  = $mythirdpartyaccount->id;
					//$actioncomm->elementtype = 'thirdparty';
					$ret=$actioncomm->create($user);       // User creating action
				}
			}
		}

		$erroronstripecharge = 0;


		// Loop on each pending invoices of the thirdparty and try to pay them with payment = invoice remain amount.
		// Note that it may have no pending invoice yet when contract is in trial mode (running or suspended)
		if (! $error)
		{
			dol_syslog("--- Now we search pending invoices for thirdparty to pay them (Note that it may have no pending invoice yet when contract is in trial mode)", LOG_DEBUG, 0);

			dol_include_once('/sellyoursaas/class/sellyoursaasutils.class.php');

			$sellyoursaasutils = new SellYourSaasUtils($db);

			$result = $sellyoursaasutils->doTakePaymentStripeForThirdparty($service, $servicestatusstripe, $mythirdpartyaccount->id, $companypaymentmode, null, 1);
			if ($result != 0)
			{
				$error++;
				setEventMessages($sellyoursaasutils->error, $sellyoursaasutils->errors, 'errors');
			}

			// If some payment was really done, we force commit to be sure to validate invoices payment done by stripe, whatever is global result of doTakePaymentStripeForThirdparty
			if ($sellyoursaasutils->stripechargedone > 0)
			{
				dol_syslog("--- Force commit to validate payments recorded after real Stripe charges", LOG_DEBUG, 0);

				$db->commit();

				$db->begin();
			}
		}

		// Make renewals on contracts of customer
		if (! $error)
		{
			dol_syslog("--- Make renewals on crontacts for thirdparty id=".$mythirdpartyaccount->id, LOG_DEBUG, 0);

			dol_include_once('/sellyoursaas/class/sellyoursaasutils.class.php');

			$sellyoursaasutils = new SellYourSaasUtils($db);

			$result = $sellyoursaasutils->doRenewalContracts($mythirdpartyaccount->id);		// If contract was suspended (end of trial for example, this does nothing)
			if ($result != 0)
			{
				$error++;
				setEventMessages($sellyoursaasutils->error, $sellyoursaasutils->errors, 'errors');
			}
		}

		// Create a recurring invoice (+real invoice + contract renawal) if there is no reccuring invoice yet
		if (! $error)
		{
			foreach ($listofcontractid as $contract)
			{
				dol_syslog("--- Create recurring invoice on contract if it does not have yet.", LOG_DEBUG, 0);

				// Make a test to pass loop if there is already a template invoice
				$result = $contract->fetchObjectLinked();
				if ($result < 0)
				{
					continue;							// There is an error, so we discard this contract to avoid to create template twice
				}
				if (! empty($contract->linkedObjectsIds['facturerec']))
				{
					$templateinvoice = reset($contract->linkedObjectsIds['facturerec']);
					if ($templateinvoice > 0)			// There is already a template invoice, so we discard this contract to avoid to create template twice
					{
						continue;
					}
				}

				dol_syslog("--- No template invoice found for this contract contract_id = ".$contract->id.", so we refresh contract before creating template invoice + creating invoice (if template invoice date is already in past) + making contract renewal.", LOG_DEBUG, 0);


				// First launch update of resources: This update status of install.lock+authorized key and update qty of contract lines
				$result = $sellyoursaasutils->sellyoursaasRemoteAction('refresh', $contract);


				dol_syslog("--- No template invoice found for this contract contract_id = ".$contract->id.", so we create it then create real invoice (if template invoice date is already in past) then make contract renewal.", LOG_DEBUG, 0);

				// Now create invoice draft
				$dateinvoice = $contract->array_options['options_date_endfreeperiod'];
				if ($dateinvoice < $now) $dateinvoice = $now;

				$invoice_draft = new Facture($db);
				$tmpproduct = new Product($db);

				// Create empty invoice
				if (! $error)
				{
					$invoice_draft->socid				= $contract->socid;
					$invoice_draft->type				= Facture::TYPE_STANDARD;
					$invoice_draft->number				= '';
					$invoice_draft->date				= $dateinvoice;

					$invoice_draft->note_private		= 'Template invoice created after adding a payment mode for card/stripe';
					$invoice_draft->mode_reglement_id	= dol_getIdFromCode($db, 'CB', 'c_paiement', 'code', 'id', 1);
					$invoice_draft->cond_reglement_id	= dol_getIdFromCode($db, 'RECEP', 'c_payment_term', 'code', 'rowid', 1);
					$invoice_draft->fk_account          = $conf->global->STRIPE_BANK_ACCOUNT_FOR_PAYMENTS;	// stripe

					$invoice_draft->fetch_thirdparty();

					$origin='contrat';
					$originid=$contract->id;

					$invoice_draft->origin = $origin;
					$invoice_draft->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$invoice_draft->linked_objects[$invoice_draft->origin] = $invoice_draft->origin_id;

					$idinvoice = $invoice_draft->create($user);      // This include class to add_object_linked() and add add_contact()
					if (! ($idinvoice > 0))
					{
						setEventMessages($invoice_draft->error, $invoice_draft->errors, 'errors');
						$error++;
					}
				}
				// Add lines on invoice
				if (! $error)
				{
					// Add lines of contract to template invoice
					$srcobject = $contract;

					$lines = $srcobject->lines;
					if (empty($lines) && method_exists($srcobject, 'fetch_lines'))
					{
						$srcobject->fetch_lines();
						$lines = $srcobject->lines;
					}

					$frequency=1;
					$frequency_unit='m';

					$date_start = false;
					$fk_parent_line=0;
					$num=count($lines);
					for ($i=0; $i<$num; $i++)
					{
						$label=(! empty($lines[$i]->label)?$lines[$i]->label:'');
						$desc=(! empty($lines[$i]->desc)?$lines[$i]->desc:$lines[$i]->libelle);
						if ($invoice_draft->situation_counter == 1) $lines[$i]->situation_percent =  0;

						// Positive line
						$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

						// Date start
						$date_start = false;
						if ($lines[$i]->date_debut_prevue)
							$date_start = $lines[$i]->date_debut_prevue;
						if ($lines[$i]->date_debut_reel)
							$date_start = $lines[$i]->date_debut_reel;
						if ($lines[$i]->date_start)
							$date_start = $lines[$i]->date_start;

						// Date end
						$date_end = false;
						if ($lines[$i]->date_fin_prevue)
							$date_end = $lines[$i]->date_fin_prevue;
						if ($lines[$i]->date_fin_reel)
							$date_end = $lines[$i]->date_fin_reel;
						if ($lines[$i]->date_end)
							$date_end = $lines[$i]->date_end;

						// If date start is in past, we set it to now
						$now = dol_now();
						if ($date_start < $now)
						{
							dol_syslog("--- Date start is in past, so we take current date as date start and update also end date of contract", LOG_DEBUG, 0);
							$tmparray = sellyoursaasGetExpirationDate($srcobject);
							$duration_value = $tmparray['duration_value'];
							$duration_unit = $tmparray['duration_unit'];

							$date_start = $now;
							$date_end = dol_time_plus_duree($now, $duration_value, $duration_unit) - 1;

							// BecauseWe update the end date planned of contract too
							$sqltoupdateenddate = 'UPDATE '.MAIN_DB_PREFIX."contratdet SET date_fin_validite = '".$db->idate($date_end)."' WHERE fk_contrat = ".$srcobject->id;
							$resqltoupdateenddate = $db->query($sqltoupdateenddate);
						}

						// Reset fk_parent_line for no child products and special product
						if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
							$fk_parent_line = 0;
						}

						// Discount
						$discount = $lines[$i]->remise_percent;

						// Extrafields
						if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
							$lines[$i]->fetch_optionals($lines[$i]->rowid);
							$array_options = $lines[$i]->array_options;
						}

						$tva_tx = $lines[$i]->tva_tx;
						if (! empty($lines[$i]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$lines[$i]->vat_src_code.')';

						// View third's localtaxes for NOW and do not use value from origin.
						$localtax1_tx = get_localtax($tva_tx, 1, $invoice_draft->thirdparty);
						$localtax2_tx = get_localtax($tva_tx, 2, $invoice_draft->thirdparty);

						//$price_invoice_template_line = $lines[$i]->subprice * GETPOST('frequency_multiple','int');
						$price_invoice_template_line = $lines[$i]->subprice;

						$result = $invoice_draft->addline($desc, $price_invoice_template_line, $lines[$i]->qty, $tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product, $discount, $date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $invoice_draft->origin, $lines[$i]->rowid, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht, $label, $array_options, $lines[$i]->situation_percent, $lines[$i]->fk_prev_id, $lines[$i]->fk_unit);

						if ($result > 0) {
							$lineid = $result;
						} else {
							$lineid = 0;
							$error++;
							break;
						}

						// Defined the new fk_parent_line
						if ($result > 0 && $lines[$i]->product_type == 9) {
							$fk_parent_line = $result;
						}

						$tmpproduct->fetch($lines[$i]->fk_product);

						dol_syslog("--- Read frequency for product id=".$tmpproduct->id, LOG_DEBUG, 0);
						if ($tmpproduct->array_options['options_app_or_option'] == 'app')
						{
							$frequency = $tmpproduct->duration_value;
							$frequency_unit = $tmpproduct->duration_unit;
						}
					}
				}

				// Now we convert invoice into a template
				if (! $error)
				{
					//var_dump($invoice_draft->lines);
					//var_dump(dol_print_date($date_start,'dayhour'));
					//exit;

					$frequency=1;
					$frequency_unit='m';
					$tmp=dol_getdate($date_start?$date_start:$now);
					$reyear=$tmp['year'];
					$remonth=$tmp['mon'];
					$reday=$tmp['mday'];
					$rehour=$tmp['hours'];
					$remin=$tmp['minutes'];
					$nb_gen_max=0;
					//print dol_print_date($date_start,'dayhour');
					//var_dump($remonth);

					$invoice_rec = new FactureRec($db);

					$invoice_rec->titre = 'Template invoice for '.$contract->ref.' '.$contract->ref_customer;
					$invoice_rec->note_private = $contract->note_private;
					//$invoice_rec->note_public  = dol_concatdesc($contract->note_public, '__(Period)__ : __INVOICE_DATE_NEXT_INVOICE_BEFORE_GEN__ - __INVOICE_DATE_NEXT_INVOICE_AFTER_GEN__');
					$invoice_rec->note_public  = $contract->note_public;
					$invoice_rec->mode_reglement_id = $invoice_draft->mode_reglement_id;

					$invoice_rec->usenewprice = 0;

					$invoice_rec->frequency = $frequency;
					$invoice_rec->unit_frequency = $frequency_unit;
					$invoice_rec->nb_gen_max = $nb_gen_max;
					$invoice_rec->auto_validate = 0;

					$invoice_rec->fk_project = 0;

					$date_next_execution = dol_mktime($rehour, $remin, 0, $remonth, $reday, $reyear);
					$invoice_rec->date_when = $date_next_execution;

					// Get first contract linked to invoice used to generate template
					if ($invoice_draft->id > 0)
					{
						$srcObject = $invoice_draft;

						$srcObject->fetchObjectLinked();

						if (! empty($srcObject->linkedObjectsIds['contrat']))
						{
							$contractidid = reset($srcObject->linkedObjectsIds['contrat']);

							$invoice_rec->origin = 'contrat';
							$invoice_rec->origin_id = $contractidid;
							$invoice_rec->linked_objects[$invoice_draft->origin] = $invoice_draft->origin_id;
						}
					}

					$oldinvoice = new Facture($db);
					$oldinvoice->fetch($invoice_draft->id);

					$invoicerecid = $invoice_rec->create($user, $oldinvoice->id);
					if ($invoicerecid > 0)
					{
						$sql = 'UPDATE '.MAIN_DB_PREFIX.'facturedet_rec SET date_start_fill = 1, date_end_fill = 1 WHERE fk_facture = '.$invoice_rec->id;
						$result = $db->query($sql);
						if (! $error && $result < 0)
						{
							$error++;
							setEventMessages($db->lasterror(), null, 'errors');
						}

						$result=$oldinvoice->delete($user, 1);
						if (! $error && $result < 0)
						{
							$error++;
							setEventMessages($oldinvoice->error, $oldinvoice->errors, 'errors');
						}
					}
					else
					{
						$error++;
						setEventMessages($invoice_rec->error, $invoice_rec->errors, 'errors');
					}

					// A template invoice was just created, we run generation of invoice if template invoice date is already in past
					if (! $error)
					{
						dol_syslog("--- A template invoice was generated with id ".$invoicerecid.", now we run createRecurringInvoices to build real invoice", LOG_DEBUG, 0);
						$facturerec = new FactureRec($db);

						$savperm1 = $user->rights->facture->creer;
						$savperm2 = $user->rights->facture->invoice_advance->validate;

						$user->rights->facture->creer = 1;
						if (empty($user->rights->facture->invoice_advance)) $user->rights->facture->invoice_advance=new stdClass();
						$user->rights->facture->invoice_advance->validate = 1;

						$result = $facturerec->createRecurringInvoices($invoicerecid, 1);
						if ($result != 0)
						{
							$error++;
							setEventMessages($facturerec->error, $facturerec->errors, 'errors');
						}

						$user->rights->facture->creer = $savperm1;
						$user->rights->facture->invoice_advance->validate = $savperm2;
					}
					if (! $error)
					{
						dol_syslog("--- Now we try to take payment for thirdpartyid = ".$mythirdpartyaccount->id, LOG_DEBUG, 0);	// Unsuspend if it was suspended (done by trigger BILL_CANCEL or BILL_PAYED).

						dol_include_once('/sellyoursaas/class/sellyoursaasutils.class.php');

						$sellyoursaasutils = new SellYourSaasUtils($db);

						$result = $sellyoursaasutils->doTakePaymentStripeForThirdparty($service, $servicestatusstripe, $mythirdpartyaccount->id, $companypaymentmode, null, 0, 1);
						if ($result != 0)
						{
							$error++;
							setEventMessages($sellyoursaasutils->error, $sellyoursaasutils->errors, 'errors');
							dol_syslog("--- Failed to take payment ".$sellyoursaasutils->error, LOG_DEBUG, 0);
						}

						// If some payment was really done, we force commit to be sure to validate invoices payment done by stripe, whatever is global result of doTakePaymentStripeForThirdparty
						if ($sellyoursaasutils->stripechargedone > 0)
						{
							dol_syslog("--- Force commit to validate payments recorded after real Stripe charges", LOG_DEBUG, 0);

							$db->commit();

							$db->begin();
						}
					}

					// Make renewals on contracts of customer
					if (! $error)
					{
						dol_syslog("--- Now we make renewal of contracts for thirdpartyid=".$mythirdpartyaccount->id." if payments were ok (it also means contract are refreshed and even unsuspended)", LOG_DEBUG, 0);

						dol_include_once('/sellyoursaas/class/sellyoursaasutils.class.php');

						$sellyoursaasutils = new SellYourSaasUtils($db);

						$result = $sellyoursaasutils->doRenewalContracts($mythirdpartyaccount->id);		// If contract was suspended (end of trial not yet unlocked for example, this does nothing)
						if ($result != 0)
						{
							$error++;
							setEventMessages($sellyoursaasutils->error, $sellyoursaasutils->errors, 'errors');
						}
					}
				}
			}
		}

		if (! $error)
		{
			setEventMessages($langs->trans("PaymentModeRecorded"), null, 'mesgs');

			$db->commit();

			$url=$_SERVER["PHP_SELF"];
			if ($backurl) $url=$backurl;
			header('Location: '.$url);
			exit;
		}
		else
		{
			$db->rollback();

			$action='';
			$mode='registerpaymentmode';
		}
	}
}

if ($action == 'undeploy' || $action == 'undeployconfirmed')
{
	$db->begin();

	$contract=new Contrat($db);
	$contract->fetch(GETPOST('contractid','int'));					// This load also lines
	$contract->fetch_thirdparty();

	if ($contract->socid != $mythirdpartyaccount->id)
	{
		setEventMessages($langs->trans("ErrorYouDontOwnTheInstanceYouTryToDelete", $contract->ref_customer), null, 'errors');
		$error++;
	}

	if (! $error && $action == 'undeploy')
	{
		$urlofinstancetodestroy = GETPOST('urlofinstancetodestroy','alpha');
		if (empty($urlofinstancetodestroy))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NameOfInstanceToDestroy")), null, 'errors');
			$error++;
		}
		elseif ($urlofinstancetodestroy != $contract->ref_customer)
		{
			setEventMessages($langs->trans("ErrorNameOfInstanceDoesNotMatch", $urlofinstancetodestroy, $contract->ref_customer), null, 'errors');
			$error++;
		}
	}

	if (! $error)
	{
		$hash = dol_hash($conf->global->SELLYOURSAAS_KEYFORHASH.$contract->thirdparty->email.dol_print_date($now, 'dayrfc'));

		// Send confirmation email
		if ($action == 'undeploy')
		{
			$object = $contract;

			// SAME CODE THAN INTO ACTION_SELLYOURSAAS.CLASS.PHP

			// Disable template invoice
			$object->fetchObjectLinked();

			$freqlabel = array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year'));
			if (is_array($object->linkedObjects['facturerec']) && count($object->linkedObjects['facturerec']) > 0)
			{
				usort($object->linkedObjects['facturerec'], "cmp");

				//var_dump($object->linkedObjects['facture']);
				//dol_sort_array($object->linkedObjects['facture'], 'date');
				foreach($object->linkedObjects['facturerec'] as $idinvoice => $invoice)
				{
					if ($invoice->suspended == FactureRec::STATUS_NOTSUSPENDED)
					{
						$result = $invoice->setStatut(FactureRec::STATUS_SUSPENDED);
						if ($result <= 0)
						{
							$error++;
							setEventMessages($invoice->error, $invoice->errors, 'errors');
						}
					}
				}
			}

			if (! $error)
			{
				dol_include_once('/sellyoursaas/class/sellyoursaasutils.class.php');
				$sellyoursaasutils = new SellYourSaasUtils($db);
				$result = $sellyoursaasutils->sellyoursaasRemoteAction('suspend', $contract);
				if ($result < 0)
				{
					$error++;
					setEventMessages($sellyoursaasutils->error, $sellyoursaasutils->errors, 'errors');
				}
			}

			// Finish undeploy

			$comment = 'Services closed after an undeploy request from Customer dashboard';

			if (! $error)
			{
				dol_syslog("--- Unactivate all lines - undeploy process from myaccount", LOG_DEBUG, 0);

				$result = $contract->closeAll($user, 1, $comment);
				if ($result < 0)
				{
					$error++;
					setEventMessages($contract->error, $contract->errors, 'errors');
				}
			}

			if (! $error)
			{
				// Send deployment email
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
				$formmail=new FormMail($db);

				$arraydefaultmessage=$formmail->getEMailTemplate($db, 'contract', $user, $langs, 0, 1, 'InstanceUndeployed');	// Templates are init into data.sql

				$substitutionarray=getCommonSubstitutionArray($langs, 0, null, $contract);
				$substitutionarray['__HASH__']=$hash;

				complete_substitutions_array($substitutionarray, $langs, $contract);

				$subject = make_substitutions($arraydefaultmessage->topic, $substitutionarray, $langs);
				$msg     = make_substitutions($arraydefaultmessage->content, $substitutionarray, $langs);
				$from = $conf->global->SELLYOURSAAS_NOREPLY_EMAIL;
				$to = $contract->thirdparty->email;

				$cmail = new CMailFile($subject, $to, $from, $msg, array(), array(), array(), '', '', 0, 1);
				$result = $cmail->sendfile();
				if (! $result)
				{
					$error++;
					setEventMessages($cmail->error, $cmail->errors, 'warnings');
				}
			}
		}

		// Force to close services and launch "undeploy"
		if (! $error && $action == 'undeployconfirmed')
		{
			if ($hash != GETPOST('hash','alpha'))
			{
				$error++;
				setEventMessages('InvalidLinkImmediateDestructionCanceled', null, 'warnings');
			}
			else
			{
				$object = $contract;

				dol_syslog("--- Start undeploy after a confirmation from email for ".$contract->ref_customer, LOG_DEBUG, 0);

				// SAME CODE THAN INTO ACTION_SELLYOURSAAS.CLASS.PHP

				// Disable template invoice
				$object->fetchObjectLinked();

				$freqlabel = array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year'));
				if (is_array($object->linkedObjects['facturerec']) && count($object->linkedObjects['facturerec']) > 0)
				{
					usort($object->linkedObjects['facturerec'], "cmp");

					//var_dump($object->linkedObjects['facture']);
					//dol_sort_array($object->linkedObjects['facture'], 'date');
					foreach($object->linkedObjects['facturerec'] as $idinvoice => $invoice)
					{
						if ($invoice->suspended == FactureRec::STATUS_NOTSUSPENDED)
						{
							$result = $invoice->setStatut(FactureRec::STATUS_SUSPENDED);
							if ($result <= 0)
							{
								$error++;
								setEventMessages($invoice->error, $invoice->errors, 'errors');
							}
						}
					}
				}

				if (! $error)
				{
					dol_include_once('/sellyoursaas/class/sellyoursaasutils.class.php');
					$sellyoursaasutils = new SellYourSaasUtils($db);
					$result = $sellyoursaasutils->sellyoursaasRemoteAction('undeploy', $contract);
					if ($result < 0)
					{
						$error++;
						setEventMessages($sellyoursaasutils->error, $sellyoursaasutils->errors, 'errors');
					}
				}

				// Finish deploy all

				$comment = 'Services closed after click on confirmation request (sent by email from customer dashboard) to undeploy';

				// Unactivate all lines
				if (! $error)
				{
					dol_syslog("--- Unactivate all lines - undeployconfirmed process from myaccount", LOG_DEBUG, 0);

					$result = $object->closeAll($user, 1, $comment);
					if ($result <= 0)
					{
						$error++;
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				// End of deployment is now OK / Complete
				if (! $error)
				{
					$contract->array_options['options_deployment_status'] = 'undeployed';
					$contract->array_options['options_undeployment_date'] = dol_now('tzserver');
					$contract->array_options['options_undeployment_ip'] = $_SERVER['REMOTE_ADDR'];

					$result = $contract->update($user);
					if ($result < 0)
					{
						$error++;
						setEventMessages($contract->error, $contract->errors, 'errors');
					}
				}
			}
		}
	}

	//$error++;
	if (! $error)
	{
		if ($action == 'undeployconfirmed') setEventMessages($langs->trans("InstanceWasUndeployedConfirmed"), null, 'mesgs');
		else
		{
			setEventMessages($langs->trans("InstanceWasUndeployed"), null, 'mesgs');
			setEventMessages($langs->trans("InstanceWasUndeployedToConfirm"), null, 'mesgs');
		}
		$db->commit();
		header('Location: '.$_SERVER["PHP_SELF"].'?modes=instances&tab=resources_'.$contract->id);
		exit;
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'deleteaccount')
{
	if (! GETPOST('accounttodestroy','alpha'))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("AccountToDelete")), '', 'errors');
	}
	else
	{
		if (GETPOST('accounttodestroy','alpha') != $mythirdpartyaccount->email)
		{
			setEventMessages($langs->trans("ErrorEmailMustMatch"), null, 'errors');
		}
		else
		{
			// TODO If there is invoice, me must keep account
			$keepaccount = 1;
			if ($keepaccount)
			{
				$mythirdpartyaccount->status = 0;
				$mythirdpartyaccount->update(0, $user);
				//setEventMessages($langs->trans("YourAccountHasBeenClosed"), null, 'errors');

				llxHeader($head, $langs->trans("MyAccount"), '', '', 0, 0, '', '', '', 'myaccount');

				$linklogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&file='.urlencode('/thumbs/'.$conf->global->SELLYOURSAAS_LOGO_MINI);

				print '
					<center>
				';
				print $langs->trans("YourAccountHasBeenClosed");
				print '
					</center>
				';

				// TODO
				// Make a redirect on cancellation survey

				llxFooter();

				exit;
			}
			else
			{
				$mythirdpartyaccount->delete(0, $user);
				setEventMessages($langs->trans("YourAccountHasBeenClosed"), null, 'errors');
			}
		}
	}
}



/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

if ($welcomecid > 0)
{
	$contract=new Contrat($db);
	$contract->fetch($welcomecid);
	$listofcontractid[$welcomecid]=$contract;
}
//var_dump($listofcontractid);


$head='<link rel="icon" href="img/favicon.ico">
<!-- Bootstrap core CSS -->
<!--<link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.css" rel="stylesheet">-->
<link href="dist/css/bootstrap.css" rel="stylesheet">
<link href="dist/css/myaccount.css" rel="stylesheet">
<link href="dist/css/stripe.css" rel="stylesheet">';
$head.="
<script>
var select2arrayoflanguage = {
	matches: function (matches) { return matches + '" .dol_escape_js($langs->transnoentitiesnoconv("Select2ResultFoundUseArrows"))."'; },
	noResults: function () { return '". dol_escape_js($langs->transnoentitiesnoconv("Select2NotFound")). "'; },
	inputTooShort: function (input) {
		var n = input.minimum;
		/*console.log(input);
		console.log(input.minimum);*/
		if (n > 1) return '". dol_escape_js($langs->transnoentitiesnoconv("Select2Enter")). "' + n + '". dol_escape_js($langs->transnoentitiesnoconv("Select2MoreCharacters")) ."';
			else return '". dol_escape_js($langs->transnoentitiesnoconv("Select2Enter")) ."' + n + '". dol_escape_js($langs->transnoentitiesnoconv("Select2MoreCharacter")) . "';
		},
	loadMore: function (pageNumber) { return '".dol_escape_js($langs->transnoentitiesnoconv("Select2LoadingMoreResults"))."'; },
	searching: function () { return '". dol_escape_js($langs->transnoentitiesnoconv("Select2SearchInProgress"))."'; }
};
</script>
";



llxHeader($head, $langs->trans("MyAccount"), '', '', 0, 0, '', '', '', 'myaccount');

$linklogo = DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&file='.urlencode('/thumbs/'.$conf->global->SELLYOURSAAS_LOGO_MINI);

print '
    <nav class="navbar navbar-toggleable-md navbar-inverse bg-inverse">

	  <!-- Search + Menu -->

	  <form class="navbar-toggle navbar-toggler-right form-inline my-md-0" action="'.$_SERVER["PHP_SELF"].'">
			<input type="hidden" name="mode" value="'.dol_escape_htmltag($mode).'">
			<!--
				          <input class="form-control mr-sm-2" style="max-width: 100px;" type="text" placeholder="'.$langs->trans("Search").'">
				          <button class="btn-transparent nav-link" type="submit"><i class="fa fa-search"></i></button>
			-->
	      <button class="inline-block navbar-toggler" type="button" data-toggle="collapse" data-target="#navbars" aria-controls="navbars" aria-expanded="false" aria-label="Toggle navigation">
	        <span class="navbar-toggler-icon"></span>
	      </button>
	  </form>

	  <!-- Logo -->
      <span class="navbar-brand"><img src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=mycompany&file='.urlencode('/thumbs/'.$conf->global->SELLYOURSAAS_LOGO_MINI_BLACK).'" height="34px"></span>

	  <!-- Menu -->
      <div class="collapse navbar-collapse" id="navbars">
        <ul class="navbar-nav mr-auto">
          <li class="nav-item'.($mode == 'dashboard'?' active':'').'">
            <a class="nav-link" href="'.$_SERVER["PHP_SELF"].'?mode=dashboard"><i class="fa fa-tachometer"></i> '.$langs->trans("Dashboard").'</a>
          </li>
          <li class="nav-item'.($mode == 'instances'?' active':'').'">
            <a class="nav-link" href="'.$_SERVER["PHP_SELF"].'?mode=instances"><i class="fa fa-server"></i> '.$langs->trans("MyInstances").'</a>
          </li>
          <li class="nav-item'.($mode == 'billing'?' active':'').'">
            <a class="nav-link" href="'.$_SERVER["PHP_SELF"].'?mode=billing"><i class="fa fa-usd"></i> '.$langs->trans("MyBilling").'</a>
          </li>';

		if ($mythirdpartyaccount->isareseller)
		{
			print '
			<li class="nav-item'.($mode == 'mycustomerinstances'?' active':'').'">
			<a class="nav-link" href="'.$_SERVER["PHP_SELF"].'?mode=mycustomerinstances"><i class="fa fa-server"></i> '.$langs->trans("MyCustomersInstances").'</a>
			</li>
			<li class="nav-item'.($mode == 'mycustomerbilling'?' active':'').'">
			<a class="nav-link" href="'.$_SERVER["PHP_SELF"].'?mode=mycustomerbilling"><i class="fa fa-usd"></i> '.$langs->trans("MyCustomersBilling").'</a>
			</li>';
		}

          print '<li class="nav-item'.($mode == 'support'?' active':'').' dropdown">
            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#"><i class="fa fa-gear"></i> '.$langs->trans("Other").'</a>
            <ul class="dropdown-menu">
	            <li><a class="dropdown-item" href="'.$_SERVER["PHP_SELF"].'?mode=support">'.$langs->trans("Support").'</a></li>
			';
          if (! $mythirdpartyaccount->isareseller && ! empty($conf->global->SELLYOURSAAS_ALLOW_RESELLER_PROGRAM))
          {
          	print '<li><a class="dropdown-item" href="'.$_SERVER["PHP_SELF"].'?mode=becomereseller">'.$langs->trans("BecomeReseller").'</a></li>';
          }
          print '
                <li class="dropdown-divider"></li>
	            <li><a class="dropdown-item" href="'.$urlfaq.'" target="_newfaq">'.$langs->trans("FAQs").'</a></li>
            </ul>
          </li>

          <li class="nav-item'.($mode == 'myaccount'?' active':'').' dropdown">
             <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#socid='.$mythirdpartyaccount->id.'"><i class="fa fa-user"></i> '.$langs->trans("MyAccount").' ('.$mythirdpartyaccount->email.')</a>
             <ul class="dropdown-menu">
                 <li><a class="dropdown-item" href="'.$_SERVER["PHP_SELF"].'?mode=myaccount"><i class="fa fa-user"></i> '.$langs->trans("MyAccount").'</a></li>
                 <li class="dropdown-divider"></li>
                 <li><a class="dropdown-item" href="'.$_SERVER["PHP_SELF"].'?mode=logout"><i class="fa fa-sign-out"></i> '.$langs->trans("Logout").'</a></li>
             </ul>
           </li>

        </ul>


      </div>
    </nav>
';


print '
    <div class="container">
		<br>
';


//var_dump($_SESSION["dol_loginsellyoursaas"]);
//var_dump($user);


// Special case - when coming from a specific contract id $welcomid
if ($welcomecid > 0)
{
	$contract = $listofcontractid[$welcomecid];
	$contract->fetch_thirdparty();

	print '
      <div class="jumbotron">
        <div class="col-sm-8 mx-auto">


		<!-- BEGIN PAGE HEAD -->
		<div class="page-head">
		<!-- BEGIN PAGE TITLE -->
		<div class="page-title">
		<h1>'.$langs->trans("Welcome").'</h1>
		</div>
		<!-- END PAGE TITLE -->
		</div>
		<!-- END PAGE HEAD -->


		<!-- BEGIN PORTLET -->
		<div class="portletnoborder light">

		<div class="portlet-header">
		<div class="caption">
		<span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("InstallationComplete").'</span>
		</div>
		</div>';

	if (in_array($contract->thirdparty->country_code, array('aaa', 'bbb')))
	{
		print '
		<div class="portlet-body">
		<p>
		'.$langs->trans("YourCredentialToAccessYourInstanceHasBeenSentByEmail").'
		</p>

		</div>';
	}
	else
	{
	print '<!-- message installation finished -->
		<div class="portlet-body">
		<p>
		'.$langs->trans("YouCanAccessYourInstance", $contract->array_options['options_plan']).'&nbsp:
		</p>
		<p class="well">
		'.$langs->trans("URL").' : <a href="https://'.$contract->ref_customer.'" target="_blank">'.$contract->ref_customer.'</a>';

		print '<br> '.$langs->trans("Username").' : '.($_SESSION['initialapplogin']?'<strong>'.$_SESSION['initialapplogin'].'</strong>':'NA').'
		<br> '.$langs->trans("Password").' : '.($_SESSION['initialapppassword']?'<strong>'.$_SESSION['initialapppassword'].'</strong>':'NA').'
		</p>
		<p>
		<a class="btn btn-primary" target="_blank" href="https://'.$contract->ref_customer.'?username='.$_SESSION['initialapplogin'].'">
		'.$langs->trans("TakeMeTo", $contract->array_options['options_plan']).'
		</a>
		</p>

		</div>';
	}

	print '
		</div> <!-- END PORTLET -->

        </div>
      </div>
	';
}

// Show global announce
if (! empty($conf->global->SELLYOURSAAS_ANNOUNCE))
{

	print '
		<div class="note note-warning">
		<h4 class="block">'.$langs->trans($conf->global->SELLYOURSAAS_ANNOUNCE).'</h4>
		</div>
	';
}


// List of available plans/products (available for reseller)
$arrayofplans=array();
$arrayofplanscode=array();
$sqlproducts = 'SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.duration';
$sqlproducts.= ' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_extrafields as pe';
$sqlproducts.= ' WHERE p.tosell = 1 AND p.entity = '.$conf->entity;
$sqlproducts.= " AND pe.fk_object = p.rowid AND pe.app_or_option = 'app'";
$sqlproducts.= " AND p.ref NOT LIKE '%DolibarrV1%'";
$sqlproducts.= " AND pe.availabelforresellers = 1";
//$sqlproducts.= " AND (p.rowid = ".$planid." OR 1 = 1)";
$resqlproducts = $db->query($sqlproducts);
if ($resqlproducts)
{
	$num = $db->num_rows($resqlproducts);

	$tmpprod = new Product($db);
	$tmpprodchild = new Product($db);
	$i=0;
	while($i < $num)
	{
		$obj = $db->fetch_object($resqlproducts);
		if ($obj)
		{
			$tmpprod->fetch($obj->rowid);
			$tmpprod->get_sousproduits_arbo();
			$tmparray = $tmpprod->get_arbo_each_prod();

			$label = $obj->label;

			$priceinstance=array();
			$priceinstance_ttc=array();

			$priceinstance['fix'] = $obj->price;
			$priceinstance_ttc['fix'] = $obj->price_ttc;
			$priceinstance['user'] = 0;
			$priceinstance_ttc['user'] = 0;

			if (count($tmparray) > 0)
			{
				foreach($tmparray as $key => $value)
				{
					$tmpprodchild->fetch($value['id']);
					if (preg_match('/user/i', $tmpprodchild->ref) || preg_match('/user/i', $tmpprodchild->array_options['options_resource_label']))
					{
						$priceinstance['user'] .= $obj->price;
						$priceinstance_ttc['user'] .= $obj->price_ttc;
					}
					else
					{
						$priceinstance['fix'] += $tmpprodchild->price;
						$priceinstance_ttc['fix'] += $tmpprodchild->price_ttc;
					}
				}
			}

			$pricetoshow = price2num($priceinstance['fix'],'MT');
			if (empty($pricetoshow)) $pricetoshow = 0;
			$arrayofplans[$obj->rowid]=$label.' ('.price($pricetoshow, 1, $langs, 1, 0, -1, $conf->currency);

			if ($tmpprod->duration) $arrayofplans[$obj->rowid].=' / '.($tmpprod->duration == '1m' ? $langs->trans("Month") : '');
			if ($priceinstance['user'])
			{
				$arrayofplans[$obj->rowid].=' + '.price(price2num($priceinstance['user'],'MT'), 1, $langs, 1, 0, -1, $conf->currency).'/'.$langs->trans("User");
				if ($tmpprod->duration) $arrayofplans[$obj->rowid].=' / '.($tmpprod->duration == '1m' ? $langs->trans("Month") : '');
			}
			$arrayofplans[$obj->rowid].=')';
			$arrayofplanscode[$obj->rowid] = $obj->ref;
		}
		$i++;
	}
}
else dol_print_error($db);


// Show partner links
if ($mythirdpartyaccount->isareseller)
{
	print '
		<!-- Info reseller -->
		<div class="note note-info">
		<h4 class="block">'.$langs->trans("YouAreAReseller").'</h4>
		';
	print $langs->trans("YourURLToCreateNewInstance").' : ';
	$urlforpartner = $conf->global->SELLYOURSAAS_ACCOUNT_URL.'/register.php?partner='.$mythirdpartyaccount->id.'&partnerkey='.md5($mythirdpartyaccount->name_alias);
	print '<a class="wordbreak" href="'.$urlforpartner.'" target="_blankinstance">'.$urlforpartner;
	if (is_array($arrayofplans) && count($arrayofplans) > 1) print '&plan=XXX';
	print '</a><br>';
	if (is_array($arrayofplans) && count($arrayofplans) > 1)
	{
		print '<div class="opacitymedium">('.$langs->trans("whereXXXcanbe").' '.join(', ', $arrayofplanscode).')</div><br>';
	}
	$urformycustomerinstances = '<strong>'.$langs->transnoentitiesnoconv("MyCustomersBilling").'</strong>';
	print $langs->trans("YourCommissionsAppearsInMenu", $mythirdpartyaccount->array_options['options_commission'], $urformycustomerinstances);
	print '
		</div>
	';
}



// Fill array of company payment modes
$arrayofcompanypaymentmode = array();
$sql = 'SELECT rowid, default_rib FROM '.MAIN_DB_PREFIX."societe_rib";
$sql.= " WHERE type in ('ban', 'card', 'paypal')";
$sql.= " AND fk_soc = ".$mythirdpartyaccount->id;
$sql.= " AND (type = 'ban' OR (type='card' AND status = ".$servicestatusstripe.") OR (type='paypal' AND status = ".$servicestatuspaypal."))";
$sql.= " ORDER BY default_rib DESC, tms DESC";

$resql = $db->query($sql);
if ($resql)
{
	$num_rows = $db->num_rows($resql);
	if ($num_rows)
	{
		$i=0;
		while ($i < $num_rows)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				if ($obj->default_rib != 1) continue;	// Keep the default payment mode only

				$companypaymentmodetemp = new CompanyPaymentMode($db);
				$companypaymentmodetemp->fetch($obj->rowid);

				$arrayofcompanypaymentmode[] = $companypaymentmodetemp;
			}
			$i++;
		}
	}
}
$atleastonepaymentmode = (count($arrayofcompanypaymentmode) > 0 ? 1 : 0);
$nbpaymentmodeok = count($arrayofcompanypaymentmode);


// Fill var to count nb of instances
$nbofinstances = 0;
$nbofinstancesinprogress = 0;
$nbofinstancesdone = 0;
$nbofinstancessuspended = 0;
foreach ($listofcontractid as $contractid => $contract)
{
	if ($contract->array_options['options_deployment_status'] == 'undeployed') { continue; }
	if ($contract->array_options['options_deployment_status'] == 'processing') { $nbofinstances++; $nbofinstancesinprogress++; continue; }

	$suspended = 0;
	foreach($contract->lines as $keyline => $line)
	{
		if ($line->statut == ContratLigne::STATUS_CLOSED && $contract->array_options['options_deployment_status'] != 'undeployed')
		{
			$suspended = 1;
			break;
		}
	}

	$nbofinstances++;
	if ($suspended) $nbofinstancessuspended++;
	else $nbofinstancesdone++;
}
$nboftickets = $langs->trans("SoonAvailable");
if ($mythirdpartyaccount->isareseller)
{
	// Fill var to count nb of instances
	$nbofinstancesreseller = 0;
	$nbofinstancesinprogressreseller = 0;
	$nbofinstancesdonereseller = 0;
	$nbofinstancessuspendedreseller = 0;
	foreach ($listofcontractidreseller as $contractid => $contract)
	{
		if ($contract->array_options['options_deployment_status'] == 'undeployed') { continue; }
		if ($contract->array_options['options_deployment_status'] == 'processing') { $nbofinstancesreseller++; $nbofinstancesinprogressreseller++; continue; }

		$suspended = 0;
		foreach($contract->lines as $keyline => $line)
		{
			if ($line->statut == ContratLigne::STATUS_CLOSED && $contract->array_options['options_deployment_status'] != 'undeployed')
			{
				$suspended = 1;
				break;
			}
		}

		$nbofinstancesreseller++;
		if ($suspended) $nbofinstancessuspendedreseller++;
		else $nbofinstancesdonereseller++;
	}
}


$atleastonecontractwithtrialended = 0;
$atleastonepaymentinerroronopeninvoice = 0;

// Show warnings
if (empty($welcomecid))
{
	$companypaymentmode = new CompanyPaymentMode($db);
	$result = $companypaymentmode->fetch(0, null, $mythirdpartyaccount->id);

	foreach ($listofcontractid as $contractid => $contract)
	{
		if ($mode == 'mycustomerbilling') continue;
		if ($mode == 'mycustomerinstances') continue;
		if ($contract->array_options['options_deployment_status'] == 'undeployed') continue;

		$isAPayingContract = sellyoursaasIsPaidInstance($contract);		// At least one template or final invoice
		$isASuspendedContract = sellyoursaasIsSuspended($contract);		// Is suspended or not ?
		$tmparray = sellyoursaasGetExpirationDate($contract);
		$expirationdate = $tmparray['expirationdate'];					// End of date of service

		$messageforinstance=array();

		if (! $isAPayingContract && $contract->array_options['options_date_endfreeperiod'] > 0)
		{
			$dateendfreeperiod = $contract->array_options['options_date_endfreeperiod'];
			if (! is_numeric($dateendfreeperiod)) $dateendfreeperiod = dol_stringtotime($dateendfreeperiod);
			$delaybeforeendoftrial = ($dateendfreeperiod - $now);
			$delayindays = round($delaybeforeendoftrial / 3600 / 24);

			if (empty($atleastonepaymentmode))
			{
				if ($delaybeforeendoftrial > 0)		// Trial not yet expired
				{
					if (! $isASuspendedContract)
					{
						$firstline = reset($contract->lines);
						print '
							<!-- XDaysBeforeEndOfTrial -->
							<div class="note note-warning">
							<h4 class="block">'.$langs->trans("XDaysBeforeEndOfTrial", abs($delayindays), $contract->ref_customer).' !</h4>
							<p>
							<a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'" class="btn btn-warning">';
						print $langs->trans("AddAPaymentMode");
						print '</a>
							</p>
							</div>
						';
					}
					else
					{
						$firstline = reset($contract->lines);
						print '
							<!-- TrialInstanceWasSuspended -->
							<div class="note note-warning">
							<h4 class="block">'.$langs->trans("TrialInstanceWasSuspended", $contract->ref_customer).' !</h4>
							<p>
							<a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'" class="btn btn-warning">';
						print $langs->trans("AddAPaymentModeToRestoreInstance");
						print '</a>
							</p>
							</div>
						';
					}
				}
				else								// Trial expired
				{
					$atleastonecontractwithtrialended++;

					$messageforinstance[$contract->ref_customer] = 1;

					$firstline = reset($contract->lines);
					print '
						<!-- XDaysAfterEndOfTrial -->
						<div class="note note-warning">
						<h4 class="block">'.$langs->trans("XDaysAfterEndOfTrial", $contract->ref_customer, abs($delayindays)).' !</h4>
						<p>
						<a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'" class="btn btn-warning">';
					print $langs->trans("AddAPaymentModeToRestoreInstance");
					print '</a>
						</p>
						</div>
					';
				}
			}
			else
			{
				if ($delaybeforeendoftrial > 0)		// Trial not yet expired
				{
					if ($contract->array_options['options_deployment_status'] != 'processing')
					{
						$firstline = reset($contract->lines);
						print '
							<!-- XDaysBeforeEndOfTrialPaymentModeSet -->
							<div class="note note-info">
							<h4 class="block">'.$langs->trans("XDaysBeforeEndOfTrialPaymentModeSet", abs($delayindays), $contract->ref_customer).'</h4>
							</div>
						';
					}
				}
				else								// Trial expired
				{
					$atleastonecontractwithtrialended++;

					print '
						<!-- XDaysAfterEndOfTrialPaymentModeSet -->
						<div class="note note-info">
						<h4 class="block">'.$langs->trans("XDaysAfterEndOfTrialPaymentModeSet", $contract->ref_customer, abs($delayindays)).'</h4>
						</div>
					';
				}
			}
		}

		if ($isASuspendedContract)
		{
			if (empty($messageforinstance[$contract->ref_customer])		// If warning for expired trial alreay shown
				&& $delaybeforeendoftrial <= 0)							// If trial has expired
			{
				print ' <!-- XDaysAfterEndOfPeriodInstanceSuspended -->
						<div class="note note-warning">
							<h4 class="block">'.$langs->trans("XDaysAfterEndOfPeriodInstanceSuspended", $contract->ref_customer, abs($delayindays));
				if (empty($atleastonepaymentmode))
				{
					print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'">'.$langs->trans("AddAPaymentModeToRestoreInstance").'</a>';
				}
				else
				{
					print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'">'.$langs->trans("FixPaymentModeToRestoreInstance").'</a>';
				}
				print '</h4>
							</div>
						';
			}
		}
		else if ($isAPayingContract && $expirationdate > 0)
		{
			$delaybeforeexpiration = ($expirationdate - $now);
			$delayindays = round($delaybeforeexpiration / 3600 / 24);

			if ($delayindays < 0)	// Expired
			{
				print '
						<!-- XDaysAfterEndOfPeriodPaymentModeSet -->
						<div class="note note-warning">
						<h4 class="block">'.$langs->trans("XDaysAfterEndOfPeriodPaymentModeSet", $contract->ref_customer, abs($delayindays)).'</h4>
						</div>
					';
			}
		}
	}

	// Test if there is a payment error, if yes, ask to fix payment data
	$sql = 'SELECT f.rowid, ee.code, ee.extraparams  FROM '.MAIN_DB_PREFIX.'facture as f';
	$sql.= ' INNER JOIN '.MAIN_DB_PREFIX."actioncomm as ee ON ee.fk_element = f.rowid AND ee.elementtype = 'invoice'";
	$sql.= " AND ee.code LIKE 'AC_PAYMENT_%_KO'";
	$sql.= ' WHERE f.fk_soc = '.$mythirdpartyaccount->id.' AND f.paye = 0';
	$sql.= ' ORDER BY ee.datep DESC';

	$resql = $db->query($sql);
	if ($resql)
	{
		$num_rows = $db->num_rows($resql);
		$i=0;
		if ($num_rows)
		{
			$atleastonepaymentinerroronopeninvoice++;

			$obj = $db->fetch_object($resql);
			$labelerror = $obj->extraparams;
			if (empty($labelerror)) $labelerror=$langs->trans("UnknownError");

			// There is at least one payment error
			if ($obj->extraparams == 'PAYMENT_ERROR_INSUFICIENT_FUNDS')
			{
				print '
						<div class="note note-warning">
						<h4 class="block">'.$langs->trans("SomeOfYourPaymentFailedINSUFICIENT_FUNDS", $labelerror).'</h4>
						</div>
					';
			}
			else
			{
				print '
						<div class="note note-warning">
						<h4 class="block">'.$langs->trans("SomeOfYourPaymentFailed", $labelerror).'</h4>
						</div>
					';
			}
		}
	}
	else dol_print_error($db);
}


if ($mode == 'dashboard')
{
	print '
	<div class="page-content-wrapper">
			<div class="page-content">

	     <!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE HEAD -->
	<div class="page-head">
	  <!-- BEGIN PAGE TITLE -->
	<div class="page-title">
	  <h1>'.$langs->trans("Dashboard").'</h1>
	</div>
	<!-- END PAGE TITLE -->


	</div>
	<!-- END PAGE HEAD -->
	<!-- END PAGE HEADER-->


	    <div class="row">
	      <div class="col-md-6">

	        <div class="portlet light" id="planSection">

	          <div class="portlet-title">
	            <div class="caption">
	              <span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("MyInstances").'</span>
	            </div>
	          </div>

	          <div class="portlet-body">

	            <div class="row">
	              <div class="col-md-9">
					'.$langs->trans("NbOfActiveInstances").'
	              </div>
	              <div class="col-md-3 right">
	                <h2>'.$nbofinstancesdone.'</h2>
	              </div>
	            </div> <!-- END ROW -->

				';
				if ($nbofinstancessuspended)
				{
					print '
			            <div class="row">
			              <div class="col-md-9">
							'.$langs->trans("NbOfSuspendedInstances").'
			              </div>
			              <div class="col-md-3 right">
			                <h2 style="color:orange">'.$nbofinstancessuspended.'</h2>
			              </div>
			            </div> <!-- END ROW -->
					';
				}

				print '
					<div class="row">
					<div class="center col-md-12">
						<br>
						<a class="wordbreak" href="'.$_SERVER["PHP_SELF"].'?mode=instances" class="btn default btn-xs green-stripe">
		            	'.$langs->trans("SeeDetailsAndOptions").'
		                </a>
					</div></div>';

			print '
				</div>';		// end protlet-body

			if ($mythirdpartyaccount->isareseller)
			{
				print '
				<div class="portlet-title">
				<div class="caption"><br><br>
				<span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("InstancesOfMyCustomers").'</span>
				</div>
				</div>

				<div class="portlet-body">

				<div class="row">
				<div class="col-md-9">
				'.$langs->trans("NbOfActiveInstances").'
				</div>
				<div class="col-md-3 right">
				<h2>'.$nbofinstancesdonereseller.'</h2>
				</div>
				</div> <!-- END ROW -->

				';
				if ($nbofinstancessuspendedreseller)
				{
					print '
					<div class="row">
					<div class="col-md-9">
					'.$langs->trans("NbOfSuspendedInstances").'
					</div>
					<div class="col-md-3 right">
					<h2 style="color:orange">'.$nbofinstancessuspendedreseller.'</h2>
					</div>
					</div> <!-- END ROW -->
					';
				}

				print '
					<div class="row">
					<div class="center col-md-12">
						<br>
						<a class="wordbreak" href="'.$_SERVER["PHP_SELF"].'?mode=mycustomerinstances" class="btn default btn-xs green-stripe">
		            	'.$langs->trans("SeeDetailsAndOptionsOfMyCustomers").'
		                </a>
					</div></div>';

				print '</div>';		// end protlet-body
			}

			print '

	        </div> <!-- END PORTLET -->

	      </div> <!-- END COL -->

			<!-- My profile -->
	      <div class="col-md-6">
	        <div class="portlet light" id="myProfile">

	          <div class="portlet-title">
	            <div class="caption">
	              <span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("MyAccount").'</span>
	            </div>
	          </div>

	          <div class="portlet-body">
				<div class="row">
				<div class="col-md-12">
	                ';
					if (empty($welcomecid))		// If we just created an instance, we don't show warnings yet.
					{
		                $missing = 0;
		                if (empty($mythirdpartyaccount->array_options['options_firstname'])) $missing++;
		                if (empty($mythirdpartyaccount->array_options['options_lastname'])) $missing++;
		                if ($mythirdpartyaccount->tva_assuj && empty($mythirdpartyaccount->tva_intra)) $missing++;

		                if (! $missing)
		                {
							print $langs->trans("ProfileIsComplete");
		                }
		                else
		                {
		                	print $langs->trans("ProfileIsNotComplete", $missing, $_SERVER["PHP_SELF"].'?mode=myaccount');
		                	print ' '.img_warning();
		                }
					}
	                print '
	            </div>
				</div>

				<div class="row">
				<div class="center col-md-12">
					<br>
					<a class="wordbreak" href="'.$_SERVER["PHP_SELF"].'?mode=myaccount" class="btn default btn-xs green-stripe">
	            	'.$langs->trans("SeeOrEditProfile").'
	                </a>
				</div>
				</div>

	          </div> <!-- END PORTLET-BODY -->

	        </div> <!-- END PORTLET -->
	      </div><!-- END COL -->


	    </div> <!-- END ROW -->

	';

	print '
	    <div class="row">

			<!-- Box of payment balance -->
	      <div class="col-md-6">
	        <div class="portlet light" id="paymentBalance">

	          <div class="portlet-title">
	            <div class="caption">
	              <span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("PaymentBalance").'</span>
	            </div>
	          </div>';

				//var_dump($contract->linkedObjects['facture']);
				//dol_sort_array($contract->linkedObjects['facture'], 'date');
				$nbinvoicenotpayed = 0;
				$amountdue = 0;
				foreach ($listofcontractid as $id => $contract)
				{
					$contract->fetchObjectLinked();
					if (is_array($contract->linkedObjects['facture']))
					{
						foreach($contract->linkedObjects['facture'] as $idinvoice => $invoice)
						{
							if ($invoice->statut == $invoice::STATUS_DRAFT) continue;
							if ($invoice->statut != $invoice::STATUS_CLOSED)
							{
								$nbinvoicenotpayed++;
							}
							$alreadypayed = $invoice->getSommePaiement();
							$amount_credit_notes_included = $invoice->getSumCreditNotesUsed();
							$amountdue = $invoice->total_ttc - $alreadypayed - $amount_credit_notes_included;
						}
					}
				}
				print '
	          <div class="portlet-body">

				<div class="row">
				<div class="col-md-9">
	            ';
				if ($amountdue > 0 && $atleastonepaymentmode) print $form->textwithpicto($langs->trans("UnpaidInvoices"), $langs->trans("PaymentWillBeProcessedSoon"));
				else print $langs->trans("UnpaidInvoices");
	            print '
				</div>
				<div class="col-md-3 right"><h2>';
				if ($nbinvoicenotpayed > 0) print '<font style="color: orange">';
				print $nbinvoicenotpayed;
				if ($nbinvoicenotpayed) print '</font>';
				print '<h2></div>
	            </div>
				<div class="row">
				<div class="col-md-9">';
				if ($amountdue > 0 && $atleastonepaymentmode) print $form->textwithpicto($langs->trans("RemainderToPay"), $langs->trans("PaymentWillBeProcessedSoon"));
				else print $langs->trans("RemainderToPay");
				print '</div>
				<div class="col-md-3 right"><h2>';
				if ($amountdue > 0) print '<font style="color: orange">';
				print price($amountdue, 1, $langs, 0, -1, $conf->global->MAIN_MAX_DECIMALS_TOT, $conf->currency);
				if ($amountdue > 0) print '</font>';
				print '</h2></div>
	            </div>

				<div class="row">
				<div class="center col-md-12">
					<br>
					<a class="wordbreak" href="'.$_SERVER["PHP_SELF"].'?mode=billing" class="btn default btn-xs green-stripe">
	            	'.$langs->trans("SeeDetailsOfPayments").'
	                </a>
				</div>
				</div>

	          </div> <!-- END PORTLET-BODY -->

	        </div> <!-- END PORTLET -->
	      </div><!-- END COL -->


			<!-- Box of tickets -->
	      <div class="col-md-6">
	        <div class="portlet light" id="boxOfTickets">

	          <div class="portlet-title">
	            <div class="caption">
	              <span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("SupportTickets").'</span>
	            </div>
	          </div>';

			$nboftickets = 0;
			$nbofopentickets = 0;

			print '
	          <div class="portlet-body">

	            <div class="row">
	              <div class="col-md-9">
					'.$langs->trans("NbOfTickets").'
	              </div>
	              <div class="col-md-3 right"><h2>
	                '.$nboftickets.'
	              </h2></div>
	            </div> <!-- END ROW -->

	            <div class="row">
	              <div class="col-md-9">
					'.$langs->trans("NbOfOpenTickets").'
	              </div>
	              <div class="col-md-3 right"><h2>';
					if ($nbofopentickets > 0) print '<font style="color: orange">';
					print $nbofopentickets;
					if ($nbofopentickets > 0) print '</font>';
	                print '</h2>
	              </div>
	            </div> <!-- END ROW -->

				<div class="row">
				<div class="center col-md-12">
					<br>
					<a class="wordbreak" href="'.$_SERVER["PHP_SELF"].'?mode=support" class="btn default btn-xs green-stripe">
	            	'.$langs->trans("SeeDetailsOfTickets").'
	                </a>
				</div></div>

	          </div> <!-- END PORTLET-BODY -->

	        </div> <!-- END PORTLET -->
	      </div><!-- END COL -->

	    </div> <!-- END ROW -->
	';

	print '
		</div>

	    </div>
		</div>
	';
}

if ($mode == 'instances')
{
	// List of available plans/producs
	$arrayofplans=array();
	$sqlproducts = 'SELECT p.rowid, p.ref, p.label, p.price, p.price_ttc, p.duration';
	$sqlproducts.= ' FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_extrafields as pe';
	$sqlproducts.= ' WHERE p.tosell = 1 AND p.entity = '.$conf->entity;
	$sqlproducts.= " AND pe.fk_object = p.rowid AND pe.app_or_option = 'app'";
	$sqlproducts.= " AND p.ref NOT LIKE '%DolibarrV1%'";
	//$sqlproducts.= " AND (p.rowid = ".$planid." OR 1 = 1)";
	//$sqlproducts.=' AND p.rowid = 202';
	//print $sqlproducts;

	$resqlproducts = $db->query($sqlproducts);
	if ($resqlproducts)
	{
		$num = $db->num_rows($resqlproducts);

		$tmpprod = new Product($db);
		$tmpprodchild = new Product($db);
		$i=0;
		while($i < $num)
		{
			$obj = $db->fetch_object($resqlproducts);
			if ($obj)
			{
				$tmpprod->fetch($obj->rowid);
				$tmpprod->sousprods = array();
				$tmpprod->get_sousproduits_arbo();
				$tmparray = $tmpprod->get_arbo_each_prod();

				$label = $obj->label;

				$priceinstance=array();
				$priceinstance_ttc=array();

				$priceinstance['fix'] = $obj->price;
				$priceinstance_ttc['fix'] = $obj->price_ttc;
				$priceinstance['user'] = 0;
				$priceinstance_ttc['user'] = 0;

				if (count($tmparray) > 0)
				{
					foreach($tmparray as $key => $value)
					{
						$tmpprodchild->fetch($value['id']);
						if (preg_match('/user/i', $tmpprodchild->ref) || preg_match('/user/i', $tmpprodchild->array_options['options_resource_label']))
						{
							$priceinstance['user'] += $tmpprodchild->price;
							$priceinstance_ttc['user'] += $tmpprodchild->price_ttc;
						}
						else
						{
							$priceinstance['fix'] += $tmpprodchild->price;
							$priceinstance_ttc['fix'] += $tmpprodchild->price_ttc;
						}
						//var_dump($tmpprodchild->id.' '.$tmpprodchild->array_options['options_app_or_option'].' '.$tmpprodchild->price_ttc.' -> '.$priceuser.' / '.$priceuser_ttc);
					}
				}

				$pricetoshow = price2num($priceinstance['fix'],'MT');
				if (empty($pricetoshow)) $pricetoshow = 0;
				$arrayofplans[$obj->rowid]=$label.' ('.price($pricetoshow, 1, $langs, 1, 0, -1, $conf->currency);

				if ($tmpprod->duration) $arrayofplans[$obj->rowid].=' / '.($tmpprod->duration == '1m' ? $langs->trans("Month") : '');
				if ($priceinstance['user'])
				{
					$arrayofplans[$obj->rowid].=' + '.price(price2num($priceinstance['user'],'MT'), 1, $langs, 1, 0, -1, $conf->currency).' / '.$langs->trans("User");
					if ($tmpprod->duration) $arrayofplans[$obj->rowid].=' / '.($tmpprod->duration == '1m' ? $langs->trans("Month") : '');
				}
				$arrayofplans[$obj->rowid].=')';
			}
			$i++;
		}
	}
	else dol_print_error($db);


	print '
	<div class="page-content-wrapper">
			<div class="page-content">


 	<!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE HEAD -->
	<div class="page-head">
	  <!-- BEGIN PAGE TITLE -->
	<div class="page-title">
	  <h1>'.$langs->trans("MyInstances").'</h1>
	</div>
	<!-- END PAGE TITLE -->
	</div>
	<!-- END PAGE HEAD -->
	<!-- END PAGE HEADER-->';


	if (count($listofcontractid) == 0)				// Should not happen
	{
		print '<span class="opacitymedium">'.$langs->trans("None").'</span>';
	}
	else
	{
		dol_include_once('sellyoursaas/class/sellyoursaasutils.class.php');
		$sellyoursaasutils = new SellYourSaasUtils($db);

		$arrayforsort = array();
		foreach ($listofcontractid as $id => $contract)
		{
			$position = 20;
			if ($contract->array_options['options_deployment_status'] == 'processing') $position = 1;
			if ($contract->array_options['options_deployment_status'] == 'suspended')  $position = 10;	// This is not a status
			if ($contract->array_options['options_deployment_status'] == 'done')       $position = 20;
			if ($contract->array_options['options_deployment_status'] == 'undeployed') $position = 100;

			$arrayforsort[$id] = array('position'=>$position, 'id'=>$id, 'contract'=>$contract);
		}
		$arrayforsort = dol_sort_array($arrayforsort, 'position');

		foreach ($arrayforsort as $id => $tmparray)
		{
			$id = $tmparray['id'];
			$contract = $tmparray['contract'];

			$planref = $contract->array_options['options_plan'];
			$statuslabel = $contract->array_options['options_deployment_status'];
			$instancename = preg_replace('/\..*$/', '', $contract->ref_customer);

			$dbprefix = $contract->array_options['options_db_prefix'];
			if (empty($dbprefix)) $dbprefix = 'llx_';


			// Get info about PLAN of Contract
			$package = new Packages($db);
			$package->fetch(0, $planref);
			$planlabel = ($package->label?$package->label:$planref);
			$planid = 0;
			$freeperioddays = 0;
			$directaccess = 0;
			foreach($contract->lines as $keyline => $line)
			{
				if ($line->statut == ContratLigne::STATUS_CLOSED && $contract->array_options['options_deployment_status'] != 'undeployed')
				{
					$statuslabel = 'suspended';
				}

				$tmpproduct = new Product($db);
				if ($line->fk_product > 0)
				{
					$tmpproduct->fetch($line->fk_product);
					if ($tmpproduct->array_options['options_app_or_option'] == 'app')
					{
						$planlabel = $tmpproduct->label;		// Warning, label is in language of user
						$planid = $tmpproduct->id;
						$freeperioddays = $tmpproduct->array_options['options_freeperioddays'];
						$directaccess = $tmpproduct->array_options['options_directaccess'];
						break;
					}
				}
			}
			$color = "green"; $displayforinstance = "";
			if ($statuslabel == 'processing') { $color = 'orange'; }
			if ($statuslabel == 'suspended')  { $color = 'orange'; }
			if ($statuslabel == 'undeployed') { $color = 'grey'; $displayforinstance='display:none;'; }



			// Update resources of instance
			if (in_array($statuslabel, array('suspended', 'done')) && ! in_array($initialaction, array('changeplan')))
			{
				$result = $sellyoursaasutils->sellyoursaasRemoteAction('refresh', $contract);
				if ($result <= 0)
				{
					$error++;
					setEventMessages($langs->trans("ErrorRefreshOfResourceFailed", $contract->ref_customer).' : '.$sellyoursaasutils->error, $sellyoursaasutils->errors, 'warnings');
				}
				/*else
				 {
				 setEventMessages($langs->trans("ResourceComputed"), null, 'mesgs');
				 }*/
			}


			print '
				<!-- card for instance -->
			    <div class="row" id="contractid'.$contract->id.'" data-contractref="'.$contract->ref.'">
			      <div class="col-md-12">

					<div class="portlet light">

				      <div class="portlet-title">
				        <div class="caption">';
						  print '<form class="inline-block centpercent" action="'.$_SERVER["PHP_SELF"].'" method="POST">';

				          // Instance name
						  //print '<a href="https://'.$contract->ref_customer.'" class="caption-subject bold uppercase font-green-sharp" title="'.$langs->trans("Contract").' '.$contract->ref.'" target="_blankinstance">';
						  print '<span class="bold uppercase">'.$instancename.'</span>';
						  //print '</a>';
				          print'<span class="caption-helper"> - '.($package->label?$package->label:$planref).'</span>	<!-- This is package, not PLAN -->';

						  // Instance status
				          print '<span class="caption-helper floatright clearboth">';
				          //print $langs->trans("Status").' : ';
				          print '<span class="bold uppercase" style="color:'.$color.'">';
				          if ($statuslabel == 'processing') print $langs->trans("DeploymentInProgress");
				          elseif ($statuslabel == 'done') print $langs->trans("Alive");
				          elseif ($statuslabel == 'suspended') print $langs->trans("Suspended").' '.img_warning();
				          elseif ($statuslabel == 'undeployed') print $langs->trans("Undeployed");
				          else print $statuslabel;
				          print '</span></span><br>';

				          print '<p style="padding-top: 8px;'.($statuslabel == 'undeployed'?' margin-bottom: 0px':'').'" class="clearboth">';

				          // URL
				          if ($statuslabel != 'undeployed')
				          {
				          	print '<span class="caption-helper"><span class="opacitymedium">';
				          	if ($conf->dol_optimize_smallscreen) print $langs->trans("URL");
				          	else print $langs->trans("YourURLToGoOnYourAppInstance");
				          	print ' : </span><a class="font-green-sharp linktoinstance" href="https://'.$contract->ref_customer.'" target="blankinstance">https://'.$contract->ref_customer.'</a>';
				          	print '</span><br>';
				          }

				          print '<!-- <span class="caption-helper"><span class="opacitymedium">'.$langs->trans("ID").' : '.$contract->ref.'</span></span><br> -->';
				          print '<span class="caption-helper">';
								if ($contract->array_options['options_deployment_status'] == 'processing')
								{
									print '<span class="opacitymedium">'.$langs->trans("DateStart").' : </span><span class="bold">'.dol_print_date($contract->array_options['options_deployment_date_start'], 'dayhour').'</span>';
									if (($now - $contract->array_options['options_deployment_date_start']) > 120)	// More than 2 minutes ago
									{
										print ' - <a href="register_instance.php?reusecontractid='.$contract->id.'">'.$langs->trans("Restart").'</a>'; // Link to redeploy / restart deployment
									}
								}
								elseif ($contract->array_options['options_deployment_status'] == 'done')
								{
									print '<span class="opacitymedium">'.$langs->trans("DeploymentDate").' : </span><span class="bold">'.dol_print_date($contract->array_options['options_deployment_date_end'], 'dayhour').'</span>';
								}
								else
								{
									print '<span class="opacitymedium">'.$langs->trans("DeploymentDate").' : </span><span class="bold">'.dol_print_date($contract->array_options['options_deployment_date_end'], 'dayhour').'</span>';
									print '<br>';
									print '<span class="opacitymedium">'.$langs->trans("UndeploymentDate").' : </span><span class="bold">'.dol_print_date($contract->array_options['options_undeployment_date'], 'dayhour').'</span>';
								}
							print '
							</span><br>';

							// Calculate price on invoicing
							$contract->fetchObjectLinked();

							$foundtemplate=0;
							$pricetoshow = ''; $priceinvoicedht = 0;
							$freqlabel = array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year'));
							if (is_array($contract->linkedObjects['facturerec']))
							{
								foreach($contract->linkedObjects['facturerec'] as $idtemplateinvoice => $templateinvoice)
								{
									$foundtemplate++;
									if ($templateinvoice->suspended && $contract->array_options['options_deployment_status'] == 'undeployed') print '';
									elseif ($templateinvoice->suspended && $contract->array_options['options_deployment_status'] != 'deployed') print $langs->trans("InvoicingSuspended");
									else
									{
										if ($templateinvoice->unit_frequency == 'm' && $templateinvoice->frequency == 1)
										{
											$pricetoshow = price($templateinvoice->total_ht, 1, $langs, 0, -1, -1, $conf->currency).' '.$langs->trans("HT").' / '.$langs->trans("Month");
											$priceinvoicedht = $templateinvoice->total_ht;
										}
										elseif ($templateinvoice->unit_frequency == 'y' && $templateinvoice->frequency == 1)
										{
											$pricetoshow = price($templateinvoice->total_ht, 1, $langs, 0, -1, -1, $conf->currency).' '.$langs->trans("HT").' / '.$langs->trans("Year");
											$priceinvoicedht = $templateinvoice->total_ht;
										}
										else
										{
											$pricetoshow  = $templateinvoice->frequency.' '.$freqlabel[$templateinvoice->unit_frequency];
											$pricetoshow .= ', ';
											$pricetoshow .= price($templateinvoice->total_ht, 1, $langs, 0, -1, -1, $conf->currency).' '.$langs->trans("HT");
											$priceinvoicedht = $templateinvoice->total_ht;
										}
									}
								}
							}

							print '
				          </p>';
						print '</form>';
						print '</div>';
				     print '</div>';

				     print '
				      <div class="portlet-body" style="'.$displayforinstance.'">

				        <div class="tabbable-custom nav-justified">
				          <ul class="nav nav-tabs nav-justified">
				            <li><a id="a_tab_resource_'.$contract->id.'" href="#tab_resource_'.$contract->id.'" data-toggle="tab"'.(! in_array($action, array('updateurlxxx')) ? ' class="active"' : '').'>'.$langs->trans("ResourcesAndOptions").'</a></li>';
				            print '<li><a id="a_tab_domain_'.$contract->id.'" href="#tab_domain_'.$contract->id.'" data-toggle="tab"'.($action == 'updateurlxxx' ? ' class="active"' : '').'>'.$langs->trans("Domain").'</a></li>';
				 		    if (in_array($statuslabel, array('done','suspended')) && $directaccess) print '<li><a id="a_tab_ssh_'.$contract->id.'" href="#tab_ssh_'.$contract->id.'" data-toggle="tab">'.$langs->trans("SSH").' / '.$langs->trans("SFTP").'</a></li>';
				 		    if (in_array($statuslabel, array('done','suspended'))  && $directaccess) print '<li><a id="a_tab_db_'.$contract->id.'" href="#tab_db_'.$contract->id.'" data-toggle="tab">'.$langs->trans("Database").'</a></li>';
				 		    if (in_array($statuslabel, array('done','suspended')) ) print '<li><a id="a_tab_danger_'.$contract->id.'" href="#tab_danger_'.$contract->id.'" data-toggle="tab">'.$langs->trans("DangerZone").'</a></li>';
				     	print '
				          </ul>

				          <div class="tab-content">

				            <div class="tab-pane active" id="tab_resource_'.$contract->id.'">
								<!-- <p class="opacitymedium" style="padding: 15px; margin-bottom: 5px;">'.$langs->trans("YourResourceAndOptionsDesc").' :</p> -->
					            <div style="padding-left: 12px; padding-bottom: 12px; padding-right: 12px">';

				     			$arrayoflines = $contract->lines;
				     			//var_dump($arrayoflines);
								// Loop on each service / option
				     			foreach($arrayoflines as $keyline => $line)
								{
									//var_dump($line);
									print '<div class="resource inline-block boxresource">';

				                  	$resourceformula='';
				                  	$tmpproduct = new Product($db);
				                  	if ($line->fk_product > 0)
				                  	{
					                  	$tmpproduct->fetch($line->fk_product);

					                  	$maxHeight=40;
					                  	$maxWidth=40;
					                  	$alt='';
					                  	$htmlforphoto = $tmpproduct->show_photos('product', $conf->product->dir_output, 1, 1, 1, 0, 0, $maxHeight, $maxWidth, 1, 1, 1);

										if (empty($htmlforphoto) || $htmlforphoto == '<!-- Photo -->' || $htmlforphoto == '<!-- Photo -->'."\n")
					                  	{
					                  		print '<!--no photo defined -->';
					                  		print '<table width="100%" valign="top" align="center" border="0" cellpadding="2" cellspacing="2"><tr><td width="100%" class="photo">';
					                  		print '<img class="photo photowithmargin" border="0" height="'.$maxHeight.'" src="'.DOL_URL_ROOT.'/public/theme/common/nophoto.png" title="'.dol_escape_htmltag($alt).'">';
					                  		print '</td></tr></table>';
					                  	}
					                  	else
					                  	{
					                  		print $htmlforphoto;
					                  	}

					                  	//var_dump($tmpproduct->array_options);
					                  	/*if ($tmpproduct->array_options['options_app_or_option'] == 'app')
					                  	{
					                  		print '<span class="opacitymedium small">'.'&nbsp;'.'</span><br>';
					                  	}
					                  	if ($tmpproduct->array_options['options_app_or_option'] == 'system')
					                  	{
					                  		print '<span class="opacitymedium small">'.'&nbsp;'.'</span><br>';
					                  	}
					                  	if ($tmpproduct->array_options['options_app_or_option'] == 'option')
					                  	{
					                  		print '<span class="opacitymedium small">'.$langs->trans("Option").'</span><br>';
					                  	}*/


					                  	// Label
					                  	$labelprod = $tmpproduct->label;
					                  	if (preg_match('/instance/i', $tmpproduct->ref) || preg_match('/instance/i', $tmpproduct->label))
					                  	{
					                  		$labelprod = $langs->trans("Application");
					                  	}
					                  	elseif (preg_match('/user/i', $tmpproduct->ref) || preg_match('/user/i', $tmpproduct->label))
					                  	{
					                  		$labelprod = $langs->trans("Users");
					                  	}

					                  	print '<span class="opacitymedium small">'.$labelprod.'</span><br>';

					                  	// Qty
					                  	$resourceformula = $tmpproduct->array_options['options_resource_formula'];
					                  	if (preg_match('/SQL:/', $resourceformula))
					                  	{
					                  		$resourceformula = preg_match('/__d__/', $dbprefix, $resourceformula);
					                  	}
					                  	if (preg_match('/DISK:/', $resourceformula))
					                  	{
					                  		$resourceformula = $resourceformula;
					                  	}

										print '<span class="font-green-sharp counternumber">'.$line->qty.'</span>';
										print '<br>';
										if ($line->price)
										{
											print '<span class="opacitymedium small">'.price($line->price, 1, $langs, 0, -1, -1, $conf->currency);
											//if ($line->qty > 1 && $labelprodsing) print ' / '.$labelprodsing;
											if ($tmpproduct->array_options['options_resource_label']) print ' / '.$tmpproduct->array_options['options_resource_label'];
											elseif (preg_match('/users/i', $tmpproduct->ref)) print ' / '.$langs->trans("User");	// backward compatibility
											// TODO
											print ' / '.$langs->trans("Month");
											print '</span>';
										}
										else
										{
											print '<span class="opacitymedium small">'.price($line->price, 1, $langs, 0, -1, -1, $conf->currency);
											// TODO
											print ' / '.$langs->trans("Month");
											print '</span>';
										}
				                  	}
				                  	else	// If there is no product, this is users
				                  	{
				                  		print '<span class="opacitymedium small">';
				                  		print ($line->label ? $line->label : $line->libelle);
				                  		// TODO
				                  		print ' / '.$langs->trans("Month");
				                  		print '</span>';
				                  	}

									print '</div>';
								}

								// Add new option

								print '<div class="resource inline-block boxresource opacitymedium small">';
								print '<br><br><br>';
								print $langs->trans("SoonMoreOptionsHere");
								print '</div>';



								print '<br><br>';

								// Show the current Plan (with link to change it)
								print '<span class="caption-helper"><span class="opacitymedium">'.$langs->trans("YourSubscriptionPlan").' : </span>';
								if ($action == 'changeplan' && $planid > 0 && $id == GETPOST('id','int'))
								{
									print '<input type="hidden" name="mode" value="instances"/>';
									print '<input type="hidden" name="action" value="updateplan" />';
									print '<input type="hidden" name="contractid" value="'.$contract->id.'" />';

									// List of available plans/products
									$arrayofplanstoswitch=array();
									$sqlproducts = 'SELECT p.rowid, p.ref, p.label FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_extrafields as pe';
									$sqlproducts.= ' WHERE p.tosell = 1 AND p.entity = '.$conf->entity;
									$sqlproducts.= " AND pe.fk_object = p.rowid AND pe.app_or_option = 'app'";
									$sqlproducts.= " AND p.ref NOT LIKE '%DolibarrV1%'";
									$sqlproducts.= " AND (p.rowid = ".$planid." OR 1 = 1)";		// TODO Restict on plans compatible with current plan...
									$resqlproducts = $db->query($sqlproducts);
									if ($resqlproducts)
									{
										$num = $db->num_rows($resqlproducts);
										$i=0;
										while($i < $num)
										{
											$obj = $db->fetch_object($resqlproducts);
											if ($obj)
											{
												$arrayofplanstoswitch[$obj->rowid]=$obj->label;
											}
											$i++;
										}
									}
									print $form->selectarray('planid', $arrayofplanstoswitch, $planid, 0, 0, 0, '', 0, 0, 0, '', 'minwidth300');
									print '<input type="submit" class="btn btn-warning default change-plan-link" name="changeplan" value="'.$langs->trans("ChangePlan").'">';
								}
								else
								{
									print '<span class="bold">'.$planlabel.'</span>';
									if ($statuslabel != 'undeployed')
									{
										if ($foundtemplate == 0 || $priceinvoicedht == $contract->total_ht)
										{
											print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=instances&action=changeplan&id='.$contract->id.'#contractid'.$contract->id.'">'.$langs->trans("ChangePlan").'</a>';
										}
									}
								}
								print '</span>';
								print '<br>';

								// Billing
								if ($statuslabel != 'undeployed')
								{
									print '<span class="caption-helper spanbilling"><span class="opacitymedium">'.$langs->trans("Billing").' : </span>';
									if ($foundtemplate > 1) print '<span style="color:orange">'.$langs->trans("WarningFoundMoreThanOneInvoicingTemplate", $conf->global->SELLYOURSAAS_MAIN_EMAIL).'</span>';
									else
									{
										if ($foundtemplate != 0 && $priceinvoicedht != $contract->total_ht)
										{
											print $langs->trans("FlatOrDiscountedPrice").' = ';
										}
										print '<span class="bold">'.$pricetoshow.'</span>';
										if ($foundtemplate == 0)	// Same than ispaid
										{
											if ($contract->array_options['options_date_endfreeperiod'] < $now) $color='orange';

											print ' <span style="color:'.$color.'">';
											if ($contract->array_options['options_date_endfreeperiod'] > 0) print $langs->trans("TrialUntil", dol_print_date($contract->array_options['options_date_endfreeperiod'], 'day'));
											else print $langs->trans("Trial");
											print '</span>';
											if ($contract->array_options['options_date_endfreeperiod'] < $now)
											{
												if ($statuslabel == 'suspended') print ' - <span style="color: orange">'.$langs->trans("Suspended").'</span>';
												//else print ' - <span style="color: orange">'.$langs->trans("SuspendWillBeDoneSoon").'</span>';
											}
											if ($statuslabel == 'suspended')
											{
												if (empty($atleastonepaymentmode))
												{
													print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'">'.$langs->trans("AddAPaymentModeToRestoreInstance").'</a>';
												}
												else
												{
													print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'">'.$langs->trans("FixPaymentModeToRestoreInstance").'</a>';
												}
											}
											else
											{
												if (empty($atleastonepaymentmode))
												{
													print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'">'.$langs->trans("AddAPaymentMode").'</a>';
												}
												else
												{
													// If at least one payment mode already recorded
													if (sellyoursaasIsPaymentKo($contract))
													{
														print ' - '.$langs->trans("ActivePaymentError");
													}
													else
													{
														print ' - '.$langs->trans("APaymentModeWasRecorded");
													}
												}
											}
										}
									}
									print '</span>';
								}

				            	print '
								  </div>
				              </div>

							<!-- tab domain -->
				            <div class="tab-pane" id="tab_domain_'.$contract->id.'">
				                <div class="opacitymedium" style="padding: 15px">'.$langs->trans("TheURLDomainOfYourInstance").' :</div>
								<form class="form-group" action="'.$_SERVER["PHP_SELF"].'" method="POST">
								<div class="col-md-9">
									<input type="text" class="urlofinstance" disabled="disabled" value="'.$contract->ref_customer.'">
									<input type="hidden" name="mode" value="instances"/>
									<input type="hidden" name="action" value="updateurl" />
									<input type="hidden" name="contractid" value="'.$contract->id.'" />
									<input type="hidden" name="tab" value="domain_'.$contract->id.'" />
								';
								//print '<input type="submit" class="btn btn-warning default change-domain-link" name="changedomain" value="'.$langs->trans("ChangeDomain").'">';
								print '
								</div>
							  	</form>
				            </div>

				            <div class="tab-pane" id="tab_ssh_'.$contract->id.'">
				                <p class="opacitymedium" style="padding: 15px">'.$langs->trans("SSHFTPDesc").' :</p>
				                <form class="form-horizontal" role="form">
				                <div class="form-body">
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("Hostname").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_hostname_os'].'">
				                    </div>
				                    <label class="col-md-3 control-label">'.$langs->trans("Port").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.($contract->array_options['options_port_os']?$contract->array_options['options_port_os']:22).'">
				                    </div>
				                  </div>
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("SFTP Username").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_username_os'].'">
				                    </div>
				                    <label class="col-md-3 control-label">'.$langs->trans("Password").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_password_os'].'">
				                    </div>
				                  </div>
				                </div>
				                </form>
				              </div> <!-- END TAB PANE -->

				              <div class="tab-pane" id="tab_db_'.$contract->id.'">
				                <p class="opacitymedium" style="padding: 15px">'.$langs->trans("DBDesc").' :</p>
				                <form class="form-horizontal" role="form">
				                <div class="form-body">
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("Hostname").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_hostname_db'].'">
				                    </div>
				                    <label class="col-md-3 control-label">'.$langs->trans("Port").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_port_db'].'">
				                    </div>
				                  </div>
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("DatabaseName").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_database_db'].'">
				                    </div>
				                    <label class="col-md-3 control-label">'.$langs->trans("DatabaseLogin").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_username_db'].'">
				                    </div>
				                  </div>
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("Password").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_password_db'].'">
				                    </div>
				                  </div>
				                </div>
				                </form>
				              </div> <!-- END TAB PANE -->

				            <div class="tab-pane" id="tab_danger_'.$contract->id.'">
							<form class="form-group" action="'.$_SERVER["PHP_SELF"].'" method="POST">
				              <div class="">
								';
								$hasopeninvoices = sellyoursaasHasOpenInvoices($contract);
								if ($hasopeninvoices)
								{
									print '<span class="opacitymedium">'.$langs->trans("CantCloseBecauseOfOpenInvoices").'</span><br><br>';
								}
								else
								{
									print '
					                <p class="opacitymedium" style="padding: 15px">
					                    '.$langs->trans("PleaseBeSure", $contract->ref_customer).'
					                </p>
									<p class="center" style="padding-bottom: 15px">
										<input type="text" class="center urlofinstancetodestroy" name="urlofinstancetodestroy" value="'.GETPOST('urlofinstancetodestroy','alpha').'" placeholder="">
									</p>';
								}
								print '
								<p class="center">
									<input type="hidden" name="mode" value="instances"/>
									<input type="hidden" name="action" value="undeploy" />
									<input type="hidden" name="contractid" value="'.$contract->id.'" />
									<input type="hidden" name="tab" value="danger_'.$contract->id.'" />
									<input type="submit" '.($hasopeninvoices?' disabled="disabled"':'').' class="btn btn-danger'.($hasopeninvoices?' disabled':'').'" name="undeploy" value="'.$langs->trans("UndeployInstance").'">
								</p>
				              </div>
							</form>
				            </div> <!-- END TAB PANE -->

				          </div> <!-- END TAB CONTENT -->
				        </div> <!-- END TABABLE CUSTOM-->

				      </div><!-- END PORTLET-BODY -->


					</div> <!-- END PORTLET -->

			      </div> <!-- END COL -->

			    </div> <!-- END ROW -->';

		}		// End loop contract

	}


	// Link to add new instance
	print '
	<!-- Add a new instance -->
	<div class="portlet-body" style=""><br>
	';

	print '<a href="#addanotherinstance" id="addanotherinstance" class="valignmiddle">';
	print '<span class="fa fa-plus-circle valignmiddle" style="font-size: 1.5em; padding-right: 4px;"></span><span class="valignmiddle">'.$langs->trans("AddAnotherInstance").'...</span><br>';
	print '</a>';

	print '<script type="text/javascript" language="javascript">
	jQuery(document).ready(function() {
		jQuery("#addanotherinstance").click(function() {
			console.log("Click on addanotherinstance");
			jQuery("#formaddanotherinstance").toggle();
		});
	});
		</script>';

	print '<br>';

	print '<form id="formaddanotherinstance" class="form-group reposition" style="display: none;" action="register_instance.php" method="POST">';
	print '<input type="hidden" name="action" value="deployall" />';
	print '<input type="hidden" name="fromsocid" value="0" />';
	print '<input type="hidden" name="reusesocid" value="'.$socid.'" />';

	print '<div class="row">
	<div class="col-md-12">

	<div class="portlet light">';

	//var_dump($arrayofplans);
	natcasesort($arrayofplans);

	$MAXINSTANCES = 4;
	if (count($listofcontractid) < $MAXINSTANCES)
	{
		print '<div class="group">';

		print '<div class="horizontal-fld centpercent marginbottomonly">';
		print '<strong>'.$langs->trans("YourSubscriptionPlan").'</strong> ';
		print $form->selectarray('service', $arrayofplans, $planid, 0, 0, 0, '', 0, 0, 0, '', 'minwidth500');
		print '<br>';
		print '</div>';
		//print ajax_combobox('service');

		print '

			<div class="horizontal-fld clearboth margintoponly">
			<div class="control-group required">
			<label class="control-label" for="password" trans="1">'.$langs->trans("Password").'</label><input name="password" type="password" required />
			</div>
			</div>
			<div class="horizontal-fld margintoponly">
			<div class="control-group required">
			<label class="control-label" for="password2" trans="1">'.$langs->trans("ConfirmPassword").'</label><input name="password2" type="password" required />
			</div>
			</div>
			</div> <!-- end group -->

			<section id="selectDomain margintoponly" style="margin-top: 20px;">
			<div class="fld select-domain required">
			<label trans="1">'.$langs->trans("ChooseANameForYourApplication").'</label>
			<div class="linked-flds">
			<span class="opacitymedium">https://</span>
			<input class="sldAndSubdomain" type="text" name="sldAndSubdomain" value="" maxlength="29" required />
			<select name="tldid" id="tldid" >';
				$listofdomain = explode(',', $conf->global->SELLYOURSAAS_SUB_DOMAIN_NAMES);
				foreach($listofdomain as $val)
				{
					$newval=$val;
					if (! preg_match('/^\./', $newval)) $newval='.'.$newval;
					print '<option value="'.$newval.'">'.$newval.'</option>';
				}
			print '</select>
			<br class="unfloat" />
			</div>
			</div>
			</section>';

		print '<br><input type="submit" class="btn btn-warning default change-plan-link" name="changeplan" value="'.$langs->trans("Create").'">';
	}
	else
	{
		// Max number of instances reached
		print '<!-- Max number of instances reached -->';
		print '<div class="warning">'.$langs->trans("MaxNumberOfInstanceReached", $MAXINSTANCES, $conf->global->SELLYOURSAAS_MAIN_EMAIL).'</div>';
	}

	print '</div></div></div>';

	print '</form>';


	print '
	    </div>
		</div>
	';

	if (GETPOST('tab','alpha'))
	{
		print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			console.log("Click on '.GETPOST('tab','alpha').'");
			jQuery("#a_tab_'.GETPOST('tab','alpha').'").click();
		});
		</script>';
	}
}


if ($mode == 'mycustomerinstances')
{
	print '
	<div class="page-content-wrapper">
			<div class="page-content">


 	<!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE HEAD -->
	<div class="page-head">
	  <!-- BEGIN PAGE TITLE -->
	<div class="page-title">
	  <h1>'.$langs->trans("MyCustomersInstances").'</h1>
	</div>
	<!-- END PAGE TITLE -->
	</div>
	<!-- END PAGE HEAD -->
	<!-- END PAGE HEADER-->';


	//print $langs->trans("Filters").' : ';
	print '<div class="row"><div class="col-md-12"><div class="portlet light">';
	print '<form name="refresh" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print $langs->trans("InstanceName").' : <input type="text" name="search_instance_name" value="'.$search_instance_name.'"><br>';
	//$savsocid = $user->socid;	// Save socid of user
	//$user->socid = 0;
	print $langs->trans("Customer").'/'.$langs->trans("Email").' : <input type="text" name="search_customer_name" value="'.$search_customer_name.'"><br>';
	//.$form->select_company(GETPOST('search_customer_name', 'search_customer_name'), 'search_customer_name', 'parent = '.$mythirdpartyaccount->id, '1', 0, 1, array(), 0, 'inline-block').'</div><br>';
	//$user->socid = $savsocid;	// Restore socid of user

	print '<input type="hidden" name="mode" value="'.$mode.'">';
	print '<div style="padding-top: 10px; padding-bottom: 10px">';
	print '<input type="submit" name="submit" value="'.$langs->trans("Refresh").'">';
	print ' &nbsp; ';
	print '<input type="submit" name="reset" value="'.$langs->trans("Reset").'"><br>';
	print '</div>';

	if (count($listofcontractidreseller) > 0)
	{
		print $langs->trans("FirstRecord").' <input type="text" name="firstrecord" class="maxwidth50 right" value="'.$firstrecord.'"> - '.$langs->trans("LastRecord");
		print ' <input type="text" name="lastrecord" class="maxwidth50" value="'.$lastrecord.'"> / ';
		print '<span style="font-size: 14px;">'. count($listofcontractidreseller) .'</span><br>';
	}

	print '</form>';
	print '</div></div></div>';

	print '<br>';

	if (count($listofcontractidreseller) == 0)				// Should not happen
	{
		print '<span class="opacitymedium">'.$langs->trans("NoneF").'</span>';
	}
	else
	{
		dol_include_once('sellyoursaas/class/sellyoursaasutils.class.php');
		$sellyoursaasutils = new SellYourSaasUtils($db);

		$arrayforsort = array();
		foreach ($listofcontractidreseller as $id => $contract)
		{
			$position = 20;
			if ($contract->array_options['options_deployment_status'] == 'processing') $position = 1;
			if ($contract->array_options['options_deployment_status'] == 'suspended')  $position = 10;	// This is not a status
			if ($contract->array_options['options_deployment_status'] == 'done')       $position = 20;
			if ($contract->array_options['options_deployment_status'] == 'undeployed') $position = 100;

			$arrayforsort[$id] = array('position'=>$position, 'id'=>$id, 'contract'=>$contract);
		}
		$arrayforsort = dol_sort_array($arrayforsort, 'position');

		$i=0;
		foreach ($arrayforsort as $id => $tmparray)
		{
			$i++;

			if ($i < $firstrecord) continue;
			if ($i > $lastrecord) break;

			$id = $tmparray['id'];
			$contract = $tmparray['contract'];

			$planref = $contract->array_options['options_plan'];
			$statuslabel = $contract->array_options['options_deployment_status'];
			$instancename = preg_replace('/\..*$/', '', $contract->ref_customer);

			$dbprefix = $contract->array_options['options_db_prefix'];
			if (empty($dbprefix)) $dbprefix = 'llx_';


			// Get info about PLAN of Contract
			$package = new Packages($db);
			$package->fetch(0, $planref);
			$planlabel = ($package->label?$package->label:$planref);
			$planid = 0;
			$freeperioddays = 0;
			$directaccess = 0;
			foreach($contract->lines as $keyline => $line)
			{
				if ($line->statut == 5 && $contract->array_options['options_deployment_status'] != 'undeployed')
				{
					$statuslabel = 'suspended';
				}

				$tmpproduct = new Product($db);
				if ($line->fk_product > 0)
				{
					$tmpproduct->fetch($line->fk_product);
					if ($tmpproduct->array_options['options_app_or_option'] == 'app')
					{
						$planlabel = $tmpproduct->label;		// Warning, label is in language of user
						$planid = $tmpproduct->id;
						$freeperioddays = $tmpproduct->array_options['options_freeperioddays'];
						$directaccess = $tmpproduct->array_options['options_directaccess'];
						break;
					}
				}
			}
			$color = "green"; $displayforinstance = "";
			if ($statuslabel == 'processing') { $color = 'orange'; }
			if ($statuslabel == 'suspended')  { $color = 'orange'; }
			if ($statuslabel == 'undeployed') { $color = 'grey'; $displayforinstance='display:none;'; }



			// Update resources of instance
			/*
			if (in_array($statuslabel, array('suspended', 'done')))
			{
				$result = $sellyoursaasutils->sellyoursaasRemoteAction('refresh', $contract);
				if ($result <= 0)
				{
					$error++;
					setEventMessages($langs->trans("ErrorRefreshOfResourceFailed", $contract->ref_customer).' : '.$sellyoursaasutils->error, $sellyoursaasutils->errors, 'warnings');
				}
			}*/

			print '
			    <div class="row" id="contractid'.$contract->id.'" data-contractref="'.$contract->ref.'">
			      <div class="col-md-12">

					<div class="portlet light">

				      <div class="portlet-title">
				        <div class="caption">';
			print '<form class="inline-block centpercent" action="'.$_SERVER["PHP_SELF"].'" method="POST">';

			// Customer
			$tmpcustomer = new Societe($db);
			$tmpcustomer->fetch($contract->socid);

			// Instance name
			print '<a href="https://'.$contract->ref_customer.'" class="caption-subject bold uppercase font-green-sharp" title="'.$langs->trans("Contract").' '.$contract->ref.'">'.$instancename.'</a>
				          <span class="caption-helper"> - '.($package->label?$package->label:$planref).'</span>	<!-- This is package, not PLAN -->';

			// Instance status
			print '<span class="caption-helper floatright clearboth">';
			//print $langs->trans("Status").' : ';
			print '<span class="bold uppercase" style="color:'.$color.'">';
			if ($statuslabel == 'processing') print $langs->trans("DeploymentInProgress");
			elseif ($statuslabel == 'done') print $langs->trans("Alive");
			elseif ($statuslabel == 'suspended') print $langs->trans("Suspended");
			elseif ($statuslabel == 'undeployed') print $langs->trans("Undeployed");
			else print $statuslabel;
			print '</span></span><br>';

			print '<p style="padding-top: 8px;" class="clearboth">';

			// Customer (link to login on customer dashboard)
			print '<span class="opacitymedium">'.$langs->trans("Customer").' : </span>'.$tmpcustomer->name;
			$dol_login_hash=dol_hash($conf->global->SELLYOURSAAS_KEYFORHASH.$tmpcustomer->email.dol_print_date(dol_now(),'dayrfc'), 5);	// hash is valid one hour
			print ' &nbsp;-&nbsp;  <a target="_blankcustomer" href="'.$_SERVER["PHP_SELF"].'?mode=logout_dashboard&username='.$tmpcustomer->email.'&password=&login_hash='.$dol_login_hash.'"><span class="fa fa-desktop"></span> '.$langs->trans("LoginWithCustomerAccount").'</a>';
			print '<br>';

			// URL
			if ($statuslabel != 'undeployed')
			{
				print '<span class="caption-helper"><span class="opacitymedium">';
				if ($conf->dol_optimize_smallscreen) print $langs->trans("URL");
				else print $langs->trans("YourURLToGoOnYourAppInstance");
				print ' : </span><a class="font-green-sharp linktoinstance" href="https://'.$contract->ref_customer.'" target="blankinstance">'.$contract->ref_customer.'</a>';
				print '</span><br>';
			}

			print '<!-- <span class="caption-helper"><span class="opacitymedium">'.$langs->trans("ID").' : '.$contract->ref.'</span></span><br> -->';
			print '<span class="caption-helper">';
			if ($contract->array_options['options_deployment_status'] == 'processing')
			{
				print '<span class="opacitymedium">'.$langs->trans("DateStart").' : </span><span class="bold">'.dol_print_date($contract->array_options['options_deployment_date_start'], 'dayhour').'</span>';
				if (($now - $contract->array_options['options_deployment_date_start']) > 120)	// More then 2 minutes ago
				{
					print ' - <a href="register_instance.php?reusecontractid='.$contract->id.'">'.$langs->trans("Restart").'</a>';
				}
			}
			elseif ($contract->array_options['options_deployment_status'] == 'done')
			{
				print '<span class="opacitymedium">'.$langs->trans("DeploymentDate").' : </span><span class="bold">'.dol_print_date($contract->array_options['options_deployment_date_end'], 'dayhour').'</span>';
			}
			else
			{
				print '<span class="opacitymedium">'.$langs->trans("DeploymentDate").' : </span><span class="bold">'.dol_print_date($contract->array_options['options_deployment_date_end'], 'dayhour').'</span>';
				print '<br>';
				print '<span class="opacitymedium">'.$langs->trans("UndeploymentDate").' : </span><span class="bold">'.dol_print_date($contract->array_options['options_undeployment_date'], 'dayhour').'</span>';
			}
			print '
							</span><br>';

			// Calculate price on invoicing
			$contract->fetchObjectLinked();

			$foundtemplate=0;
			$pricetoshow = ''; $priceinvoicedht = 0;
			$freqlabel = array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year'));
			if (is_array($contract->linkedObjects['facturerec']))
			{
				foreach($contract->linkedObjects['facturerec'] as $idtemplateinvoice => $templateinvoice)
				{
					$foundtemplate++;
					if ($templateinvoice->suspended && $contract->array_options['options_deployment_status'] == 'undeployed') print '';
					elseif ($templateinvoice->suspended && $contract->array_options['options_deployment_status'] != 'deployed') print $langs->trans("InvoicingSuspended");
					else
					{
						if ($templateinvoice->unit_frequency == 'm' && $templateinvoice->frequency == 1)
						{
							$pricetoshow = price($templateinvoice->total_ht, 1, $langs, 0, -1, -1, $conf->currency).' '.$langs->trans("HT").' / '.$langs->trans("Month");
							$priceinvoicedht = $templateinvoice->total_ht;
						}
						elseif ($templateinvoice->unit_frequency == 'y' && $templateinvoice->frequency == 1)
						{
							$pricetoshow = price($templateinvoice->total_ht, 1, $langs, 0, -1, -1, $conf->currency).' '.$langs->trans("HT").' / '.$langs->trans("Year");
							$priceinvoicedht = $templateinvoice->total_ht;
						}
						else
						{
							$pricetoshow  = $templateinvoice->frequency.' '.$freqlabel[$templateinvoice->unit_frequency];
							$pricetoshow .= ', ';
							$pricetoshow .= price($templateinvoice->total_ht, 1, $langs, 0, -1, -1, $conf->currency).' '.$langs->trans("HT");
							$priceinvoicedht = $templateinvoice->total_ht;
						}
					}
				}
			}

			print '
				          </p>';
			print '</form>';
			print '</div>';
			print '</div>';

			print '
				      <div class="portlet-body" style="'.$displayforinstance.'">

				        <div class="tabbable-custom nav-justified">
				          <ul class="nav nav-tabs nav-justified">
				            <li><a id="a_tab_resource_'.$contract->id.'" href="#tab_resource_'.$contract->id.'" data-toggle="tab"'.(! in_array($action, array('updateurlxxx')) ? ' class="active"' : '').'>'.$langs->trans("ResourcesAndOptions").'</a></li>';
				            //print '<li><a id="a_tab_domain_'.$contract->id.'" href="#tab_domain_'.$contract->id.'" data-toggle="tab"'.($action == 'updateurlxxx' ? ' class="active"' : '').'>'.$langs->trans("Domain").'</a></li>';
			if (in_array($statuslabel, array('done','suspended')) && $directaccess) print '<li><a id="a_tab_ssh_'.$contract->id.'" href="#tab_ssh_'.$contract->id.'" data-toggle="tab">'.$langs->trans("SSH").' / '.$langs->trans("SFTP").'</a></li>';
			if (in_array($statuslabel, array('done','suspended'))  && $directaccess) print '<li><a id="a_tab_db_'.$contract->id.'" href="#tab_db_'.$contract->id.'" data-toggle="tab">'.$langs->trans("Database").'</a></li>';
			//if (in_array($statuslabel, array('done','suspended')) ) print '<li><a id="a_tab_danger_'.$contract->id.'" href="#tab_danger_'.$contract->id.'" data-toggle="tab">'.$langs->trans("DangerZone").'</a></li>';
			print '
				          </ul>

				          <div class="tab-content">

				            <div class="tab-pane active" id="tab_resource_'.$contract->id.'">
								<!-- <p class="opacitymedium" style="padding: 15px; margin-bottom: 5px;">'.$langs->trans("YourCustomersResourceAndOptionsDesc").' :</p> -->
					            <div style="padding-left: 12px; padding-bottom: 12px; padding-right: 12px">';
			foreach($contract->lines as $keyline => $line)
			{
				//var_dump($line);
				print '<div class="resource inline-block boxresource">';
				print '<div class="">';

				$resourceformula='';
				$tmpproduct = new Product($db);
				if ($line->fk_product > 0)
				{
					$tmpproduct->fetch($line->fk_product);

					$htmlforphoto = $tmpproduct->show_photos('product', $conf->product->dir_output, 1, 1, 1, 0, 0, 40, 40, 1, 1, 1);
					print $htmlforphoto;

					//var_dump($tmpproduct->array_options);
					/*if ($tmpproduct->array_options['options_app_or_option'] == 'app')
					 {
					 print '<span class="opacitymedium small">'.'&nbsp;'.'</span><br>';
					 }
					 if ($tmpproduct->array_options['options_app_or_option'] == 'system')
					 {
					 print '<span class="opacitymedium small">'.'&nbsp;'.'</span><br>';
					 }
					 if ($tmpproduct->array_options['options_app_or_option'] == 'option')
					 {
					 print '<span class="opacitymedium small">'.$langs->trans("Option").'</span><br>';
					 }*/

					$labelprod = $tmpproduct->label;
					if (preg_match('/instance/i', $tmpproduct->ref) || preg_match('/instance/i', $tmpproduct->label))
					{
						$labelprod = $langs->trans("Application");
					}
					elseif (preg_match('/user/i', $tmpproduct->ref) || preg_match('/user/i', $tmpproduct->label))
					{
						$labelprod = $langs->trans("Users");
					}
					// Label
					print '<span class="opacitymedium small">'.$labelprod.'</span><br>';
					// Qty
					$resourceformula = $tmpproduct->array_options['options_resource_formula'];
					if (preg_match('/SQL:/', $resourceformula))
					{
						$resourceformula = preg_match('/__d__/', $dbprefix, $resourceformula);
					}
					if (preg_match('/DISK:/', $resourceformula))
					{
						$resourceformula = $resourceformula;
					}

					print '<span class="font-green-sharp counternumber">'.$line->qty.'</span>';
					print '<br>';
					if ($line->price)
					{
						print '<span class="opacitymedium small">'.price($line->price, 1, $langs, 0, -1, -1, $conf->currency);
						if ($tmpproduct->array_options['options_resource_label']) print ' / '.$tmpproduct->array_options['options_resource_label'];
						elseif (preg_match('/users/i', $tmpproduct->ref)) print ' / '.$langs->trans("User");	// backward compatibility
						// TODO
						print ' / '.$langs->trans("Month");
						print '</span>';
					}
					else
					{
						print '<span class="opacitymedium small">'.price($line->price, 1, $langs, 0, -1, -1, $conf->currency);
						// TODO
						print ' / '.$langs->trans("Month");
						print '</span>';
					}
				}
				else	// If there is no product, this is users
				{
					print '<span class="opacitymedium small">';
					print ($line->label ? $line->label : $line->libelle);
					// TODO
					print ' / '.$langs->trans("Month");
					print '</span>';
				}

				print '</div>';
				print '</div>';
			}

			print '<br><br>';

			// Plan
			print '<span class="caption-helper"><span class="opacitymedium">'.$langs->trans("YourSubscriptionPlan").' : </span>';
			if ($action == 'changeplan' && $planid > 0 && $id == GETPOST('id','int'))
			{
				print '<input type="hidden" name="mode" value="instances"/>';
				print '<input type="hidden" name="action" value="updateplan" />';
				print '<input type="hidden" name="contractid" value="'.$contract->id.'" />';

				// List of available plans
				$arrayofplanstoswitch=array();
				$sqlproducts = 'SELECT p.rowid, p.ref, p.label FROM '.MAIN_DB_PREFIX.'product as p, '.MAIN_DB_PREFIX.'product_extrafields as pe';
				$sqlproducts.= ' WHERE p.tosell = 1 AND p.entity = '.$conf->entity;
				$sqlproducts.= " AND pe.fk_object = p.rowid AND pe.app_or_option = 'app'";
				$sqlproducts.= " AND (p.rowid = ".$planid." OR 1 = 1)";		// TODO Restict on plans compatible with current plan...
				$resqlproducts = $db->query($sqlproducts);
				if ($resqlproducts)
				{
					$num = $db->num_rows($resqlproducts);
					$i=0;
					while($i < $num)
					{
						$obj = $db->fetch_object($resqlproducts);
						if ($obj)
						{
							$arrayofplanstoswitch[$obj->rowid]=$obj->label;
						}
						$i++;
					}
				}
				print $form->selectarray('planid', $arrayofplanstoswitch, $planid, 0, 0, 0, '', 0, 0, 0, '', 'minwidth300');
				print '<input type="submit" class="btn btn-warning default change-plan-link" name="changeplan" value="'.$langs->trans("ChangePlan").'">';
			}
			else
			{
				print '<span class="bold">'.$planlabel.'</span>';
				if ($statuslabel != 'undeployed')
				{
					if ($priceinvoicedht == $contrat->total_ht)
					{
						// Disabled on "My customer invoices" view
						//print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=mycustomerinstances&action=changeplan&id='.$contract->id.'#contractid'.$contract->id.'">'.$langs->trans("ChangePlan").'</a>';
					}
				}
			}
			print '</span>';
			print '<br>';

			// Billing
			if ($statuslabel != 'undeployed')
			{
				print '<span class="caption-helper spanbilling"><span class="opacitymedium">'.$langs->trans("Billing").' : </span>';
				if ($foundtemplate > 1) print '<span style="color:orange">'.$langs->trans("WarningFoundMoreThanOneInvoicingTemplate", $conf->global->SELLYOURSAAS_MAIN_EMAIL).'</span>';
				else
				{
					if ($priceinvoicedht != $contrat->total_ht)
					{
						print $langs->trans("FlatOrDiscountedPrice").' = ';
					}
					print '<span class="bold">'.$pricetoshow.'</span>';
					if ($foundtemplate == 0)	// Same than ispaid
					{
						if ($contract->array_options['options_date_endfreeperiod'] < $now) $color='orange';

						print ' <span style="color:'.$color.'">';
						if ($contract->array_options['options_date_endfreeperiod'] > 0) print $langs->trans("TrialUntil", dol_print_date($contract->array_options['options_date_endfreeperiod'], 'day'));
						else print $langs->trans("Trial");
						print '</span>';
						if ($contract->array_options['options_date_endfreeperiod'] < $now)
						{
							if ($statuslabel == 'suspended') print ' - <span style="color: orange">'.$langs->trans("Suspended").'</span>';
							//else print ' - <span style="color: orange">'.$langs->trans("SuspendWillBeDoneSoon").'</span>';
						}
						if ($statuslabel == 'suspended')
						{
							/*if (empty($atleastonepaymentmode))
							{
								print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'">'.$langs->trans("AddAPaymentModeToRestoreInstance").'</a>';
							}
							else
							{
								print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'">'.$langs->trans("FixPaymentModeToRestoreInstance").'</a>';
							}*/
						}
						else
						{
							if (empty($atleastonepaymentmode))
							{
								//print ' - <a href="'.$_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode).'">'.$langs->trans("AddAPaymentMode").'</a>';
							}
							else
							{
								// If at least one payment mode already recorded
								if (sellyoursaasIsPaymentKo($contract))
								{
									print ' - '.$langs->trans("ActivePaymentError");
								}
								else
								{
									print ' - '.$langs->trans("APaymentModeWasRecorded");
								}
							}
						}
					}
				}
				print '</span>';
			}

			print '
								  </div>
				              </div>';

			/*
			print '				<!-- tab domain -->
				            <div class="tab-pane" id="tab_domain_'.$contract->id.'">
				                <div class="opacitymedium" style="padding: 15px">'.$langs->trans("TheURLDomainOfYourInstance").' :</div>
								<form class="form-group" action="'.$_SERVER["PHP_SELF"].'" method="POST">
								<div class="col-md-9">
									<input type="text" class="urlofinstance" disabled="disabled" value="'.$contract->ref_customer.'">
									<input type="hidden" name="mode" value="instances"/>
									<input type="hidden" name="action" value="updateurl" />
									<input type="hidden" name="contractid" value="'.$contract->id.'" />
									<input type="hidden" name="tab" value="domain_'.$contract->id.'" />
								';
			//print '<input type="submit" class="btn btn-warning default change-domain-link" name="changedomain" value="'.$langs->trans("ChangeDomain").'">';
			print '
								</div>
							  	</form>
				            </div>';
			*/

			// SSH
			print '

				            <div class="tab-pane" id="tab_ssh_'.$contract->id.'">
				                <p class="opacitymedium" style="padding: 15px">'.$langs->trans("SSHFTPDesc").' :</p>
				                <form class="form-horizontal" role="form">
				                <div class="form-body">
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("Hostname").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_hostname_os'].'">
				                    </div>
				                    <label class="col-md-3 control-label">'.$langs->trans("Port").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.($contract->array_options['options_port_os']?$contract->array_options['options_port_os']:22).'">
				                    </div>
				                  </div>
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("SFTP Username").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_username_os'].'">
				                    </div>
				                    <label class="col-md-3 control-label">'.$langs->trans("Password").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_password_os'].'">
				                    </div>
				                  </div>
				                </div>
				                </form>
				              </div> <!-- END TAB PANE -->

				              <div class="tab-pane" id="tab_db_'.$contract->id.'">
				                <p class="opacitymedium" style="padding: 15px">'.$langs->trans("DBDesc").' :</p>
				                <form class="form-horizontal" role="form">
				                <div class="form-body">
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("Hostname").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_hostname_db'].'">
				                    </div>
				                    <label class="col-md-3 control-label">'.$langs->trans("Port").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_port_db'].'">
				                    </div>
				                  </div>
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("DatabaseName").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_database_db'].'">
				                    </div>
				                    <label class="col-md-3 control-label">'.$langs->trans("DatabaseLogin").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_username_db'].'">
				                    </div>
				                  </div>
				                  <div class="form-group col-md-12 row">
				                    <label class="col-md-3 control-label">'.$langs->trans("Password").'</label>
				                    <div class="col-md-3">
				                      <input type="text" disabled="disabled" class="form-control input-medium" value="'.$contract->array_options['options_password_db'].'">
				                    </div>
				                  </div>
				                </div>
				                </form>
				              </div> <!-- END TAB PANE -->
					';

				/*
				print '
				            <div class="tab-pane" id="tab_danger_'.$contract->id.'">
							<form class="form-group" action="'.$_SERVER["PHP_SELF"].'" method="POST">
				              <div class="">
				                <p class="opacitymedium" style="padding: 15px">
				                    '.$langs->trans("PleaseBeSure", $contract->ref_customer).'
				                </p>
								<p class="center" style="padding-bottom: 15px">
									<input type="text" class="center urlofinstancetodestroy" name="urlofinstancetodestroy" value="'.GETPOST('urlofinstancetodestroy','alpha').'" placeholder="">
								</p>
								<p class="center">
									<input type="hidden" name="mode" value="instances"/>
									<input type="hidden" name="action" value="undeploy" />
									<input type="hidden" name="contractid" value="'.$contract->id.'" />
									<input type="hidden" name="tab" value="danger_'.$contract->id.'" />
									<input type="submit" class="btn btn-danger" name="undeploy" value="'.$langs->trans("UndeployInstance").'">
								</p>
				              </div>
							</form>
				            </div> <!-- END TAB PANE -->
				'; */

				print '
				          </div> <!-- END TAB CONTENT -->
				        </div> <!-- END TABABLE CUSTOM-->

				      </div><!-- END PORTLET-BODY -->


					</div> <!-- END PORTLET -->

			      </div> <!-- END COL -->

			    </div> <!-- END ROW -->';

		}		// End loop contract

		// Link to add new instance
		print '
		<!-- Add a new instance -->
		<div class="portlet-body" style=""><br>
		';

		print '<a href="#addanotherinstance" id="addanotherinstance">';
		print $langs->trans("AddAnotherInstance").'...<br>';
		print '</a>';

		print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			jQuery("#addanotherinstance").click(function() {
				console.log("Click on addanotherinstance");
				jQuery("#formaddanotherinstance").toggle();
			});
		});
			</script>';

		print '<br>';

		print '<form id="formaddanotherinstance" class="form-group reposition" style="display: none;" action="register_instance.php" method="POST">';
		print '<input type="hidden" name="action" value="deployall" />';
		print '<input type="hidden" name="fromsocid" value="'.$mythirdpartyaccount->id.'" />';
		//print '<input type="hidden" name="reusesocid" value="'.$socid.'" />';

		print '<div class="row">
		<div class="col-md-12">

		<div class="portlet light">';

		natcasesort($arrayofplans);

		print '
			<div class="group">
			<div class="horizontal-fld">';

		$savsocid = $user->socid;	// Save socid of user
		$user->socid = 0;
		print $langs->trans("Customer").' '.$form->select_company('', 'reusesocid', 'parent = '.$mythirdpartyaccount->id, '1', 0, 1, array(), 0, 'centpercent').'<br><br>';
		$user->socid = $savsocid;	// Restore socid of user

		print $langs->trans("Type").' '.$form->selectarray('service', $arrayofplans, $planid, 0, 0, 0, '', 0, 0, 0, '', 'centpercent').'<br><br>';
		print '
			</div>

			<div class="horizontal-fld clearboth">
			<div class="control-group required">
			<label class="control-label" for="password" trans="1">'.$langs->trans("Password").'</label><input name="password" type="password" required />
			</div>
			</div>
			<div class="horizontal-fld ">
			<div class="control-group required">
			<label class="control-label" for="password2" trans="1">'.$langs->trans("ConfirmPassword").'</label><input name="password2" type="password" required />
			</div>
			</div>
			</div> <!-- end group -->

			<section id="selectDomain">
			<br>
			<div class="fld select-domain required">
			<label trans="1">'.$langs->trans("ChooseANameForYourApplication").'</label>
			<div class="linked-flds">
			<span class="opacitymedium">https://</span>
			<input class="sldAndSubdomain" type="text" name="sldAndSubdomain" value="" maxlength="29" required />
			<select name="tldid" id="tldid" >';
				$listofdomain = explode(',', $conf->global->SELLYOURSAAS_SUB_DOMAIN_NAMES);
				foreach($listofdomain as $val)
				{
					$newval=$val;
					if (! preg_match('/^\./', $newval)) $newval='.'.$newval;
					print '<option value="'.$newval.'">'.$newval.'</option>';
				}
			print '</select>
			<br class="unfloat" />
			</div>
			</div>
			</section>';

		print '<br><input type="submit" class="btn btn-warning default change-plan-link" name="changeplan" value="'.$langs->trans("Create").'">';

		print '</div></div></div>';

		print '</form>';

	}

	print '
	    </div>
		</div>
	';

	if (GETPOST('tab','alpha'))
	{
		print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			console.log("Click on '.GETPOST('tab','alpha').'");
			jQuery("#a_tab_'.GETPOST('tab','alpha').'").click();
		});
		</script>';
	}
}

if ($mode == 'billing')
{
	print '
	<div class="page-content-wrapper">
			<div class="page-content">


    <!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE HEAD -->
	<div class="page-head">
	  <!-- BEGIN PAGE TITLE -->
	<div class="page-title">
	  <h1>'.$langs->trans("Billing").' <small>'.$langs->trans("BillingDesc").'</small></h1>
	</div>
	<!-- END PAGE TITLE -->
	</div>
	<!-- END PAGE HEAD -->
	<!-- END PAGE HEADER-->

	    <div class="row">
	      <div class="col-md-9">

	        <div class="portlet light" id="planSection">

	          <div class="portlet-title">
	            <div class="caption">
	              <span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("MyInvoices").'</span>
	            </div>
	          </div>
';

		if (count($listofcontractid) > 0)
		{
			foreach ($listofcontractid as $id => $contract)
			{
				$planref = $contract->array_options['options_plan'];
				$statuslabel = $contract->array_options['options_deployment_status'];
				$instancename = preg_replace('/\..*$/', '', $contract->ref_customer);

				$package = new Packages($db);
				$package->fetch(0, $planref);

				$color = "green";
				if ($statuslabel == 'processing') $color = 'orange';
				if ($statuslabel == 'suspended') $color = 'orange';

				$dbprefix = $contract->array_options['options_db_prefix'];
				if (empty($dbprefix)) $dbprefix = 'llx_';

				print '
				<br>
		        <div class="portlet-body">

		            <div class="row" style="border-bottom: 1px solid #ddd;">

		              <div class="col-md-6">
				          <a href="https://'.$contract->ref_customer.'" class="caption-subject bold uppercase font-green-sharp" title="'.$langs->trans("Contract").' '.$contract->ref.'" target="_blankinstance">'.$instancename.'</a>
				          <span class="caption-helper"> - '.($package->label?$package->label:$planref).'</span>	<!-- This is package, not PLAN -->
		              </div><!-- END COL -->
		              <div class="col-md-2 hideonsmartphone">
		                '.$langs->trans("Date").'
		              </div>
		              <div class="col-md-2 hideonsmartphone">
		                '.$langs->trans("Amount").'
		              </div>
		              <div class="col-md-2 hideonsmartphone">
		                '.$langs->trans("Status").'
		              </div>
		            </div> <!-- END ROW -->
				';

				$contract->fetchObjectLinked();
				$freqlabel = array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year'));
				if (is_array($contract->linkedObjects['facture']) && count($contract->linkedObjects['facture']) > 0)
				{
					usort($contract->linkedObjects['facture'], "cmp");

					//var_dump($contract->linkedObjects['facture']);
					//dol_sort_array($contract->linkedObjects['facture'], 'date');
					foreach($contract->linkedObjects['facture'] as $idinvoice => $invoice)
					{
						if ($invoice->statut == Facture::STATUS_DRAFT) continue;

						print '
					            <div class="row" style="margin-top:20px">

					              <div class="col-md-6">
									';
									$url = $invoice->getLastMainDocLink($invoice->element, 0, 1);
									print '<a href="'.DOL_URL_ROOT.'/'.$url.'">'.$invoice->ref.' '.img_mime($invoice->ref.'.pdf', $langs->trans("File").': '.$invoice->ref.'.pdf').'</a>
					              </div>
					              <div class="col-md-2">
									'.dol_print_date($invoice->date, 'day').'
					              </div>
					              <div class="col-md-2">
									'.price(price2num($invoice->total_ttc), 1, $langs, 0, 0, $conf->global->MAIN_MAX_DECIMALS_TOT, $conf->currency).'
					              </div>
					              <div class="col-md-2 nowrap">
									';
									$alreadypayed = $invoice->getSommePaiement();
									$amount_credit_notes_included = $invoice->getSumCreditNotesUsed();
									$paymentinerroronthisinvoice = 0;

									// Test if there is a payment error, if yes, ask to fix payment data
									$sql = 'SELECT f.rowid, ee.code, ee.extraparams  FROM '.MAIN_DB_PREFIX.'facture as f';
									$sql.= ' INNER JOIN '.MAIN_DB_PREFIX."actioncomm as ee ON ee.fk_element = f.rowid AND ee.elementtype = 'invoice'";
									$sql.= " AND ee.code LIKE 'AC_PAYMENT_%_KO'";
									$sql.= ' WHERE f.fk_soc = '.$mythirdpartyaccount->id.' AND f.paye = 0 AND f.rowid = '.$invoice->id;
									$sql.= ' ORDER BY ee.datep DESC';
									$sql.= ' LIMIT 1';

									$resql = $db->query($sql);
									if ($resql)
									{
										$num_rows = $db->num_rows($resql);
										$i=0;
										if ($num_rows)
										{
											$paymentinerroronthisinvoice++;
											$obj = $db->fetch_object($resql);
											// There is at least one payment error
											if ($obj->extraparams == 'PAYMENT_ERROR_INSUFICIENT_FUNDS')
											{
												print '<img src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png"> '.$langs->trans("PaymentError");
											}
											else
											{
												print '<img src="'.DOL_URL_ROOT.'/theme/eldy/img/statut8.png"> '.$langs->trans("PaymentError");
											}
										}
									}
									if (! $paymentinerroronthisinvoice)
									{
										print $invoice->getLibStatut(2, $alreadypayed + $amount_credit_notes_included);
									}
									print '
					              </div>

					            </div>
							';
					}
				}
				else
				{
					print '
					            <div class="row" style="margin-top:20px">

					              <div class="col-md-12">
								<span class="opacitymedium">'.$langs->trans("NoneF").'</span>
								  </div>
								</div>
						';
				}

				print '
		          </div> <!-- END PORTLET-BODY -->
				<br><br>
				';
			}
		}
		else
		{
			print '
					            <div class="row" style="margin-top:20px">

					              <div class="col-md-12">
								<span class="opacitymedium">'.$langs->trans("NoneF").'</span>
								  </div>
								</div>
						';
		}

		print '

	        </div> <!-- END PORTLET -->



	      </div> <!-- END COL -->

			<!-- Box of payment modes -->
	      <div class="col-md-3">
	        <div class="portlet light" id="paymentMethodSection">

	          <div class="portlet-title">
	            <div class="caption">
	              <i class="icon-credit-card font-green-sharp"></i>
	              <span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("PaymentMode").'</span>
	            </div>
	          </div>

	          <div class="portlet-body">
	            <p>';

				$urltoenterpaymentmode = $_SERVER["PHP_SELF"].'?mode=registerpaymentmode&backtourl='.urlencode($_SERVER["PHP_SELF"].'?mode='.$mode);

				if ($nbpaymentmodeok > 0)
				{
					print '<table class="centpercent">';
					print '<!-- '.$companypaymentmodetemp->id.' -->';

					$i = 0;
					foreach($arrayofcompanypaymentmode as $companypaymentmodetemp)
					{
						if ($i > 0) print '<tr><td colspan="3"><br></td></tr>';
						if ($companypaymentmodetemp->type == 'card')
						{
							print '<tr>';
							print '<td>';
							print '<!-- '.$companypaymentmodetemp->id.' -->';
							print img_credit_card($companypaymentmodetemp->type_card);
							print '</td>';
							print '<td class="wordbreak" style="word-break: break-word" colspan="2">';
							print $langs->trans("CreditCard");
							print '</td>';
							print '</tr>';
							print '<tr>';
							print '<td>';
							print '....'.$companypaymentmodetemp->last_four;
							print '</td>';
							print '<td></td>';
							print '<td>';
							print sprintf("%02d",$companypaymentmodetemp->exp_date_month).'/'.$companypaymentmodetemp->exp_date_year;
							print '</td>';
							print '</tr>';
							// Warning if expiring
							if ($companypaymentmodetemp->exp_date_year < $nowyear ||
								($companypaymentmodetemp->exp_date_year == $nowyear && $companypaymentmodetemp->exp_date_month <= $nowmonth))
							{
								print '<tr><td colspan="3" style="color: orange">';
								print img_warning().' '.$langs->trans("YourPaymentModeWillExpireFixItSoon", $urltoenterpaymentmode);
								print '</td></tr>';
							}
							if (GETPOST('debug','int'))
							{
								include_once DOL_DOCUMENT_ROOT.'/stripe/class/stripe.class.php';
								$stripe = new Stripe($db);
								$stripeacc = $stripe->getStripeAccount($service);								// Get Stripe OAuth connect account if it exists (no network access here)
								$customer = $stripe->customerStripe($mythirdpartyaccount, $stripeacc, $servicestatusstripe, 0);

								print '<tr><td>';
								print 'Stripe customer: '.$customer->id;
								print '</td><td colspan="2">';
								print 'Stripe card: '.$companypaymentmodetemp->stripe_card_ref;
								print '</td></tr>';
							}

						}
						elseif ($companypaymentmodetemp->type == 'paypal')
						{
							print '<tr>';
							print '<td>';
							print '<!-- '.$companypaymentmodetemp->id.' -->';
							print img_picto('', 'paypal');
							print '</td>';
							print '<td class="wordbreak" style="word-break: break-word" colspan="2">';
							print $langs->trans("Paypal");
							print '</td>';
							print '</tr>';
							print '<tr>';
							print '<td>';
							print $companypaymentmodetemp->email;
							print '<br>'.'Preaproval key: '.$companypaymentmodetemp->preapproval_key;
							print '</td>';
							print '<td>';
							print dol_print_date($companypaymentmodetemp->starting_date, 'day').'/'.dol_print_date($companypaymentmodetemp->ending_date, 'day');
							print '</td>';
							print '</tr>';
							// Warning if expiring
							if (dol_time_plus_duree($companypaymentmodetemp->ending_date, -1, 'm') < $nowyear)
							{
								print '<tr><td colspan="3" style="color: orange">';
								print img_warning().' '.$langs->trans("YourPaymentModeWillExpireFixItSoon", $urltoenterpaymentmode);
								print '</td></tr>';
							}
						}
						elseif ($companypaymentmodetemp->type == 'ban')
						{
							print '<tr>';
							print '<td>';
							print img_picto('', 'bank', '',  false, 0, 0, '', 'fa-2x');
							print '</td>';
							print '<td class="wordbreak" style="word-break: break-word" colspan="2">';
							print $langs->trans("WithdrawalReceipt");
							print '</td>';
							print '</tr>';

							print '<tr><td colspan="3">';
							print $langs->trans("IBAN").': '.$companypaymentmodetemp->iban_prefix.'<br>';
							if ($companypaymentmodetemp->rum) print $langs->trans("RUM").': '.$companypaymentmodetemp->rum;
							print '</td></tr>';
						}
						else
						{
							print '<tr>';
							print '<td>';
							print $companypaymentmodetemp->type;
							print '</td>';
							print '<td>';
							print $companypaymentmodetemp->label;
							print '</td>';
							print '<td>';
							print '</td>';
							print '</tr>';
						}

						$i++;
					}

					print '</table>';
				}
				else
				{
					print $langs->trans("NoPaymentMethodOnFile");
					if ($nbofinstancessuspended || $ispaid || $atleastonecontractwithtrialended) print ' '.img_warning();
				}

	            print '
	                <br><br>
	                <a href="'.$urltoenterpaymentmode.'" class="btn default btn-xs green-stripe">';
	            	if ($nbpaymentmodeok) print $langs->trans("ModifyPaymentMode");
	            	else print $langs->trans("AddAPaymentMode");
	                print '</a>

	            </p>
	          </div> <!-- END PORTLET-BODY -->

	        </div> <!-- END PORTLET -->
	      </div><!-- END COL -->

	    </div> <!-- END ROW -->


	    </div>
		</div>
	';
}



if ($mode == 'registerpaymentmode')
{
	print '<!-- mode = registerpaymentmode -->
	<div class="page-content-wrapper">
		<div class="page-content">


		<!-- BEGIN PAGE HEADER-->
		<!-- BEGIN PAGE HEAD -->
		<div class="page-head">
		  <!-- BEGIN PAGE TITLE -->
		<div class="page-title">
		  <h1>'.$langs->trans("PaymentMode").'<br><small>'.$langs->trans("SetANewPaymentMode").'</small></h1>
		</div>
		<!-- END PAGE TITLE -->
		</div>
		<!-- END PAGE HEAD -->
		<!-- END PAGE HEADER-->


	    <div class="row">
		<div class="col-md-12 center">
		<div class="portlet light">

		<div class="portlet-body">';


		print '
		<form action="'.$_SERVER["PHP_SELF"].'" method="POST" id="payment-form">
		<input type="hidden" name="action" value="createpaymentmode">
		<input type="hidden" name="backtourl" value="'.$backtourl.'">


		<div class="radio-list">
		<label class="radio-inline" style="margin-right: 0px" id="linkcard">
		<div class="radio inline-block"><span class="checked">'.$langs->trans("CreditOrDebitCard").'<input type="radio" name="type" value="card" checked></span></div><br>
		<img src="/img/mastercard.png" width="50" height="31">
		<img src="/img/visa.png" width="50" height="31">
		<img src="/img/american_express.png" width="50" height="31">
		</label>
		<!--
		<label class="radio-inline" id="linkpaypal" style="margin-left: 40px;">
		<div class="radio inline-block"><span>'.$langs->trans("PayPal").'<input type="radio" name="type" value="PayPal"></span></div><br>
		<img src="/img/paypal.png" width="50" height="31">
		</label>
		-->
		<label class="radio-inline" id="linksepa" style="margin-left: 30px;">
		<div class="radio inline-block"><span>'.$langs->trans("SEPAMandate").'<input type="radio" name="type" value="SepaMandate"></span></div><br>
		<img src="/img/sepa.png" width="50" height="31">
		</label>
		</div>

		<br>

		<div class="linkcard">';

			$foundcard=0;
			// Check if there is already a payment
			foreach($arrayofcompanypaymentmode as $companypaymentmodetemp)
			{
				if ($companypaymentmodetemp->type == 'card')
				{
					$foundcard++;
					print '<hr>';
					print img_credit_card($companypaymentmodetemp->type_card);
					print $langs->trans("CurrentCreditOrDebitCard").':<br>';
					print '<!-- '.$companypaymentmodetemp->id.' -->';
					print '....'.$companypaymentmodetemp->last_four;
					print ' - ';
					print sprintf("%02d",$companypaymentmodetemp->exp_date_month).'/'.$companypaymentmodetemp->exp_date_year;
					// Warning if expiring
					if ($companypaymentmodetemp->exp_date_year < $nowyear ||
						($companypaymentmodetemp->exp_date_year == $nowyear && $companypaymentmodetemp->exp_date_month <= $nowmonth))
					{
						print '<br>';
						print img_warning().' '.$langs->trans("YourPaymentModeWillExpireFixItSoon", $urltoenterpaymentmode);
					}
				}
			}
			if ($foundcard)
			{
				print '<hr>';
				print img_credit_card($companypaymentmodetemp->type_card);
				print $langs->trans("NewCreditOrDebitCard").':<br>';
			}

			print '<div class="row"><div class="col-md-12"><label class="valignmiddle" style="margin-bottom: 20px">'.$langs->trans("NameOnCard").':</label> ';
			print '<input class="minwidth200 valignmiddle" style="margin-bottom: 15px" type="text" name="proprio" value="'.GETPOST('proprio','alpha').'"></div></div>';


			if (! empty($conf->global->SELLYOURSAAS_STRIPE_USE_TOKEN))
			{
				require_once DOL_DOCUMENT_ROOT.'/stripe/config.php';
				// Reforce the $stripearrayofkeys because content may change depending on option
				if (empty($conf->global->STRIPE_LIVE) || GETPOST('forcesandbox','alpha') || ! empty($conf->global->SELLYOURSAAS_FORCE_STRIPE_TEST))
				{
					$stripearrayofkeys = $stripearrayofkeysbyenv[0];	// Test
				}
				else
				{
					$stripearrayofkeys = $stripearrayofkeysbyenv[1];	// Live
				}

				print '<script src="https://js.stripe.com/v3/"></script>';

				print '	<center><div class="form-row" style="max-width: 320px">

				<div id="card-element">
				<!-- A Stripe Element will be inserted here. -->
				</div>

				<!-- Used to display form errors. -->
				<div id="card-errors" role="alert"></div>

				</div></form>';

				print '<br>';
				print '<button class="btn btn-info btn-circle" id="buttontopay">'.$langs->trans("Save").'</button>';
				print '<img id="hourglasstopay" class="hidden" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/working.gif'.'">';
				print ' ';
				print '<a id="buttontocancel" href="'.($backtourl ? $backtourl : $_SERVER["PHP_SELF"]).'" class="btn green-haze btn-circle">'.$langs->trans("Cancel").'</a>';


				print '<script type="text/javascript" language="javascript">';
				print "
					// Create a Stripe client.
					var stripe = Stripe('".$stripearrayofkeys['publishable_key']."');		/* Defined into config.php */

					// Create an instance of Elements.
					var elements = stripe.elements();

					// Custom styling can be passed to options when creating an Element.
					// (Note that this demo uses a wider set of styles than the guide below.)
					var style = {
					  base: {
					    color: '#32325d',
					    lineHeight: '18px',
					    fontFamily: '\"Helvetica Neue\", Helvetica, sans-serif',
					    fontSmoothing: 'antialiased',
					    fontSize: '16px',
					    '::placeholder': {
					      color: '#aab7c4'
					    }
					  },
					  invalid: {
					    color: '#fa755a',
					    iconColor: '#fa755a'
					  }
					};

					// Create an instance of the card Element.
					var card = elements.create('card', {style: style});

					// Add an instance of the card Element into the `card-element` <div>.
					card.mount('#card-element');

					// Handle real-time validation errors from the card Element.
					card.addEventListener('change', function(event) {
					  var displayError = document.getElementById('card-errors');
					  if (event.error) {
					    displayError.textContent = event.error.message;
					  } else {
					    displayError.textContent = '';
					  }
					});

					// Handle form submission.
					var form = document.getElementById('payment-form');
					form.addEventListener('submit', function(event) {
					  event.preventDefault();";
						if (empty($conf->global->STRIPE_USE_3DSECURE))	// Ask credit card directly, no 3DS test
						{
						?>
							/* Use token */
							stripe.createToken(card).then(function(result) {
						        if (result.error) {
						          // Inform the user if there was an error
						          var errorElement = document.getElementById('card-errors');
						          errorElement.textContent = result.error.message;
						        } else {
						          // Send the token to your server
						          stripeTokenHandler(result.token);
						        }
							});
						<?php
						}
						else											// Ask credit card with 3DS test
						{
						?>
							/* Use 3DS source */
							stripe.createSource(card).then(function(result) {
							    if (result.error) {
							      // Inform the user if there was an error
							      var errorElement = document.getElementById('card-errors');
							      errorElement.textContent = result.error.message;
							    } else {
							      // Send the source to your server
							      stripeSourceHandler(result.source);
							    }
							});
						<?php
						}
					print "
					});


					/* Insert the Token into the form so it gets submitted to the server */
				    function stripeTokenHandler(token) {
				      // Insert the token ID into the form so it gets submitted to the server
				      var form = document.getElementById('payment-form');
				      var hiddenInput = document.createElement('input');
				      hiddenInput.setAttribute('type', 'hidden');
				      hiddenInput.setAttribute('name', 'stripeToken');
				      hiddenInput.setAttribute('value', token.id);
				      form.appendChild(hiddenInput);

				      // Submit the form
				      jQuery('#buttontopay').hide();
				      jQuery('#buttontocancel').hide();
				      jQuery('#hourglasstopay').show();
				      console.log('submit token');
				      form.submit();
				    }

					/* Insert the Source into the form so it gets submitted to the server */
					function stripeSourceHandler(source) {
					  // Insert the source ID into the form so it gets submitted to the server
					  var form = document.getElementById('payment-form');
					  var hiddenInput = document.createElement('input');
					  hiddenInput.setAttribute('type', 'hidden');
					  hiddenInput.setAttribute('name', 'stripeSource');
					  hiddenInput.setAttribute('value', source.id);
					  form.appendChild(hiddenInput);

					  // Submit the form
				      jQuery('#buttontopay').hide();
				      jQuery('#buttontocancel').hide();
				      jQuery('#hourglasstopay').show();
				      console.log('submit source');
					  form.submit();
					}

					</script>
					";
			}
			else
			{
				print '<div class="row"><div class="col-md-12"><label>'.$langs->trans("CardNumber").'</label>';
				print '<input class="minwidth200" type="text" name="cardnumber" value="'.GETPOST('cardnumber','alpha').'"></div></div>';

				print '<div class="row"><div class="col-md-12"><label>'.$langs->trans("ExpiryDate").'</label><br>';
				print $formother->select_month(GETPOST('exp_date_month','int'), 'exp_date_month', 1, 1, 'width100');
				print $formother->select_year(GETPOST('exp_date_year','int'), 'exp_date_year', 1, 5, 10, 0, 0, '', 'marginleftonly width100');
				print '</div></div>';

				print '<div class="row"><div class="col-md-12"><label><br>'.$langs->trans("CVN").'</label>';
				print '<input size="5" type="text" class="maxwidth100" name="cvn" value="'.GETPOST('cvn','alpha').'"></div></div>';

				print '<br>';
				print '<input type="submit" name="submitcard" value="'.$langs->trans("Save").'" class="btn btn-info btn-circle">';
				print ' ';
				print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'" class="btn green-haze btn-circle">';
			}

			print '
		</div>

		<div class="linkpaypal" style="display: none;">';
			print '<br>';
			//print $langs->trans("PaypalPaymentModeAvailableForYealySubscriptionOnly");
			print $langs->trans("PaypalPaymentModeNotYetAvailable");
			print '<br><br>';
			//print '<input type="submit" name="submitpaypal" value="'.$langs->trans("Continue").'" class="btn btn-info btn-circle">';
			print ' ';
			print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'" class="btn green-haze btn-circle">';

		print '
		</div>

		<div class="linksepa" style="display: none;">';
		if ($mythirdpartyaccount->isInEEC())
		{
			$foundban=0;

			// Check if there is already a payment
			foreach($arrayofcompanypaymentmode as $companypaymentmodetemp)
			{
				if ($companypaymentmodetemp->type == 'ban')
				{
					/*print img_picto('', 'bank', '',  false, 0, 0, '', 'fa-2x');
					print '<span class="wordbreak" style="word-break: break-word" colspan="2">';
					print $langs->trans("WithdrawalReceipt");
					print '</span>';
					print '<br>';*/
					print $langs->trans("IBAN").': '.$companypaymentmodetemp->iban_prefix.'<br>';
					if ($companypaymentmodetemp->rum) print $langs->trans("RUM").': '.$companypaymentmodetemp->rum;
					$foundban++;
					print '<br>';

					$companybankaccounttemp = new CompanyBankAccount($db);

					include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
					$ecmfile = new EcmFiles($db);
					$result = $ecmfile->fetch(0, '', '', '', '', $companybankaccounttemp->table_element, $companypaymentmodetemp->id);
					if ($result > 0)
					{
						$companybankaccounttemp->last_main_doc = $ecmfile->filepath.'/'.$ecmfile->filename;
						print '<br><!-- Link to download main doc -->'."\n";
						$publicurltodownload = $companybankaccounttemp->getLastMainDocLink($object->element, 0, 1);
						// Define $urlwithroot
						//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
						//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
						//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
						/*var_dump($conf->global->SELLYOURSAAS_ACCOUNT_URL);
						var_dump(DOL_URL_ROOT);
						var_dump($publicurltodownload);*/
						$urltouse=$conf->global->SELLYOURSAAS_ACCOUNT_URL.'/'.(DOL_URL_ROOT?DOL_URL_ROOT.'/':'').$publicurltodownload;
						//print img_mime('sepa.pdf').'  <a href="'.$urltouse.'" target="_download">'.$langs->trans("DownloadTheSEPAMandate").'</a><br>';
					}
				}
			}

			if (! $foundban)
			{
				print '<br>';
				//print $langs->trans("SEPAPaymentModeAvailableForYealyAndCeeSubscriptionOnly");
				print $langs->trans("SEPAPaymentModeAvailableNotYetAvailable");
			}

			print '<br><br>';
			//print '<input type="submit" name="submitpaypal" value="'.$langs->trans("Continue").'" class="btn btn-info btn-circle">';
			print ' ';
			print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'" class="btn green-haze btn-circle">';
		}
		else
		{
			print '<br>';
			print $langs->trans("SEPAPaymentModeAvailableForCeeOnly", $mythirdpartyaccount->country);
			print '<br><br>';
			print ' ';
			print '<input type="submit" name="cancel" value="'.$langs->trans("Cancel").'" class="btn green-haze btn-circle">';
		}
		print '
		</div>

		</form>
		</div>

		</div></div></div>

	    </div>
		</div>
	';

	print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			jQuery("#linkcard").click(function() {
				console.log("Click on linkcard");
				jQuery(".linkcard").show();
				jQuery(".linkpaypal").hide();
				jQuery(".linksepa").hide();
			});
			jQuery("#linkpaypal").click(function() {
				console.log("Click on linkpaypal");
				jQuery(".linkcard").hide();
				jQuery(".linkpaypal").show();
				jQuery(".linksepa").hide();
			});
			jQuery("#linksepa").click(function() {
				console.log("Click on linksepa");
				jQuery(".linkcard").hide();
				jQuery(".linkpaypal").hide();
				jQuery(".linksepa").show();
			});
		});
		</script>';

}




if ($mode == 'mycustomerbilling')
{
	print '
	<div class="page-content-wrapper">
			<div class="page-content">



	     <!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE HEAD -->
	<div class="page-head">
	  <!-- BEGIN PAGE TITLE -->
	<div class="page-title">
	  <h1>'.$langs->trans("MyCustomersBilling").'</h1>
	</div>
	<!-- END PAGE TITLE -->
	</div>
	<!-- END PAGE HEAD -->
	<!-- END PAGE HEADER-->



	    <div class="row">
	      <div class="col-md-12">

			<div class="portlet light">
	          <div class="portlet-title">
	            <div class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("MyCommissionsReceived").'</div>
	          </div>
							'.$langs->trans("SoonAvailable").'
	        </div>
		  </div>
	    </div> <!-- END ROW -->


	    <div class="row">
	      <div class="col-md-12">

	        <div class="portlet light">
	          <div class="portlet-title">
	            <div class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("MyCommissionsEarned").'</div>


';

	print '
			<div class="div-table-responsive-no-min">
				<table class="noborder centpercent tablecommission">
				<tr class="liste_titre">

	              <td style="min-width: 150px">
			         '.$langs->trans("Customer").'
	              </td>
	              <td style="min-width: 100px">
	                '.$langs->trans("Date").'
	              </td>
	              <td>
	                '.$langs->trans("Invoice").'
	              </td>
	              <td>
	                '.$langs->trans("Amount").'
	              </td>
	              <td>
	                '.$langs->trans("Status").'
	              </td>
	              <td align="right">
	                '.$langs->trans("Commission").' (%)
	              </td>
	              <td align="right">
	                '.$langs->trans("Commission").' ('.$langs->trans("Amount").')
	              </td>

				</tr>
		';


		$sql ='SELECT f.rowid, f.facnumber as ref, f.fk_soc, f.datef, total as total_ht, total_ttc, f.paye, f.fk_statut, fe.commission';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f LEFT JOIN '.MAIN_DB_PREFIX.'facture_extrafields as fe ON fe.fk_object = f.rowid';
		//$sql.=' WHERE fe.reseller IN ('.join(',', $listofcustomeridreseller).')';
		$sql.=' WHERE fe.reseller = '.$mythirdpartyaccount->id;

		$sql.=$db->order($sortfield,$sortorder);

		// Count total nb of records
		$nbtotalofrecords = '';
		$resql = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($resql);

		// if total resultset is smaller then paging size (filtering), goto and load page 0
		if (($page * $limit) > $nbtotalofrecords)
		{
			$page = 0;
			$offset = 0;
		}
		// if total resultset is smaller the limit, no need to do paging.
		if (is_numeric($nbtotalofrecords) && $limit > $nbtotalofrecords)
		{
			$num = $nbtotalofrecords;
		}
		else
		{
			$sql.= $db->plimit($limit+1, $offset);

			$resql=$db->query($sql);
			if (! $resql)
			{
				dol_print_error($db);
				exit;
			}

			$num = $db->num_rows($resql);
		}

		include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

		$tmpthirdparty = new Societe($db);
		$tmpinvoice = new Facture($db);
		$ecmfile = new EcmFiles($db);


		// Loop on record
		// --------------------------------------------------------------------
		$i=0;
		while ($i < min($num, $limit))
		{
			$obj = $db->fetch_object($resql);
			if (empty($obj)) break;		// Should not happen

			$tmpthirdparty->fetch($obj->fk_soc);	// To get current efault commission of this customer
			$tmpinvoice->fetch($obj->rowid);

			if ($tmpinvoice->statut == Facture::STATUS_DRAFT) continue;

			$currentcommissionpercent = $tmpthirdparty->array_options['options_commission'];
			$commissionpercent = $obj->commission;
			if ($obj->paye) $commission = price2num($obj->total_ttc * $commissionpercent / 100, 'MT');
			else $commission = 0;

			print '
				<tr>
              <td>
		         '.$tmpthirdparty->name.' '.$form->textwithpicto('', $langs->trans("CurrentCommission").': '.($commissionpercent?$commissionpercent:0).'%', 1).'
              </td>
              <td>
                '.dol_print_date($obj->datef, 'dayrfc', $langs).'
              </td>
              <td>
                '.img_mime('pdf.pdf').' '.$obj->ref;
                	$publicurltodownload = $tmpinvoice->getLastMainDocLink($tmpinvoice->element, 0, 1);
                	// Define $urlwithroot
                	//$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
                	//$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
                	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current
                	/*var_dump($conf->global->SELLYOURSAAS_ACCOUNT_URL);
                	 var_dump(DOL_URL_ROOT);
                	 var_dump($publicurltodownload);*/
                	$urltouse=$conf->global->SELLYOURSAAS_ACCOUNT_URL.'/'.(DOL_URL_ROOT?DOL_URL_ROOT.'/':'').$publicurltodownload;
             print '<br><a href="'.$urltouse.'" target="_download">'.$langs->trans("Download").'</a>';

             print '
              </td>
              <td>
                '.price(price2num($obj->total_ttc), 1, $langs, 0, 0, $conf->global->MAIN_MAX_DECIMALS_TOT, $conf->currency).'
              </td>
              <td>
                '.Facture::LibStatut($obj->paye, $obj->fk_statut).'
              </td>
              <td align="right">
                '.($commissionpercent?$commissionpercent:0).'
              </td>
              <td align="right">
                '.price($commission).'
              </td>
		    </tr>
	        ';

			$i++;
		}

		if ($nbtotalofrecords > $limit)
		{
			print '<tr><td colspan="6" class="center">';
			if ($page > 0) print '<a href="'.$_SERVER["PHP_SEFL"].'?mode='.$mode.'&limit='.$limit.'&page='.($page-1).'">'.$langs->trans("Previous").'</a>';
			if ($page > 0 && (($page + 1) * $limit) <= $nbtotalofrecords) print ' &nbsp; ... &nbsp; ';
			if ((($page + 1) * $limit) <= $nbtotalofrecords) print '<a href="'.$_SERVER["PHP_SELF"].'?mode='.$mode.'&limit='.$limit.'&page='.($page+1).'">'.$langs->trans("Next").'</a>';
			print '<br><br>';
			print '</td>';
			print '<td class="right">...<br><br></td>';
			print '</tr>';
		}

		// Get total of commissions
		$totalamountcommission='ERROR';

		$sql ='SELECT SUM(fe.commission * f.total / 100) as total';
		$sql.= ' FROM '.MAIN_DB_PREFIX.'facture as f LEFT JOIN '.MAIN_DB_PREFIX.'facture_extrafields as fe ON fe.fk_object = f.rowid';
		//$sql.=' WHERE fe.reseller IN ('.join(',', $listofcustomeridreseller).')';
		$sql.=' WHERE fe.reseller = '.$mythirdpartyaccount->id;
		$sql.=' AND fk_statut <> '.Facture::STATUS_DRAFT;
		$sql.=' AND paye = 1';

		$resql = $db->query($sql);
		if ($resql)
		{
			$obj = $db->fetch_object($resql);
			$totalamountcommission=$obj->total;
		}

		print '<tr class="liste_title"><td colspan="6">'.$langs->trans("Total").'</td>';
		print '<td align="right"><strong>'.price($totalamountcommission).'</strong></td>';
		print '</tr>';

		print '</table>
		</div>';

	print '
</div></div>
            </div>
          </div>
		';

	print '



	    </div>
		</div>
	';
}



if ($mode == 'support')
{
	// Print warning to read FAQ before
	print '
		<div class="alert alert-success note note-success">';
	if ($urlfaq)
	{
		print '<h4 class="block">'.$langs->trans("PleaseReadFAQFirst", $urlfaq).'</h4><br>';
	}
	print $langs->trans("CurrentServiceStatus", $urlstatus).'<br>';
	print '
		</div>
	';


	print '
	<div class="page-content-wrapper">
			<div class="page-content">


	     <!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE HEAD -->
	<div class="page-head">
	  <!-- BEGIN PAGE TITLE -->
	<div class="page-title">
	  <h1>'.$langs->trans("NewTicket").' <small>'.$langs->trans("SupportDesc").'</small></h1>
	</div>
	<!-- END PAGE TITLE -->


	</div>
	<!-- END PAGE HEAD -->
	<!-- END PAGE HEADER-->';


	print '
			    <div class="row" id="choosechannel">
			      <div class="col-md-12">

					<div class="portlet light">

				      <div class="portlet-title">
				        <div class="caption">';

						print '<form class="inline-block centpercent" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
						print '<input type="hidden" name="mode" value="support">';
						print '<input type="hidden" name="action" value="presend">';

						print $langs->trans("SelectYourSupportChannel").'<br>';

						print '<select id="supportchannel" name="supportchannel" class="maxwidth500 minwidth500" style="width: auto">';
						print '<option value=""></option>';
						if (count($listofcontractid) == 0)				// Should not happen
						{



						}
						else
						{
							$atleastonehigh=0;

							foreach ($listofcontractid as $id => $contract)
							{
								$planref = $contract->array_options['options_plan'];
								$statuslabel = $contract->array_options['options_deployment_status'];
								$instancename = preg_replace('/\..*$/', '', $contract->ref_customer);

								$dbprefix = $contract->array_options['options_db_prefix'];
								if (empty($dbprefix)) $dbprefix = 'llx_';

								// Get info about PLAN of Contract
								$package = new Packages($db);
								$package->fetch(0, $planref);
								$planlabel = ($package->label?$package->label:$planref);
								$planid = 0;
								$freeperioddays = 0;
								$directaccess = 0;

								$tmpproduct = new Product($db);
								foreach($contract->lines as $keyline => $line)
								{
									if ($line->statut == 5 && $contract->array_options['options_deployment_status'] != 'undeployed')
									{
										$statuslabel = 'suspended';
									}

									if ($line->fk_product > 0)
									{
										$tmpproduct->fetch($line->fk_product);
										if ($tmpproduct->array_options['options_app_or_option'] == 'app')
										{
											$planlabel = $tmpproduct->label;		// Warning, label is in language of user
											$planid = $tmpproduct->id;
											$freeperioddays = $tmpproduct->array_options['options_freeperioddays'];
											$directaccess = $tmpproduct->array_options['options_directaccess'];
											break;
										}
									}
								}

								$ispaid = sellyoursaasIsPaidInstance($contract);

								$color = "green";
								if ($statuslabel == 'processing') $color = 'orange';
								if ($statuslabel == 'suspended') $color = 'orange';
								if ($statuslabel == 'undeployed') $color = 'grey';

								if ($tmpproduct->array_options['options_typesupport'] != 'none')
								{
									if (! $ispaid)
									{
										$priority = 'low';
										$prioritylabel = $langs->trans("Trial").'-'.$langs->trans("Low");
									}
									else
									{
										if ($ispaid)
										{
											if ($tmpproduct->array_options['options_typesupport'] == 'premium')
											{
												$priority = 'high';
												$prioritylabel = $langs->trans("High");
												$atleastonehigh++;
											}
											else
											{
												$priority = 'medium';
												$prioritylabel = $langs->trans("Medium");
											}
										}
									}
									$optionid = $priority.'_'.$id;
									print '<option value="'.$optionid.'"'.(GETPOST('supportchannel','alpha') == $optionid ? ' selected="selected"':'').'">';
									//print $langs->trans("Instance").' '.$contract->ref_customer.' - ';
									print $tmpproduct->label.' - '.$contract->ref_customer.' ';
									//print $tmpproduct->array_options['options_typesupport'];
									//print $tmpproduct->array_options['options_typesupport'];
									print ' ('.$langs->trans("Priority").': ';
									print $prioritylabel;
									print ')';
									print '</option>';
									//print ajax_combobox('supportchannel');
								}
							}
						}


						//print '<option value="low_other"'.(GETPOST('supportchannel','alpha') == 'low_other' ? ' selected="selected"':'').'>'.$langs->trans("Other").' ('.$langs->trans("Priority").': '.$langs->trans("Low").')</option>';
						print '<option value="low_other"'.(GETPOST('supportchannel','alpha') == 'low_other' ? ' selected="selected"':'').'>'.$langs->trans("Other").'</option>';
						if (empty($atleastonehigh))
						{
							print '<option value="high_premium" disabled="disabled">'.$langs->trans("PremiumSupport").' ('.$langs->trans("Priority").': '.$langs->trans("High").') - '.$langs->trans("NoPremiumPlan").'</option>';
						}
						print '</select>';

						print '&nbsp;
						<input type="submit" name="submit" value="'.$langs->trans("Choose").'" class="btn green-haze btn-circle">
						';

						print '</form>';

						if ($action == 'presend' && GETPOST('supportchannel','alpha'))
						{
							print '<br><br>';
							print '<form class="inline-block centpercent" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
							print '<input type="hidden" name="mode" value="support">';
							print '<input type="hidden" name="contractid" value="'.$id.'">';
							print '<input type="hidden" name="action" value="send">';
							print '<input type="hidden" name="supportchannel" value="'.GETPOST('supportchannel','alpha').'">';

							$email = $conf->global->SELLYOURSAAS_MAIN_EMAIL;
							if (preg_match('/high/', GETPOST('supportchannel','alpha'))) $email = preg_replace('/@/', '+premium@', $email);

							$subject = (GETPOST('subject','none')?GETPOST('subject','none'):'');

							print '<input type="hidden" name="to" value="'.$email.'">';
							print $langs->trans("MailFrom").' : <input type="text" required name="from" value="'.(GETPOST('from','none')?GETPOST('from','none'):$mythirdpartyaccount->email).'"><br><br>';
							print $langs->trans("MailTopic").' : <input type="text" required class="minwidth500" name="subject" value="'.$subject.'"><br><br>';
							print '<textarea rows="6" required placeholder="'.$langs->trans("YourText").'" style="border: 1px solid #888" name="content" class="centpercent">'.GETPOST('content','none').'</textarea><br><br>';

							print '<center><input type="submit" name="submit" value="'.$langs->trans("SendMail").'" class="btn green-haze btn-circle">';
							print ' ';
							print '<input type="submit" name="cancel" formnovalidate value="'.$langs->trans("Cancel").'" class="btn green-haze btn-circle">';
							print '</center>';

							print '</form>';
						}

					print ' 	</div></div>

					</div> <!-- END PORTLET -->



			      </div> <!-- END COL -->


			    </div> <!-- END ROW -->
			';

	if ($action != 'presend')
	{
		print '
				<!-- BEGIN PAGE HEADER-->
				<!-- BEGIN PAGE HEAD -->
				<div class="page-head">
				<!-- BEGIN PAGE TITLE -->
				<div class="page-title">
				<h1>'.$langs->trans("Tickets").' </h1>
				</div>
				<!-- END PAGE TITLE -->


				</div>
				<!-- END PAGE HEAD -->
				<!-- END PAGE HEADER-->';

		print '
		<div class="row">
		<div class="col-md-12">

		<div class="portlet light" id="planSection">

		<div class="portlet-title">
		<div class="caption">
		<!--<span class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("Tickets").'</span>-->
		</div>
		</div>';

		print '
					<div class="row" id="contractid'.$contract->id.'" data-contractref="'.$contract->ref.'">
					<div class="col-md-12">';


						print $langs->trans("SoonAvailable");

					print '</div></div>';


		print '</div></div>';
	}

	print '
	    </div>
		</div>
	';

}


if ($mode == 'becomereseller')
{
	// Print warning to read FAQ before
	$url = 'https://www.dolicloud.com/en-become-a-dolicloud-reseller.php';
	if (preg_match('/^fr/i', $langs->defaultlang)) $url = 'https://www.dolicloud.com/fr-become-a-dolicloud-reseller.php';
	if (preg_match('/^es/i', $langs->defaultlang)) $url = 'https://www.dolicloud.com/es-become-a-dolicloud-reseller.php';
	print '
		<div class="alert alert-success note note-success">
		<h4 class="block">'.$langs->trans("BecomeResellerDesc", $conf->global->SELLYOURSAAS_NAME, $url, $conf->global->SELLYOURSAAS_NAME).'</h4>
	<br>
		</div>
	';


	print '
	<div class="page-content-wrapper">
			<div class="page-content">


	     <!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE HEAD -->
	<div class="page-head">


	</div>
	<!-- END PAGE HEAD -->
	<!-- END PAGE HEADER-->';


	print '
			    <div class="row" id="choosechannel">
			      <div class="col-md-12">

					<div class="portlet light">

				      <div class="portlet-title">
				        <div class="caption">';

		print '<form class="inline-block centpercent" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="mode" value="becomereseller">';
		print '<input type="hidden" name="action" value="sendbecomereseller">';

		$email = $conf->global->SELLYOURSAAS_MAIN_EMAIL;
		if (preg_match('/high/', GETPOST('supportchannel','alpha'))) $email = preg_replace('/@/', '+premium@', $email);
		$subject = (GETPOST('subject','none')?GETPOST('subject','none'):(preg_match('/fr/i', $langs->defaultlang)?$langs->trans("BecomeReseller"):$langsen->trans("BecomeReseller")).' - '.$email);

		$commissiondefault = 20;

		print '<input type="hidden" name="to" value="'.$email.'">';
		print $langs->trans("MailFrom").' : <input type="text" required name="from" value="'.(GETPOST('from','none')?GETPOST('from','none'):$mythirdpartyaccount->email).'"><br><br>';
		print $langs->trans("MailTopic").' : <input type="text" required class="minwidth500" name="subject" value="'.$subject.'"><br><br>';

		$texttouse = GETPOST('content','none');
		if (! $texttouse) $texttouse = (preg_match('/fr/i', $langs->defaultlang)?$langs->trans("YourTextBecomeReseller", $conf->global->SELLYOURSAAS_NAME, $commissiondefault):$langsen->trans("YourTextBecomeReseller", $conf->global->SELLYOURSAAS_NAME, $commissiondefault));
		$texttouse=preg_replace('/\\\\n/',"\n",$texttouse);
		print '<textarea rows="6" required style="border: 1px solid #888" name="content" class="centpercent">';
		print $texttouse;
		print '</textarea><br><br>';

		/*include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('content', $texttouse, '95%');
		$doleditor->Create(0);*/

		print '<center><input type="submit" name="submit" value="'.$langs->trans("SendMail").'" class="btn green-haze btn-circle">';
		print ' ';
		print '<input type="submit" name="cancel" formnovalidate value="'.$langs->trans("Cancel").'" class="btn green-haze btn-circle">';
		print '</center>';

		print '</form>';

	print ' 	</div></div>

					</div> <!-- END PORTLET -->



			      </div> <!-- END COL -->


			    </div> <!-- END ROW -->
			';

	print '
	    </div>
		</div>
	';

}


if ($mode == 'myaccount')
{
	print '
	<div class="page-content-wrapper">
			<div class="page-content">


	     <!-- BEGIN PAGE HEADER-->
	<!-- BEGIN PAGE HEAD -->
	<div class="page-head">
	  <!-- BEGIN PAGE TITLE -->
	<div class="page-title">
	  <h1>'.$langs->trans("MyAccount").' <small>'.$langs->trans("YourPersonalInformation").'</small></h1>
	</div>
	<!-- END PAGE TITLE -->


	</div>
	<!-- END PAGE HEAD -->
	<!-- END PAGE HEADER-->


	<div class="row">
	      <div class="col-md-6">

	        <div class="portlet light">
          <div class="portlet-title">
            <div class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("Organization").'</div>
          </div>
          <div class="portlet-body">
            <form action="'.$_SERVER["PHP_SELF"].'" method="post">
				<input type="hidden" name="action" value="updatemythirdpartyaccount">
				<input type="hidden" name="mode" value="'.dol_escape_htmltag($mode).'">
              <div class="form-body">

                <div class="form-group">
                  <label>'.$langs->trans("NameOfCompany").'</label>
                  <input type="text" class="form-control" placeholder="'.$langs->trans("NameOfYourOrganization").'" value="'.$mythirdpartyaccount->name.'" name="orgName">
                </div>

                <div class="form-group">
                  <label>'.$langs->trans("AddressLine").'</label>
                  <input type="text" class="form-control" placeholder="'.$langs->trans("HouseNumberAndStreet").'" value="'.$mythirdpartyaccount->address.'" name="address">
                </div>
                <div class="form-group">
                  <label>'.$langs->trans("Town").'</label>
                  <input type="text" class="form-control" value="'.$mythirdpartyaccount->town.'" name="town">
                </div>
                <div class="form-group">
                  <label>'.$langs->trans("Zip").'</label>
                  <input type="text" class="form-control input-small" value="'.$mythirdpartyaccount->zip.'" name="zip">
                </div>
                <div class="form-group">
                  <label>'.$langs->trans("State").'</label>
                  <input type="text" class="form-control" placeholder="'.$langs->trans("StateOrCounty").'" name="stateorcounty" value="">
                </div>
                <div class="form-group">
                  <label>'.$langs->trans("Country").'</label><br>';
				$countryselected = $mythirdpartyaccount->country_code;
				print $form->select_country($countryselected, 'country_id', '', 0, 'minwidth300', 'code2', 0);
				print '
                </div>
                <div class="form-group">
                  <label>'.$langs->trans("VATIntra").'</label> ';
				if (! empty($mythirdpartyaccount->tva_assuj) && empty($mythirdpartyaccount->tva_intra))
					{
						print img_warning($langs->trans("WarningMandatorySetupNotComplete"), 'class="hideifnonassuj"');
					}

					$placeholderforvat='';
					if ($mythirdpartyaccount->country_code == 'FR') $placeholderforvat='Exemple: FR12345678';
					elseif ($mythirdpartyaccount->country_code == 'BE') $placeholderforvat='Exemple: BE12345678';
					else $placeholderforvat=$langs->trans("EnterVATHere");

					print '
					<br>
                  <input type="checkbox" style="vertical-align: top" class="inline-block"'.($mythirdpartyaccount->tva_assuj?' checked="checked"':'').'" id="vatassuj" name="vatassuj"> '.$langs->trans("VATIsUsed").'
					<br>
                  <input type="text" class="input-small quatrevingtpercent hideifnonassuj" value="'.$mythirdpartyaccount->tva_intra.'" name="vatnumber" placeholder="'.$placeholderforvat.'">
                </div>
              </div>
              <!-- END FORM BODY -->

              <div>
                <input type="submit" name="submit" value="'.$langs->trans("Save").'" class="btn green-haze btn-circle">
              </div>

            </form>
            <!-- END FORM DIV -->
          </div> <!-- END PORTLET-BODY -->
        </div>

	    </div>
		';

		print '<script type="text/javascript" language="javascript">
		jQuery(document).ready(function() {
			jQuery("#vatassuj").click(function() {
				console.log("Click on vatassuj "+jQuery("#vatassuj").is(":checked"));
				jQuery(".hideifnonassuj").hide();
				jQuery(".hideifnonassuj").show();
			});
		});
		</script>';

		print '

	      <div class="col-md-6">

			<div class="portlet light">
	          <div class="portlet-title">
	            <div class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("YourContactInformation").'</div>
	          </div>
	          <div class="portlet-body">
	            <form action="'.$_SERVER["PHP_SELF"].'" method="post">
				<input type="hidden" name="action" value="updatemythirdpartylogin">
				<input type="hidden" name="mode" value="'.dol_escape_htmltag($mode).'">
	              <div class="form-body">
	                <div class="form-group">
	                  <label>'.$langs->trans("Email").'</label>
	                  <input type="text" class="form-control" value="'.$mythirdpartyaccount->email.'" name="email">
	                </div>
	                <div class="row">
	                  <div class="col-md-6">
	                    <div class="form-group">
	                      <label>'.$langs->trans("Firstname").'</label> ';
							if (empty($mythirdpartyaccount->array_options['options_firstname'])) print img_warning($langs->trans("WarningMandatorySetupNotComplete"));
						print '
							<br>
	                      <input type="text" class="inline-block" value="'.$mythirdpartyaccount->array_options['options_firstname'].'" name="firstName">
	                    </div>
	                  </div>
	                  <div class="col-md-6">
	                    <div class="form-group">
	                      <label>'.$langs->trans("Lastname").'</label> ';
							if (empty($mythirdpartyaccount->array_options['options_lastname'])) print img_warning($langs->trans("WarningMandatorySetupNotComplete"));
						print '<br>
	                      <input type="text" class="inline-block" value="'.$mythirdpartyaccount->array_options['options_lastname'].'" name="lastName">
	                    </div>
	                  </div>
	                </div>
	              </div>
	              <div>
	                <input type="submit" name="submit" value="'.$langs->trans("Save").'" class="btn green-haze btn-circle">
	              </div>
	            </form>


	          </div>
	        </div>


			<div class="portlet light">
	          <div class="portlet-title">
	            <div class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("Password").'</div>
	          </div>
	          <div class="portlet-body">
	            <form action="'.$_SERVER["PHP_SELF"].'" method="post" id="updatepassword">
				<input type="hidden" name="action" value="updatepassword">
				<input type="hidden" name="mode" value="'.dol_escape_htmltag($mode).'">
	              <div class="form-body">
	                <div class="form-group">
	                  <label>'.$langs->trans("Password").'</label>
	                  <input type="password" class="form-control" name="password">
	                </div>
	                <div class="form-group">
	                  <label>'.$langs->trans("RepeatPassword").'</label>
	                  <input type="password" class="form-control" name="password2">
	                </div>
	              </div>
	              <div>
	                <input type="submit" name="submit" value="'.$langs->trans("ChangePassword").'" class="btn green-haze btn-circle">
	              </div>
	            </form>
	          </div>
	        </div>



			';
			if (! GETPOST('deleteaccount')) print '<div class="center"><br><a href="#deletemyaccountarea" class="deletemyaccountclick">'.$langs->trans("DeleteMyAccount").'<br><br></a></div>';

			print '
			<script type="text/javascript" language="javascript">
			jQuery(document).ready(function() {
				';

				if (! GETPOST('deleteaccount')) print 'jQuery("#deletemyaccountarea").hide();';

				print '
				jQuery(".deletemyaccountclick").click(function() {
					console.log("Click on deletemyaccountclick");
					jQuery("#deletemyaccountarea").toggle();
					jQuery(".deletemyaccountclick").toggle();
				});
			});
			</script>

			<div class="portlet light deletemyaccountarea" id="deletemyaccountarea">
	          <div class="portlet-title">
	            <div class="caption-subject font-green-sharp bold uppercase">'.$langs->trans("DeleteMyAccount").'</div>
	          </div>
	          <div class="portlet-body">
							<form class="form-group" action="'.$_SERVER["PHP_SELF"].'" method="POST">
				              <div class="">
				                <p class="opacitymedium" style="padding: 5px">
				                    ';
									if (($nbofinstancesinprogressreseller + $nbofinstancesdonereseller + $nbofinstancessuspendedreseller) > 0)
									{
										print $langs->trans("ClosingAccountResellerNotPossible", ($nbofinstancesinprogressreseller + $nbofinstancesdonereseller + $nbofinstancessuspendedreseller), $langs->transnoentities("MyInstances"), $langs->transnoentities("DangerZone")).'<br>';
									}
									elseif (($nbofinstancesinprogress + $nbofinstancesdone + $nbofinstancessuspended) > 0)
				                    {
				                    	print $langs->trans("ClosingAccountNotPossible", ($nbofinstancesinprogress + $nbofinstancesdone + $nbofinstancessuspended), $langs->transnoentities("MyInstances"), $langs->transnoentities("DangerZone")).'<br>';
				                    }
				                    else
				                    {
				                    	print $langs->trans("PleaseBeSureCustomerAccount", $contract->ref_customer);
					                    print '
						                </p>
										<p class="center" style="padding-bottom: 15px">
											<input type="text" class="center urlofinstancetodestroy" name="accounttodestroy" value="'.GETPOST('accounttodestroy','alpha').'" placeholder="">
										</p>
										<p class="center">
											<input type="hidden" name="mode" value="myaccount"/>
											<input type="hidden" name="action" value="deleteaccount" />
											<input type="submit" class="btn btn-danger" name="deleteaccount" value="'.$langs->trans("DeleteMyAccount").'">
										';
				                    }
				                print '</p>
				              </div>
							</form>
				</div>
			</div>


	      </div><!-- END COL -->

	    </div> <!-- END ROW -->


	    </div>
		</div>
	';
}



print '
	</div>






	<!-- Bootstrap core JavaScript
	================================================== -->
	<!-- Placed at the end of the document so the pages load faster -->
	<script src="dist/js/popper.min.js"></script>
	<script src="dist/js/bootstrap.min.js"></script>
	<!--
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.13.0/umd/popper.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-beta.2/js/bootstrap.min.js"></script>
	-->

	</body>
</html>
';

llxFooter();

$db->close();
