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

require_once('/var/www/omnicha.in/theme/safe/wallet.php');
require_once('/var/www/omnicha.in/theme/recaptchalib.php');
$publicKey = "6LfrmfYSAAAAAH7wVXhIZ9ORnz1gFqD3Dl7wjjpc";
$privateKey = "****************************************";

if (isset($_GET['method']) && is_string($_GET['method'])) {
	$error = true;
	$error_message = "Unknown error";
	$response = array();
	if ($_GET['method'] == "getcharts") {
		$error = false;
		$btc_usd_price = get_option($database, "btc_usd_price");
		$graph_data_query = mysqli_query($database, "SELECT * FROM 24h_graph");
		$difficulty = array();
		$btc_price = array();
		$usd_price = array();
		$volume = array();
		$transactions = array();
		$transaction_volume = array();
		$block_time = array();
		$hashrate = array();
		$coins_mined = array();
		$lifetime_coins_mined = array();
		$lifetime_transactions = array();
		$lifetime_transactions_volume = array();
		$response = array("difficulty" => array(), "btc_price" => array(), "usd_price" => array(), "volume" => array(), "transactions" => array(), "transaction_volume" => array(), "block_time" => array(), "hashrate" => array(), "coins_mined" => array(), "lifetime_coins_mined" => array(), "lifetime_transactions" => array(), "lifetime_transactions_volume" => array());
		while ($day = mysqli_fetch_array($graph_data_query)) {
			$response['difficulty'][] = doubleval($day['avg_difficulty']);
			$response['btc_price'][] = doubleval($day['avg_price']);
			$response['usd_price'][] = doubleval($day['avg_price'] * $btc_usd_price);
			$response['volume'][] = doubleval($day['volume_per_day']);
			$response['transactions'][] = doubleval($day['transactions']);
			$response['transaction_volume'][] = doubleval($day['transaction_volume_per_day']);
			$response['block_time'][] = doubleval($day['avg_block_time']);
			$response['hashrate'][] = doubleval($day['avg_hash_per_day']);
			$response['coins_mined'][] = doubleval($day['coins_mined_per_day']);
			$response['lifetime_coins_mined'][] = doubleval($day['coins_mined']);
			$response['lifetime_transactions'][] = doubleval($day['total_transactions']);
			$response['lifetime_transactions_volume'][] = doubleval($day['transaction_volume']);
		}
	} else if ($_GET['method'] == "getrichlist") {
		$error = false;
		$response['last_update'] = mysqli_fetch_array(mysqli_query($database, "SELECT date FROM richlist LIMIT 0, 1"))['date'];
		$response['richlist'] = array();
		
		$rich_list = mysqli_query($database, "SELECT b.label, a.address, a.balance, a.rank, a.percent FROM richlist AS a left JOIN claimed_addresses AS b ON (b.address = a.address) ORDER BY a.id LIMIT 0, 25");
		while ($richie = mysqli_fetch_array($rich_list)) {
			$response['richlist'][] = array("rank" => $richie['rank'], "address" => $richie['address'], "vanity_name" => $richie['label'] == null ? "" : $richie['label'], "balance" => doubleval($richie['balance']), "usd_value" => doubleval(omc2usd($omc_usd_price, $richie['balance'], 2)), "percent" => doubleval($richie['percent']));
		}	
	} else if ($_GET['method'] == "wallet_register") {
		if (isset($_GET['username']) && isset($_GET['password']) && isset($_GET['passwordConfirm']) && isset($_GET['recapChallenge']) && isset($_GET['recapResp'])) {
			if (is_string($_GET['username']) && is_string($_GET['password']) && is_string($_GET['passwordConfirm']) && is_string($_GET['recapChallenge']) && is_string($_GET['recapResp'])) {
				if (!recaptcha_check_answer($privateKey, $_SERVER["REMOTE_ADDR"], $_GET['recapChallenge'], $_GET['recapResp'])->is_valid) {
					$error_message = "INVALID_CAPTCHA";
				} else {
					$username = $_GET['username'];
					$password = $_GET['password'];
					$passwordConfirm = $_GET['passwordConfirm'];
				
					$usernameSafe = preg_replace('/[^A-Za-z0-9!@#$%^&*=_+?.-]/', '', $username);
					$passwordSafe = preg_replace('/[^A-Za-z0-9]/', '', $password);
					$passwordConfirmSafe = preg_replace('/[^A-Za-z0-9]/', '', $passwordConfirm);
					
					if ($username == "" || $password == "" || $passwordConfirm == "") {
						$error_message = "EMPTY_REQUIRED_FIELDS";
					} else if ($username != $usernameSafe || strlen($usernameSafe) < 3 || strlen($usernameSafe) > 30) {
						$error_message = "INVALID_USERNAME";
					} else if (mysqli_query($database, "SELECT id FROM users WHERE username = '" . $usernameSafe . "'")->num_rows != 0) {
						$error_message = "USERNAME_TAKEN";
					} else if ($password != $passwordSafe || $passwordConfirm != $passwordConfirmSafe) {
						$error_message = "INVALID_PASSWORD";
					} else if ($passwordSafe != $passwordConfirmSafe) {
						$error_message = "NONMATCHING_PASSWORDS";
					} else {
						$salt = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
						$passwordSalted = hash('sha512', $passwordSafe . $salt);
						
						mysqli_query($database, "INSERT INTO users (username, password, salt) VALUES ('" . $usernameSafe . "', '" . $passwordSalted . "', '" . $salt . "')");
						$error = false;
					}
				}
			}
		}
	} else if ($_GET['method'] == "wallet_login") {
		$login = check_wallet_login(isset($_GET['username']) ? $_GET['username'] : null, isset($_GET['password']) ? $_GET['password'] : null, $_SERVER['REMOTE_ADDR'], $database);
		
		if ($login[0] == "GOOD_LOGIN") {
			$error = false;
			$session = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
			mysqli_query($database, "UPDATE users SET session = '" . $session . "', session_expire_time = '" . date("y-m-d H:i:s") . "' WHERE id = '" . $login[1]['id'] . "'");
			$response['session'] = $session;
		} else {
			$error_message = $login[0];
		}
	} else if ($_GET['method'] == "wallet_getinfo") {
		$login = check_wallet_login(isset($_GET['username']) ? $_GET['username'] : null, isset($_GET['password']) ? $_GET['password'] : null, $_SERVER['REMOTE_ADDR'], $database, true);
		
		if ($login[0] == "GOOD_LOGIN") {
			$lastblock = get_total_blocks($abedatabase);
			$response['tx_out'] = 0;
			$response['total_out'] = 0;
			$response['tx_in'] = 0;
			$response['total_in'] = 0;
			$response['balance'] = 0;
			$response['pending_balance'] = 0;
			$response['transactions'] = array();
			$response['addresses'] = array();
			$txs = array();
			foreach ($wallet->getaddressesbyaccount($login[1]['username']) as $address) {
				$address_txs = mysqli_query($abedatabase, "SELECT a.tx_id, a.txin_id, b.block_nTime, b.block_height, b.block_hash, 'in' AS 'type', a.tx_hash, a.tx_pos, -a.txin_value AS 'value' FROM txin_detail AS a JOIN block AS b ON (b.block_id = a.block_id) JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE a.pubkey_hash = '" . address_to_hash($address) . "' AND cc.in_longest = 1 UNION SELECT a.tx_id, a.txout_id, b.block_nTime, b.block_height, b.block_hash, 'out' AS 'type', a.tx_hash, a.tx_pos, a.txout_value AS 'value' FROM txout_detail AS a JOIN block AS b ON (b.block_id = a.block_id) JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) WHERE a.pubkey_hash = '" . address_to_hash($address) . "' AND cc.in_longest = 1 ORDER BY tx_id");
				while ($tx = mysqli_fetch_array($address_txs)) {
					if (abs($tx['value']) >= 1000000) {
						$response['balance'] += $tx['value'];
						/*
						if ($tx['value'] > 0) {
							$response['total_in'] += $tx['value'];
						} else {
							$response['total_out'] -= $tx['value'];
						}
						*/
						if (!isset($txs[$tx['tx_hash']])) {
							$txs[$tx['tx_hash']] = array("date" => date("y-m-d H:i:s", $tx['block_nTime']), "confirmations" => $lastblock - $tx['block_height'] + 1, "tx_hash" => $tx['tx_hash'], "value" => $tx['value'], "balance" => 0);
							/*
							if ($tx['value'] > 0) {
								$response['tx_in']++;
							} else {
								$response['tx_out']++;
							}
							*/
						} else {
							$txs[$tx['tx_hash']]['value'] += $tx['value'];
						}
					}
				}
				$response['addresses'][] = array("address" => $address, "private_key" => $wallet->dumpprivkey($address));
			}
			usort($txs, function($a, $b) {
				return $a['confirmations'] - $b['confirmations'];
			});
			$bal = $response['balance'];
			foreach ($txs as &$tx) {
				$tx['balance'] = $bal;
				$bal -= $tx['value'];
									
				if ($tx['value'] > 0) {
					$response['total_in'] += $tx['value'];
					$response['tx_in']++;
				} else {
					$response['total_out'] -= $tx['value'];
					$response['tx_out']++;
				}
			}
			$response['transactions'] = $txs;
			
			$adrses = $wallet->getaddressesbyaccount($login[1]['username']);		
			$input_total = 0;
			$input_pending_total = 0;
			if (!empty($adrses)) {
				$unspent = $wallet->listunspent(0, 9999999, $adrses);
				foreach ($unspent as $input) {
					$input_pending_total += $input['amount'];
					if ($input['confirmations'] >= 1) {
						$input_total += $input['amount'];
					} else {
						$good = true;
						$in = $wallet->getrawtransaction($input['txid'], 1);
						foreach ($in['vin'] as $tx) {
							$in2 = $wallet->getrawtransaction($tx['txid'], 1);
							foreach ($in2['vout'] as $tx2) {
								if ($tx2['n'] == $tx['vout']) {
									foreach ($tx2['scriptPubKey']['addresses'] as $adr) {
										if ($wallet->getaccount($adr) != $login[1]['username']) {
											$good = false;
										}
									}
								}
							}
						}
						if ($good) {
							$input_total += $input['amount'];
						}
					}
				}
			}
			$response['balance'] = $input_total;
			$response['pending_balance'] = $input_pending_total - $input_total;
			$response['omc_usd_price'] = doubleval(omc2usd($omc_usd_price, 1));
			$error = false;
			/*
			$response['tx_out'] = 0;
			$response['total_out'] = 0;
			$response['tx_in'] = 0;
			$response['total_in'] = 0;
			$response['balance'] = 0;
			$response['pending_balance'] = 0;
			$response['transactions'] = array();
			
			$txs = array();
			$transactions = $wallet->listtransactions($login[1]['username'], 9999);
			foreach($transactions as $tx) {
				$txs[] = $tx;
				if ($tx['category'] == "move") {
					continue;
				}
				if ($tx['category'] == "send") {
					if ($tx['confirmations']) {
						$response['tx_out'] ++;
						$response['total_out'] += -$tx['amount'] - $tx['fee'];
					}
				} else {
					if ($tx['confirmations'] >= 1) {
						$response['tx_in'] ++;
						$response['total_in'] += $tx['amount'];
					}
				}
			}
			
			$response['balance'] = $wallet->getbalance($login[1]['username'], 1);
			$response['pending_balance'] = $wallet->getbalance($login[1]['username'], 0) - $wallet->getbalance($login[1]['username'], 1);

			$balance = $wallet->getbalance($login[1]['username'], 0);
			$txs = array_reverse($txs);
			foreach ($txs as $tx) {
				if ($tx['category'] == "move") {
					continue;
				}
				$txinfo = $tx['txid'];
				$confs = $tx['confirmations'];

					
				$transaction = array("type" => $tx['category'], "txinfo" => $txinfo, "date" => date("y-m-d h:m:s", ($tx['category'] == "move" ? $tx['time'] : $tx['timereceived'])), "confirmations" => $confs, "amount" => $tx['amount'], "balance" => $balance);

				$balance -= $tx['amount'] + ($tx['category'] == "send" ? $tx['fee'] : 0);
				$response['transactions'][] = $transaction;
			}
			$response['addresses'] = $wallet->getaddressesbyaccount($login[1]['username']);
			$error = false;
			*/
		} else {
			$error_message = $login[0];
		}
	} else if ($_GET['method'] == "wallet_genaddr") {
		$login = check_wallet_login(isset($_GET['username']) ? $_GET['username'] : null, isset($_GET['password']) ? $_GET['password'] : null, $_SERVER['REMOTE_ADDR'], $database, true);
		if ($login[0] == "GOOD_LOGIN") {
			if ((strtotime(date("y-m-d H:i:s")) - strtotime($login[1]['last_new_address'])) >= (60 * 60)) {
				$error = false;
				$address = $wallet->getnewaddress($login[1]['username']);
				$response['address'] = $address;
				mysqli_query($database, "UPDATE users SET last_new_address = '" . date("y-m-d H:i:s") . "' WHERE id = '" . $login[1]['id'] . "'");
			}
		} else {
			$error_message = $login[0];
		}
	} else if ($_GET['method'] == "wallet_send") {
		$login = check_wallet_login(isset($_GET['username']) ? $_GET['username'] : null, isset($_GET['password']) ? $_GET['password'] : null, $_SERVER['REMOTE_ADDR'], $database, true);
		if ($login[0] == "GOOD_LOGIN") {
			if (isset($_GET['address']) && isset($_GET['amount']) && is_string($_GET['address']) && is_string($_GET['amount'])) {
				$amount = $_GET['amount'];
				$address = $_GET['address'];

				$amount_safe = preg_replace('/[^0-9.]/', '', $amount);
				$address_safe = preg_replace('/[^A-Za-z0-9]/', '', $address);
				
				$error_message = array();

				if ($amount == "" || $address == "") {
					$error_message[] = "EMPTY_REQUIRED_FIELDS";
					if ($amount == "") {
						$error_message[] = "AMOUNT_ERROR";
					}
					if ($address == "") {
						$error_message[] = "ADDRESS_ERROR";
					}
				} else {
					if ($amount != $amount_safe || !is_numeric($amount_safe) || $amount_safe <= 0) {
						$error_message[] = "INVALID_AMOUNT";
					}
					if ($address != $address_safe || !$wallet->validateaddress($address_safe)['isvalid']) {
						$error_message[] = "INVALID_ADDRESS";
					}
					
					$adrses = $wallet->getaddressesbyaccount($login[1]['username']);		
					$input_total = 0;
					$input_pending_total = 0;
					if (!empty($adrses)) {
						$unspent = $wallet->listunspent(0, 9999999, $adrses);
						foreach ($unspent as $input) {
							if ($input['confirmations'] >= 1) {
								$input_total += $input['amount'];
							} else {
								$good = true;
								$in = $wallet->getrawtransaction($input['txid'], 1);
								foreach ($in['vin'] as $tx) {
									$in2 = $wallet->getrawtransaction($tx['txid'], 1);
									foreach ($in2['vout'] as $tx2) {
										if ($tx2['n'] == $tx['vout']) {
											foreach ($tx2['scriptPubKey']['addresses'] as $adr) {
												if ($wallet->getaccount($adr) != $login[1]['username']) {
													$good = false;
												}
											}
										}
									}
								}
								if ($good) {
									$input_total += $input['amount'];
								}
							}
						}
					}
					$balance = $input_total;
					if (empty($error_message)) {
						if ($amount_safe > $balance) {
							$error_message[] = "BROKE";
						} else if ($amount_safe + 0.1 > $balance) {
							$error_message[] = "BROKE_FEE";
						} else {
							$addresses = $wallet->getaddressesbyaccount($login[1]['username']);
							$unspent = array();
							if (!empty($addresses)) {
								foreach ($wallet->listunspent(0, 9999999, $addresses) as $input) {
									if ($input['confirmations'] >= 1) {
										$unspent[] = $input;
									} else {
										$good = true;
										$in = $wallet->getrawtransaction($input['txid'], 1);
										foreach ($in['vin'] as $tx) {
											$in2 = $wallet->getrawtransaction($tx['txid'], 1);
											foreach ($in2['vout'] as $tx2) {
												if ($tx2['n'] == $tx['vout']) {
													foreach ($tx2['scriptPubKey']['addresses'] as $adr) {
														if ($wallet->getaccount($adr) != $login[1]['username']) {
															$good = false;
														}
													}
												}
											}
										}
										if ($good) {
											$unspent[] = $input;
										}
									}
								}
							}
							usort($unspent, function($a, $b) {
								return $a['confirmations'] - $b['confirmations'];
							});
							$input_total = 0;
							$inputs = array();
							foreach ($unspent as $input) {
								$input_total += $input['amount'];
								$inputs[] = array("txid" => $input['txid'], "vout" => $input['vout']);
								if ($input_total >= $amount_safe + 0.1) {
									break;
								}
							}

							$transaction = $wallet->createrawtransaction($inputs, array($address_safe => doubleval($amount_safe), $addresses[0] => doubleval($input_total - $amount_safe - 0.1)));
							$signed_transaction = $wallet->signrawtransaction($transaction);
							if ($signed_transaction != false && $signed_transaction['complete'] == true) {
								$wallet->sendrawtransaction($signed_transaction['hex']);
								$wallet->move($login[1]['username'], "", $input_total);
								$error = false;
								$response['amount'] = $amount_safe;
								$response['address'] = $address_safe;
							}
							
							/*
							$addresses = $wallet->getaddressesbyaccount($login[1]['username']);
							$unspent = $wallet->listunspent(0);

							$inputs = array();
							foreach ($unspent as $input) {
								if (!in_array($input['address'], $addresses)) {
									$inputs[] = $input;
								}
							}

							$wallet->settxfee(0.1);
							$wallet->lockunspent(false, $inputs);

							$send = $wallet->sendfrom($login[1]['username'], $address_safe, doubleval($amount_safe), 1);
							if ($send) {
								$error = false;
								$response['amount'] = $amount_safe;
								$response['address'] = $address_safe;
							}

	
							
							$wallet->lockunspent(true, $inputs);
							*/
						}
					}
				}
			}
		} else {
			$error_message = $login[0];
		}
	} else if ($_GET['method'] == "wallet_importkey") {
		$login = check_wallet_login(isset($_GET['username']) ? $_GET['username'] : null, isset($_GET['password']) ? $_GET['password'] : null, $_SERVER['REMOTE_ADDR'], $database, true);
		if ($login[0] == "GOOD_LOGIN") {
			if (isset($_GET['privkey']) && is_string($_GET['privkey'])) {
				$privkey = $_GET['privkey'];

				$privkey_safe = preg_replace('/[^A-Za-z0-9]/', '', $privkey);
				
				//$resp = $wallet->importprivkey($privkey_safe, $login[1]['username']);
				//if ($resp) {
				//	$error = false;
				//}
			}
		} else {
			$error_message = $login[0];
		}
	}
	
	
	
	else if ($_GET['method'] == "getbalance") {
		if (isset($_GET['address']) && is_string($_GET['address'])) {
			$address_safe = $size = preg_replace('/[^A-Za-z0-9]/', '', $_GET['address']);
			if (!$wallet->validateaddress($address_safe)['isvalid']) {
				$error_message = "Invalid address";
			} else {
				$error = false;
				
				$address_txs = mysqli_query($abedatabase, "SELECT a.tx_id, a.txin_id, b.block_nTime, b.block_height, 'in' AS 'type', a.tx_hash, a.tx_pos, -a.txin_value AS 'value' FROM txin_detail AS a JOIN block AS b ON (b.block_id = a.block_id) WHERE a.pubkey_hash = '" . address_to_hash($address_safe) . "' UNION SELECT a.tx_id, a.txout_id, b.block_nTime, b.block_height, 'out' AS 'type', a.tx_hash, a.tx_pos, a.txout_value AS 'value' FROM txout_detail AS a JOIN block AS b ON (b.block_id = a.block_id) WHERE a.pubkey_hash = '" . address_to_hash($address_safe) . "' ORDER BY tx_id");
				$balance = 0;
				while ($tx = mysqli_fetch_array($address_txs)) {
					$balance += $tx['value'];
				}
				$response = array("balance" => format_satoshi(doubleval($balance)));
			}
		} else {
			$error_message = "Address not specified";
		}
	} else if ($_GET['method'] == "checkaddress") {
		if (isset($_GET['address']) && is_string($_GET['address'])) {
			$error = false;
			$address_safe = $size = preg_replace('/[^A-Za-z0-9]/', '', $_GET['address']);
			$response = array("isvalid" => $wallet->validateaddress($address_safe)['isvalid']);
		} else {
			$error_message = "Address not specified";
		}
	} else if ($_GET['method'] == "getinfo") {
		$error = false;
		$mininginfo = $wallet->getmininginfo();
		$lastblock = mysqli_fetch_array(mysqli_query($abedatabase, "SELECT b.block_nTime, b.block_id, b.block_height FROM block AS b JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) AND cc.in_longest = 1 ORDER BY b.block_height DESC LIMIT 0, 1"));
		$time = mysqli_fetch_array(mysqli_query($abedatabase, "SELECT AVG(a.block_nTime-b.block_nTime) AS 'block_time' FROM block AS a, block AS b WHERE a.block_nTime >= " . (strtotime(date("y-m-d H:i:s")) - (60 * 60 * 24)) . " AND a.prev_block_id = b.block_id"));

		$response['block_count'] = doubleval($lastblock['block_height']);
		$response['difficulty'] = doubleval($mininginfo['difficulty']);
		$response["netmhps"] = doubleval($wallet->getnetworkhashps(-1, intval($lastblock['block_height'])) / 1000000);
		$response["seconds_since_block"] = doubleval(time() - $lastblock['block_nTime']);
		$response["avg_block_time"] = doubleval($time['block_time']);
		$response["total_mined_omc"] = doubleval($lastblock['block_height'] * 66.85);
		$response['omc_btc_price'] = doubleval($omc_btc_price);
		$response['omc_usd_price'] = doubleval(omc2usd($omc_usd_price, 1));
		$response['market_cap'] = doubleval(omc2usd($omc_usd_price, $mininginfo['blocks'] * 66.85));
	} else if ($_GET['method'] == "getwstats") {
		$error = false;
		$users = mysqli_query($database, "SELECT username FROM users");
		
		$input_total = 0;
		$unspent = $wallet->listunspent();
		foreach ($unspent as $input) {
			$input_total += $input['amount'];
		}

		$response['users'] = $users->num_rows;
		$response['balance'] = $input_total;
	} else if ($_GET['method'] == "verifymessage") {
		if (isset($_GET['address']) && is_string($_GET['address'])) {
			if (isset($_GET['message']) && is_string($_GET['message'])) {
				if (isset($_GET['signature']) && is_string($_GET['signature'])) {
					$error = false;
					$address_safe = preg_replace('/[^A-Za-z0-9]/', '', $_GET['address']);
					$message_safe = preg_replace('/[^ A-Za-z0-9!@#$%^&*=_+?.\-:]/', '', $_GET['message']);
					$signature_safe = preg_replace('/[^A-Za-z0-9=+-\/]/', '', urldecode($_GET['signature']));

					$response = array("isvalid" => $wallet->verifymessage($address_safe, $signature_safe, $message_safe));
				} else {
					$error_message = "Message not specified";
				}
			} else {
				$error_message = "Message not specified";
			}
		} else {
			$error_message = "Address not specified";
		}
	} else {
		$error_message = "Unknown API method";
	}
	if ($error) {
		echo json_encode(array("error" => $error, "error_info" => $error_message));
	} else {
		echo json_encode(array("error" => $error, "response" => $response));
	}
} else {
	get_header($pages, $currentpage, "API v0.1", "API");
	?>
	<div class="container">
		<h3>API Methods</h3>
		<table class="table table-striped">
			<tr>
				<th>Name</th>
				<th>Description</th>
				<th class="hidden-xs">Arguments</th>
			</tr>
			<tr>
				<td>getinfo</td>
				<td>Get misc information like difficulty, mining speed, and average block time.</td>
				<td class="hidden-xs">/api/?method=getinfo</td>
			</tr>
			<tr>
				<td>getbalance</td>
				<td>Get the balance of a omnicoin address.</td>
				<td class="hidden-xs">/api/?method=getbalance&address=&#60;address&#62;</td>
			</tr>
			<tr>
				<td>checkaddress</td>
				<td>Check whether an address is a valid OMC address.</td>
				<td class="hidden-xs">/api/?method=checkaddress&address=&#60;address&#62;</td>
			</tr>
			<tr>
				<td>verifymessage</td>
				<td>Check whether the signature is as valid hash for the message for the address.</td>
				<td class="hidden-xs">/api/?method=verifymessage&address=&#60;address&#62;&message=&#60;message&#62;&signature=&#60;signature&#62;</td>
			</tr>
			<tr>
				<td>getcharts</td>
				<td>Get data for the charts.</td>
				<td class="hidden-xs">/api/?method=getcharts</td>
			</tr>
			<tr>
				<td>getrichlist</td>
				<td>Get the rich list.</td>
				<td class="hidden-xs">/api/?method=getrichlist</td>
			</tr>
			<tr>
				<td>getwstats</td>
				<td>Get total users and total balance of all online wallet accounts.</td>
				<td class="hidden-xs">/api/?method=getwstats</td>
			</tr>
			<tr>
				<td>wallet_register</td>
				<td>Registers for an online wallet.</td>
				<td class="hidden-xs">/api/?method=wallet_register&username=&#60;username&#62;&password=&#60;encryptedpassword&#62;&passwordConfirm=&#60;encryptedpasswordconfirm&#62;&recapChallenge=&#60;recapchallenge&#62;&recapResp=&#60;recapresp&#62;</td>
			</tr>
			<tr>
				<td>wallet_login</td>
				<td>Logs into an online wallet and gets a session token.</td>
				<td class="hidden-xs">/api/?method=wallet_login&username=&#60;username&#62;&password=&#60;password&#62;</td>
			</tr>
			<tr>
				<td>wallet_getinfo</td>
				<td>Gets addresses, balances, and transactions for an online wallet.</td>
				<td class="hidden-xs">/api/?method=wallet_getinfo&username=&#60;username&#62;&password=&#60;sessiontoken&#62;</td>
			</tr>
			<tr>
				<td>wallet_genaddr</td>
				<td>Generates and adds a new address to an online wallet.</td>
				<td class="hidden-xs">/api/?method=wallet_genaddr&username=&#60;username&#62;&password=&#60;sessiontoken&#62;</td>
			</tr>
			<tr>
				<td>wallet_send</td>
				<td>Sends OMC to an address from an online wallet.</td>
				<td class="hidden-xs">/api/?method=wallet_send&username=&#60;username&#62;&password=&#60;sessiontoken&#62;&address=&#60;address&#62;&amount=&#60;amount&#62;</td>
			</tr>
			<tr>
				<td>wallet_importkey</td>
				<td>Imports a private key into an online wallet. (Currently disabled)</td>
				<td class="hidden-xs">/api/?method=wallet_importkey&username=&#60;username&#62;&password=&#60;sessiontoken&#62;&address=&#60;address&#62;&amount=&#60;amount&#62;</td>
			</tr>
		</table>
		<p>
		All API calls use the endpoint https://www.omnicha.in/api. Any required arguments are sent as HTML GET headers. The response from the server will be in JSON.<br><br>
		
		<div class="panel panel-default">
			<div class="panel-heading">
				PHP Example
			</div>
			<div class="panel-body">
				<pre><code class="language-php">$response = json_decode(file_get_contents("https://omnicha.in/api/?method=getinfo"), true);
if ($response != null) {
	if ($response['error']) {
		echo "Error occurred: " . $response['error_message'];
	} else {
		$info = $response['response'];
		echo "Block Count: " . $info['block_count'] . "&#60;br&#62;";
		echo "Difficulty: " . $info['difficulty'] . "&#60;br&#62;";
		echo "Network MH/s: " . $info['netmhps'] . "&#60;br&#62;";
		echo "Seconds Since Last Block: " . $info['seconds_since_block'] . "&#60;br&#62;";
		echo "24 Hour Average Block Time: " . $info['avg_block_time'] . "&#60;br&#62;";
	}
}</code></pre>
			</div>
		</div>
		</p>
	</div>
	<?php
	get_footer();
}

