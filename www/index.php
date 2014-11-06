<?php
require_once('/var/www/omnicha.in/theme/functions.php');
require_once('/var/www/omnicha.in/theme/header.php');
require_once('/var/www/omnicha.in/theme/footer.php');

$pages = array();
//				id				url 														navtitle							navbar 				filepath 
$pages[] = array("id" => 0, 	"url" => array(array(""), array("chain", "omnicoin")),		"navtitle" => "Block Explorer", 	"navbar" => true,	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/pages/home.php");
$pages[] = array("id" => 1, 	"url" => array(array("stats")), 							"navtitle" => "Stats",				"navbar" => true,	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/pages/stats.php");
$pages[] = array("id" => 2, 	"url" => array(array("wallet")), 							"navtitle" => "Wallet",				"navbar" => true,	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/pages/wallet.php");
$pages[] = array("id" => 3, 	"url" => array(array("wallet", "tos")), 					"navtitle" => "Wallet TOS",			"navbar" => false,	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/pages/wallet_tos.php");
$pages[] = array("id" => 4, 	"url" => array(array("charts")), 							"navtitle" => "Charts",				"navbar" => true,	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/pages/charts.php");
$pages[] = array("id" => 5, 	"url" => array(array("richlist")), 							"navtitle" => "Rich List",			"navbar" => true,	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/pages/richlist.php");
$pages[] = array("id" => 6, 	"url" => array(array("api")), 								"navtitle" => "API",				"navbar" => true,	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/pages/api.php");
$pages[] = array("id" => 7, 	"url" => array(array("claimaddress")), 						"navtitle" => "Claim Address",		"navbar" => true,	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/pages/claimaddress.php");


$url = array();

$path = strtolower($_SERVER['REQUEST_URI']);

if (strpos($path, "?") !== false) {
	$path = substr($path, 0, strpos($path, "?"));
}

while (pathinfo($path)['dirname'] != "/") {
	$inf = pathinfo($path);
	$url[] = $inf['filename'] . (array_key_exists("extension", $inf) ? ("." . $inf['extension']) : "");
	$path = $inf['dirname'];
}

$url[] = pathinfo($path)['filename'] . (array_key_exists("extension", pathinfo($path)) ? ("." . pathinfo($path)['extension']) : "");

$url = array_reverse($url);


$currentpage = null;
$four04 = true;
foreach ($pages as &$page) {
	foreach ($page['url'] as $urls) {
		for ($x = 0; $x < count($urls) || $x < count($url); $x++) {
			if (count($urls) > $x && ((count($url) > $x && $urls[$x] == $url[$x]) || $urls[$x] == "*")) {
				continue;
			} else {
				break 2;
			}
		}
		$currentpage = $page;
		include_once($page['filepath']);
		$four04 = false;
		break 2;
	}
}
if ($four04) {
	include_once("/var/www/omnicha.in/theme/pages/404.php");
}
?>
