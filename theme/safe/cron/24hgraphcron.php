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
	//die();
	$time = strtotime(date("Y-m-d H:i:00"));
	record($time);
}
//record(strtotime("2014-11-13 23:00:00"));
/*
	$start = "2014-11-13 22:30:00";
	
	$end = date("Y-m-d H:i:s");
	
	while (strtotime($start) <= strtotime($end)) {
		record(strtotime($start));
		$start = date("Y-m-d H:i:s", strtotime("+15 minutes", strtotime($start)));
	}
*/
	
	function record($time) {
		global $database, $abedatabase, $wallet;
		$endTime = $time;
		$startTime = $time - (60 * 15);
		
		$difficulty = 0;
		$exchange_price = 0;
		$exchange_volume = 0;
		$tx_num = 0;
		$tx_volume = 0;
		$block_time = 0;
		$hashrate = 0;
		$coins_mined = 0;
		$total_coins_mined = 0;
		$total_tx_num = 0;
		$total_tx_volume = 0;
		
		$prev_diff = 0;
		$prev_time = 0;
		
		$current_stats = mysqli_query($database, "SELECT block_time, difficulty, total_coins_mined, total_tx_num, total_tx_volume FROM charts ORDER BY date DESC LIMIT 1");
		if ($current_stats->num_rows == 1) {
			$x = mysqli_fetch_array($current_stats);
			$prev_diff = $x['difficulty'];
			$prev_time = 0;
			$total_coins_mined = $x['total_coins_mined'];
			$total_tx_num = $x['total_tx_num'];
			$total_tx_volume = $x['total_tx_volume'];
		}

		$blocks = mysqli_query($abedatabase, 
		"SELECT 
					c.block_height, 
					c.block_nBits, 
					c.block_num_tx, 
					c.block_nTime-c2.block_nTime AS 'block_time',
					c.block_value_out 
				FROM chain_summary AS c JOIN chain_candidate AS cc ON (cc.block_id = c.block_id), 
				chain_summary AS c2 JOIN chain_candidate AS cc2 ON (cc2.block_id = c2.block_id) 
			WHERE cc.in_longest = 1 
			AND c2.block_hash = c.prev_block_hash
			AND cc2.in_longest = 1 
			AND c.block_nTime >= '" . $startTime . "' 
			AND c.block_nTime < '" . $endTime . "'");
		$num_blocks = $blocks->num_rows;
		
		$last_height = 0;
		
		if ($num_blocks != 0) {
			while ($block = mysqli_fetch_array($blocks)) {
				$difficulty += calculate_difficulty($block['block_nBits']);
				$tx_num += $block['block_num_tx'] - 1;//Don't include mining reward transaction
				$tx_volume += format_satoshi($block['block_value_out']) - calculate_reward($block['block_height']);
				if ($tx_volume < 0) {
					$tx_volume = 0;
				}
				if ($block['block_time'] != 7836934) {//Don't include gensis block
					$block_time += $block['block_time'];
				}
				$coins_mined += calculate_reward($block['block_height']);
				$last_height = $block['block_height'];
			}
			$difficulty /= $num_blocks;
			$block_time /= $num_blocks;
		} else {
			$difficulty = $prev_diff;
			$block_time = $prev_time;
		}
		
		$hashrate = $wallet->getnetworkhashps(intval($num_blocks), intval($last_height)) / 1000000;
		
		$total_coins_mined += $coins_mined;
		$total_tx_num += $tx_num;
		$total_tx_volume += $tx_volume;
		
		$v0862_percent = 0;
		$v0900_percent = 0;
		
		$peerinfo = $wallet->getpeerinfo();
		
		if ($peerinfo) {
			$total = count($peerinfo);
			
			foreach ($peerinfo as $peer) {
				switch ($peer['subver']) {
					case "/Satoshi:0.8.6.2/":
						$v0862_percent++;
						break;
					case "/Satoshi:0.9.0/":
						$v0900_percent++;
						break;
				}
			}
			if ($total != 0) {
				$v0862_percent = ($v0862_percent / $total) * 100;
				$v0900_percent = ($v0900_percent / $total) * 100;
			}
		}
		
		//mysqli_query($database, "INSERT INTO charts (date, difficulty, exchange_price, exchange_volume, tx_num, tx_volume, block_time, hashrate, coins_mined, total_coins_mined, total_tx_num, total_tx_volume) VALUES ('" . date("Y-m-d H:i:s", $time) . "', '" . $difficulty . "', '" . $exchange_price . "', '" . $exchange_volume . "', '" . $tx_num . "', '" . $tx_volume . "', '" . $block_time . "', '" . $hashrate . "', '" . $coins_mined . "', '" . $total_coins_mined . "', '" . $total_tx_num . "', '" . $total_tx_volume . "')");
				mysqli_query($database, "INSERT INTO charts (date, difficulty, exchange_price, exchange_volume, tx_num, tx_volume, block_time, hashrate, coins_mined, total_coins_mined, total_tx_num, total_tx_volume, v0862, v0900) VALUES ('" . date("Y-m-d H:i:s", $time) . "', '" . $difficulty . "', '" . $exchange_price . "', '" . $exchange_volume . "', '" . $tx_num . "', '" . $tx_volume . "', '" . $block_time . "', '" . $hashrate . "', '" . $coins_mined . "', '" . $total_coins_mined . "', '" . $total_tx_num . "', '" . $total_tx_volume . "', '" . $v0862_percent . "', '" . $v0900_percent . "')");

	}
	
	
	/*
	
	$v0862_percent = 0;
		$v0900_percent = 0;
		
		$peerinfo = $wallet->getpeerinfo()
		
		if ($peerinfo) {
			$total = count($peerinfo);
			
			foreach ($peerinfo as $peer) {
				switch ($peer['subver']) {
					case "/Satoshi:0.8.6.2/":
						$v0862_percent++;
						break;
					case "/Satoshi:0.9.0.0/":
						$v0900_percent++;
						break;
				}
			}
			if ($total != 0) {
				$v0862_percent = ($v0862_percent / $total) * 100;
				$v0900_percent = ($v0900_percent / $total) * 100;
			}
		}
		
		mysqli_query($database, "INSERT INTO charts (date, difficulty, exchange_price, exchange_volume, tx_num, tx_volume, block_time, hashrate, coins_mined, total_coins_mined, total_tx_num, total_tx_volume, v0862, v0900) VALUES ('" . date("Y-m-d H:i:s", $time) . "', '" . $difficulty . "', '" . $exchange_price . "', '" . $exchange_volume . "', '" . $tx_num . "', '" . $tx_volume . "', '" . $block_time . "', '" . $hashrate . "', '" . $coins_mined . "', '" . $total_coins_mined . "', '" . $total_tx_num . "', '" . $total_tx_volume . "', '" . $v0862_percent . "', '" . $v0900_percent . "')");

		
		*/
/*
	$num_blocks = $blocks->num_rows;
	
	$x_diff = 0;
	$x_
	
	while ($block = mysqli_fetch_array($blocks)) {
		$total_dif += calculate_difficulty($block['block_nBits']);
		$transactions += $block['block_num_tx'] - 1;

		if ($block['block_time'] != 7836934) {
			$total_time += $block['block_time'];
		} else {
			$num_blocks--;
		}
		$last_height = $block['block_height'];
		$transaction_volume_per_day += format_satoshi($block['block_value_out']) - 66.85;
	}

	$avg_hash_per_day = $wallet->getnetworkhashps(intval($num_blocks), intval($last_height)) / 1000000;
*/
	/*
	if ($_SERVER['argv']['2'] == null) {
		$total_trades = 0;
		$total_trades_price = 0;
		$total_trades_amount = 0;
		$omc_btc_price = json_decode(file_get_contents('https://www.allcrypt.com/api?method=singlemarketdata&marketid=672'), true);
		if ($omc_btc_price != null) {
			if ($omc_btc_price['success']) {
				foreach ($omc_btc_price['return']['markets']['OMC']['recenttrades'] as $trade) {
					if (date("y-m-d", strtotime($trade['time'])) == date("y-m-d", $startTime)) {
						$total_trades++;
						$total_trades_price += $trade['price'];
						$total_trades_amount += $trade['quantity'];
					}
				}
			} else {
				sleep(20);
				$omc_btc_price = json_decode(file_get_contents('https://www.allcrypt.com/api?method=singlemarketdata&marketid=672'), true);
				if ($omc_btc_price['success']) {
					foreach ($omc_btc_price['return']['markets']['OMC']['recenttrades'] as $trade) {
						if (date("y-m-d", strtotime($trade['time'])) == date("y-m-d", $startTime)) {
							$total_trades++;
							$total_trades_price += $trade['price'];
							$total_trades_amount += $trade['quantity'];
						}
					}
				}
			}
		}
		if ($total_trades != 0) {
			$avg_price = $total_trades_price / $total_trades;
			$volume_per_day = $total_trades_amount / $total_trades;
		}
	}
	*/

	//mysqli_query($database, "INSERT INTO 24h_graph (date, avg_difficulty, coins_mined, transactions, avg_block_time, total_transactions, coins_mined_per_day, avg_hash_per_day, avg_price, volume_per_day, transaction_volume_per_day, transaction_volume) VALUES ('" . $time . "', '" . $avg_difficulty . "', '" . $coins_mined . "', '" . $transactions . "', '" . $avg_block_time . "', '" . $total_transactions . "', '" . $coins_mined_per_day . "', '" . $avg_hash_per_day . "', '" . $avg_price . "', '" . $volume_per_day . "', '" . $transaction_volume_per_day . "', '" . $transaction_volume . "')");
	//mysqli_query($walletdatabase, "UPDATE 24h_graph SET transaction_volume_per_day = '" . $transaction_volume_per_day . "', transaction_volume = '" . $transaction_volume . "' WHERE date = '" . $time . "'");
//}
?>