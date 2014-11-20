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

function get_footer() {
	global $wallet;
?>
	<div class="footer hidden-xs">
		<div class="container">
			<p class="text-muted" style="margin:10px 0;">
				Website by <a href="http://www.hackforums.net/member.php?action=profile&uid=1256441">Abraham Lincoln</a>
				<span class="pull-right">Server Time: <?php echo date("Y-m-d H:i:s"); ?> - <a href="#" data-toggle="modal" data-target="#node-modal"><?php echo count($peerinfo = $wallet->getpeerinfo()); ?> Connected Nodes</a></span>
			</p>
		</div>
	</div>
	<div class="modal fade" id="node-modal" tabindex="-1" role="dialog" aria-hidden="true">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					<h4 class="modal-title">Connected Nodes</h4>
				</div>
				<div class="modal-body">
					<table class="table table-bordered table-striped">
						<tr>
							<th>IP</th>
							<th>Version</th>
							<th>Time Connected</th>
						</tr>
						<?php
						foreach ($peerinfo as $node) {
							?>
							<tr>
								<td><?php echo $node['addr']; ?></td>
								<td><?php echo $node['version']; ?></td>
								<td><?php echo format_time($node['conntime']); ?></td>
							</tr>
							<?php
						}
						?>
					</table>
				</div>
			</div>
		</div>
	</div>
	</body>
</html>
<?php
}
?>