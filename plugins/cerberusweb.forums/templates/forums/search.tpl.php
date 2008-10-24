{include file="$path/forums/submenu.tpl.php"}

<h1>Forums</h1>

<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td style="padding-right:10px;" valign="top" width="1%" nowrap="nowrap">
			{include file="file:$tpl_path/internal/views/criteria_list.tpl.php" divName="searchCriteriaDialog"}
			<div id="searchCriteriaDialog" style="visibility:visible;"></div>
		</td>
		
		<td valign="top" width="99%">
			<div id="view{$view->id}">
				{$view->render()}
			</div>
		</td>
	</tr>
</table>

