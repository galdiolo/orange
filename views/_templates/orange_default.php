<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="x-ua-compatible" content="ie=edge">
		<title><?=$page_title ?></title>
		<meta name="description" content="<?=$meta_description ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<?=$page_meta ?>
		<link rel="icon" type="image/x-icon" href="<?=$theme_path ?>/assets/images/box.png">
		<link rel="apple-touch-icon" href="<?=$theme_path ?>/assets/images/box.png">
		<?=$page_css ?>
		<link href="//fonts.googleapis.com/css?family=Roboto:400,700,700italic,400italic" rel="stylesheet" type="text/css">
		<style><?=$page_style ?></style>
		<?=$page_head ?>
	</head>
	<body class="<?=$page_body_class ?>">
		<?=bootstrap_menu::nav() ?>
		<?=$page_start ?>
		<?=$page_header ?>
		<div class="container">
		<?=$page_center ?>
		</div>
		<?=$page_footer ?>
		<script><?=$javascript_variables ?></script>
		<?=$page_js ?>
		<script><?=$page_script ?></script>
		<?=$page_end ?>
		<!--<?=base64_encode('ROUTE_RAW:'.$route_raw.' ROUTE_CLASS:'.$route_class.' ROUTE:'.$route.' ENV:'.ENVIRONMENT.' DEBUG:'.DEBUG.' LOG_THRESHOLD:'.LOG_THRESHOLD) ?>-->
	</body>
</html>