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

$good = false;

$lastblock = get_total_blocks($abedatabase);

if (isset($_GET['l']) && is_string($_GET['l'])) {
	$size = preg_replace('/[^0-9]/', '', $_GET['l']);
	if ($size != "") {
		if ($size > 100) {
			$size = 100;
		}
		if ($size < 1) {
			$size = 1;
		}
	} else {
		$size = 10;
	}
} else {
	$size = 10;
}

if (isset($_GET['s']) && is_string($_GET['s'])) {
	$start = preg_replace('/[^0-9]/', '', $_GET['s']);
	if ($start != "") {
		if ($start > $lastblock - $size) {
			$start = $lastblock - $size + 1;
		}
		if ($start < 0) {
			$start = 0;
		}
	} else {
		$start = $lastblock - $size + 1;
	}
} else {
	$start = $lastblock - $size + 1;
}

$search_error = false;

$is_address = false;
$address = false;
$address_valid = false;

$is_block = false;
$block = false;
$block_valid = false;

$is_transaction = false;
$transaction = false;
$transaction_valid = false;

$title = false;
if (isset($_GET['search']) && is_string($_GET['search'])) {
	$search = preg_replace('/[^0-9A-Za-z]/', '', $_GET['search']);
	$title = "Search: " . $search;
	
	$blockbyheight = mysqli_query($abedatabase, "SELECT b.block_hash FROM block AS b JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE b.block_height = '" . $search . "' AND cc.in_longest = 1");
	if ($wallet->validateaddress($search)['isvalid']) {
		$_GET['address'] = $search;
	} else if (mysqli_query($abedatabase, "SELECT b.block_hash FROM block AS b JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE b.block_hash = '" . $search . "' AND cc.in_longest = 1")->num_rows == 1) {
		$_GET['block'] = $search;
	} else if (mysqli_query($abedatabase, "SELECT t.tx_hash FROM tx AS t JOIN block_tx AS bt ON (bt.tx_id = t.tx_id) JOIN chain_candidate AS cc ON (cc.block_id = bt.block_id) WHERE t.tx_hash = '" . $search . "' AND cc.in_longest = 1")->num_rows == 1) {
		$_GET['transaction'] = $search;
	} else if (is_numeric($search) && $blockbyheight->num_rows == 1) {
		$_GET['block'] = mysqli_fetch_array($blockbyheight)['block_hash'];
	} else {
		$search_error = true;
	}
}

if (isset($_GET['address']) && is_string($_GET['address'])) {
	$is_address = true;
	$address = preg_replace('/[^0-9A-Za-z]/', '', $_GET['address']);
	if (is_address_valid($address)) {
		$address_valid = true;
		$title = "Address: " . $address;
	}
}

if (isset($_GET['block']) && is_string($_GET['block'])) {
	$is_block = true;
	$blockhash = preg_replace('/[^0-9A-Za-z]/', '', $_GET['block']);
	$block = mysqli_query($abedatabase, "SELECT c.block_id, c.block_hash, c.block_hashMerkleRoot, c.block_nTime, c.block_nBits, c.block_height, c.prev_block_hash, c.block_value_out, c.block_value_in, c.block_total_seconds, c.block_num_tx FROM chain_summary AS c JOIN chain_candidate AS cc ON (cc.block_id = c.block_id) WHERE c.block_hash = '" . $blockhash . "' AND cc.in_longest = 1");
	if ($block && $block->num_rows == 1) {
		$block = mysqli_fetch_array($block);
		$block_valid = true;
		$title = "Block: " . $block['block_height'];
		
		$addr = hash_to_address(mysqli_fetch_array(mysqli_query($abedatabase, "SELECT pubkey_hash FROM txout_detail WHERE block_id = '" . $block['block_id'] . "' LIMIT 1"))['pubkey_hash']);
		$finder = mysqli_query($database, "SELECT label, pool_url FROM claimed_addresses WHERE address = '" . $addr . "'");
		if ($finder->num_rows == 1) {
			$label = mysqli_fetch_array($finder);
			if ($label['pool_url'] == "") {
				$finder = "<a href='?address=" . hash_to_address($block['pubkey_hash']) . "'>" . $label['label'] . "</a>";
			} else {
				$finder = "<a href='" . $label['pool_url'] . "' target='_blank'>" . $label['label'] . "</a>";
			}
		} else {
			$finder = "<a href='?address=" . hash_to_address($block['pubkey_hash']) . "'>" . substr(hash_to_address($block['pubkey_hash']), 0, 20) . "...</a>";
		}
	}
}

