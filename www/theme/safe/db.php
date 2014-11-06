<?php
$abedatabase = mysqli_connect('localhost', 'abe', '*********************************************', 'abe');
$database = mysqli_connect('localhost', 'omnichain', '*********************************************', 'omnichain');

function get_total_blocks($database) {
	return mysqli_fetch_array(mysqli_query($database, "SELECT block_height FROM chain_summary ORDER BY block_height DESC LIMIT 1"))['block_height'];
}

function get_option($database, $name) {
	return mysqli_fetch_array(mysqli_query($database, "SELECT value FROM options WHERE name = '" . $name . "'"))['value'];
}

function set_option($database, $id, $value) {
	return mysqli_query($database, "UPDATE options SET value = '" . $value . "' WHERE id = '" . $id . "'");
}
?>
