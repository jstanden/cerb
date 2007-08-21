{if !empty($templates)}
	<b>Folder:</b>
	<select name="folder" onchange="genericAjaxGet('templates','c=display&a=getTemplates&reply_id={$reply_id}&folder='+escape(selectValue(this)));">
		<option value="">-- any --</option>
		{foreach from=$folders item=folder}
		<option value="{$folder|escape:"htmlall"}">{$folder}</option>
		{/foreach}
	</select><br>
{/if}

<div id="templates" style="display:block;height:300px;margin:5px;overflow:auto;">
{include file="$path/display/rpc/email_templates/template_results.tpl.php"}
</div>

<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplateEditPanel&reply_id={$reply_id}',null,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_new.gif{/devblocks_url}" align="top"> Create Template</button>
{if !empty($templates)}
	<button type="button" onclick="genericAjaxPanel('c=display&a=showTemplateEditPanel&reply_id={$reply_id}&id='+radioValue(this.form.template_id),null,false,'550px');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/document_edit.gif{/devblocks_url}" align="top"> Edit Selected</button>
{/if}
