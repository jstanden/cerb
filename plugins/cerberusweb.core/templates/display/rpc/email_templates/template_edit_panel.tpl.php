<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/text_rich.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap"><h1>E-mail Reply Templates</h1></td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="post" name="replyTemplateEditForm" onsubmit="return false;">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveReplyTemplate">
<input type="hidden" name="id" value="{$template->id}">
<input type="hidden" name="do_delete" value="0">

<b>Title:</b><br>
<input type="text" name="title" size="35" value="{$template->title|escape:"htmlall"}" style="width:100%;"><br>

<b>Description:</b><br>
<input type="text" name="description" size="35" value="{$template->description|escape:"htmlall"}" style="width:100%;"><br>

<b>Folder:</b><br>
<select name="folder" onchange="toggleDiv('replyTemplateFolderNew',(selectValue(this)==''?'inline':'none'));">
	{foreach from=$folders item=folder}
	<option value="{$folder|escape:"htmlall"}" {if $template->folder==$folder}selected{/if}>{$folder}</option>
	{/foreach}
	<option value="">-- new folder: --</option>
</select>
<span id="replyTemplateFolderNew" style="display:{if empty($folders)}inline{else}none{/if};">
<b>Folder Name:</b> 
<input type="text" name="folder_new" value="" size="24" maxlength="64">
</span>
<br>

<b>Text:</b><br>
<textarea name="template" rows="10" cols="45" style="width:100%;">{$template->content}</textarea><br>

<b>Insert Placeholder:</b> <select name="token" onchange="insertAtCursor(this.form.template,selectValue(this.form.token));this.form.token.selectedIndex=0;this.form.template.focus();">
	<option value="">-- choose --</option>
	<optgroup label="Sender">
		<option value="#sender_first_name#">First Name</option>
		<option value="#sender_last_name#">Last Name</option>
		<option value="#sender_org#">Organization</option>
	</optgroup>
	<optgroup label="Ticket">
		<option value="#ticket_mask#">Mask</option>
		<option value="#ticket_subject#">Subject</option>
	</optgroup>
	<optgroup label="Worker">
		<option value="#worker_first_name#">First Name</option>
		<option value="#worker_last_name#">Last Name</option>
		<option value="#worker_title#">Title</option>
	</optgroup>
</select>
<br>
<br>

<button type="button" onclick="saveGenericAjaxPanel('replyTemplateEditForm',true,displayAjax.onSaveReplyTemplate);"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_ok.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{if $template->id}
<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this template?')){literal}{{/literal}this.form.do_delete.value='1';saveGenericAjaxPanel('replyTemplateEditForm',true,displayAjax.onSaveReplyTemplate);{literal}}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_delete.gif{/devblocks_url}" align="top"> {$translate->_('common.delete')|capitalize}</button>
{/if}

</form>