function check_wallet_login($username, $password, $ip, $database, $session = false) {
	$toReturn = "BAD_LOGIN";
	$userdata = null;
	if (isset($username) && isset($password)) {
		if (is_string($username) && is_string($password)) {
			if (mysqli_query($database, "SELECT id FROM logins WHERE ip = '" . $ip . "' AND good = 0 AND date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->num_rows >= 20) {
				$toReturn = "IP_BANNED";
			} else {
				$username = $username;
				$password = $password;
			
				$usernameSafe = preg_replace('/[^A-Za-z0-9!@#$%^&*=_+?.-]/', '', $username);
				$passwordSafe = preg_replace('/[^A-Za-z0-9]/', '', $password);
				
				if ($username == "" || $password == "" || $username != $usernameSafe || $password != $passwordSafe) {
					$toReturn = "BAD_LOGIN";
				} else {
					if (!$session) {
						$salt = mysqli_query($database, "SELECT salt FROM users WHERE username = '" . $usernameSafe . "'");
						
						if ($salt->num_rows != 1) {
							$toReturn = "BAD_LOGIN";
						} else {
							$salt = mysqli_fetch_array($salt);
							$passwordSalted = hash('sha512', $passwordSafe . $salt['salt']);
							$user = mysqli_query($database, "SELECT id, last_new_address, username FROM users WHERE username = '" . $usernameSafe . "' AND password = '" . $passwordSalted . "'");
							if ($user->num_rows != 1) {
								$toReturn = "BAD_LOGIN";
							} else {
								$toReturn = "GOOD_LOGIN";
								$userdata = mysqli_fetch_array($user);
							}
						}
					} else {
						$user = mysqli_query($database, "SELECT id, username, session_expire_time, last_new_address FROM users WHERE username = '" . $usernameSafe . "' AND session = '" . $passwordSafe . "'");
						
						$user2 = mysqli_fetch_array($user);
						if ($user->num_rows != 1) {
							$toReturn = "BAD_LOGIN";
						} else if ((strtotime(date("y-m-d H:i:s")) - strtotime($user2['session_expire_time'])) >= (60 * 60)){
							$toReturn = "BAD_LOGIN";
						} else {
							$toReturn = "GOOD_LOGIN";
							$userdata = $user2;
						}
					}
				}
			}
			if (!$session) {
				mysqli_query($database, "INSERT INTO logins (username, date, good, ip) VALUES ('" . (isset($usernameSafe) ? $usernameSafe : "") . "' , '" . date("y-m-d H:i:s") . "', '" . ($toReturn == "GOOD_LOGIN") . "', '" . $ip . "')");
			}
		}
	}
	return array($toReturn, $userdata);
}
?>
