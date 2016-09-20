<?php

/** TODO 
 * 1. What if user redeem coupon, for existing serviceid and userid in tblcoupons, why is another coupon issued? [DONE]
 * 2. Flash messages? [DONE]
 * 3. What if user allready has maximum number of coupons redeemed? [DONE]
 * 4. What if coupons are gone, nada, no more? [DONE]
 * 5. Disable reedem button if coupon is issued and notify user{SERVER_SIDE} [DONE] in tpl file
 * 6. URL rewriting -> remove index.php part from URL but keep POSTed data {HARD}
 * 7. {BONUS} Slow redirectin if user is not logged-in?
 * 8. Fix paths for sidebar URL's [DONE]
 * 9. Actually fetch userid, not hardcoded version, and replace hcoded version vith var
 * 10. STRANGE TABLE CSS ISSUE WHEN PAGINATING!!!
 * 11. Add some information to adwords page.
 */

/** 
 * WHMCS housekeeping and page initialization
 */
use WHMCS\Database\Capsule;

define("EMPTY_COUPON_TEXT", "Kupon nije izdan!");
 
define("CLIENTAREA", true);

define("OUT_OF_COUPONS_SUPPORT_MAIL", "alan.kish38@gmail.com"); 

define("OUT_OF_COUPONS_MAIL_SUBJECT", "Google AdWords promocija - Korisnička zona");

define("OUT_OF_COUPONS_MAIL_BODY", "Svi Google AdWords kuponi za preuzimanje unutar korisničke zone su iskorišteni!");
 
require("init.php");
 
$ca = new WHMCS_ClientArea();
 
$ca->setPageTitle("Google AdWords promotivna ponuda");
 
$ca->addToBreadCrumb('index.php', Lang::trans('globalsystemname'));
$ca->addToBreadCrumb('mypage.php', 'Google AdWords promotivna ponuda');
 
$ca->initPage();

/**
 * We require user to login, else redirect to login page
 */ 
$ca->requireLogin(); 
 
/**
 * Store underlying connection to $pdo, so we can easily access it later
 */
$pdo = Capsule::connection()->getPdo();

/**
 * Fetch current logged in userid
 */
$userid = $ca->getUserID();

$ca->assign('userid', $userid);

/**
 * Get numbers of issued coupons for specific user
 */
