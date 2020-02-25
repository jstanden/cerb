{$uniq_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frm{$uniq_id}" name="frm{$uniq_id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="records">
<input type="hidden" name="action" value="renderMergeMappingPopup">
<input type="hidden" name="context" value="{$context_ext->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>Select {$aliases.plural|lower} to merge:</b><br>
<button type="button" class="cerb-chooser-trigger" data-context="{$context_ext->id}" data-field-name="ids[]" data-query="" {if $context_ext->hasOption('autocomplete')}data-autocomplete="true"{/if}><span class="glyphicons glyphicons-search"></span></button>
<ul class="chooser-container bubbles" style="display:block;">
{if $dicts}
{foreach from=$dicts item=dict}
<li>
	<input type="hidden" name="ids[]" value="{$dict->id}">
	{if $context_ext->hasOption('avatars')}
	<img class="cerb-avatar" src="{devblocks_url}c=avatars&context={$context_ext->id}&context_id={$dict->id}{/devblocks_url}?v={$dict->updated_at}">
	{/if}
	<a href="javascript:;" class="cerb-peek-trigger" data-context="{$dict->_context}" data-context-id="{$dict->id}">{$dict->_label}</a>
	<a href="javascript:;" onclick="$(this).parent().remove();"><span class="glyphicons glyphicons-circle-remove"></span></a>
</li>
{/foreach}
{/if}
</ul>
<br>

{if $active_worker->hasPriv("contexts.{$context_ext->id}.merge")}
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.continue'|devblocks_translate|capitalize}</button>
{/if}
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', "Merge {$aliases.plural|capitalize}");
		$frm = $('#frm{$uniq_id}');
		
		// Peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Chooser
		$popup.find('.cerb-chooser-trigger').cerbChooserTrigger();

		$('#frm{$uniq_id} BUTTON.submit').click(function() {
			// Replace the current form
			genericAjaxPost('frm{$uniq_id}','popuppeek','');
		});
	});
});
</script>