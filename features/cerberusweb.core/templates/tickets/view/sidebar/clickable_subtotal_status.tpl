<table cellspacing="0" cellpadding="2" border="0" width="220" style="padding-top:5px;">
{foreach from=$counts item=category key=category_id}
	<tr>
		<td style="padding-right:20px;" nowrap="nowrap" valign="top">
			<a href="javascript:;" onclick="ajax.viewAddFilter('{$view_id}', '*_status', '', { 'value[]':'{$category_id}' } );" style="font-weight:bold;">{$category.label}</a> <div class="badge">{$category.hits}</div>
		</td>
	</tr>
{/foreach}
</table>

<script type="text/javascript">
	
</script>
