<?php
function get_header($navlist, $currentpage, $pagetitle, $tabtitle = null, $useheader = true) {
if ($tabtitle == null) {
	$tabtitle = $pagetitle;
}
?>
	<!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="utf-8">
			<meta http-equiv="X-UA-Compatible" content="IE=edge">
			<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
			<meta content="OmniCoin, OMC, Omni, OmniCoin Block Explorer, OMC Block Explorer, Omni Block Explorer" name="keywords">
			
			<title><?php echo $tabtitle; ?> - OmniCha.in</title>
			
			<link href="/theme/css/style.css" rel="stylesheet">
			<?php
			if (isset($_COOKIE['theme']) && $_COOKIE['theme'] == "black") {
				?>
				<link href="/theme/css/bootstrap.min.css" rel="stylesheet">
				<?php
			} else {
				?>
				<link href="/theme/css/theme.css" rel="stylesheet">
				<?php
			}
			?>
			<link href="/theme/css/override.css" rel="stylesheet">
			<link href="/theme/css/prism.css" rel="stylesheet">
			<link href="/theme/css/bootstrap-switch.css" rel="stylesheet">
	
			<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
			<link rel="icon" href="favicon.ico" type="image/x-icon">
			
			<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
			<script src="/theme/js/bootstrap.min.js"></script>
			<script src="/theme/js/script.js"></script>
			<script src="/theme/js/prism.js"></script>
			<script src="/theme/js/bootstrap-switch.min.js"></script>
			<?php if ($currentpage['id'] == 4) { ?>
				<script src="/theme/js/highcharts.js"></script>
				<script src="/theme/js/highcharts-exporting.js"></script>
			<?php } else if ($currentpage['id'] == 2) { ?>
				<script src="/theme/js/wallet.js"></script>
				<script src="/theme/js/sha512.js"></script>
				<script src="https://www.google.com/recaptcha/api/js/recaptcha_ajax.js"></script>
			<?php } ?>
			<script>
			(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

			ga('create', 'UA-52620420-1', 'auto');
			ga('require', 'displayfeatures');
			ga('send', 'pageview');
			  
			var RecaptchaOptions = {
				theme : 'custom',
				custom_theme_widget: 'recaptcha_widget'
			};
			</script>
		</head>
		<body>
			<div class="navbar <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] == "black") ? "navbar-inverse" : "navbar-default"; ?> navbar-top">
				<div class="container">
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
					<div class="logo">
						<a href="/"><img src="/theme/images/logo.png"></a>
					</div>
					<div class="navbar-collapse collapse">
						<ul class="nav navbar-nav">
							<?php
							foreach($navlist as $page) {
								if ($page['navbar']) {
									echo "<li " . ($page == $currentpage ? "class='active'" : "") . "><a href='/" . implode("/", $page['url'][0]) . "'>" . $page['navtitle'] . ($page['label'] == "" ? "" : "<span class='badge' style='border-radius:10px;margin-left:5px;'>" . $page['label'] . "</span>") . "</a></li>";
								}
							}
							?>
						</ul>
					</div>
				</div>
			</div>
			<?php if ($useheader) { ?>
				<div class="page-header">
					<div class="container">
						<h2><?php echo $pagetitle; ?></h2>
					</div>
				</div>
			<?php } ?>
<?php
}
?>
