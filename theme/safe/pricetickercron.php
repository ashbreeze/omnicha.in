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