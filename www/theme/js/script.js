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

function omc2usd(omcrate, omc, roundnum) {
	if (roundnum == undefined) {
		var roundnum = 5;
	}
	var price = omc * omcrate;
	if (price != 0) {
		while ((Math.round(omc * omcrate * roundnum) / roundnum) == 0) {
			roundnum *= 10;
		}
	}

	return (Math.round(omc * omcrate * roundnum) / roundnum);
}

function usd2omc(omc_rate, usd) {
	return usd / omc_rate;
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toGMTString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
} 

function format_num(val, precision) {
	precision = (typeof precision === 'undefined') ? 10 : precision;
	var to_return = number_format(Math.round(val * Math.pow(10, precision)) / Math.pow(10, precision), 10).replace(/0+$/, '').replace(/[.]+$/, '');
	return to_return == "" ? "0" : to_return;
}

function format_time(seconds, seconds2, precise) {
	seconds2 = (typeof seconds2 === 'undefined') ? new Date().getTime() : seconds2;
	precise = (typeof precise === 'undefined') ? false : precise;
	var time = (seconds2 - seconds);
	var time2 = 0;
	var postfix = "second";
	var postfix2 = "";
	if (time >= 60) {
		postfix2 = postfix;
		var old_time = time;
		time = time / 60;
		postfix = "minute";
		if (precise) {
			time2 = old_time - (60 * Math.floor(time));
		}
		if (time >= 60) {
			postfix2 = postfix;
			old_time = time;
			time = time / 60;
			postfix = "hour";
			time2 = old_time - (60 * Math.floor(time));
			if (time >= 24) {
				postfix2 = postfix;
				old_time = time;
				time = time / 24;
				postfix = "day";
				time2 = old_time - (24 * Math.floor(time));				
				if (time >= 7) {
					postfix2 = postfix;
					old_time = time;
					time = time / 7;
					postfix = "week";
					time2 = old_time - (7 * Math.floor(time));
					if (time >= 4.34812) {
						postfix2 = postfix;
						old_time = time;
						time = time / 4.34812;
						postfix = "month";
						time2 = old_time - (4.34812 * Math.floor(time));
						if (time >= 52.1775) {
							postfix2 = postfix;
							old_time = time;
							time = time / 52.1775;
							postfix = "year";
							time2 = old_time - (52.1775 * Math.floor(time));
						}
					}
				}
			}
		}
	}
	time = Math.floor(time);
	time2 = Math.floor(time2);
	return time + " " + postfix + format_s(time) + (time2 != 0 ? (", " + time2 + " " + postfix2 + format_s(time2)) : "");
}

function number_format(number, decimals, dec_point, thousands_sep) {
  number = (number + '')
    .replace(/[^0-9+\-Ee.]/g, '');
  var n = !isFinite(+number) ? 0 : +number,
    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
    s = '',
    toFixedFix = function(n, prec) {
      var k = Math.pow(10, prec);
      return '' + (Math.round(n * k) / k)
        .toFixed(prec);
    };
  // Fix for IE parseFloat(0.55).toFixed(0) = 0;
  s = (prec ? toFixedFix(n, prec) : '' + Math.round(n))
    .split('.');
  if (s[0].length > 3) {
    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
  }
  if ((s[1] || '')
    .length < prec) {
    s[1] = s[1] || '';
    s[1] += new Array(prec - s[1].length + 1)
      .join('0');
  }
  return s.join(dec);
}

function format_s(number) {
	return (number == "1" ? "" : "s");
}
