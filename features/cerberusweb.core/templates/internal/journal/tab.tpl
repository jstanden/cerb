<form action="#" style="margin:5px;">
	<table cellpadding="0" cellspacing="0" border="0" style="width:100%;">
		<tr>
			<td>
				<button type="button" id="btnJournal"><span class="cerb-sprite sprite-document_plain_yellow"></span> {$translate->_('common.new_journal_entry')}</button>
			</td>
			<td style="width:18px;">
				{if $active_worker->hasPriv('core.rss')}
					<a href="javascript:;" onclick="genericAjaxGet('srv_jrn_tips','c=internal&a=viewShowRss&view=journal&source=core.rss.source.server');toggleDiv('{$view->id}_tips','block');"><span class="cerb-sprite sprite-rss"></span></a>
				{else}
					&nbsp;
				{/if}
			</td>
		</tr>
	</table>
</form>

{if $active_worker->hasPriv('core.rss')}
	<div id="srv_jrn_tips" class="block" style="display:none;margin:10px;padding:5px;">Analyzing...</div>
{/if}

{* Display Notes *}
{foreach from=$journal item=entry}
	{include file="devblocks:cerberusweb.core::internal/journal/entry.tpl"}
{/foreach}

<script type="text/javascript">
	$('#btnJournal').click(function(event) {
		$popup = genericAjaxPopup('peek', 'c=internal&a=journalShowPopup&context={$context}&context_id={$context_id}', null, false, '550');
		$popup.one('journal_save', function(event) {
			$tabs = $('#btnJournal').closest('div.ui-tabs');
			if(0 != $tabs) {
				$tabs.tabs('load', $tabs.tabs('option','selected'));
			}
		});
	});
</script>