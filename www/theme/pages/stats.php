<?php
get_header($pages, $currentpage, "Stats");
?>
<div class="container">
	<table class="table table-striped">
		<tr>
			<td>Total Blocks Mined</td>
			<td id="total_blocks_mined"></td>
			<td></td>
		</tr>
		<tr>
			<td>Total OmniCoins Mined</td>
			<td id="total_coins_mined"></td>
			<td><a href="/charts/#lifetime-coins-mined"><span class="glyphicon glyphicon-signal text-primary"></span></a></td>
		</tr>
		<tr>
			<td>Current Mining Difficulty</td>
			<td id="difficulty"></td>
			<td><a href="/charts/#difficulty"><span class="glyphicon glyphicon-signal text-primary"></span></a></td>
		</tr>
		<tr>
			<td>Current Mining Speed</td>
			<td id="hashrate"></td>
			<td><a href="/charts/#hashrate"><span class="glyphicon glyphicon-signal text-primary"></span></a></td>
		</tr>
		<tr>
			<td>Time Since Last Block</td>
			<td id="last_block_time"></td>
			<td><a href="/charts/#block-time"><span class="glyphicon glyphicon-signal text-primary"></span></a></td>
		</tr>
		<tr>
			<td>24 Hour Average Block Time</td>
			<td id="avg_block_time"></td>
			<td></td>
		</tr>
		<tr>
			<th>Omnicoin Market Info</th>
			<th></th>
			<th></th>
		</tr>
		<tr>
			<td>OMC/BTC Price <a href="https://www.allcrypt.com/market?id=672">(AllCrypt)</a></td>
			<td id="omc_btc_price"></td>
			<td><a href="/charts/#price"><span class="glyphicon glyphicon-signal text-primary"></span></a></td>
		</tr>
		<tr>
			<td>OMC/USD Price <a href="https://btc-e.com/">(BTC-E)</a></td>
			<td id="omc_usd_price"></td>
			<td><a href="/charts/#price"><span class="glyphicon glyphicon-signal text-primary"></span></a></td>
		</tr>
		<tr>
			<td>Market Cap</td>
			<td id="market_cap"></td>
			<td></td>
		</tr>
		<tr>
			<th>Omnicoin Specifications</th>
			<th></th>
			<th></th>
		</tr>
		<tr>
			<td>Total Coins</td>
			<td>13,371,337</td>
			<td></td>
		</tr>
		<tr>
			<td>Block Time</td>
			<td>3 minutes</td>
			<td></td>
		</tr>
		<tr>
			<td>Coins Per Block</td>
			<td>66.85</td>
			<td></td>
		</tr>
		<tr>
			<td>Current Address Version</td>
			<td>115</td>
			<td></td>
		</tr>
	</table>
</div>
<script>
	$("document").ready(function() {
		function updateStats() {
			$.ajax({
				url: "/api?method=getinfo",
				type: "GET",
				contentType: "application/json"
			}).done(function(data) {
				var jsonResponse = jQuery.parseJSON(data);
				$("#total_blocks_mined").html(jsonResponse.response.block_count);
				$("#total_coins_mined").html(format_num(jsonResponse.response.total_mined_omc) + " OMC");
				$("#difficulty").html(jsonResponse.response.difficulty);
				$("#hashrate").html(jsonResponse.response.netmhps + " MH/s");
				$("#last_block_time").html(format_time(0, jsonResponse.response.seconds_since_block, true));
				$("#avg_block_time").html(format_time(0, jsonResponse.response.avg_block_time, true));
				$("#omc_btc_price").html(jsonResponse.response.omc_btc_price + " BTC");
				$("#omc_usd_price").html("$" + jsonResponse.response.omc_usd_price);
				$("#market_cap").html("$" + format_num(jsonResponse.response.market_cap));
			});
		}
		setInterval(updateStats, 1000);
		updateStats();
	});
</script>
<?php
get_footer();
?>
