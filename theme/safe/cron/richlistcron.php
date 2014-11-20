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
if ($_SERVER['argv']['1'] == "--cron") {
	$addresses = mysqli_query($abedatabase, "SELECT pubkey_hash FROM pubkey");

	$address_list = array();
	while ($addr = mysqli_fetch_array($addresses)) {
		$bal = get_hash_balance($abedatabase, $addr['pubkey_hash']);
		$address_list[$bal] = array("hash" => $addr['pubkey_hash'], "balance" => $bal);
	}

	$total_omc = get_total_blocks($abedatabase) * 66.85;
	krsort($address_list);
	$x = 0;
	foreach ($address_list as $addr) {
		$x++;
		mysqli_query($database, "UPDATE richlist SET date = '" . date("y-m-d H:i:s") . "', address = '" . hash_to_address($addr['hash']) . "', balance = '" . format_satoshi($addr['balance']) . "', percent = '" . ((format_satoshi($addr['balance']) / $total_omc) * 100) . "' WHERE rank = '" . $x . "'");
		if ($x > 9999) {
			break;
		}
	}
}

function get_hash_balance($database, $hash) {
	$address_txs = mysqli_query($database, "SELECT a.tx_id, a.txin_id, b.block_nTime, b.block_height, 'in' AS 'type', a.tx_hash, a.tx_pos, -a.txin_value AS 'value' FROM txin_detail AS a JOIN block AS b ON (b.block_id = a.block_id) WHERE a.pubkey_hash = '" . $hash . "' UNION SELECT a.tx_id, a.txout_id, b.block_nTime, b.block_height, 'out' AS 'type', a.tx_hash, a.tx_pos, a.txout_value AS 'value' FROM txout_detail AS a JOIN block AS b ON (b.block_id = a.block_id) WHERE a.pubkey_hash = '" . $hash . "' ORDER BY tx_id");
	$balance = 0;
	while ($tx = mysqli_fetch_array($address_txs)) {
		$balance += $tx['value'];
	}
	return $balance;
}
?>