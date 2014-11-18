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

session_start();
require_once('/var/www/omnicha.in/theme/safe/wallet.php');
require_once('/var/www/omnicha.in/theme/recaptchalib.php');
get_header($pages, $currentpage, "Claim Address");
?>
<div class="container">
	<?php
	$address_error = false;
	$label_error = false;
	$hfuid_error = false;
	$signature_error = false;
	
	if (isset($_POST['address']) && isset($_POST['label']) && isset($_POST['hfuid']) && isset($_POST['signature']) && isset($_POST['csrf-token']) && isset($_SESSION['csrf-token']) && isset($_SESSION['signing-message']) && is_string($_POST['address']) && is_string($_POST['label']) && is_string($_POST['hfuid']) && is_string($_POST['signature']) && is_string($_POST['csrf-token']) && $_POST['csrf-token'] == $_SESSION['csrf-token']) {
		$captcha = recaptcha_check_answer($recaptcha_private, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

		if (!$captcha->is_valid) {
			$captcha_error = true;
			echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid captcha.</div>";
		} else {
			$address = $_POST['address'];
			$label = $_POST['label'];
			$hfuid = $_POST['hfuid'];
			$signature = $_POST['signature'];
			
			$address_safe = preg_replace('/[^A-Za-z0-9]/', '', $address);
			$label_safe = preg_replace('/[^A-Za-z0-9 ]/', '', $label);
			$hfuid_safe = preg_replace('/[^0-9]/', '', $hfuid);
			$signature_safe = preg_replace('/[^A-Za-z0-9=+-\/]/', '', $signature);

			$ip_check = mysqli_query($database, "SELECT ip FROM claimed_addresses WHERE ip = '" . $_SERVER['REMOTE_ADDR'] . "' AND date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
			if ($ip_check->num_rows >= 10) {
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>You can only claim 10 addresses per 24 hours.</div>";
			} else if ($address_safe == "" || $label_safe == "" || $signature == "") {
				$address_error = $address_error == "";
				$label_error = $label_error == "";
				$signature_error = $signature_error == "";
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Required fields left empty.</div>";
			} else if ($address != $address_safe) {
				$address_error = true;
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid address.</div>";
			} else if ($label != $label_safe) {
				$label_error = true;
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid label.</div>";
			} else if ($hfuid != $hfuid_safe) {
				$hfuid_error = true;
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid HF UID.</div>";
			} else if ($signature != $signature_safe) {
				$signature_error = true;
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid signature.</div>";
			} else if (strlen($label_safe) < 10 || strlen($label_safe) > 30) {
				$label_error = true;
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Label must be between 10 and 30 characters.</div>";
			} else if (!$wallet->validateaddress($address_safe)) {
				$address_error = true;
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid address.</div>";
			} else if (mysqli_query($database, "SELECT address FROM claimed_addresses WHERE address = '" . $address_safe . "'")->num_rows != 0) {
				echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Address is already claimed.</div>";
			} else {
				if ($wallet->verifymessage($address_safe, $signature_safe, $_SESSION['signing-message'])) {
					if ($hfuid_safe == "") {
						mysqli_query($database, "INSERT INTO claimed_addresses (address, label, ip, date) VALUES ('" . $address_safe . "', '" . $label_safe . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . date("y-m-d H:i:s") . "')");
						echo "<div class='alert alert-success'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Address claimed.</div>";
					} else {
						$conf_code = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true));
						mysqli_query($database, "INSERT INTO claimed_addresses (address, label, hf_uid, hf_uid_conf_code, ip, date) VALUES ('" . $address_safe . "', '" . $label_safe . "', '" . $hfuid_safe . "', '" . $conf_code . "', '" . $_SERVER['REMOTE_ADDR'] . "', '" . date("y-m-d H:i:s") . "')");
						echo "<div class='alert alert-success'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Your address claimed but your HF UID has not been validated. Click <a target='_blank' href='http://hackforums.net/private.php?action=send&uid=1256441&subject=OmniCha.in HF UID Validation&message=" . $conf_code . "'>here</a> to validate your HF UID.</div>";
					}
				} else {
					$signature_error = true;
					echo "<div class='alert alert-danger'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid signature.</div>";
				}
			}
		}
	}
	?>
	<div class="col-md-6">
		<form class="form" action="/claimaddress/" method="post">
			<div class="form-group">
				<div class="input-group<?php echo $address_error ? " has-error" : "";?>">
					<span class="input-group-addon" style="min-width:90px; text-align:right;">Address</span>
					<input name="address" type="text" class="form-control" placeholder="Your OmniCoin address" <?php if (isset($address_safe)) { echo 'value="' . $address_safe . '" '; } ?>required autofocus>
				</div>
			</div>
			<div class="form-group">
				<div class="input-group<?php echo $label_error ? " has-error" : "";?>">
					<span class="input-group-addon" style="min-width:90px; text-align:right;">Label</span>
					<input name="label" type="text" class="form-control" placeholder="Your name (10 - 30 alphanumerical characters)" <?php if (isset($label_safe)) { echo 'value="' . $label_safe . '" '; } ?>required>
				</div>
			</div>
			<div class="form-group">
				<div class="input-group<?php echo $hfuid_error ? " has-error" : "";?>">
					<span class="input-group-addon" style="min-width:90px; text-align:right;">HF UID</span>
					<input name="hfuid" type="text" class="form-control" placeholder="(optional)" <?php if (isset($hfuid_safe)) { echo 'value="' . $hfuid_safe . '" '; } ?>>
				</div>
			</div>
			<pre><?php echo $_SESSION['signing-message'] = "OmniChain.in Address Claim " . substr(md5(microtime()), rand(0, 26), 10) . " " . date("y-m-d H:i:s"); ?></pre>
			<div class="form-group">
				<div class="input-group<?php echo $signature_error ? " has-error" : "";?>">
					<span class="input-group-addon" style="min-width:90px; text-align:right;">Signature</span>
					<textarea name="signature" type="text" class="form-control" placeholder="Signature from signing the message above" required></textarea>
				</div>
			</div>
			<div class="form-group">
				<?php
				echo recaptcha_get_html($recaptcha_public, null, true);
				?>
			</div>
			<div class="form-group">
				<input type="hidden" name="csrf-token" value="<?php echo $_SESSION['csrf-token'] = hash('sha512', uniqid(mt_rand(1, mt_getrandmax()), true)); ?>">
				<button class="btn btn-primary btn-block" type="submit">Claim Address</button>
			</div>
		</form>
	</div>
	<div class="col-md-6">
		<div class="well">
			<h3>What is address claiming</h3>
			<p>
				Address claiming allows you to link your name with your address on OmniCha.in. It will be displayed on the address page and also on the top 100 list.
				<br><br>
				You can also add and verify your HackForums UID so that users can be certain that they are sending to your OMC address during deals.
			</p>
			<h3 style="margin-top: 40px;">How it works</h3>
			<p>
				To claim your address enter your address, desired label (your username), and optionally a HackForums UID (you will verify this later). Then copy the signing message and sign with the address you entered (Helpful video: <a href="http://bit.ly/1jfmJF5">http://bit.ly/1jfmJF5</a>) and enter the generated signature. Click Claim Address to complete the process.
				<br><br>
				That's it! Your label will now be displayed on your address page and on your top 100 entry!
				<br><br>
				If you entered a HackForums UID you will be required to complete a further validation process to verify that you own that UID.
				<br><br>
				<b>
					Important note: You can only claim your address ONCE. Once you set a label / HF UID you cannot change it.
					<br><br>
					Also please keep the labels appropriate. If I see any offensive labels I will remove them and you wont be able to set a new one.
				</b>
				
			</p>
		</div>
	</div>
</div>
<?php
get_footer();
?>