if (isset($_GET['transaction']) && is_string($_GET['transaction'])) {
	$is_transaction = true;
	$transactionhash = preg_replace('/[^0-9A-Za-z]/', '', $_GET['transaction']);
	$transaction = mysqli_query($abedatabase, "SELECT t.tx_id, t.tx_hash, t.tx_size FROM tx AS t JOIN block_tx AS bt ON (bt.tx_id = t.tx_id) JOIN chain_candidate AS cc ON (cc.block_id = bt.block_id) WHERE t.tx_hash = '" . $transactionhash . "' AND cc.in_longest = 1");
	if ($transaction && $transaction->num_rows == 1) {
		$transaction = mysqli_fetch_array($transaction);
		$transaction_valid = true;
		$title = "Transaction: " . $transaction['tx_hash'];
	}
}
if ($title) {
	get_header($pages, $currentpage, "Block Explorer", $title);
} else {
	get_header($pages, $currentpage, "Block Explorer", "OmniCoin Block Explorer");
}
?>
<div class="container">
	<?php
	if ($search_error) {
		?>
		<div class="alert alert-danger"><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Error: <?php echo $search; ?> is not a valid OmniCoin address, block number, block hash, or transaction ID.</div>
		<?php
	}
	
	if ($is_address) {
		if ($address_valid) {
			$good = true;
			$address_txs = mysqli_query($abedatabase, "SELECT a.tx_id, a.txin_id, b.block_nTime, b.block_height, b.block_hash, 'in' AS 'type', a.tx_hash, a.tx_pos, -a.txin_value AS 'value' FROM txin_detail AS a JOIN block AS b ON (b.block_id = a.block_id) JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE a.pubkey_hash = '" . address_to_hash($address) . "' AND cc.in_longest = 1 UNION SELECT a.tx_id, a.txout_id, b.block_nTime, b.block_height, b.block_hash, 'out' AS 'type', a.tx_hash, a.tx_pos, a.txout_value AS 'value' FROM txout_detail AS a JOIN block AS b ON (b.block_id = a.block_id) JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE a.pubkey_hash = '" . address_to_hash($address) . "' AND cc.in_longest = 1 ORDER BY tx_id");
			$balance = 0;
			$total_in = 0;
			$total_out = 0;
			$tx_in = 0;
			$tx_out = 0;
			$txs = array();
			while ($tx = mysqli_fetch_array($address_txs)) {
				if (abs($tx['value']) >= 1000000) {
					$balance += $tx['value'];
					/*
					if ($tx['value'] > 0) {
						$total_in += $tx['value'];
					} else {
						$total_out -= $tx['value'];
					}
					*/
					if (!isset($txs[$tx['tx_hash']])) {
						$txs[$tx['tx_hash']] = array("block" => $tx['block_height'], "block_hash" => $tx['block_hash'], "time" => $tx['block_nTime'], "confirmations" => $lastblock - $tx['block_height'] + 1, "tx_hash" => $tx['tx_hash'], "value" => $tx['value'], "balance" => $balance);
						/*
						if ($tx['value'] > 0) {
							$tx_in++;
						} else {
							$tx_out++;
						}
						*/
					} else {
						$txs[$tx['tx_hash']]['value'] += $tx['value'];
						$txs[$tx['tx_hash']]['balance'] = $balance;
					}
				}
			}
			foreach ($txs as &$tx) {									
				if ($tx['value'] > 0) {
					$total_in += $tx['value'];
					$tx_in++;
				} else {
					$total_out -= $tx['value'];
					$tx_out++;
				}
			}
			?>
			<h2 class="hidden-xs">Address <small><?php echo $address; ?></small></h2>
			<table class="table table-striped">
				<?php
				$vanity = mysqli_query($database, "SELECT label, pool_url, hf_uid, hf_uid_confirmed FROM claimed_addresses WHERE address = '" . $address . "'");
				$richlist = mysqli_query($database, "SELECT a.rank FROM richlist WHERE a.address = '" . $address . "'");
				if ($vanity->num_rows == 1) {
					$vanity = mysqli_fetch_array($vanity);
					if ($vanity['label'] != null) {
						?>
						<tr>
							<td>Vanity Label</td>
							<td><?php echo $vanity['label']; ?></td>
						</tr>
						<?php
						if ($vanity['pool_url'] != "") {
						?>
						<tr>
							<td>Pool URL</td>
							<td><a href="<?php echo $vanity['pool_url']; ?>"><?php echo $vanity['pool_url']; ?></a></td>
						</tr>
						<?php
						}
						if ($vanity['hf_uid_confirmed']) {
						?>
						<tr>
							<td>HackForums UID</td>
							<td><a href="http://www.hackforums.net/member.php?action=profile&uid=<?php echo $vanity['hf_uid']; ?>"><?php echo $vanity['hf_uid']; ?></a></td>
						</tr>
						<?php
						}
					}
				}
				if ($richlist['rank'] != null) {
					$richlist = mysqli_fetch_array($vanity);
					?>
					<tr>
						<td>Rank</td>
						<td><?php echo $richlist['rank']; ?></td>
					</tr>
					<?php
				}					
				?>
				<tr>
					<td>Address</td>
					<td><span class="hidden-xs"><?php echo $address; ?></span><span class="visible-xs"><?php echo substr($address, 0, 18); ?>...</span></td>
				</tr>
				<tr>
					<td>Public Key Hash</td>
					<td><span class="hidden-xs"><?php echo address_to_hash($address); ?></span><span class="visible-xs"><?php echo substr(address_to_hash($address), 0, 18); ?>....</span></td>
				</tr>
				<tr>
					<td>Transactions In</td>
					<td><?php echo format_num($tx_in); ?></td>
				</tr>
				<tr>
					<td>Transactions Out</td>
					<td><?php echo format_num($tx_out); ?></td>
				</tr>
				<tr>
					<td>Total In</td>
					<td><?php echo format_num(format_satoshi($total_in)); ?> OMC</td>
				</tr>
				<tr>
					<td>Total Out</td>
					<td><?php echo format_num(format_satoshi($total_out)); ?> OMC</td>
				</tr>
				<tr>
					<td>Balance</td>
					<td><?php echo format_num(format_satoshi($total_in - $total_out)); ?> OMC</td>
				</tr>
			</table>
			<h3>Transactions</h3>
			<table id="transaction-list" class="table table-striped">
				<tr>
					<th>Transaction</th>
					<th class="hidden-xs">Block</th>
					<th class="hidden-xs">Date</th>
					<th class="hidden-xs">Confirmations</th>
					<th>Amount</th>
					<th>Balance</th>
				</tr>
				<?php
				$txs = array_reverse($txs);
				foreach ($txs as $tx) {
					?>
					<tr>
						<td><a class="visible-lg" href='/?transaction=<?php echo $tx['tx_hash']; ?>'><?php echo $tx['tx_hash']; ?></a><a class="hidden-lg hidden-xs" href='/?transaction=<?php echo $tx['tx_hash']; ?>'><?php echo substr($tx['tx_hash'], 0, 20); ?>...</a><a class="visible-xs" href='/?transaction=<?php echo $tx['tx_hash']; ?>'><?php echo substr($tx['tx_hash'], 0, 5); ?>...</a></td>
						<td class="hidden-xs"><a href="/?block=<?php echo $tx['block_hash']; ?>"><?php echo $tx['block']; ?></a></td>
						<td class="hidden-xs"><?php echo date("y-m-d H:i:s", $tx['time']); ?></td>
						<td class="hidden-xs"><?php echo $tx['confirmations']; ?></td>
						<td style='color:<?php echo $tx['value'] >= 0 ? "green" : "red"; ?>;'><?php echo format_num(format_satoshi($tx['value'])); ?> OMC</td>
						<td><?php echo format_num(format_satoshi($tx['balance'])); ?> OMC</td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
		} else {
			?>
			<div class="alert alert-danger">Error: <?php echo $address; ?> is not a valid OmniCoin address.</div>
			<?php
		}
	} else if ($is_block) {
		if ($block_valid) {
			$good = true;
			?>
			<h2 class="hidden-xs">Block <small><?php echo $block['block_hash']; ?></small></h2>
			<table class="table table-striped">
				<tr>
					<td>Finder</td>
					<td><?php echo $finder; ?></td>
				</tr>
				<tr>
					<td>Hash</td>
					<td><span class="hidden-xs"><?php echo $block['block_hash']; ?></span><span class="visible-xs"><?php echo substr($block['block_hash'], 0, 18); ?>...</span></td>
				</tr>
				<tr>
					<td>Merkle Root</td>
					<td><span class="hidden-xs"><?php echo $block['block_hashMerkleRoot']; ?></span><span class="visible-xs"><?php echo substr($block['block_hashMerkleRoot'], 0, 18); ?>...</span></td>
				</tr>
				<tr>
					<td>Height</td>
					<td><?php echo $block['block_height']; ?></td>
				</tr>
				<tr>
					<td>Difficulty</td>
					<td><?php echo calculate_difficulty($block['block_nBits']); ?></td>
				</tr>
				<tr>
					<td>Time</td>
					<td><?php echo date("y-m-d H:i:s", $block['block_nTime']); ?></td>
				</tr>
				<tr>
					<td>Age</td>
					<td><?php echo format_time($block['block_nTime']); ?></td>
				</tr>
				<?php
				if (isset($block['prev_block_hash'])) {
					$prev_block = mysqli_fetch_array(mysqli_query($abedatabase, "SELECT c.block_nTime FROM chain_summary AS c JOIN chain_candidate AS cc ON (cc.block_id = c.block_id) WHERE c.block_hash = '" . $block['prev_block_hash'] . "' AND cc.in_longest = 1"));
					?>
					<tr>
						<td>Time to Mine</td>
						<td><?php echo format_time($prev_block['block_nTime'], $block['block_nTime']); ?></td>
					</tr>
					<?php
				}
				?>
				<tr>
					<td>Transactions</td>
					<td><?php echo format_num($block['block_num_tx']); ?></td>
				</tr>
				<tr>
					<td>Total Sent</td>
					<td><?php echo format_num(format_satoshi($block['block_value_out'])); ?> OMC</td>
				</tr>
				<?php 
				if (isset($block['prev_block_hash'])) {
				?>
					<tr>
						<td>Previous Block</td>
						<td><a class="hidden-xs" href="/?block=<?php echo $block['prev_block_hash']; ?>"><?php echo $block['prev_block_hash']; ?></a><a class="visible-xs" href="/?block=<?php echo $block['prev_block_hash']; ?>"><?php echo substr($block['prev_block_hash'], 0, 18); ?>...</a></td>
					</tr>
				<?php
				}
				$next_block = mysqli_query($abedatabase, "SELECT c.block_hash FROM chain_summary AS c JOIN chain_candidate AS cc ON (cc.block_id = c.block_id) WHERE c.prev_block_hash = '" . $block['block_hash'] . "' AND cc.in_longest = 1");
				if ($next_block && $next_block->num_rows == 1) {
					$next_block = mysqli_fetch_array($next_block);
				?>
					<tr>
						<td>Next Block</td>
						<td><a class="hidden-xs" href="/?block=<?php echo $next_block['block_hash']; ?>"><?php echo $next_block['block_hash']; ?></a><a class="visible-xs" href="/?block=<?php echo $next_block['block_hash']; ?>"><?php echo substr($next_block['block_hash'], 0, 18); ?>...</a></td>
					</tr>
				<?php
				}
				?>
			</table>
			<h3>Transactions</h3>
			<?php
			$tx_ids = array();
			$txs = array();
			$tx_outs = mysqli_query($abedatabase, "SELECT t.tx_id, t.tx_hash, t.tx_size, t.txout_value, t.pubkey_hash FROM txout_detail AS t JOIN chain_candidate AS cc ON (cc.block_id = t.block_id) WHERE t.block_id = '" . $block['block_id'] . "' AND cc.in_longest = 1 ORDER BY t.tx_pos, t.txout_pos");
			while ($tx_out = mysqli_fetch_array($tx_outs)) {
				if (!isset($txs[$tx_out['tx_id']])) {
					$tx_ids[] = $tx_out['tx_id'];
					$txs[$tx_out['tx_id']] = array("hash" => $tx_out['tx_hash'], "total_out" => 0, "total_in" => 0, "out" => array(), "in" => array(), "size" => $tx_out['tx_size']);
				}
				$txs[$tx_out['tx_id']]['total_out'] += $tx_out['txout_value'];
				$txs[$tx_out['tx_id']]['out'][] = array("value" => $tx_out['txout_value'], "pubkey_hash" => $tx_out['pubkey_hash']);
			}
			$tx_ins = mysqli_query($abedatabase, "SELECT t.tx_id, t.txin_value, t.pubkey_hash FROM txin_detail AS t JOIN chain_candidate AS cc ON (cc.block_id = t.block_id) WHERE t.block_id = '" . $block['block_id'] . "' AND cc.in_longest = 1 ORDER BY t.tx_pos, t.txin_pos");
			while ($tx_in = mysqli_fetch_array($tx_ins)) {
				$txs[$tx_in['tx_id']]['total_in'] += $tx_in['txin_value'];
				$txs[$tx_in['tx_id']]['in'][] = array("value" => $tx_in['txin_value'], "pubkey_hash" => $tx_in['pubkey_hash']);
			}
			foreach ($tx_ids as $txid) {
				$tx = $txs[$txid];
				$coinbase = $txid == $tx_ids[0];
				?>
				<div class="well">
					<table class="table table-striped">
						<tr>
							<td colspan="2"><b><a class="visible-lg pull-left" href="/?transaction=<?php echo $tx['hash']; ?>"><?php echo $tx['hash']; ?></a><a class="visible-xs pull-left" href="/?transaction=<?php echo $tx['hash']; ?>"><?php echo substr($tx['hash'], 0, 10); ?>...</a><a class="visible-md visible-sm pull-left" href="/?transaction=<?php echo $tx['hash']; ?>"><?php echo substr($tx['hash'], 0, 40); ?>...</a><span class="pull-right">(<span class="hidden-xs"><?php echo $tx['size'] . " bytes"; ?> </span><?php echo date("y-m-d H:i:s", $block['block_nTime']); ?>)</span></b></td>
						</tr>
						<tr>
							<td class="hidden-xs">
								<?php
								if ($coinbase) {
									$gen = $block['block_value_out'] - $block['block_value_in'];
									$fees = $tx['total_out'] - $gen;
									echo "Block reward (" . format_satoshi($gen) . " OMC + " . format_satoshi($fees) . " OMC mining fee)";
								} else {
									foreach ($tx['in'] as $txin) {
										?>
										<div class="visible-lg"><span class="glyphicon glyphicon-minus" style="color:#D9534F;"></span> <a href="/?address=<?php echo hash_to_address($txin['pubkey_hash']); ?>"><?php echo hash_to_address($txin['pubkey_hash']); ?></a> (<?php echo format_num(format_satoshi($txin['value'])); ?> OMC)<br></div>
										<div class="visible-md"><span class="glyphicon glyphicon-minus" style="color:#D9534F;"></span> <a href="/?address=<?php echo hash_to_address($txin['pubkey_hash']); ?>"><?php echo substr(hash_to_address($txin['pubkey_hash']), 0, 20); ?>...</a> (<?php echo format_num(format_satoshi($txin['value'])); ?> OMC)<br></div>
										<div class="visible-sm"><span class="glyphicon glyphicon-minus" style="color:#D9534F;"></span> <a href="/?address=<?php echo hash_to_address($txin['pubkey_hash']); ?>"><?php echo substr(hash_to_address($txin['pubkey_hash']), 0, 10); ?>...</a> (<?php echo format_num(format_satoshi($txin['value'])); ?> OMC)<br></div>
										<?php
									}
								}
								?>
							</td>
							<td style="text-align:right;">
								<?php
								foreach ($tx['out'] as $txout) {
									?>
									<div class="visible-lg visible-md"><span class="glyphicon glyphicon-plus" style="color:#5CB85C"></span> <a href="/?address=<?php echo hash_to_address($txout['pubkey_hash']); ?>"><?php echo hash_to_address($txout['pubkey_hash']); ?></a> (<?php echo format_num(format_satoshi($txout['value'])); ?> OMC)<br></div>
									<div class="visible-sm"><span class="glyphicon glyphicon-plus" style="color:#5CB85C"></span> <a href="/?address=<?php echo hash_to_address($txout['pubkey_hash']); ?>"><?php echo substr(hash_to_address($txout['pubkey_hash']), 0, 20); ?>...</a> (<?php echo format_num(format_satoshi($txout['value'])); ?> OMC)<br></div>
									<div class="visible-xs"><span class="glyphicon glyphicon-plus" style="color:#5CB85C"></span> <a href="/?address=<?php echo hash_to_address($txout['pubkey_hash']); ?>"><?php echo substr(hash_to_address($txout['pubkey_hash']), 0, 10); ?>...</a> (<?php echo format_num(format_satoshi($txout['value'])); ?> OMC)<br></div>
									<?php
								}
								?>
							</td>
						</tr>
					</table>
					<?php
					$confirmations = $lastblock - $block['block_height'] + 1;
					if ($confirmations >= 3) {
						$confstatus = "success";
					} else if ($confirmations == 2) {
						$confstatus = "warning";
					} else {
						$confstatus = "danger";
					}
					?>
					<div class="btn btn-<?php echo $confstatus; ?>">
						<span class="glyphicon glyphicon-saved"></span>
						<?php echo format_num($confirmations) . " Confirmation" . format_s($confirmations); ?>
					</div>
					<div class="btn btn-primary">
						<?php
							echo format_num(format_satoshi($tx['total_out'])) . " OMC";
						?>
					</div>
				</div>
			<?php
			}
		} else {
			?>
			<div class="alert alert-danger">Error: <?php echo $blockhash; ?> is not a valid OmniCoin block hash.</div>
			<?php
		}
	} else if ($is_transaction) {
		if ($transaction_valid) {
			$good = true;
			$containing_block = mysqli_fetch_array(mysqli_query($abedatabase, "SELECT a.block_nTime, a.block_hash, a.block_height, a.block_value_in, a.block_value_out FROM chain_summary a JOIN block_tx b ON (b.block_id = a.block_id) JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE b.tx_id = '" . $transaction['tx_id'] . "' AND cc.in_longest = 1"));
			?>
			<h2 class="hidden-xs">Transaction <small><?php echo $transaction['tx_hash']; ?></small></h2>
			<table class="table table-striped">
				<tr>
					<td>Transaction ID</td>
					<td><span class="hidden-xs"><?php echo $transaction['tx_hash']; ?></span><span class="visible-xs"><?php echo substr($transaction['tx_hash'], 0, 18); ?>...</span></td>
				</tr>
				<tr>
					<td>Time</td>
					<td><?php echo date("y-m-d H:i:s", $containing_block['block_nTime']); ?></td>
				</tr>
				<tr>
					<td>Age</td>
					<td><?php echo format_time($containing_block['block_nTime']); ?></td>
				</tr>
				<tr>
					<td>Size</td>
					<td><?php echo format_num($transaction['tx_size']); ?> Bytes</td>
				</tr>
				<tr>
					<td>Block</td>
					<td><a href="/?block=<?php echo $containing_block['block_hash']; ?>"><?php echo $containing_block['block_height']; ?></a></td>
				</tr>
				<tr>
					<td>Confirmations</td>
					<?php
					$confirmations = $lastblock - $containing_block['block_height'] + 1;
					?>
					<td><?php echo format_num($confirmations); ?></td>
				</tr>
			</table>
			<?php
			$tx = array("total_out" => 0, "total_in" => 0, "out" => array(), "in" => array());
			$coinbase = false;
			$tx_outs = mysqli_query($abedatabase, "SELECT t.txout_value, t.pubkey_hash, t.tx_pos FROM txout_detail AS t JOIN chain_candidate AS cc ON (cc.block_id = t.block_id) WHERE t.tx_id = '" . $transaction['tx_id'] . "' AND cc.in_longest = 1 ORDER BY t.tx_pos, t.txout_pos");
			while ($tx_out = mysqli_fetch_array($tx_outs)) {
				$coinbase = $tx_out['tx_pos'] == 0;
				$tx['total_out'] += $tx_out['txout_value'];
				$tx['out'][] = array("value" => $tx_out['txout_value'], "pubkey_hash" => $tx_out['pubkey_hash']);
			}
			$tx_ins = mysqli_query($abedatabase, "SELECT t.txin_value, t.pubkey_hash FROM txin_detail AS t JOIN chain_candidate AS cc ON (cc.block_id = t.block_id) WHERE t.tx_id = '" . $transaction['tx_id'] . "' AND cc.in_longest = 1 ORDER BY t.tx_pos, t.txin_pos");
			while ($tx_in = mysqli_fetch_array($tx_ins)) {
				$tx['total_in'] += $tx_in['txin_value'];
				$tx['in'][] = array("value" => $tx_in['txin_value'], "pubkey_hash" => $tx_in['pubkey_hash']);
			}
			?>
			<div class="well">
				<table class="table table-striped">
					<tr>
						<td colspan="2"><b><a class="visible-lg pull-left" href="/?transaction=<?php echo $transaction['tx_hash']; ?>"><?php echo $transaction['tx_hash']; ?></a><a class="visible-xs pull-left" href="/?transaction=<?php echo $transaction['tx_hash']; ?>"><?php echo substr($transaction['tx_hash'], 0, 10); ?>...</a><a class="visible-md visible-sm pull-left" href="/?transaction=<?php echo $transaction['tx_hash']; ?>"><?php echo substr($transaction['tx_hash'], 0, 40); ?>...</a><span class="pull-right">(<span class="hidden-xs"><?php echo format_num($transaction['tx_size']) . " bytes"; ?> </span><?php echo date("y-m-d H:i:s", $containing_block['block_nTime']); ?>)</span></b></td>
					</tr>
					<tr>
						<td class="hidden-xs">
							<?php
							if ($coinbase) {
								$gen = $block['block_value_out'] - $block['block_value_in'];
								$fees = $tx['total_out'] - $gen;
								echo "Block reward (" . format_satoshi($gen) . " OMC + " . format_satoshi($fees) . " OMC mining fee)";
							} else {
								foreach ($tx['in'] as $txin) {
									?>
									<div class="visible-lg"><span class="glyphicon glyphicon-minus" style="color:#D9534F;"></span> <a href="/?address=<?php echo hash_to_address($txin['pubkey_hash']); ?>"><?php echo hash_to_address($txin['pubkey_hash']); ?></a> (<?php echo format_num(format_satoshi($txin['value'])); ?> OMC)<br></div>
									<div class="visible-md"><span class="glyphicon glyphicon-minus" style="color:#D9534F;"></span> <a href="/?address=<?php echo hash_to_address($txin['pubkey_hash']); ?>"><?php echo substr(hash_to_address($txin['pubkey_hash']), 0, 20); ?>...</a> (<?php echo format_num(format_satoshi($txin['value'])); ?> OMC)<br></div>
									<div class="visible-sm"><span class="glyphicon glyphicon-minus" style="color:#D9534F;"></span> <a href="/?address=<?php echo hash_to_address($txin['pubkey_hash']); ?>"><?php echo substr(hash_to_address($txin['pubkey_hash']), 0, 10); ?>...</a> (<?php echo format_num(format_satoshi($txin['value'])); ?> OMC)<br></div>
									<?php
								}
							}
							?>
						</td>
						<td style="text-align:right;">
							<?php
							foreach ($tx['out'] as $txout) {
								?>
								<div class="visible-lg visible-md"><span class="glyphicon glyphicon-plus" style="color:#5CB85C"></span> <a href="/?address=<?php echo hash_to_address($txout['pubkey_hash']); ?>"><?php echo hash_to_address($txout['pubkey_hash']); ?></a> (<?php echo format_num(format_satoshi($txout['value'])); ?> OMC)<br></div>
								<div class="visible-sm"><span class="glyphicon glyphicon-plus" style="color:#5CB85C"></span> <a href="/?address=<?php echo hash_to_address($txout['pubkey_hash']); ?>"><?php echo substr(hash_to_address($txout['pubkey_hash']), 0, 20); ?>...</a> (<?php echo format_num(format_satoshi($txout['value'])); ?> OMC)<br></div>
								<div class="visible-xs"><span class="glyphicon glyphicon-plus" style="color:#5CB85C"></span> <a href="/?address=<?php echo hash_to_address($txout['pubkey_hash']); ?>"><?php echo substr(hash_to_address($txout['pubkey_hash']), 0, 10); ?>...</a> (<?php echo format_num(format_satoshi($txout['value'])); ?> OMC)<br></div>
								<?php
							}
							?>
						</td>
					</tr>
				</table>
				<?php
				$confirmations = $lastblock - $containing_block['block_height'] + 1;
				if ($confirmations >= 3) {
					$confstatus = "success";
				} else if ($confirmations == 2) {
					$confstatus = "warning";
				} else {
					$confstatus = "danger";
				}
				?>
				<div class="btn btn-<?php echo $confstatus; ?>">
					<span class="glyphicon glyphicon-saved"></span>
					<?php echo $confirmations . " Confirmation" . format_s($confirmations); ?>
				</div>
				<div class="btn btn-primary">
					<?php
						echo format_satoshi($tx['total_out']) . " OMC";
					?>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="alert alert-danger">Error: <?php echo $transactionhash; ?> is not a valid OmniCoin transaction ID.</div>
			<?php
		}
	}
	if ($good != true) {
	?>
		<div class="well" style="max-width:420px; margin:0 auto;">
			<h3>Search</h3>
			<p>Search by address, block number, block hash, or transaction ID.</p>
			<form class="form-inline" method="get" action="/">
				<div class="input-group">
					<input class="form-control" name="search" type="text" placeholder="Address / Block / Hash / Transaction ID" autofocus>
					<span class="input-group-btn">
						<button class="btn btn-primary" type="submit">
							<span class="glyphicon glyphicon-search"></span> Search
						</button>
					</span>
				</div>
			</form>
		</div>
		<ul class="pager">
			<li class="hidden-xs previous<?php if ($start == 0) { echo " disabled"; } ?>"><a href="/?l=<?php echo $size; ?>&s=0">&larr; Oldest</a></li>
			<li class="previous<?php if ($start == 0) { echo " disabled"; } ?>"><a href="/?l=<?php echo $size; ?>&s=<?php echo $start - $size; ?>">&larr; Older</a></li>

			<li class="hidden-xs<?php if ($size == 10) { echo " disabled"; } ?>"><a href="/?l=10&s=<?php echo $start; ?>">10</a></li>
			<li class="hidden-xs<?php if ($size == 20) { echo " disabled"; } ?>"><a href="/?l=20&s=<?php echo $start; ?>">20</a></li>
			<li class="hidden-xs<?php if ($size == 50) { echo " disabled"; } ?>"><a href="/?l=50&s=<?php echo $start; ?>">50</a></li>
			<li class="hidden-xs<?php if ($size == 100) { echo " disabled"; } ?>"><a href="/?l=100&s=<?php echo $start; ?>">100</a></li>

			<li class="hidden-xs next<?php if ($start + $size - 1 == $lastblock) { echo " disabled"; } ?>"><a href="/?l=<?php echo $size; ?>&s=<?php echo $lastblock - $size; ?>">Newest &rarr;</a></li>
			<li class="next<?php if ($start + $size - 1 == $lastblock) { echo " disabled"; } ?>"><a href="/?l=<?php echo $size; ?>&s=<?php echo $start + $size; ?>">Newer &rarr;</a></li>
		</ul>

		<table class="table table-striped" style="margin-top: 10px;" >
			<tr>
				<th>Height</th>
				<th>Age</th>
				<th>Difficulty</th>
				<th class="hidden-xs">Finder</th>
				<th class="hidden-xs">Transactions</th>
				<th class="hidden-xs">Total Sent</th>
			</tr>
			<?php
			$blocks_query = mysqli_query($abedatabase, "SELECT b.block_id, b.block_hash, b.block_height, b.block_nTime, b.block_nBits, b.block_num_tx, b.block_value_out FROM chain_summary AS b JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE b.block_height >= " . $start . " AND b.block_height <= " . ($start + $size) . " ORDER BY cc.block_height DESC LIMIT 0, " . $size);
			$blocks = array();
			while ($block = mysqli_fetch_array($blocks_query)) {
				$blocks[] = $block;
			}
			foreach ($blocks as $block) {
				$addr = hash_to_address(mysqli_fetch_array(mysqli_query($abedatabase, "SELECT pubkey_hash FROM txout_detail WHERE block_id = '" . $block['block_id'] . "' LIMIT 1"))['pubkey_hash']);
				$finder = mysqli_query($database, "SELECT label, pool_url FROM claimed_addresses WHERE address = '" . $addr . "'");
				
				if ($finder->num_rows == 1) {
					$label = mysqli_fetch_array($finder);
					if ($label['pool_url'] == "") {
						$finder = "<a href='?address=" . hash_to_address($block['pubkey_hash']) . "'>" . $label['label'] . "</a>";
					} else {
						$finder = "<a href='" . $label['pool_url'] . "' target='_blank'>" . $label['label'] . "</a>";
					}
				} else {
					$finder = "<a href='?address=" . hash_to_address($block['pubkey_hash']) . "'>" . substr(hash_to_address($block['pubkey_hash']), 0, 20) . "...</a>";
				}
				?>
				<tr>
					<td><a href="/?block=<?php echo $block['block_hash']; ?>"><?php echo $block['block_height']; ?></a></td>
					<td><?php echo format_time($block['block_nTime']); ?></td>
					<td><span class="hidden-xs"><?php echo format_num(calculate_difficulty($block['block_nBits'])); ?></span><span class="visible-xs"><?php echo round(calculate_difficulty($block['block_nBits']), 5); ?></span></td>
					<td class="hidden-xs"><?php echo $finder; ?></td>
					<td class="hidden-xs"><?php echo format_num($block['block_num_tx']); ?></td>
					<td class="hidden-xs"><?php echo format_num(format_satoshi($block['block_value_out'])); ?> OMC</td>
				</tr>
				<?php
			}
			?>
		</table>

		<ul class="pager">
			<li class="hidden-xs previous<?php if ($start == 0) { echo " disabled"; } ?>"><a href="/?l=<?php echo $size; ?>&s=0">&larr; Oldest</a></li>
			<li class="previous<?php if ($start == 0) { echo " disabled"; } ?>"><a href="/?l=<?php echo $size; ?>&s=<?php echo $start - $size; ?>">&larr; Older</a></li>

			<li class="hidden-xs<?php if ($size == 10) { echo " disabled"; } ?>"><a href="/?l=10&s=<?php echo $start; ?>">10</a></li>
			<li class="hidden-xs<?php if ($size == 20) { echo " disabled"; } ?>"><a href="/?l=20&s=<?php echo $start; ?>">20</a></li>
			<li class="hidden-xs<?php if ($size == 50) { echo " disabled"; } ?>"><a href="/?l=50&s=<?php echo $start; ?>">50</a></li>
			<li class="hidden-xs<?php if ($size == 100) { echo " disabled"; } ?>"><a href="/?l=100&s=<?php echo $start; ?>">100</a></li>

			<li class="hidden-xs next<?php if ($start + $size - 1 == $lastblock) { echo " disabled"; } ?>"><a href="/?l=<?php echo $size; ?>&s=<?php echo $lastblock - $size; ?>">Newest &rarr;</a></li>
			<li class="next<?php if ($start + $size - 1 == $lastblock) { echo " disabled"; } ?>"><a href="/?l=<?php echo $size; ?>&s=<?php echo $start + $size; ?>">Newer &rarr;</a></li>
		</ul>
		<?php
		}
	?>
</div>
<?php
get_footer();
?>