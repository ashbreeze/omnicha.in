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

get_header($pages, $currentpage, "Charts");
?>
<div class="container">
	<div class="row">
		<div style="margin-bottom:50px;" id="difficulty"></div>
		<div style="margin-bottom:50px;" id="price"></div>
		<div style="margin-bottom:50px;" id="volume"></div>
		<div style="margin-bottom:50px;" id="transactions"></div>
		<div style="margin-bottom:50px;" id="transaction-volume"></div>
		<div style="margin-bottom:50px;" id="block-time"></div>
		<div style="margin-bottom:50px;" id="hashrate"></div>
		<div style="margin-bottom:50px;" id="coins-mined"></div>
		<div style="margin-bottom:50px;" id="lifetime-coins-mined"></div>
		<div style="margin-bottom:50px;" id="lifetime-transactions"></div>
		<div style="margin-bottom:50px;" id="lifetime-transactions-volume"></div>
	</div>
</div>
<script>
	$("document").ready(function() {
		$.ajax({
			url: "/api?method=getcharts",
			type: "GET",
			contentType: "application/json"
		}).done(function(data) {
			var jsonResponse = jQuery.parseJSON(data);
			var difficulty = jsonResponse.response.difficulty;
			var btc_price = jsonResponse.response.btc_price;
			var usd_price = jsonResponse.response.usd_price;
			var volume = jsonResponse.response.volume;
			var transactions = jsonResponse.response.transactions;
			var transaction_volume = jsonResponse.response.transaction_volume;
			var block_time = jsonResponse.response.block_time;
			var hashrate = jsonResponse.response.hashrate;
			var coins_mined = jsonResponse.response.coins_mined;
			var lifetime_coins_mined = jsonResponse.response.lifetime_coins_mined;
			var lifetime_transactions = jsonResponse.response.lifetime_transactions;
			var lifetime_transactions_volume = jsonResponse.response.lifetime_transactions_volume;

			$('#difficulty').highcharts({
				title: {
					text: 'Average Mining Difficulty Per Day',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Difficulty',
					data: difficulty,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#price').highcharts({
				title: {
					text: 'Average Exchange Price Per Day',
					x: -20
				},
				subtitle: {
					text: 'Courtesy of AllCrypt.com',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: [{
					title: {
						text: ''
					},
					floor: 0,
					labels: {
						format: '{value} BTC'
					}
				},{
					title: {
						text: ''
					},
					opposite: true,
					floor: 0,
					labels: {
						format: '{value} USD'
					}
				}],
				series: [{
					name: 'BTC Exchange Price',
					data: btc_price,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					yAxis: 0,
					color: '#dd4814'
				},{
					name: 'USD Exchange Price',
					data: usd_price,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					yAxis: 1
				}],
				tooltip: {
					shared: true
				},
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#volume').highcharts({
				title: {
					text: 'Exchange Volume Per Day',
					x: -20
				},
				subtitle: {
					text: 'Courtesy of AllCrypt.com',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Volume',
					data: volume,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				tooltip: {
					valueSuffix: " OMC"
				},
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#transactions').highcharts({
				title: {
					text: 'Transactions Per Day',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Transactions',
					data: transactions,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#transaction-volume').highcharts({
				title: {
					text: 'Transaction Volume Per Day',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Volume',
					data: transaction_volume,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				tooltip: {
					valueSuffix: " OMC"
				},
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#block-time').highcharts({
				title: {
					text: 'Average Block Time Per Day',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0,
					plotBands: [{
						color: '#CCFFCC',
						from: 0,
						to: 180
					}]
				},
				series: [{
					name: 'Block Time',
					data: block_time,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				tooltip: {
					valueSuffix: " Seconds"
				},
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#hashrate').highcharts({
				title: {
					text: 'Average Hashrate Per Day',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Hashrate',
					data: hashrate,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				tooltip: {
					valueSuffix: " MH/s"
				},
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#coins-mined').highcharts({
				title: {
					text: 'Coins Mined Per Day',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Coins Mined',
					data: coins_mined,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				tooltip: {
					valueSuffix: " OMC"
				},
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#lifetime-coins-mined').highcharts({
				title: {
					text: 'Total Coins Mined',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Coins Mined',
					data: lifetime_coins_mined,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				tooltip: {
					valueSuffix: " OMC"
				},
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#lifetime-transactions').highcharts({
				title: {
					text: 'Total Transactions',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Transactions',
					data: lifetime_transactions,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
			$('#lifetime-transactions-volume').highcharts({
				title: {
					text: 'Total Transaction Volume',
					x: -20
				},
				xAxis: {
					type: 'datetime'
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Volume',
					data: lifetime_transactions_volume,
					pointStart: Date.UTC(2014, 3, 4),
					pointInterval: 24 * 3600 * 1000,
					color: '#dd4814'
				}],
				tooltip: {
					valueSuffix: " OMC"
				},
				credits: {
					enabled: false
				},
				plotOptions: {
					series: {
						marker: {
							enabled: false
						}
					}
				}
			});
		});
	});
</script>
<?php
get_footer();
?>
