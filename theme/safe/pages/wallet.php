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

get_header($pages, $currentpage, "Wallet", "Wallet", false);
?>
<div class="page-header">
	<div class="container">
		<h2 class="pull-left hidden-xs">Wallet</h2>
		<div style="margin-top: 5px; display: none;" class="pull-right post-login-nav">
			<h3 style="display: inline; margin: 0 7px 0 0;" class="balance-title"></h3>
		</div>
	</div>
	<div class="container post-login-nav" style="display: none;">
		<ul class="nav nav-tabs">
			<li onClick="selectTab(0);" class="hidden-xs tab-selector tab-selector-0 active"><a style="cursor: pointer;">Home</a></li>
			<li onClick="selectTab(1);" class="hidden-xs tab-selector tab-selector-1"><a style="cursor: pointer;">Send</a></li>
			<li onClick="selectTab(2);" class="hidden-xs tab-selector tab-selector-2"><a style="cursor: pointer;">Receive</a></li>
			<li onClick="selectTab(3);" class="hidden-xs tab-selector tab-selector-3"><a style="cursor: pointer;">Transactions</a></li>
			<li class="dropdown active visible-xs">
				<a class="dropdown-toggle current-tab-name" data-toggle="dropdown" href="#">
				Home <span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<li onClick="selectTab(0);" class="tab-selector tab-selector-0 active"><a style="cursor: pointer;">Home</a></li>
					<li onClick="selectTab(1);" class="tab-selector tab-selector-1"><a style="cursor: pointer;">Send</a></li>
					<li onClick="selectTab(2);" class="tab-selector tab-selector-2"><a style="cursor: pointer;">Receive</a></li>
					<li onClick="selectTab(3);" class="tab-selector tab-selector-3"><a style="cursor: pointer;">Transactions</a></li>
				</ul>
			</li>
		</ul>
	</div>
</div>
<div class="container">
	<form class="well form" id="login-form" style="margin:0 auto; max-width:358px;" onKeyPress="if (event.keyCode == 13) { login(); }">
		<h2>Login</h2>
		<div class="form-group" id="login-username-group">
			<input type="text" id="login-username" class="form-control" placeholder="Username" required autofocus>
		</div>
		<div class="form-group" id="login-password-group">
			<input type="password" id="login-password" class="form-control" placeholder="Password" required>
		</div>
		<button onClick="login(this.form, this.form.password, this.form.passwordHashed);" class="btn btn-primary btn-block" type="button">Login</button>
		<a href="#" onClick="$('#login-form').slideUp();$('#register-form').slideDown();">Don't have an account?</a>
	</form>
	<form class="well form" id="register-form" style="margin:0 auto; max-width:358px; display:none;" onKeyPress="if (event.keyCode == 13) { register(); }">
		<h2>Register</h2>
		<div class="form-group" id="register-username-group">
			<input type="text" id="register-username" class="form-control tip-right" placeholder="Username" title="Pick a unique username between 3 and 30 characters in length. Make sure to write it down." required autofocus>
		</div>
		<div class="form-group" id="register-password-group">
			<input type="password" id="register-password" class="form-control tip-right" placeholder="Password" title="Pick a strong password between 10 and 30 characters in length. Make sure to write it down." required>
		</div>
		<div class="form-group" id="register-password-confirm-group">
			<input type="password" id="register-password-confirm" class="form-control tip-right" placeholder="Confirm Password" title="Retype your password for safety." required>
		</div>
		<div class="form-group">
			<a href="https://omnicha.in/wallet/tos" target="_blank">By registering you agree to our Terms of Service</a>
		</div>
		<button onClick="register();" class="btn btn-primary btn-block" type="button">Register</button>
		<a href="#" onClick="$('#register-form').slideUp();$('#login-form').slideDown();">Already have an account?</a>
	</form>
	<div class="tab tab-0" style="display: none;">
		<h3>Home</h3>
		<div class="table-responsive">
			<table class="table table-striped table-bordered">
				<tr>
					<td>Total Transactions In</td>
					<td class="tx_in"></td>
				</tr>
				<tr>
					<td>Total Transactions Out</td>
					<td class="tx_out"></td>
				</tr>
				<tr>
					<td>Total In</td>
					<td class="total_in"></td>
				</tr>
				<tr>
					<td>Total Out</td>
					<td class="total_out"></td>
				</tr>
				<tr>
					<td>Balance</td>
					<td class="balance"></td>
				</tr>
				<tr>
					<td>Unconfirmed</td>
					<td class="pending_balance"></td>
				</tr>
			</table>
		</div>
	</div>
	<div class="tab hide tab-1">
		<h3>Send Omnicoins</h3>
		<div class="well">
			<form class="form">
				<div class="form-group">
					<div style="width:calc(50% - 10px); display:inline-block;">
						<div class="input-group" id="send-amount-group">
							<input class="form-control" id="send-amount" type="text" placeholder="Amount (+0.1 OMC fee)" required>
							<div class="input-group-addon" onClick="getSendable();" >OMC</div>
						</div>
					</div><!--
					--><div style="width:20px; text-align:center; line-height:34px; vertical-align:top; display:inline-block;">
						=
					</div><!--
					--><div style="width:calc(50% - 10px); display:inline-block;">
						<div class="input-group">
							<div class="input-group-addon hidden-xs">$</div>
							<input class="form-control" id="send-amount-usd" type="text" placeholder="Amount (+<?php echo omc2usd($omc_usd_price, 1); ?> USD fee)" required>
							<div class="input-group-addon">USD</div>
						</div>
					</div>
				</div>
				<div class="form-group" id="send-address-group">
					<input class="form-control" id="send-address" type="text" placeholder="Address" required>
				</div>
				<button class="btn btn-primary" onClick="sendomnicoins();" type="button">Send Omnicoins</button>
			</form>
		</div>
	</div>
	<div class="tab hide tab-2">
		<h3 style="display: inline; margin: 0; vertical-align: middle;">Receive Omnicoins</h3>
		<button style="display: inline;" onclick="getnewaddress();" type="button" class="btn btn-primary btn-sm">
			<span class="glyphicon glyphicon-plus"></span> Create new address
		</button>
		<button style="display: inline;" type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#import-modal">
			<span class="glyphicon glyphicon-plus"></span> Import Private Key
		</button>
		<div class="table-responsive" style="margin-top:10px;">
			<table id="address-list" class="table table-striped">
				<tr>
					<th>Address</th>
					<th>Private Key</th>
				</tr>
			</table>
		</div>
	</div>
	<div class="tab hide tab-3">
		<h3>Transactions</h3>
		<div class="table-responsive">
			<table id="transaction-list" class="table table-striped">
				<tr>
					<th>Transaction</th>
					<th>Date</th>
					<th>Confirmations</th>
					<th>Amount</th>
					<th>Balance</th>
				</tr>
			</table>
		</div>
	</div>
	<div class="modal fade" id="import-modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					<h4 class="modal-title" id="myModalLabel">Import Private Key</h4>
				</div>
				<div class="modal-body" id="import-key-body">
					<div class="alert alert-warning">Private key importing is currently disabled.</div>
					<form class="form">
						<div class="form-group" id="import-key-group">
							<input class="form-control" id="import-key" type="text" placeholder="Private Key" required>
						</div>
					</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					<button type="button" class="btn btn-primary" onClick="importkey();">Import</button>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
get_footer();
?>