<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formSnippetsPeek" name="formSnippetsPeek" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveSnippetsPeek">
<input type="hidden" name="id" value="{$snippet->id}">
<input type="hidden" name="owner_context" value="{$snippet->owner_context}">
<input type="hidden" name="owner_context_id" value="{$snippet->owner_context_id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<fieldset>
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
	<input type="text" name="title" value="{$snippet->title}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;"><br>
	<br>
	
	<b>Type:</b><br>
	<select name="context">
		<option value="" {if empty($snippet->id)}selected="selected"{/if}>Plaintext</option>
		{foreach from=$contexts item=ctx key=k}
		{if is_array($ctx->params.options.0) && isset($ctx->params.options.0.snippets)}
		<option value="{$k}" {if $snippet->context==$k}selected="selected"{/if}>{$ctx->name}</option>
		{/if}
		{/foreach}
	</select>
	<br>
	<br>

	<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
	<textarea name="content" style="width:98%;height:200px;border:1px solid rgb(180,180,180);padding:2px;">{$snippet->content}</textarea>
	<br>
	
	<div class="toolbar"></div>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{if $active_worker->hasPriv('core.snippets.actions.create')}
	<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formSnippetsPeek', 'view{$view_id}')"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
{else}
	<fieldset class="delete" style="font-weight:bold;">
		{'error.core.no_acl.edit'|devblocks_translate}
	</fieldset>
{/if}
{if !empty($snippet->id)}
	{if $snippet->created_by==$active_worker->id || $active_worker->hasPriv('core.snippets.actions.update_all')}
	<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this snippet?')) { this.form.do_delete.value='1';genericAjaxPopupClose('peek');genericAjaxPost('formSnippetsPeek', 'view{$view_id}'); } "><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {$translate->_('common.delete')|capitalize}</button>
	{/if}
{/if}
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		{if empty($snippet->id)}
		$(this).dialog('option','title', 'Create Snippet');
		{else}
		$(this).dialog('option','title', 'Modify Snippet');
		{/if}

		// Change
		
		$change_dropdown = $popup.find("form select[name=context]");
		$change_dropdown.change(function(e) {
			ctx = $(this).val();
			genericAjaxGet($popup.find('DIV.toolbar'), 'c=internal&a=showSnippetsPeekToolbar&context=' + ctx);
		});
		
		// [TODO] If editing and a target context is known
		{if !empty($snippet->context)}
		genericAjaxGet($popup.find('DIV.toolbar'), 'c=internal&a=showSnippetsPeekToolbar&context={$snippet->context}');
		{/if}
		
		$(this).find('input:text:first').focus().select();
	} );
</script>
