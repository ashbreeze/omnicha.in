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

get_header($pages, $currentpage, "Rich List");
?>
<div class="container">
	<h3>Top 25 Richest Addresses <small>Updated Hourly. Last update: <span id="last_update"></span></small></h3>
	<table class="table table-striped" id="richlist">
		<tr>
			<th>Rank</th>
			<th>Address</th>
			<th class="hidden-xs">Vanity Name</th>
			<th>Balance</th>
			<th class="hidden-xs">USD Value</th>
			<th class="hidden-xs">Percent of Total OMC</th>
		</tr>
	</table>
</div>
<script>
	$("document").ready(function() {
		function updateRichList() {
			$.ajax({
				url: "/api?method=getrichlist",
				type: "GET",
				contentType: "application/json"
			}).done(function(data) {
				var jsonResponse = jQuery.parseJSON(data);
				$("#last_update").html(jsonResponse.response.last_update);
				$(".richie").remove();
				for (var x = 0; x < jsonResponse.response.richlist.length; x++) {
					var richie = jsonResponse.response.richlist[x];
					$("#richlist").append("<tr class='richie'><td>" + richie.rank + "</td><td><a class='hidden-xs' href='/?address=" + richie.address + "'>" + richie.address + "</a><a class='visible-xs' href='/?address=" + richie.address + "'>" + richie.address.substring(0, 10) + "...</a></td><td>" + richie.vanity_name + "</td><td>" + format_num(richie.balance) + " OMC</td><td>$" + richie.usd_value + "</td><td>" + format_num(richie.percent, 2) + "%</td></tr>");
				}
			});
		}
		setInterval(updateRichList, 60000);
		updateRichList();
	});
</script>
<?php
get_footer();
?>
