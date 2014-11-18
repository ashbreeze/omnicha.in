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
require_once('/var/www/omnicha.in/theme/functions.php');
require_once('/var/www/omnicha.in/theme/recaptchalib.php');
$publicKey = "6LfrmfYSAAAAAH7wVXhIZ9ORnz1gFqD3Dl7wjjpc";
$privateKey = "6LfrmfYSAAAAABJq6Bb2rrgwdkzoAKVj3MwxebdO";

if (isset($_GET['method']) && is_string($_GET['method'])) {
	$error = true;
	$error_message = "Unknown error";
	$response = array();
	if ($_GET['method'] == "getcharts") {
		$zoom = "3600";
		if (isset($_GET['zoom']) && is_string($_GET['zoom'])) {
			$zoom = $_GET['zoom'] == "15m" 	? "900"		 : $zoom;
			$zoom = $_GET['zoom'] == "30m" 	? "1800"	 : $zoom;
			$zoom = $_GET['zoom'] == "1h" 	? "3600"	 : $zoom;
			$zoom = $_GET['zoom'] == "6h" 	? "21600" 	 : $zoom;
			$zoom = $_GET['zoom'] == "12h" 	? "43200" 	 : $zoom;
			$zoom = $_GET['zoom'] == "1d" 	? "86400"	 : $zoom;
		}
		$error = false;
		$btc_usd_price = get_option($database, "btc_usd_price");
		$graph_data_query = mysqli_query($database, "(SELECT id, date, difficulty, exchange_price, exchange_volume, tx_num, tx_volume, block_time, hashrate, coins_mined, total_coins_mined, total_tx_num, total_tx_volume FROM charts WHERE id = 1) UNION (SELECT a.id, a.date, a.difficulty, a.exchange_price, a.exchange_volume, a.tx_num, a.tx_volume, a.block_time, a.hashrate, a.coins_mined, a.total_coins_mined, a.total_tx_num, a.total_tx_volume FROM charts AS a JOIN charts AS b ON(a.id - 1 = b.id) WHERE a.date > (b.date + " . $zoom . ") AND a.id != 1)");
		$response = array("difficulty" => array(), "btc_price" => array(), "usd_price" => array(), "volume" => array(), "transactions" => array(), "transaction_volume" => array(), "block_time" => array(), "hashrate" => array(), "coins_mined" => array(), "lifetime_coins_mined" => array(), "lifetime_transactions" => array(), "lifetime_transactions_volume" => array(), "zoom" => intval($zoom));
		$lastTime = 0;
		while ($day = mysqli_fetch_array($graph_data_query)) {
			$lastTime = strtotime($day['date']);
			$response['difficulty'][] = doubleval($day['difficulty']);
			$response['btc_price'][] = doubleval($day['exchange_price']);
			$response['usd_price'][] = doubleval($day['exchange_price'] * $btc_usd_price);
			$response['volume'][] = doubleval($day['exchange_volume']);
			$response['transactions'][] = intval($day['tx_num']);
			$response['transaction_volume'][] = doubleval($day['tx_volume']);
			$response['block_time'][] = doubleval($day['block_time']);
			$response['hashrate'][] = doubleval($day['hashrate']);
			$response['coins_mined'][] = doubleval($day['coins_mined']);
			$response['lifetime_coins_mined'][] = doubleval($day['total_coins_mined']);
			$response['lifetime_transactions'][] = intval($day['total_tx_num']);
			$response['lifetime_transactions_volume'][] = doubleval($day['total_tx_volume']);
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
					if (abs($tx['value']) >= 1000000) {
						$balance += $tx['value'];
					}
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
		$blockReward = format_satoshi($wallet->getBlockTemplate()['coinbasevalue']);
		$lastblock = mysqli_fetch_array(mysqli_query($abedatabase, "SELECT b.block_total_satoshis, b.block_nTime, b.block_id, b.block_height FROM block AS b JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) AND cc.in_longest = 1 ORDER BY b.block_height DESC LIMIT 0, 1"));
		$time = mysqli_fetch_array(mysqli_query($abedatabase, "SELECT AVG(a.block_nTime-b.block_nTime) AS 'block_time' FROM block AS a, block AS b WHERE a.block_nTime >= " . (strtotime(date("y-m-d H:i:s")) - (60 * 60 * 24)) . " AND a.prev_block_id = b.block_id"));

		$response['block_count'] = intval($lastblock['block_height']);
		$response['difficulty'] = doubleval($mininginfo['difficulty']);
		$response["netmhps"] = doubleval($wallet->getnetworkhashps(-1, intval($lastblock['block_height'])) / 1000000);
		$response["seconds_since_block"] = intval(time() - $lastblock['block_nTime']);
		$response["avg_block_time"] = doubleval($time['block_time']);
		$response["total_mined_omc"] = doubleval(format_satoshi($lastblock['block_total_satoshis']));
		$response['omc_btc_price'] = doubleval($omc_btc_price);
		$response['omc_usd_price'] = doubleval(omc2usd($omc_usd_price, 1));
		$response['market_cap'] = doubleval(omc2usd($omc_usd_price, format_satoshi($lastblock['block_total_satoshis'])));
		$response['block_reward'] = doubleval(calculate_reward($lastblock['block_height']));
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
					$error_message = "Signature not specified";
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
		<div class="alert alert-info">
			Updating the API docs. Some data may be inaccurate or incomplete.
		</div>
		<h3>API Methods</h3>
		<table class="table table-striped">
			<tr>
				<th>Name</th>
				<th>Description</th>
			</tr>
			<tr>
				<td><a href="#getinfo-docs" onClick="$('#panel-1').collapse('show');">getinfo</a></td>
				<td>Get misc information like difficulty, mining speed, and average block time</td>
			</tr>
			<tr>
				<td><a href="#getbalance-docs" onClick="$('#panel-2').collapse('show');">getbalance</a></td>
				<td>Get the balance of an Omnicoin address</td>
			</tr>
			<tr>
				<td><a href="#checkaddress-docs" onClick="$('#panel-3').collapse('show');">checkaddress</a></td>
				<td>Check whether an address is a valid OMC address</td>
			</tr>
			<tr>
				<td><a href="#verifymessage-docs" onClick="$('#panel-4').collapse('show');">verifymessage</a></td>
				<td>Check whether the signature is as valid hash for the message for the address</td>
			</tr>
			<tr>
				<td><a href="#getcharts-docs" onClick="$('#panel-5').collapse('show');">getcharts</a></td>
				<td>Get data for the charts</td>
			</tr>
			<tr>
				<td><a href="#getrichlist-docs" onClick="$('#panel-6').collapse('show');">getrichlist</a></td>
				<td>Get the rich list</td>
			</tr>
			<tr>
				<td><a href="#getwstats-docs" onClick="$('#panel-7').collapse('show');">getwstats</a></td>
				<td>Get total users and total balance of all online wallet accounts</td>
			</tr>
			<?php /* 
			<tr>
				<td><a href="#wallet_register-docs" onClick="$('#panel-8').collapse('show');">wallet_register</a></td>
				<td>Registers for an online wallet</td>
			</tr>
			<tr>
				<td><a href="#wallet_login-docs" onClick="$('#panel-9').collapse('show');">wallet_login</a></td>
				<td>Logs into an online wallet and gets a session token</td>
			</tr>
			<tr>
				<td><a href="#wallet_getinfo-docs" onClick="$('#panel-10').collapse('show');">wallet_getinfo</a></td>
				<td>Gets addresses, balances, and transactions for an online wallet</td>
			</tr>
			<tr>
				<td><a href="#wallet_genaddr-docs" onClick="$('#panel-11').collapse('show');">wallet_genaddr</a></td>
				<td>Generates and adds a new address to an online wallet</td>
			</tr>
			<tr>
				<td><a href="#wallet_send-docs" onClick="$('#panel-12').collapse('show');">wallet_send</a></td>
				<td>Sends OMC to an address from an online wallet</td>
			</tr>
			<tr>
				<td><a href="#wallet_importkey-docs" onClick="$('#panel-13').collapse('show');">wallet_importkey</a></td>
				<td>Imports a private key into an online wallet (Currently disabled)</td>
			</tr>
			*/ ?>
		</table>
		<div class="panel panel-default">
			<div class="panel-heading">
				Base Call
			</div>
			<div class="panel-body">
				<p>
					Every call uses the endpoint <code>https://www.omnicha.in/api/</code>. The API method and all arguments are appended as GET arguments. All responses from the server will be in JSON format.
				</p>
				<p>
					The API will return an array with 2 elements, <code>error</code> and <code>error_info</code> or <code>response</code>. If any error occurred <code>error</code> will equal <code>TRUE</code> and <code>error_info</code> will be populated with the error info. If no error occurred, <code>error</code> will be <code>FALSE</code> and <code>response</code> will contain any returned information from the API call.
				</p>
				<br />
				<p>
					Example API call to <code>https://omnicha.in/api/?method=testcall</code> with error:
					<pre><code class="language-json">{
	"error": true,
	"error_info": "Unknown API method"
}</code></pre>
				</p>
				<br />
				<p>
					Example API call to <code>https://omnicha.in/api/?method=getwstats</code> with no error:
					<pre><code class="language-json">{
	"error": false,
	"response": {
		"users": 81,
		"balance": 7426.45055478
	}
}</code></pre>
				</p>
			</div>
		</div>
		<div class="panel panel-default" id="getinfo-docs">
			<div class="panel-heading" data-toggle="collapse" href="#panel-1" aria-expanded="false" style="cursor: pointer;">
				<h4 class="panel-title">
					getinfo
					<span class="glyphicon glyphicon-chevron-down pull-right" aria-hidden="true"></span>
				</h4>
			</div>
			<div id="panel-1" class="panel-body panel-collapse collapse">
				<p>
					The getinfo method returns misc information like difficulty, mining speed, and average block time, as well as generating the info for use on <a href="https://omnicha.in/stats/">https://omnicha.in/stats/</a>. There are no arguments and no errors are thrown.
				</p>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Return Variable</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>block_count <span class="label label-info">Integer</span></td>
							<td>The current number of blocks</td>
						</tr>
						<tr>
							<td>difficulty <span class="label label-info">Double</span></td>
							<td>The current mining difficulty</td>
						</tr>
						<tr>
							<td>netmhps <span class="label label-info">Double</span></td>
							<td>The current estimated mining MH/s</td>
						</tr>
						<tr>
							<td>seconds_since_block <span class="label label-info">Integer</span></td>
							<td>The number of seconds since the last block was mined</td>
						</tr>
						<tr>
							<td>avg_block_time <span class="label label-info">Double</span></td>
							<td>The average seconds it took to mine a block in the last 24 hours</td>
						</tr>
						<tr>
							<td>total_mined_omc <span class="label label-info">Double</span></td>
							<td>The current total amount of OMC</td>
						</tr>
						<tr>
							<td>omc_btc_price <span class="label label-info">Double</span></td>
							<td>The current conversion ratio between Omnicoin and Bitcoin</td>
						</tr>
						<tr>
							<td>omc_usd_price <span class="label label-info">Double</span></td>
							<td>The current conversion ratio between Omnicoin and United States Dollars</td>
						</tr>
						<tr>
							<td>market_cap <span class="label label-info">Double</span></td>
							<td>The current market cap (market value of all Omnicoins in circulation)</td>
						</tr>
						<tr>
							<td>block_reward <span class="label label-info">Double</span></td>
							<td>The current reward for mining a block</td>
						</tr>
					</table>
				</div>
				<p>
					Example API call to <code>https://omnicha.in/api/?method=getinfo</code>
					<pre><code class="language-json">{
	"error": false,
	"response": {
		"block_count": 106239,
		"difficulty": 8.93492672,
		"netmhps": 196.971668,
		"seconds_since_block": 1036,
		"avg_block_time": 188.2923,
		"total_mined_omc": 6893927.3989376,
		"omc_btc_price": 0.0000051,
		"omc_usd_price": 0.002,
		"market_cap": 13694.9347,
		"block_reward": 33.424999995
	}
}</code></pre>
				</p>
			</div>
		</div>
		<div class="panel panel-default" id="getbalance-docs">
			<div class="panel-heading" data-toggle="collapse" href="#panel-2" aria-expanded="false" style="cursor: pointer;">
				<h4 class="panel-title">
					getbalance
					<span class="glyphicon glyphicon-chevron-down pull-right" aria-hidden="true"></span>
				</h4>
			</div>
			<div id="panel-2" class="panel-body panel-collapse collapse">
				<p>
					The getbalance method returns the balance of an Omnicoin address. If the address is invalid, an error is thrown. Otherwise the balance is returned.
				</p>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Argument</th>
							<th>Description</th>
							<th>Required</th>
						</tr>
						<tr>
							<td>Address <span class="label label-info">String</span></td>
							<td>The Omnicoin address</td>
							<td>Yes</td>
						</tr>
					</table>
				</div>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Return Variable</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>balance <span class="label label-info">Double</span></td>
							<td>The balance of the specified Omnicoin address</td>
						</tr>
					</table>
				</div>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Error</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>Invalid address</td>
							<td>The Omnicoin address specified is invalid</td>
						</tr>
						<tr>
							<td>Address not specified</td>
							<td>No address parameter was passed to the API</td>
						</tr>
					</table>
				</div>
				<p>
					Example API call to <code>https://omnicha.in/api?method=getbalance&address=oYSANYiopYAZ68YmHNcZv4g9W3q97wWt3v</code>
					<pre><code class="language-json">{
	"error": false,
	"response": {
		"balance": 22595.60459331
	}
}</code></pre>
				</p>
			</div>
		</div>
		<div class="panel panel-default" id="checkaddress-docs">
			<div class="panel-heading" data-toggle="collapse" href="#panel-3" aria-expanded="false" style="cursor: pointer;">
				<h4 class="panel-title">
					checkaddress
					<span class="glyphicon glyphicon-chevron-down pull-right" aria-hidden="true"></span>
				</h4>
			</div>
			<div id="panel-3" class="panel-body panel-collapse collapse">
				<p>
					The checkaddress method returns whether the specified address is a valid OMC address.
				</p>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Argument</th>
							<th>Description</th>
							<th>Required</th>
						</tr>
						<tr>
							<td>Address <span class="label label-info">String</span></td>
							<td>The Omnicoin address</td>
							<td>Yes</td>
						</tr>
					</table>
				</div>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Return Variable</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>isvalid <span class="label label-info">Boolean</span></td>
							<td>If the specified address is valid</td>
						</tr>
					</table>
				</div>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Error</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>Invalid address</td>
							<td>The Omnicoin address specified is invalid</td>
						</tr>
						<tr>
							<td>Address not specified</td>
							<td>No address parameter was passed to the API</td>
						</tr>
					</table>
				</div>
				<p>
					Example API call to <code>https://omnicha.in/api?method=checkaddress&address=oYSANYiopYAZ68YmHNcZv4g9W3q97wWt3v</code>
					<pre><code class="language-json">{
	"error": false,
	"response": {
		"isvalid": true
	}
}</code></pre>
				</p>
			</div>
		</div>
		<div class="panel panel-default" id="verifymessage-docs">
			<div class="panel-heading" data-toggle="collapse" href="#panel-4" aria-expanded="false" style="cursor: pointer;">
				<h4 class="panel-title">
					verifymessage
					<span class="glyphicon glyphicon-chevron-down pull-right" aria-hidden="true"></span>
				</h4>
			</div>
			<div id="panel-4" class="panel-body panel-collapse collapse">
				<p>
					The checkaddress method returns whether the specified signature is as valid hash for the specified message for the specified address.
				</p>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Argument</th>
							<th>Description</th>
							<th>Required</th>
						</tr>
						<tr>
							<td>Address <span class="label label-info">String</span></td>
							<td>The Omnicoin address</td>
							<td>Yes</td>
						</tr>
						<tr>
							<td>Message <span class="label label-info">String</span></td>
							<td>The message that was signed</td>
							<td>Yes</td>
						</tr>
						<tr>
							<td>Signature <span class="label label-info">String</span></td>
							<td>The signature generated from signing the message</td>
							<td>Yes</td>
						</tr>
					</table>
				</div>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Return Variable</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>isvalid <span class="label label-info">Boolean</span></td>
							<td>If the specified signature is valid</td>
						</tr>
					</table>
				</div>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Error</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>Address not specified</td>
							<td>No address parameter was passed to the API</td>
						</tr>
						<tr>
							<td>Message not specified</td>
							<td>No message parameter was passed to the API</td>
						</tr>
						<tr>
							<td>Signature not specified</td>
							<td>No signature parameter was passed to the API</td>
						</tr>
					</table>
				</div>
				<p>
					Example API call to <code>https://omnicha.in/api?method=verifymessage&address=obaMRGwpBM8kQcEydJFTMoC23WYX3by6Fx&message=Test Signing Message&signature=Hxry2N6pWfDSZrjKGUqFRmAe2JZWFIgDysd6oIdFv7KBlNRcYDRyt9NQuJJkZDXNA56bwBeG6gmipuEx3RIdlUA=</code>
					<pre><code class="language-json">{
	"error": false,
	"response": {
		"isvalid": true
	}
}</code></pre>
				</p>
			</div>
		</div>
		<div class="panel panel-default" id="getcharts-docs">
			<div class="panel-heading" data-toggle="collapse" href="#panel-5" aria-expanded="false" style="cursor: pointer;">
				<h4 class="panel-title">
					getcharts
					<span class="glyphicon glyphicon-chevron-down pull-right" aria-hidden="true"></span>
				</h4>
			</div>
			<div id="panel-5" class="panel-body panel-collapse collapse">
				<p>
					The getinfo method returns data for generating the charts on <a href="https://omnicha.in/charts/">https://omnicha.in/charts/</a>. The first entry start on 2014-04-05 12:00:00 and each entry is separated by the zoom factor. No errors are thrown.
				</p>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Argument</th>
							<th>Description</th>
							<th>Allowed Values</th>
							<th>Required</th>
						</tr>
						<tr>
							<td>Zoom <span class="label label-info">Integer</span></td>
							<td>Seconds between data entries</td>
							<td>900, 1800, 3600, 21600, 43200, 86400</td>
							<td>No (defaults 3600)</td>
						</tr>
					</table>
				</div>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Return Variable</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>difficulty <span class="label label-info">Array(Double)</span></td>
							<td>The mining difficulty</td>
						</tr>
						<tr>
							<td>btc_price <span class="label label-info">Array(Double)</span></td>
							<td>The exchange price between Bitcoin and Omnicoin (Currently disabled)</td>
						</tr>
						<tr>
							<td>usd_price <span class="label label-info">Array(Double)</span></td>
							<td>The exchange price between Bitcoin and United States Dollars (Currently disabled)</td>
						</tr>
						<tr>
							<td>volume <span class="label label-info">Array(Double)</span></td>
							<td>The volume of exchange Omnicoin on AllCrypt.com (Currently disabled)</td>
						</tr>
						<tr>
							<td>transactions <span class="label label-info">Array(Integer)</span></td>
							<td>The number of Omnicoin transactions</td>
						</tr>
						<tr>
							<td>transaction_volume <span class="label label-info">Array(Double)</span></td>
							<td>The volume of Omnicoin transactions</td>
						</tr>
						<tr>
							<td>block_time <span class="label label-info">Array(Double)</span></td>
							<td>The number of seconds to mine an Omnicoin block</td>
						</tr>
						<tr>
							<td>hashrate <span class="label label-info">Array(Double)</span></td>
							<td>The estimated mining MH/s</td>
						</tr>
						<tr>
							<td>coins_mined <span class="label label-info">Array(Double)</span></td>
							<td>The number of Omnicoins mined</td>
						</tr>
						<tr>
							<td>lifetime_coins_mined <span class="label label-info">Array(Double)</span></td>
							<td>The total number of Omnicoins</td>
						</tr>
						<tr>
							<td>lifetime_transactions <span class="label label-info">Array(Integer)</span></td>
							<td>The total number of Omnicoin transactions</td>
						</tr>
						<tr>
							<td>lifetime_transactions_volume <span class="label label-info">Array(Double)</span></td>
							<td>The total volume of Omnicoin transactions</td>
						</tr>
						<tr>
							<td>zoom <span class="label label-info">Array(Integer)</span></td>
							<td>The number of seconds between each entry</td>
						</tr>
					</table>
				</div>
				<p>
					Example API call to <code>https://omnicha.in/api?method=getcharts&zoom=3600</code>
					<pre><code class="language-json">{
	"error": false,
	"response": {
		"difficulty": [0, 0.067952005434041, 0.13503441250499, 0.86525169754056, 1.7578842482421, ...],
		"btc_price": [0, 0, 0, 0, 0, ...],
		"usd_price": [0, 0, 0, 0, 0, ...],
		"volume": [0, 0, 0, 0, 0, ...],
		"transactions": [0, 1, 0, 0, 3, ...],
		"transaction_volume": [0, 133.7, 0, 0, 235.464, ...],
		"block_time": [180, 13.9667, 28.9333, 63.9286, 67.8182, ...],
		"hashrate": [0, 20.8963, 20.045, 58.1309, 111.328, ...],
		"coins_mined": [0, 4011, 2005.5, 935.9, 735.35, ...],
		"lifetime_coins_mined": [0, 20990.9, 25336.2, 31486.5, 34561.6, ...], 
		"lifetime_transactions": [0, 1, 1, 4, 21, ...],
		"lifetime_transactions_volume": [0, 133.7, 133.7, 668.5, 2560.27, ...],
		"zoom": 3600
	}
}</code></pre>
				</p>
			</div>
		</div>
		<div class="panel panel-default" id="getrichlist-docs">
			<div class="panel-heading" data-toggle="collapse" href="#panel-6" aria-expanded="false" style="cursor: pointer;">
				<h4 class="panel-title">
					getrichlist
					<span class="glyphicon glyphicon-chevron-down pull-right" aria-hidden="true"></span>
				</h4>
			</div>
			<div id="panel-6" class="panel-body panel-collapse collapse">
				<p>
					The getrichlist method returns data for generating the richlist on <a href="https://omnicha.in/richlist/">https://omnicha.in/richlist/</a>. There are no parameters and no errors are thrown.
				</p>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Argument</th>
							<th>Description</th>
							<th>Required</th>
						</tr>
					</table>
				</div>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Return Variable</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>last_update <span class="label label-info">String</span></td>
							<td>The timestamp of the last update to the richlist</td>
						</tr>
						<tr>
							<td>richlist <span class="label label-info">Array([entries])</span></td>
							<td>Array of all entries</td>
						</tr>
						<tr>
							<td>[entry]->rank <span class="label label-info">Integer</span></td>
							<td>The world rank of the Omnicoin address</td>
						</tr>
						<tr>
							<td>[entry]->address <span class="label label-info">String</span></td>
							<td>The Omnicoin address</td>
						</tr>
						<tr>
							<td>[entry]->vanity_name <span class="label label-info">String</span></td>
							<td>The vanity name linked to the Omnicoin address</td>
						</tr>
						<tr>
							<td>[entry]->balance <span class="label label-info">Double</span></td>
							<td>The balance of the Omnicoin address</td>
						</tr>
						<tr>
							<td>[entry]->usd_value <span class="label label-info">Double</span></td>
							<td>The USD value of the balance of the Omnicoin address</td>
						</tr>
						<tr>
							<td>[entry]->percent <span class="label label-info">Double</span></td>
							<td>The percentage of all Omnicoins this address holds</td>
						</tr>
					</table>
				</div>
				<p>
					Example API call to <code>https://omnicha.in/api?method=getrichlist</code>
					<pre><code class="language-json">{
	"error": false, 
	"response": {
		"last_update": "14-11-17 21:03:49",
		"richlist": [
			{
				"rank": "1", 
				"address": "oK6tf99VfiqovtG8YSWBMSBZZ7Ei6poyLe",
				"vanity_name": "Omni's Phat Wallet",
				"balance": 2700715.849678,
				"usd_value": 3797.6,
				"percent": 37.971726543451
			}, ...
		]
	}
}</code></pre>
				</p>
			</div>
		</div>
		<div class="panel panel-default" id="getwstats-docs">
			<div class="panel-heading" data-toggle="collapse" href="#panel-7" aria-expanded="false" style="cursor: pointer;">
				<h4 class="panel-title">
					getwstats
					<span class="glyphicon glyphicon-chevron-down pull-right" aria-hidden="true"></span>
				</h4>
			</div>
			<div id="panel-7" class="panel-body panel-collapse collapse">
				<p>
					The getwstats method returns total users and total balance of all <a href="https://omnicha.in/wallet/">online wallet accounts</a>. There are no arguments and no errors are thrown.
				</p>
				<div class="panel panel-default">
					<table class="table table-striped">
						<tr>
							<th>Return Variable</th>
							<th>Description</th>
						</tr>
						<tr>
							<td>users <span class="label label-info">Integer</span></td>
							<td>The current number of online wallet users</td>
						</tr>
						<tr>
							<td>balance <span class="label label-info">Double</span></td>
							<td>The current balance of all online wallet users</td>
						</tr>
					</table>
				</div>
				<p>
					Example API call to <code>https://omnicha.in/api?method=getwstats</code>
					<pre><code class="language-json">{
	"error": false,
	"response": {
		"users": 81, 
		"balance": 7426.45055478
	}
}</code></pre>
				</p>
			</div>
		</div>
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
