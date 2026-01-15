<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Installing Cerb...</title>
	<meta http-equiv="refresh" content="1;url=index.php?step={$step}">
	{include file="includes/cerb.css.tpl"}
</head>
<body>
{include file="includes/icons.tpl"}

<div class="installer-container">
	<header class="installer-header">
		{include file="includes/cerb_logo.svg.tpl"}
		<h1>Installing Cerb {$smarty.const.APP_VERSION}</h1>
	</header>

	<main class="installer-card">
		<div class="loading-container">
			<svg class="spinner" viewBox="0 0 50 50">
				<circle cx="25" cy="25" r="20"></circle>
			</svg>
			<div class="loading-text">Please wait...</div>
		</div>
	</main>
</div>

</body>
</html>
