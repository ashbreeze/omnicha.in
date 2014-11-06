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
