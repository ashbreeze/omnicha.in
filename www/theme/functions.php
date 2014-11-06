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

require_once('/var/www/omnicha.in/theme/safe/db.php');
$omc_btc_price = get_option($database, "omc_btc_price");
$btc_usd_price = get_option($database, "btc_usd_price");
$omc_usd_price = $omc_btc_price * $btc_usd_price;

file_get_contents("http://omnicha.in:6666/chain/OmniCoin");


function format_num($val, $precision = 10) {
	$to_return = rtrim(rtrim(number_format(round($val, $precision), $precision), "0"), ".");
	return $to_return == "" ? "0" : $to_return;
	//return $val;
}

function omc2usd($omc_rate, $omc, $round = 4) {
	$price = $omc * $omc_rate;
	if ($price != 0) {
		while (round($price, $round) == 0) {
			$round++;
		}
	}
	return round($price, $round);
}

function usd2omc($omc_rate, $usd) {
	return $usd / $omc_rate;
}

function format_time($seconds, $seconds2 = null, $precise = false) {
	if ($seconds2 == null) {
		$seconds2 = time();
	}
	$time = ($seconds2 - $seconds);
	$time2 = 0;
	$postfix = "second";
	$postfix2 = "";
	if ($time >= 60) {
		$postfix2 = $postfix;
		$old_time = $time;
		$time = $time / 60;
		$postfix = "minute";
		if ($precise) {
			$time2 = $old_time - (60 * floor($time));
		}
		if ($time >= 60) {
			$postfix2 = $postfix;
			$old_time = $time;
			$time = $time / 60;
			$postfix = "hour";
			$time2 = $old_time - (60 * floor($time));
			if ($time >= 24) {
				$postfix2 = $postfix;
				$old_time = $time;
				$time = $time / 24;
				$postfix = "day";
				$time2 = $old_time - (24 * floor($time));				
				if ($time >= 7) {
					$postfix2 = $postfix;
					$old_time = $time;
					$time = $time / 7;
					$postfix = "week";
					$time2 = $old_time - (7 * floor($time));
					if ($time >= 4.34812) {
						$postfix2 = $postfix;
						$old_time = $time;
						$time = $time / 4.34812;
						$postfix = "month";
						$time2 = $old_time - (4.34812 * floor($time));
						if ($time >= 52.1775) {
							$postfix2 = $postfix;
							$old_time = $time;
							$time = $time / 52.1775;
							$postfix = "year";
							$time2 = $old_time - (52.1775 * floor($time));
						}
					}
				}
			}
		}
	}
	$time = floor($time);
	$time2 = floor($time2);
	return $time . " " . $postfix . format_s($time) . ($time2 != 0 ? (", " . $time2 . " " . $postfix2 . format_s($time2)) : "");
}

//---------Begin Kinda working OMC functions-------------
//Some might not work. I should really check them all out.

function is_address_valid($addr) {
    $addr = decodeBase58($addr);
    if (strlen($addr) != 50) {
		return false;
    }
    $version = substr($addr, 0, 2);
    if (hexdec($version) != 115) {
		return false;
    }
    $check = substr($addr, 0, strlen($addr) - 8);
    $check = pack("H*", $check);
    $check = strtoupper(hash("sha256", hash("sha256", $check, true)));
    $check = substr($check, 0, 8);
    return $check == substr($addr, strlen($addr) - 8);
  }

function hash_to_address($hash, $version = 73) {
    $hash160 = $version . $hash;
	$check = pack("H*", $hash160);
	$check = hash('sha256', hash('sha256', $check, true));
	$check = substr($check, 0, 8);
	$hash160 = strtoupper($hash160 . $check);
    return encodeBase58($hash160);
}

function address_to_hash($address) {
    $address = decodeBase58($address);
    $address = substr($address, 2, strlen($address) - 10);
    return $address;
}

function encodeBase58($hex) {
	$base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
	if (strlen($hex) % 2 != 0) {
		die("encodeBase58: uneven number of hex characters");
	}
	$orighex = $hex;

	$hex = decodeHex($hex);
	$return = "";
	while (bccomp($hex, 0) == 1) {
		$dv = (string) bcdiv($hex, "58", 0);
		$rem = (integer) bcmod($hex, "58");
		$hex = $dv;
		$return = $return . $base58chars[$rem];
	}
	$return = strrev($return);

	//leading zeros
	for ($i = 0; $i < strlen($orighex) && substr($orighex, $i, 2) == "00"; $i += 2) {
		$return = "1" . $return;
	}

	return $return;
}

function decodeBase58($base58) {
	$base58chars = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
    $origbase58 = $base58;

    //only valid chars allowed
    if (preg_match('/[^1-9A-HJ-NP-Za-km-z]/', $base58)) {
      return "";
    }

    $return = "0";
    for ($i = 0; $i < strlen($base58); $i++) {
      $current = (string) strpos($base58chars, $base58[$i]);
      $return = (string) bcmul($return, "58", 0);
      $return = (string) bcadd($return, $current, 0);
    }

    $return = encodeHex($return);

    //leading zeros
    for ($i = 0; $i < strlen($origbase58) && $origbase58[$i] == "1"; $i++) {
      $return = "00" . $return;
    }

    if (strlen($return) % 2 != 0) {
      $return = "0" . $return;
    }

    return $return;
}

function encodeHex($dec) {
	$hexchars = "0123456789ABCDEF";
    $return = "";
    while (bccomp($dec, 0) == 1) {
		$dv = (string) bcdiv($dec, "16", 0);
		$rem = (integer) bcmod($dec, "16");
		$dec = $dv;
		$return = $return . $hexchars[$rem];
    }
    return strrev($return);
}

function decodeHex($hex) {
	$hexchars = "0123456789ABCDEF";
	$hex = strtoupper($hex);
	$return = "0";
	for ($i = 0; $i < strlen($hex); $i++) {
		$current = (string) strpos($hexchars, $hex[$i]);
		$return = (string) bcmul($return, "16", 0);
		$return = (string) bcadd($return, $current, 0);
	}
	return $return;
}

//---------End Kinda working OMC functions-------------

function format_satoshi($satoshi) {
	//$to_return = rtrim(rtrim(bcdiv(intval($satoshi), 100000000, 8), "0"), ".");
	//return $to_return == "" ? "0" : $to_return;
	return round(bcdiv(intval($satoshi), 100000000, 8), 10);
}

function calculate_target($nBits) {
    return ($nBits & 0xffffff) << (8 * (($nBits >> 24) - 3));
}

function target_to_difficulty($target) {
    return ((1 << 224) - 1) * 1000 / ($target + 1) / 1000.0;
}

function calculate_difficulty($nBits) {
    return target_to_difficulty(calculate_target($nBits));
}

function format_s($number) {
	return ($number == "1" ? "" : "s");
}
?>
