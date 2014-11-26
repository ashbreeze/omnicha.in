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

var username = "";
var password = "";
var session = "";
var omcPrice = "";
var cron;
var cron2;
var cron3;
var idle = 0;

$(document).ready(function() {
	$(".tip-right").tooltip({
		placement : 'right'
	});
	$("#send-amount").keyup(function() {
		$("#send-amount-usd").val($("#send-amount").val() == "" ? "" : (omc2usd(omcPrice, $("#send-amount").val(), 1000000)));
	});
	$("#send-amount-usd").keyup(function() {
		$("#send-amount").val($("#send-amount-usd").val() == "" ? "" : (usd2omc(omcPrice, $("#send-amount-usd").val())));
	});
	document.onclick = function() {
		window.idle = 0;
	};
	document.onmousemove = function() {
		window.idle = 0;
	};
	document.onkeypress = function() {
		window.idle = 0;
	};
});

function updateSession() {
	var json = {"method": "wallet_login", "username": window.username, "password": window.password};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to wallet server");
	}).done(function(data) {
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error_info == "IP_BANNED") {
				logout();
			} else if (jsonResponse.error_info == "BAD_LOGIN") {
				logout();
			}
		} else {
			session = jsonResponse.response.session;
		}
	});
}

function idleTimer() {
    window.idle++;
    if (idle >= 900) {
        logout();
    }
}

function register() {
	$("#register-username-group").removeClass("has-error");
	$("#register-password-group").removeClass("has-error");
	$("#register-password-confirm-group").removeClass("has-error");
	$(".register-alert").remove();
	var json = {"method": "wallet_register", "username": $("#register-username").val(), "password": hex_sha512($("#register-password").val()), "passwordConfirm": hex_sha512($("#register-password-confirm").val())};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to wallet server");
	}).done(function(data) {
		$(".register-alert").remove();
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error_info == "EMPTY_REQUIRED_FIELDS") {
				$("#register-username-group").addClass("has-error");
				$("#register-password-group").addClass("has-error");
				$("#register-password-confirm-group").addClass("has-error");
				$("#register-form").prepend("<div class='alert alert-danger register-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Required fields left empty</div>")
			} else if (jsonResponse.error_info == "INVALID_USERNAME") {
				$("#register-username-group").addClass("has-error");
				$("#register-form").prepend("<div class='alert alert-danger register-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid username</div>")
			} else if (jsonResponse.error_info == "USERNAME_TAKEN") {
				$("#register-username-group").addClass("has-error");
				$("#register-form").prepend("<div class='alert alert-danger register-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Username is already taken</div>")
			} else if (jsonResponse.error_info == "INVALID_PASSWORD") {
				$("#register-password-group").addClass("has-error");
				$("#register-password-confirm-group").addClass("has-error");
				$("#register-form").prepend("<div class='alert alert-danger register-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid password</div>")
			} else if (jsonResponse.error_info == "NONMATCHING_PASSWORDS") {
				$("#register-password-group").addClass("has-error");
				$("#register-password-confirm-group").addClass("has-error");
				$("#register-form").prepend("<div class='alert alert-danger register-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Passwords don't match</div>")
			}
		} else {
			$("#register-form").slideUp();
			$("#login-form").slideDown();			
			$("#login-form").prepend("<div class='alert alert-success'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Registration complete! Please login.</div>")
		}
	});
}

function login() {
	$("#login-username-group").removeClass("has-error");
	$("#login-password-group").removeClass("has-error");
	$(".login-alert").remove();
	var json = {"method": "wallet_login", "username": $("#login-username").val(), "password": hex_sha512($("#login-password").val())};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to wallet server");
	}).done(function(data) {
		$("#login-username-group").removeClass("has-error");
		$("#login-password-group").removeClass("has-error");
		$(".login-alert").remove();
		window.username = $("#login-username").val();
		window.password = hex_sha512($("#login-password").val());
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error_info == "IP_BANNED") {
				$("#login-form").prepend("<div class='alert alert-danger login-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>IP is banned.</div>")
			} else if (jsonResponse.error_info == "BAD_LOGIN") {
				$("#login-username-group").addClass("has-error");
				$("#login-password-group").addClass("has-error");
				$("#login-form").prepend("<div class='alert alert-danger login-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid credentials</div>")
			}
		} else {
			username = $("#login-username").val();
			password = hex_sha512($("#login-password").val());
			session = jsonResponse.response.session;
			$("#login-form").slideUp('slow', function() {
				setTimeout(function() {
					$(".post-login-nav").slideDown('slow', function() {
						
					});
					$(".tab-0").slideDown('slow');
					$("#login-username").val("");
					$("#login-password").val("");
				}, 1000);
			});
			window.cron = setInterval(getwalletinfo, 10000);
			window.cron2 = setInterval(updateSession, 3300000);
			setTimeout(function() {
				window.cron3 = window.setInterval(idleTimer, 1000);
			}, 500);
			getwalletinfo();
		}
	});
}