$statement = $pdo->prepare("SELECT COUNT(*)
						   FROM tblcoupons
						   WHERE userid = :userid;
		           ");

$statement->execute(
	[
		':userid' => $userid,
	]
);

$num_of_services_with_coupon = $statement->fetchAll(PDO::FETCH_ASSOC);

$num_of_services_with_coupon = $num_of_services_with_coupon[0]['COUNT(*)'];

/**
 * Asign $num_of_services_with_coupon to $smarty
 */
$ca->assign('num_of_services_with_coupon', $num_of_services_with_coupon);

/**
 * Unset variable so we can reuse it later
 */
unset($statement);


/**
 * Now get number of active services!
 */
$statement = $pdo->prepare("SELECT COUNT(*) FROM 
							tblhosting
							LEFT JOIN tblproducts ON tblhosting.packageid = tblproducts.id
							WHERE tblhosting.userid = :userid
							AND tblhosting.domainstatus = 'Active'
							AND 
							(
							       tblproducts.type = 'hostingaccount'
							    OR tblproducts.type = 'server'
							    OR tblproducts.type  = 'reselleraccount'
							);
					");

$statement->execute(
	[
		':userid' => $userid,
	]
);

$num_of_active_services = $statement->fetchAll(PDO::FETCH_ASSOC);

$num_of_active_services = $num_of_active_services[0]['COUNT(*)'];

unset($statement);

/**
 * Assign $num_of_active_service to $smarty instance
 */
$ca->assign('num_of_active_services', $num_of_active_services);

/** 
 *  User has active hosting services?
 */
$user_has_active_hosting_services = ($num_of_active_services > 0 ? true : false);

$ca->assign('user_has_active_hosting_services', $user_has_active_hosting_services);

if (!$user_has_active_hosting_services)
{
	$flash_message = "Google AdWords promocija je dostupna samo uz hosting usluge!";
	$ca->assign('flash_message', $flash_message);
}

/**
 * Now compare nummber of active service with mumber of related and issued coupons, if 
 * 'num_of_services_with_coupon' < 'num_of_active_services' allow user to fetch coupon
 */
$is_user_allowed_to_fetch_coupons = $num_of_services_with_coupon < $num_of_active_services;

$ca->assign('is_user_allowed_to_fetch_coupons', $is_user_allowed_to_fetch_coupons);

 	
	/**
	 * Get number of available coupons, if < 0, $has_coupons = false
	 */ 
	$statement = $pdo->prepare(
		"SELECT COUNT(*)
		 FROM tblcoupons
		 WHERE status = 'Unused'
		 AND userid IS NULL
		 AND serviceid IS NULL;
	 	");

	$statement->execute();

	$num_of_available_coupons = $statement->fetchAll(PDO::FETCH_ASSOC);

	$num_of_available_coupons = $num_of_available_coupons[0]['COUNT(*)'];

	/**
	 * Assign $num_of_available_coupons to $smarty instance
	 */
	$ca->assign('num_of_available_coupons', $num_of_available_coupons);

 


/** 
 * Auxiliary functions => Mutate result set, if coupon, value is NULL, set CONSTANT
 */
function mutate_array (array $array)
{
	foreach ($array as $arrayitem => &$arrayvalue)
	{
		if ( is_array($arrayvalue) )
			foreach ( $arrayvalue as $k => $v)
			{
				if( $arrayvalue['Coupon'] === NULL)
				{
					//echo "Null Value";
					$arrayvalue['Coupon'] = EMPTY_COUPON_TEXT;
				}
			//echo $arrayvalue[$k];
			}
	}
	return $array;
}


/** 
* Fetch action variables!
*/
//DEBUG ONLY
$ca->assign('posted_value', $_POST);
$post_action = $_POST;


if ($post_action['action'] == 'fetch_coupon' && $post_action['hostingid'] != 0 && $post_action['userid'] != 0)
{
	
	/**
	 * If user has allready redeem all coupons for all active services, notice user about it
	 */	
	if ($is_user_allowed_to_fetch_coupons)
	{
		/**
		 * Is there any available coupons? If not, tell user, and e-mail admin
		 */
		if ($num_of_available_coupons > 0)
		{
			try 
			{
				$statement = $pdo->prepare(
					"UPDATE tblcoupons
					 SET status = 'Used', userid = :userid , serviceid = :hostingid
					 WHERE status = 'Unused' AND userid IS NULL AND serviceid IS NULL
					 LIMIT 1;
					");

				$statement->execute(
					[
						':userid' 		=> $post_action['userid'],
						':hostingid' 	=> $post_action['hostingid'],
					]
				);
			} 
			catch(\Exception $e)
			{
				$pdo_message = $e->getMessage();
			}
		}
		else
		{
			$flash_message = "Nema više raspoloživih Google AdWords kupona! Molimo kontakirajte podršku!";
			$ca->assign('flash_message', $flash_message);
			// Notify support that there is no more AdWords coupons!
			mail(OUT_OF_COUPONS_SUPPORT_MAIL, OUT_OF_COUPONS_MAIL_SUBJECT, OUT_OF_COUPONS_MAIL_BODY);
		}
	}
	else 
	{
		$flash_message = "Iskoristili ste maksimalan broj Google AdWords kupona!";
		$ca->assign('flash_message', $flash_message);
	}
}
		




$ca->assign('pdo_message', $pdo_message);


$needle = "/SQLSTATE\\[23000]:/"; 


if (preg_match($needle, $pdo_message))
{
$flash_message = "Kupon za navedenu uslugu je već izdan!";
$ca->assign('flash_message', $flash_message);
}
//else
//{
//	$flash_message = "...";
//	$ca->assign('flash_message', $flash_message);
//}





$pdo->beginTransaction();

$statement = $pdo->prepare("SELECT tblhosting.id AS HostingID,
								   tblhosting.userid AS UserID,
								   tblhosting.domain AS HostingDomain, 
								   tblcoupons.coupon AS Coupon
						FROM tblhosting
						LEFT JOIN tblcoupons ON tblhosting.id = tblcoupons.serviceid
						LEFT JOIN tblproducts ON tblproducts.id = tblhosting.packageid
						WHERE tblhosting.userid = :userid
						AND tblhosting.domainstatus =  'Active'
						AND (
								tblproducts.type =  'hostingaccount'
								OR tblproducts.type =  'server'
								OR tblproducts.type =  'reselleraccount'
							);"
		 	   );

$statement->execute(
	[
		':userid' => $userid,
	]
);

$result = $statement->fetchAll(PDO::FETCH_ASSOC);

$result = mutate_array($result);

$ca->assign('activeservices', $result);

// Unset statemen object down bellow so it can be reusable
unset($statement);

 /* Set a context for sidebars
 *
 * @link http://docs.whmcs.com/Editing_Client_Area_Menus#Context
 */
Menu::addContext();
 
/**
 * Setup the primary and secondary sidebars
 *
 * @link http://docs.whmcs.com/Editing_Client_Area_Menus#Context
 */
Menu::primarySidebar('clientView');
Menu::secondarySidebar('announcementList');
 
# Define the template filename to be used without the .tpl extension

$ca->setTemplate('adwords');
 
$ca->output();
