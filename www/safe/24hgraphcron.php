<?php
include('/var/www/omnicha.in/theme/functions.php');
include('/var/www/omnicha.in/theme/safe/wallet.php');
if ($_SERVER['argv']['1'] == "--cron") {
	if ($_SERVER['argv']['2'] != null) {
		$time = $_SERVER['argv']['2'];
	} else {
		$time = date("y-m-d");
	}
	
	$startTime = strtotime($time);
	$endTime = strtotime($time) + (60 * 60 * 24);
	$dayBefore = strtotime($time) - (60 * 60 * 24);
	
	

	$avg_difficulty = 0;
	$coins_mined = 0;
	$transactions = 0;
	$transaction_volume_per_day = 0;
	$avg_block_time = 0;
	$total_transactions = 0;
	$transaction_volume = 0;
	$coins_mined_per_day = 0;
	$avg_hash_per_day = 0;
	$avg_price = 0;
	$volume_per_day = 0;
	
	$current_stats = mysqli_query($database, "SELECT total_transactions, transaction_volume FROM 24h_graph WHERE date = '" . date("y-m-d", $dayBefore) . "'");
	if ($current_stats->num_rows != 0) {
		$x = mysqli_fetch_array($current_stats);
		$total_transactions = $x['total_transactions'];
		$transaction_volume = $x['transaction_volume'];
	}
	
	$blocks = mysqli_query($abedatabase, "SELECT a.block_height, a.block_nBits, a.block_num_tx, a.block_nTime-b.block_nTime AS 'block_time', a.block_value_out FROM block AS a, block AS b WHERE a.block_nTime >= " . $startTime . " AND a.block_nTime < " . $endTime . " AND a.prev_block_id = b.block_id");
	$num_blocks = $blocks->num_rows;
	$total_dif = 0;
	$total_time = 0;
	$coins_mined = 0;
	$last_height = 0;
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
	$coins_mined = $last_height * 66.85;
	$avg_hash_per_day = $wallet->getnetworkhashps(intval($num_blocks), intval($last_height)) / 1000000;
	
	$coins_mined_per_day = $num_blocks * 66.85;
	if ($num_blocks != 0) {
		$avg_difficulty = $total_dif / $num_blocks;
		$avg_block_time = $total_time / $num_blocks;
	}
	
	$total_transactions += $transactions;
	$transaction_volume += $transaction_volume_per_day;
	
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

	mysqli_query($database, "INSERT INTO 24h_graph (date, avg_difficulty, coins_mined, transactions, avg_block_time, total_transactions, coins_mined_per_day, avg_hash_per_day, avg_price, volume_per_day, transaction_volume_per_day, transaction_volume) VALUES ('" . $time . "', '" . $avg_difficulty . "', '" . $coins_mined . "', '" . $transactions . "', '" . $avg_block_time . "', '" . $total_transactions . "', '" . $coins_mined_per_day . "', '" . $avg_hash_per_day . "', '" . $avg_price . "', '" . $volume_per_day . "', '" . $transaction_volume_per_day . "', '" . $transaction_volume . "')");
	//mysqli_query($walletdatabase, "UPDATE 24h_graph SET transaction_volume_per_day = '" . $transaction_volume_per_day . "', transaction_volume = '" . $transaction_volume . "' WHERE date = '" . $time . "'");
}
?>
