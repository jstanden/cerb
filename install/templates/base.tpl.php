<html>
<head>
<title>Cerberus Helpdesk - Web Installer</title>
<link rel="stylesheet" href="install.css" type="text/css">
</head>

<body>
<H1>Installing Cerberus Helpdesk 4.0</H1>
<table cellpadding="2" cellspacing="2">
	<tr>
		<td>Progress: </td>
		{section start=0 loop=$smarty.const.TOTAL_STEPS name=progress}
		<td {if $smarty.section.progress.iteration <= $step}class='progress_complete'{else}class='progress_incomplete'{/if}>
			&nbsp;
		</td>
		{/section}
		<td>({math equation="(x/y)*100" x=$step y=$smarty.const.TOTAL_STEPS format="%d"}%)</td>
	</tr>
</table>

{if !empty($template)}{include file=$template}{/if}
</body>

</html>