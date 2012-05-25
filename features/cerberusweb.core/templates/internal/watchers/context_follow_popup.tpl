{if $active_worker->hasPriv('core.watchers.assign')}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmWatchersPopup" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveContextWatchers">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">
{else}
<form action="#" method="post" id="" onsubmit="return false;">
{/if}

<b>{$extension->manifest->name}</b>: 

{if isset($meta) && !empty($meta.name) && !empty($meta.permalink)}
<a href="{$meta.permalink}">{$meta.name}</a>
<br>
<br>
{/if}

{if $active_worker->hasPriv('core.watchers.assign')}
<b>Add:</b> <button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
{/if}

<ul class="bubbles chooser-container" style="display:block;">
	{foreach from=$object_watchers.{$context_id} item=watcher key=watcher_id}
	{if isset($workers.{$watcher_id})}
		{$worker = $workers.{$watcher_id}}
		<li>
			<input type="hidden" name="current_watchers[]" value="{$worker->id}">
			<a href="{devblocks_url}c=profiles&t=worker&id={$worker->id}-{$worker->getName()|devblocks_permalink}&tab=links{/devblocks_url}" target="_blank">{$worker->getName()}</a><!--
			-->{if $active_worker->hasPriv('core.watchers.unassign')}<a href="javascript:;" onclick="$li=$(this).closest('li');$('<input type=\'hidden\' name=\'delete_worker_ids[]\' value=\'{$worker->id}\'>').appendTo($li);$li.hide();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>{/if}
		</li>
	{/if}
	{/foreach}
</ul>
<br>

{if $active_worker->hasPriv('core.watchers.assign')}
<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
{else}
<button type="button" onclick="genericAjaxPopupDestroy('watchers');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.ok'|devblocks_translate}</button>
{/if}

</form>

<script type="text/javascript">
	// Popups
	$popup = genericAjaxPopupFetch('watchers');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title',"{'common.watchers'|devblocks_translate|capitalize}");

		{if $active_worker->hasPriv('core.watchers.assign')}
			// Choosers
			$(this).find('button.chooser_worker').each(function() {
				ajax.chooser(this,'cerberusweb.contexts.worker','add_worker_ids', { autocomplete:true });
			});
	
			$(this).find('button.submit').click(function(e) {
				$popup = genericAjaxPopupFetch('watchers');
				$frm = $(this).closest('form');
	
				add_worker_ids = $frm
					.find('input:hidden[name="add_worker_ids[]"]')
					.map(function(e) { 
						return $(this).val(); 
					})
					.get()
					;
				delete_worker_ids = $frm
					.find('input:hidden[name="delete_worker_ids[]"]')
					.map(function(e) { 
						return $(this).val(); 
					})
					.get()
					;

				event = jQuery.Event('watchers_save');
				event.add_worker_ids = add_worker_ids;
				event.delete_worker_ids = delete_worker_ids;
				$popup.trigger(event);
				
				genericAjaxPopupDestroy('watchers');
			});
	
			$(this).find('input:text:first').focus();
		{/if}
	});
</script>