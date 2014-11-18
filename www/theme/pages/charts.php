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
	<div class="alert alert-info">
		Currently working on a new charts system. Data is a little inaccurate.
	</div>
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
			/*
			function afterSetExtremes(e) {
				var chart = $('#difficulty').highcharts();
				var zoom = e.rangeSelectorButton.text
				
				chart.showLoading('Loading data from server...');
				$.ajax({
					url: "/api?method=getcharts&zoom=" + zoom,
					type: "GET",
					contentType: "application/json"
				}).done(function(data) {
					var jsonResponse = jQuery.parseJSON(data);
					chart.series[0].setData(jsonResponse.response.difficulty);
					//chart.series[0].update({pointInterval: 1000 * jsonResponse.response.zoom});
					chart.hideLoading();
				});
			}
			*/
			$('#difficulty').highcharts('StockChart', {
				title: {
					text: 'Average Mining Difficulty Per Day',
					x: -20
				},
				xAxis: {
					type: 'datetime',
					minRange: 1000 * 60 * 15/*,
					events: {
						afterSetExtremes: function(e) {
							if (typeof(e.rangeSelectorButton) !== 'undefined') {
								afterSetExtremes(e);
							}
						}
					}*/
				},
				yAxis: {
					title: {
						text: ''
					},
					floor: 0
				},
				series: [{
					name: 'Difficulty',
					data: jsonResponse.response.difficulty,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: '',
						count: 0,
						text: '15m'
					},{
						type: '',
						count: 0,
						text: '30m'
					},{
						type: '',
						count: 0,
						text: '1h'
					},{
						type: '',
						count: 0,
						text: '6h'
					},{
						type: '',
						count: 0,
						text: '12h'
					},{
						type: '',
						count: 0,
						text: '1d'
					}],
					selected: 5
				}
			});
			/*
			$('#price').highcharts('StockChart', {
				title: {
					text: 'Exchange Price',
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
					data: jsonResponse.response.btc_price,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
					yAxis: 0,
					color: '#dd4814'
				},{
					name: 'USD Exchange Price',
					data: jsonResponse.response.usd_price,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			$('#volume').highcharts('StockChart', {
				title: {
					text: 'Exchange Volume',
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
					data: jsonResponse.response.volume,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			*/
			$('#transactions').highcharts('StockChart', {
				title: {
					text: 'Transactions',
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
					data: jsonResponse.response.transactions,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			$('#transaction-volume').highcharts('StockChart', {
				title: {
					text: 'Transaction Volume',
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
					data: jsonResponse.response.transaction_volume,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			$('#block-time').highcharts('StockChart', {
				title: {
					text: 'Block Time',
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
					data: jsonResponse.response.block_time,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			$('#hashrate').highcharts('StockChart', {
				title: {
					text: 'Hashrate',
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
					data: jsonResponse.response.hashrate,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			$('#coins-mined').highcharts('StockChart', {
				title: {
					text: 'Coins Mined',
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
					data: jsonResponse.response.coins_mined,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			$('#lifetime-coins-mined').highcharts('StockChart', {
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
					data: jsonResponse.response.lifetime_coins_mined,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			$('#lifetime-transactions').highcharts('StockChart', {
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
					data: jsonResponse.response.lifetime_transactions,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
			$('#lifetime-transactions-volume').highcharts('StockChart', {
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
					data: jsonResponse.response.lifetime_transactions_volume,
					pointStart: Date.UTC(2014, 3, 5, 12),
					pointInterval: 1000 * jsonResponse.response.zoom,
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
				},
				rangeSelector: {
					allButtonsEnabled: true,
					buttons: [{
						type: 'week',
						count: 1,
						text: '1w'
					},{
						type: 'month',
						count: 1,
						text: '1m'
					},{
						type: 'month',
						count: 3,
						text: '3m'
					},{
						type: 'month',
						count: 6,
						text: '6m'
					},{
						type: 'year',
						count: 1,
						text: '1y'
					},{
						type: 'all',
						text: 'All'
					}],
					selected: 5
				}
			});
		});
	});
</script>
<?php
get_footer();
?>
