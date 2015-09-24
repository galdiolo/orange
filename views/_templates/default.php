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
		<link href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css" rel="stylesheet">
		<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
		<?=$page_css ?>
		<link href="//fonts.googleapis.com/css?family=Roboto:400,700,700italic,400italic" rel="stylesheet" type="text/css">
		<style><?=$page_style ?></style>
		<?=$page_head ?>
	</head>
	<body class="<?=$page_body_class ?>">
		<?=$page_start ?>
		<?=$page_header ?>
		<div class="container">
		<?=$page_center ?>
		</div>
		<?=$page_footer ?>
		<script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
		<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>
		<script><?=$javascript_variables ?></script>
		<?=$page_js ?>
		<script><?=$page_script ?></script>
		<?=$page_end ?>
		<!--<?=base64_encode('ROUTE_RAW:'.$route_raw.' ROUTE_CLASS:'.$route_class.' ROUTE:'.$route.' ENV:'.ENVIRONMENT.' CONFIG:'.CONFIG.' LOG_THRESHOLD:'.LOG_THRESHOLD) ?>-->
	</body>
</html>