function getwalletinfo() {
	var json = {"method": "wallet_getinfo", "username": window.username, "password": window.session};
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
			if (jsonResponse.error_info == "BAD_LOGIN") {
				logout();
			}
		} else {
			var jsonResponse = jQuery.parseJSON(data);
			$(".tx_in").html(jsonResponse.response.tx_in);
			$(".tx_out").html(jsonResponse.response.tx_out);
			$(".total_in").html(format_num(jsonResponse.response.total_in / 100000000) + " OMC");
			$(".total_out").html(format_num(jsonResponse.response.total_out / 100000000) + " OMC");
			$(".balance").html(format_num(jsonResponse.response.balance) + " OMC");
			$(".pending_balance").html(format_num(jsonResponse.response.pending_balance) + " OMC");
			$(".balance-title").html(format_num(jsonResponse.response.balance) + " OMC <small>$" + format_num(omc2usd(omcPrice, jsonResponse.response.balance, 100)) + " USD</small>");
			$(".transaction-entry").remove();
			for (var key in jsonResponse.response.transactions) {
				var tx = jsonResponse.response.transactions[key];
				$("#transaction-list").append("<tr class='transaction-entry'><td><a href='/?transaction=" + tx.tx_hash + "' target='_blank'>" + tx.tx_hash + "</a></td><td>" + tx.date + "</td><td>" + tx.confirmations + "</td><td style='color:" + (tx.value >= 0 ? "green" : "red") + ";'>" + format_num(tx.value / 100000000) + " OMC</td><td>" + format_num(tx.balance / 100000000) + " OMC</td></tr>");			
			}
			$(".receive-list-address").remove();
			for (var key in jsonResponse.response.addresses) {
				var address = jsonResponse.response.addresses[key];
				$("#address-list").append("<tr class='receive-list-address'><td><a href=/?address=" + address.address + "' target='_blank'>" + address.address + "</a></td><td><button type='button' class='btn btn-primary btn-xs' onClick='showPrivKey(\"" + address.private_key + "\");'>Show</button></td><td><button type='button' class='btn btn-primary btn-xs' onClick='$(\"#signmessage-address\").val(\"" + address.address + "\");$(\"#signmessage-message\").val(\"\");$(\".signmessage-alert\").remove();$(\"#signmessage-modal\").modal(\"show\");'>Sign Message</button></td></tr>");
			}
			window.omcPrice = jsonResponse.response.omc_usd_price;
		}
	});
}

function sendomnicoins() {
	$(".send-alert").remove();
	$("#send-amount-group").removeClass("has-error");
	$("#send-address-group").removeClass("has-error");
	var json = {"method": "wallet_send", "username": window.username, "password": window.session, "amount": $("#send-amount").val(), "address": $("#send-address").val()};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to server");
	}).done(function(data) {
		$(".send-alert").remove();
		$("#send-amount-group").removeClass("has-error");
		$("#send-address-group").removeClass("has-error");
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error_info == "BAD_LOGIN") {
				logout();
			} else if (jsonResponse.error_info == "INVALID_AMOUNT" || jsonResponse.error_info == "AMOUNT_ERROR") {
				$(".tab-1").prepend("<div class='alert alert-danger send-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid amount.</div>");
				$("#send-amount-group").addClass("has-error");
			} else if (jsonResponse.error_info == "INVALID_ADDRESS" || jsonResponse.error_info == "ADDRESS_ERROR") {
				$(".tab-1").prepend("<div class='alert alert-danger send-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid address.</div>");
				$("#send-address-group").addClass("has-error");
			} else if (jsonResponse.error_info == "EMPTY_REQUIRED_FIELDS") {
				$(".tab-1").prepend("<div class='alert alert-danger send-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Required fields left empty.</div>");
			} else if (jsonResponse.error_info == "BROKE") {
				$(".tab-1").prepend("<div class='alert alert-danger send-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>You don't have that many OmniCoins!</div>");
			} else if (jsonResponse.error_info == "BROKE_FEE") {
				$(".tab-1").prepend("<div class='alert alert-danger send-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>You don't have enough OmniCoins to include a transaction fee! (0.1 OMC).</div>");
			} else if (jsonResponse.error_info == "SEND_ERROR") {
				$(".tab-1").prepend("<div class='alert alert-danger send-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>An error occurred while creating the transaction.</div>");
			}
		} else {
			$(".tab-1").prepend("<div class='alert alert-success send-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Sent " + jsonResponse.response.amount + " OMC to " + jsonResponse.response.address + "</div>");
			$("#send-amount").val("");
			$("#send-address").val("");
			getwalletinfo();
		}
	});
}

