<?php
include('/var/www/omnicha.in/theme/functions.php');
if ($_SERVER['argv']['1'] == "--cron") {
	$omc_btc_price = json_decode(file_get_contents('https://www.allcrypt.com/api?method=singlemarketdata&marketid=672'), true);
	if ($omc_btc_price != null) {
		if ($omc_btc_price['success']) {
			set_option($database, 1, $omc_btc_price['return']['markets']['OMC']['lasttradeprice']);
		}
	}
	
	$btc_usd_price = json_decode(file_get_contents('https://btc-e.com/api/2/btc_usd/ticker'), true);
	if ($btc_usd_price != null) {
		set_option($database, 2, $btc_usd_price['ticker']['avg']);
	}
}
?>
