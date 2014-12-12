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

get_header($pages, $currentpage, "Exchange");
?>
<div class="container">
	<div class="row" class="step-1">
		<div class="col-md-6">
			<div class="panel panel-success">
				<div class="panel-heading">
					<h2 class="panel-title">Buy Omnicoin</h2>
				</div>
				<div class="panel-body">
					<form class="form" id="buy_form">
						<div class="form-group">
							<div class="input-group">
								<input name="address" type="text" class="form-control" placeholder="Number of Bitcoins to pay" id="buy_btc">
								<span class="input-group-addon">BTC</span>
							</div>
						</div>
						<div class="form-group">
							<div class="input-group">
								<input name="address" type="text" class="form-control" placeholder="Estimated number of Omnicoins you will receive" id="buy_omc" disabled>
								<span class="input-group-addon">OMC</span>
							</div>
						</div>
						<div class="form-group">
							<input type="button" class="btn btn-primary btn-block" value="Calculate OMC" id="buy_estimate" data-loading-text="Estimating...">
							<input type="button" class="btn btn-primary btn-block" value="Purchase" id="buy_purchase">
						</div>
					</form>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="panel panel-info">
				<div class="panel-heading">
					<h2 class="panel-title">Sell Omnicoin</h2>
				</div>
				<div class="panel-body">
					<form class="form" id="sell_form">
						<div class="form-group">
							<div class="input-group">
								<input name="address" type="text" class="form-control" placeholder="Number of Omnicoins to sell" id="sell_omc">
								<span class="input-group-addon">OMC</span>
							</div>
						</div>
						<div class="form-group">
							<div class="input-group">
								<input name="address" type="text" class="form-control" placeholder="Estimated number of Bitcoins you will receive" id="sell_btc" disabled>
								<span class="input-group-addon">BTC</span>
							</div>
						</div>
						<div class="form-group">
							<input type="button" class="btn btn-primary btn-block" value="Calculate BTC" id="sell_estimate" data-loading-text="Estimating...">
							<input type="button" class="btn btn-primary btn-block" value="Sell" id="sell_purchase">
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
	<div class="row" class="step-1">
		<div class="col-md-12">
			<div class="well">
				<h3>How it works</h3>
				<p>
					<b>Buying Omnicoin</b>
					<br><br>
					<b>Selling Omnicoin</b>
				</p>
				<h3 style="margin-top: 40px;">Why an estimate?</h3>
				<p>
					You may be wondering why the number of Omnicoins or Bitcoins you will receive is estimated.
					<br><br>
					These numbers are based on the current amount and price of Omnicoin currently being bought and sold. These orders can change very quickly. Because of this the cost of Omnicoin can change from the time you start the purchase process to the time we actually send you the Omnicoins. Usually if you pay promptly (within a few minutes) the price wont change and you will receive the amount estimated. If you wait a few hours to pay you though are far more likely to receive more or less than the amount estimated.
					<br><br>
					We recommend that you purchase a few more Omnicoins than you actually need just in case. This will also help you cover your transaction fees.
				</p>
			</div>
		</div>
	</div>
	<div class="row" class="step-2" style="display: none;">
		hi
	</div>
</div>
<script>
$("#buy_estimate").click(function() {
	var $btn = $(this).button('loading')
	var json = {"method": "estimate_buy_omc", "btc": $("#buy_btc").val()};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to server");
	}).done(function(data) {
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error = "NOT_ENOUGH_SUPPLY") {
				$("#buy_form").prepend("<div class='alert alert-danger register-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>There isn't that much OMC for sale!</div>")
				$("#buy_omc").val("");
			}
		} else {
			$("#buy_omc").val(jsonResponse.response.omc);
		}
		$btn.button('reset');
	});
});

$("#sell_estimate").click(function() {
	var $btn = $(this).button('loading')
	var json = {"method": "estimate_sell_omc", "omc": $("#sell_omc").val()};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to server");
	}).done(function(data) {
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error = "NOT_ENOUGH_DEMAND") {
				$("#sell_form").prepend("<div class='alert alert-danger register-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>There isn't that much OMC being bought!</div>")
				$("#buy_btc").val("");
			}
		} else {
			$("#sell_btc").val(jsonResponse.response.btc);
		}
		$btn.button('reset');
	});
});

$("#buy_purchase").click(function() {
	$("#step-1").slideUp('slow', function() {
		setTimeout(function() {
			$("#step-2").slideDown('slow');
		}, 1000);
	});
});
</script>
<?php
get_footer();
?>