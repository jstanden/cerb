<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formSnippetsPeek" name="formSnippetsPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveSnippetsPeek">
<input type="hidden" name="id" value="{$snippet->id}">
<input type="hidden" name="context" value="{$snippet->context}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.title'|devblocks_translate|capitalize}</legend>
	
	<input type="text" name="title" value="{$snippet->title}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;">
</fieldset>

<fieldset>
	<legend>{'common.content'|devblocks_translate|capitalize}</legend>
	
	<textarea name="content" style="width:98%;height:250px;border:1px solid rgb(180,180,180);padding:2px;">{$snippet->content}</textarea>
	<br>
	
	{if !empty($token_labels)}
	<button type="button" onclick="genericAjaxPost('formSnippetsPeek','peekTemplateTest','c=internal&a=snippetTest&snippet_context={$snippet->context}&snippet_field=content');"><span class="cerb-sprite sprite-gear"></span> Test</button>
	<select onchange="insertAtCursor(this.form.content,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.content.focus();">
		<option value="">-- insert at cursor --</option>
		{foreach from=$token_labels key=k item=v}
		<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
		{/foreach}
	</select>
	<br>
	<div id="peekTemplateTest"></div>
	{/if}
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formSnippetsPeek', 'view{$view_id}')"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
{if !empty($snippet->id) && ($active_worker->is_superuser || $snippet->created_by==$active_worker->id)}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this snippet?')) { this.form.do_delete.value='1';genericAjaxPopupClose('peek');genericAjaxPost('formSnippetsPeek', 'view{$view_id}'); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		{if empty($snippet->id)}
		$(this).dialog('option','title', 'Create Snippet ({$snippet->context})');
		{else}
		$(this).dialog('option','title', 'Modify Snippet ({$snippet->context})');
		{/if}
	} );
</script>
