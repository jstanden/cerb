<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><span class="cerb-sprite sprite-text_rich"></span></td>
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

{if !empty($templates)}
	<b>Folder:</b>
	<select name="folder" onchange="genericAjaxGet('templates','c=display&a=getTemplates&type={$type}&reply_id={$reply_id}&txt_name={$txt_name}&folder='+escape(selectValue(this)));">
		<option value="">-- any --</option>
		{foreach from=$folders item=folder}
		<option value="{$folder|escape}">{$folder}</option>
		{/foreach}
	</select><br>
{/if}

<div id="templates" style="display:block;margin:5px;">
{include file="$core_tpl/display/rpc/email_templates/template_results.tpl"}
</div>
<br>

<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplateEditPanel&type={$type}',null,false,'550');"><span class="cerb-sprite sprite-add"></span> Create Template</button>
{if !empty($templates)}
	<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplateEditPanel&type={$type}&id='+$('input[name=\'template_id\']:checked').val(),null,false,'550');"><span class="cerb-sprite sprite-document_edit"></span> Edit Selected</button>
{/if}
<button type="button" onclick="genericPanel.dialog('close');"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>
</form>