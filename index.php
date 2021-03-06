<?php
/* Copyright (c) 2014 by the Omnicoin Team.
This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>. */

define("API_VERSION", "0.8.0");

require_once('/var/www/omnicha.in/theme/safe/functions.php');
require_once('/var/www/omnicha.in/theme/safe/header.php');
require_once('/var/www/omnicha.in/theme/safe/footer.php');

$pages = array();
$pages[] = array("id" => 0, 	"url" => array(array(""), array("chain", "omnicoin"), array("address", "*"), array("tx", "*"), array("block", "*")),		"navtitle" => "Block Explorer", 	"navbar" => true,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/home.php");
$pages[] = array("id" => 2, 	"url" => array(array("wallet")), 							"navtitle" => "Wallet",				"navbar" => true,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/wallet.php");
$pages[] = array("id" => 3, 	"url" => array(array("wallet", "tos")), 					"navtitle" => "Wallet TOS",			"navbar" => false,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/wallet_tos.php");
$pages[] = array("id" => 4, 	"url" => array(array("pool")), 								"navtitle" => "Pool",				"navbar" => true,	"force_ssl" => true, 	"label" => "",			"filepath" => "");
$pages[] = array("id" => 1, 	"url" => array(array("stats")), 							"navtitle" => "Stats",				"navbar" => true,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/stats.php");
$pages[] = array("id" => 5, 	"url" => array(array("charts")), 							"navtitle" => "Charts",				"navbar" => true,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/charts.php");
$pages[] = array("id" => 7, 	"url" => array(array("api")), 								"navtitle" => "API",				"navbar" => true,	"force_ssl" => false, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/api.php");
$pages[] = array("id" => 6, 	"url" => array(array("richlist")), 							"navtitle" => "Rich List",			"navbar" => true,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/richlist.php");
$pages[] = array("id" => 65, 	"url" => array(array("richlist", "update")), 				"navtitle" => "",					"navbar" => false,	"force_ssl" => false, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/cron/richlistcron.php");
$pages[] = array("id" => 8, 	"url" => array(array("claimaddress")), 						"navtitle" => "Claim Address",		"navbar" => true,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/claimaddress.php");
//$pages[] = array("id" => 9, 	"url" => array(array("exchange")), 							"navtitle" => "Exchange",			"navbar" => false,	"force_ssl" => true, 	"label" => "New",		"filepath" => "/var/www/omnicha.in/theme/safe/pages/exchange.php");
$pages[] = array("id" => 10, 	"url" => array(array("tools")), 							"navtitle" => "Tools",				"navbar" => true,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/tools.php");
$pages[] = array("id" => 11, 	"url" => array(array("sitemap.xml")), 						"navtitle" => "",					"navbar" => false,	"force_ssl" => false, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/sitemap.php");
$pages[] = array("id" => 12, 	"url" => array(array("gettingstarted"), array("help")), 	"navtitle" => "Getting Started",	"navbar" => false,	"force_ssl" => true, 	"label" => "",			"filepath" => "/var/www/omnicha.in/theme/safe/pages/gettingstarted.php");


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
		$bad = false;
		for ($x = 0; $x < count($urls) || $x < count($url); $x++) {
			if (count($urls) > $x && ((count($url) > $x && $urls[$x] == $url[$x]) || $urls[$x] == "*")) {
				continue;
			} else {
				$bad = true;
				break;
			}
		}
		if ($bad) {
			continue;
		}
		if ($page['force_ssl']) {
			if ($_SERVER['HTTPS'] != "on") {
				$url = "https://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
				header("Location: $url");
				exit;
			}
		}
		$currentpage = $page;
		require_once($page['filepath']);
		$four04 = false;
		break 2;
	}
}
if ($four04) {
	require_once("/var/www/omnicha.in/theme/safe/pages/404.php");
}
?>
