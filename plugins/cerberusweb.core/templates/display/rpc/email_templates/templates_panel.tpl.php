<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/text_rich.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap">
			{if 1==$type}
				<h1>Outgoing E-mail Templates</h1>
			{elseif 2==$type}
				<h1>E-mail Reply Templates</h1>
			{elseif 3==$type}
				<h1>Incoming E-mail Templates</h1>
			{else}
				<h1>E-mail Templates</h1>
			{/if}
		</td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="">
<input type="hidden" name="a" value="">
<input type="hidden" name="reply_id" value="{$reply_id}">
<input type="hidden" name="txt_name" value="{$txt_name}">
<input type="hidden" name="type" value="{$type}">

{*<div id="templatePanelOptions" style="margin-top:5px;"></div>*}

{if !empty($templates)}
	<b>Folder:</b>
	<select name="folder" onchange="genericAjaxGet('templates','c=display&a=getTemplates&type={$type}&reply_id={$reply_id}&folder='+escape(selectValue(this)));">
		<option value="">-- any --</option>
		{foreach from=$folders item=folder}
		<option value="{$folder|escape:"htmlall"}">{$folder}</option>
		{/foreach}
	</select><br>
{/if}

<div id="templates" style="display:block;height:300px;margin:5px;overflow:auto;">
{include file="$path/display/rpc/email_templates/template_results.tpl.php"}
</div>

<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplateEditPanel&type={$type}',null,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_new.gif{/devblocks_url}" align="top"> Create Template</button>
{if !empty($templates)}
	<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplateEditPanel&type={$type}&id='+radioValue(this.form.template_id),null,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> Edit Selected</button>
{/if}

</form>