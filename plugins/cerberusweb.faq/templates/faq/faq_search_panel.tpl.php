<div id="faqSearchPanel">
<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="{devblocks_url}c=resource&a=cerberusweb.faq&f=images/help.gif{/devblocks_url}" align="absmiddle">&nbsp;</td>
		<td align="left" width="100%" nowrap="nowrap"><h1>Search FAQ</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>

{* Keyword Search Mode *}
<form action="javascript:;" id="formFaqSearch" onsubmit="genericAjaxGet('faqSearchPanel','c=faq&a=showFaqSearchPanel&q='+this.q.value);">
	<input type="hidden" name="c" value="faq">
	<input type="hidden" name="a" value="showFaqSearchPanel">
	<input type="text" name="q" size="45" style="width:98%;font-size:18px;" value="{$query|escape:"htmlall"}">
	<br>
</form>

{* Results *}
{if !empty($query)}
<form>
<div style="height:250px;overflow:auto;border:1px solid rgb(180,180,180);background-color:rgb(255,255,255);margin:2px;padding:3px;">
	{if !empty($results)}
	<b>({$results_count} results):</b><br>
	{foreach from=$results item=result key=result_id}
		<img src="{devblocks_url}c=resource&a=cerberusweb.faq&f=images/help.gif{/devblocks_url}" align="absmiddle">
		<a href="javascript:;" onclick="genericAjaxGet('faqSearchAnswer{$result_id}','c=faq&a=showFaqAnswer&id={$result_id}');" style="color:rgb(0, 102, 255);font-weight:normal;">{$result.f_question}</a>
		<br>
		<div id="faqSearchAnswer{$result_id}"></div>
	{/foreach}
	{else}
	No results found.
	{/if}
</div>
</form>
{/if}
</div>