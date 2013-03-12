<form action="{devblocks_url}{/devblocks_url}" method="post" id="internalJournalPopup" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="journalSavePopup">
<input type="hidden" name="context" value="{$context}">
<input type="hidden" name="context_id" value="{$context_id}">

<b>{$translate->_('dao.cerb_plugin.author')}:</b> {$active_worker->getName()}
<div>
	<textarea name="journal" rows="5" cols="60" style="width:98%;"></textarea>
</div>
<div>
	<button type="button" onclick="ajax.chooserSnippet('snippets',$('#internalJournalPopup textarea[name=journal]'), { '{$context}':'{$context_id}', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
</div>
<br>

<b>{$translate->_('common.show_settings')}:</b>
<div style="margin-left:20px;margin-bottom:1em;">
	<table cellpadding="1" cellspacing="0" border="0">
		<tr>
			<td>
				{$translate->_('common.show_settings.internal_and_owner')}<br />
				{$translate->_('common.show_settings.internal')}<br />
				{$translate->_('common.show_settings.public')}
			</td>
			<td>
				<input type="radio" checked="checked" onclick="$(this).parent().children().not(this).prop('checked', false);" /><br />
				<input name="isinternal" type="radio" value="1" onclick="$(this).parent().children().not(this).prop('checked', false);" /><br />
				<input name="ispublic" type="radio" value="1" onclick="$(this).parent().children().not(this).prop('checked', false);" />
			</td>
		</tr>
	</table>
</div>

<b>{$translate->_('common.status')}:</b>
<div style="margin-left:20px;margin-bottom:1em;">
	<table cellpadding="1" cellspacing="0" border="0">
		<tr>
			<td>
				<input class="journal-state" type="radio" name="state" value="2" /><br />
				<input class="journal-state" type="radio" name="state" value="1" /><br />
				<input class="journal-state" type="radio" name="state" value="0" checked="checked" />
			</td>
			<td>
				<img id="journal-tl-0" class="journal-tl" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/traffic_light_0.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" alt="green" />
				<img id="journal-tl-1" class="journal-tl" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/traffic_light_1.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" alt="yellow" style="display:none;" />
				<img id="journal-tl-2" class="journal-tl" src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/traffic_light_2.png{/devblocks_url}?v={$smarty.const.APP_BUILD}" alt="red" style="display:none;" />
			</td>
		</tr>
	</table>
</div>

<b>{$translate->_('common.attachments')}:</b><br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_file"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
	</ul>
</div>

<b>{'common.notify_watchers_and'|devblocks_translate}</b>:<br>
<div style="margin-left:20px;margin-bottom:1em;">
	<button type="button" class="chooser_worker"><span class="cerb-sprite sprite-view"></span></button>
	{if !empty($notify_workers)}<span>(<a href="javascript:;" onclick="$(this).closest('span').siblings('ul.bubbles').html('');$(this).closest('span').remove();">clear</a>)</span>{/if}
	<ul class="chooser-container bubbles" style="display:block;">
	{if !empty($notify_workers) && !isset($workers)}{$workers = DAO_Worker::getAll()}{/if}
	{foreach from=$notify_workers item=notify_worker_id}
		{$notify_worker = $workers.$notify_worker_id}
		{if !empty($notify_worker)}
		<li>{$notify_worker->getName()}<input type="hidden" name="notify_worker_ids[]" value="{$notify_worker_id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
		{/if}
	{/foreach}
	</ul>
</div>

<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	var $frm = $('#internalJournalPopup');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{$translate->_("common.new_journal_entry")}');
		
		$frm.find('button.submit').click(function() {
			$popup = genericAjaxPopupFetch('peek');
			genericAjaxPost('internalJournalPopup','','', null, { async: false } );
			$popup.trigger('journal_save');
			genericAjaxPopupClose('peek');
		});
	
		$frm.find('button.chooser_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
		
		$frm.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'file_ids');
		});
		
		$frm.find('textarea').elastic();
		$frm.find('textarea').focus();
		
		$frm.find('.journal-state').click(function() {
			var val = $(this).val();
			$('.journal-tl').hide();
			$('#journal-tl-'+val).show();
		});
	});
</script>
