<?php
function get_footer() {
?>
	<div class="footer hidden-xs">
		<div class="container">
			<p class="text-muted" style="margin:10px 0;">
				Website by <a href="http://www.hackforums.net/member.php?action=profile&uid=1256441">Abraham Lincoln</a>
				<span class="pull-right">Server Time: <?php echo date("y-m-d H:i:s"); ?></span>
				<span class="pull-right" style="margin: 0 10px;"><input type="checkbox" data-off-text="O" data-on-text="B" data-size="mini" name="theme-color" <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == "black") ? "checked" : ""; ?>></span>
			</p>
		</div>
	</div>
	<script>
	$("[name='theme-color']").bootstrapSwitch();
	$('[name="theme-color"]').on({
		'switchChange.bootstrapSwitch': function(event, state) {
			setCookie("theme", state ? "black" : "orange", 365);
			location.reload();
		}
	});
	</script>
	</body>
</html>
<?php
}
?>
