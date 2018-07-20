<html>
<head>
	<title>Installing Cerb...</title>
	<link rel="stylesheet" href="install.css" type="text/css">
	<script type="text/javascript" src="jquery-1.11.2.min.js"></script>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
</head>

<body>
<div>
	<img src="cerb_logo.png">
</div>
<H1>Installing Cerb {$smarty.const.APP_VERSION}</H1>
<table cellpadding="2" cellspacing="2">
	<tr>
		<td>Progress: </td>
		{section start=0 loop=$smarty.const.TOTAL_STEPS name=progress}
		<td {if $smarty.section.progress.iteration > $step}class='progress_incomplete'{else}class='progress_complete'{/if}>
			&nbsp;
		</td>
		{/section}
		<td>({math equation="(x/y)*100" x=$step y=$smarty.const.TOTAL_STEPS format="%d"}%)</td>
	</tr>
</table>

{if !empty($template)}{include file=$template}{/if}
</body>

</html>