function getnewaddress() {
	$(".receive-alert").remove();
	var json = {"method": "wallet_genaddr", "username": window.username, "password": window.session};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to server");
	}).done(function(data) {
		$(".receive-alert").remove();
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error_info == "BAD_LOGIN") {
				logout();
			} else {
				$(".tab-2").prepend("<div class='alert alert-danger receive-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>You can only create 1 new address per minute.</div>")
			}
		} else {
			$(".tab-2").prepend("<div class='alert alert-success receive-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Created a new Omnicoin address: " + jsonResponse.response.address + "</div>")
			getwalletinfo();
		}
	});
}

function importkey() {
	$(".import-alert").remove();
	var json = {"method": "wallet_importkey", "username": window.username, "password": window.session, "privkey": $("#import-key").val()};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to server");
	}).done(function(data) {
		$(".import-alert").remove();
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error_info == "BAD_LOGIN") {
				logout();
			} else {
				$("#import-key-body").prepend("<div class='alert alert-danger import-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid private key.</div>")
			}
		} else {
			$("#import-key-body").prepend("<div class='alert alert-success import-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Success!</div>")
			getwalletinfo();
		}
	});
}

function signmessage() {
	$(".signmessage-alert").remove();
	var json = {"method": "wallet_signmessage", "username": window.username, "password": window.session, "address": $("#signmessage-address").val(), "message": $("#signmessage-message").val()};
	$.ajax({
		url: "/api",
		type: "GET",
		data: $.param(json),
		contentType: "application/json"
	}).fail(function() {
		alert("Error connecting to server");
	}).done(function(data) {
		$(".signmessage-alert").remove();
		var jsonResponse = jQuery.parseJSON(data);
		if (jsonResponse.error) {
			if (jsonResponse.error_info == "BAD_LOGIN") {
				logout();
			} else {
				$("#import-key-body").prepend("<div class='alert alert-danger import-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Invalid private key.</div>")
			}
		} else {
			$("#signmessage-body").prepend("<div class='alert alert-success import-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>Generated signature: " + jsonResponse.response.signature + "</div>")
			getwalletinfo();
		}
	});
}

function selectTab(tabid) {
	$(".tab-selector").removeClass("active");
	$(".tab").addClass("hide");
	$(".tab-selector-" + tabid).addClass("active");
	$(".tab-" + tabid).removeClass("hide");
	var tabName = $(".tab-selector-" + tabid).first().text();
	$(".current-tab-name").html(tabName + " <span class='caret'></span>");
}

function getSendable() {
	updatewalletinfo();
	$('#send-amount').val(($(".balance").html().replace(" OMC", "") * 1) - 0.1);
}

function showPrivKey(key) {
	alert(key + " - Do NOT share this. It is the key to your OMC");
}

function logout() {
	clearInterval(window.cron);
	clearInterval(window.cron2);
	clearInterval(window.cron3);
	
	username = "";
	password = "";
	session = "";
	$("#login-form").prepend("<div class='alert alert-danger login-alert'><button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>You have been logged out.</div>")
	$(".post-login-nav").slideUp('slow', function() {
		setTimeout(function() {
			$("#login-form").slideDown('slow', function() {
				
			});
		}, 1000);
	});
	selectTab(0);
	$(".tab-0").slideUp('slow');
}