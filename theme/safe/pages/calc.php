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

get_header($pages, $currentpage, "Mining Calc");

$lastblock = mysqli_fetch_array(mysqli_query($abedatabase, "SELECT b.block_nBits FROM block AS b JOIN chain_candidate AS cc ON (cc.block_id = b.block_id) AND cc.in_longest = 1 ORDER BY b.block_height DESC LIMIT 0, 1"));
$difficulty = calculate_difficulty($lastblock['block_nBits']);
?>
<div class="container">
	<div class="row" class="step-1">
		<div class="col-md-6">
			<div class="panel panel-success">
				<div class="panel-heading">
					<h2 class="panel-title">Mining Calc</h2>
				</div>
				<div class="panel-body">
					<form class="form" id="buy_form">
						<div class="form-group">
							<div class="input-group">
								<input name="address" type="text" class="form-control" id="hashrate">
								<span class="input-group-addon">MH/s</span>
							</div>
						</div>
						<div class="form-group">
							<div class="input-group">
								<input name="address" type="text" class="form-control" value="<?php echo $difficulty; ?>" id="difficulty">
								<span class="input-group-addon">Difficulty</span>
							</div>
						</div>
						<div class="form-group">
							<div class="input-group">
								<input name="address" type="text" class="form-control" id="earnings">
								<span class="input-group-addon">OMC</span>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
$("#hashrate").keyup(function() { calculate(); });
$("#difficulty").keyup(function() { calculate(); });

function calculate() {
	var json = {"method": "earningscalc", "hashrate": $("#hashrate").val(), "difficulty": $("#difficulty").val()};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to server");
	}).done(function(data) {
		var jsonResponse = jQuery.parseJSON(data);
		if (!jsonResponse.error) {
			$("#earnings").val(number_format(jsonResponse.response.daily, 4));
		}
	});
};
</script>
<?php
get_footer();
?>