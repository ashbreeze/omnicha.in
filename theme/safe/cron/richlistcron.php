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

require_once('/var/www/omnicha.in/theme/safe/functions.php');
//if ($_SERVER['argv']['1'] == "--cron") {
	$addresses = mysqli_query($abedatabase, "SELECT pubkey_hash FROM pubkey");

	$address_list = array();
	while ($addr = mysqli_fetch_array($addresses)) {
		$bal = get_hash_balance($abedatabase, $addr['pubkey_hash']);
		if (!array_key_exists($bal, $address_list)) {
			$address_list[$bal] = array();
		}
		
		$address_list[$bal][] = array("hash" => $addr['pubkey_hash'], "balance" => $bal);
	}

	$lastblock = mysqli_fetch_array(mysqli_query($abedatabase, "SELECT b.block_total_satoshis, b.block_nTime, b.block_id, b.block_height, b.block_nBits FROM block AS b JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) AND cc.in_longest = 1 ORDER BY b.block_height DESC LIMIT 0, 1"));
	
	$total_omc = format_satoshi($lastblock['block_total_satoshis']);
	krsort($address_list);
	$x = 0;
	foreach ($address_list as $addr) {
		foreach ($addr as $ad) {
			$x++;
			mysqli_query($database, "UPDATE richlist SET date = '" . date("y-m-d H:i:s") . "', address = '" . hash_to_address($ad['hash']) . "', balance = '" . format_satoshi($ad['balance']) . "', percent = '" . ((format_satoshi($ad['balance']) / $total_omc) * 100) . "' WHERE rank = '" . $x . "'");
			if ($x > 25) {
				break 2;
			}
		}
	}
//}

function get_hash_balance($database, $hash) {
	$address_txs = mysqli_query($database, "SELECT a.tx_id, a.txin_id, b.block_nTime, b.block_height, b.block_hash, 'in' AS 'type', a.tx_hash, a.tx_pos, -a.txin_value AS 'value' FROM txin_detail AS a JOIN block AS b ON (b.block_id = a.block_id) JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE a.pubkey_hash = '" . $hash . "' AND cc.in_longest = 1 UNION SELECT a.tx_id, a.txout_id, b.block_nTime, b.block_height, b.block_hash, 'out' AS 'type', a.tx_hash, a.tx_pos, a.txout_value AS 'value' FROM txout_detail AS a JOIN block AS b ON (b.block_id = a.block_id) JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE a.pubkey_hash = '" . $hash . "' AND cc.in_longest = 1 ORDER BY tx_id");

	$balance = 0;
	while ($tx = mysqli_fetch_array($address_txs)) {
		$balance += $tx['value'];
	}
	return $balance;
}
?>