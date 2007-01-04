<h1>Knowledgebase</h1>
<img src="images/view.gif"> <b>Search:</b> 
<input type="text" size="45"><input type="button" value="Go!"> <input type="button" value="Add Content"><br>
<br>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><h2>Categories:</h2></td>
		<td width="100%" nowrap="nowrap" valign="middle">
			<img src="images/spacer.gif" width="5" height="1">
			<a href="javascript:;" onclick="kbAjax.showMailboxRouting(this);">manage</a>
		</td>
	</tr>
</table>
{foreach from=$trail item=tn name=trails}
<a href="index.php?c={$c}&a=click&id={$tn->id}">{$tn->name}</a> :  
{/foreach}
<br>
<br>

{include file="file:$path/knowledgebase/category_table.tpl.php"}

<h2>Resources:</h2>
{include file="file:$path/knowledgebase/resource_list.tpl.php"}

<script>
	var kbAjax = new cKbAjax();
</script>