<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formDraftPeek" name="formDraftPeek" onsubmit="return false;">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="saveDraftsPeek">
<input type="hidden" name="id" value="{$draft->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	{if is_null($workers)}{$workers = DAO_Worker::getAll()}{/if}
	{$worker = $workers.{$draft->worker_id}}
	{if !empty($worker)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>From:</b> </td>
		<td width="100%">
			{$worker->getName()} &lt;{$worker->email}&gt;
		</td>
	</tr>
	{/if}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>To:</b> </td>
		<td width="100%">
			{$draft->hint_to}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>Subject:</b> </td>
		<td width="100%">
			{$draft->subject}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>Date:</b> </td>
		<td width="100%">
			{$draft->updated|devblocks_date}
		</td>
	</tr>
</table>

<div id="draftPeekContent" style="width:400;height:250px;overflow:auto;border:1px solid rgb(180,180,180);padding:5px;background-color:rgb(255,255,255);" ondblclick="genericAjaxPopupClose('peek');">
<pre class="emailbody">{$draft->body|trim|devblocks_hyperlinks|devblocks_hideemailquotes}</pre>
</div>

{*include file="devblocks:cerberusweb.core::tasks/display/tabs/notes.tpl" readonly=true*}

<br>

{if $active_worker->id==$draft->worker_id || $active_worker->is_superuser}
	{*<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formDraftPeek', 'view{$view_id}');"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>*}
	{*{if !empty($task)}<button type="button" onclick="if(confirm('Are you sure you want to permanently delete this draft?')) { $('#formDraftPeek input[name=do_delete]').val('1'); genericAjaxPost('formDraftPeek', 'view{$view_id}'); genericAjaxPopupClose('peek'); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}*}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
{/if}
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{if $draft->is_queued}Queued Message{else}Draft{/if}');
		$('#formDraftPeek :input:text:first').focus().select();
		$("#draftPeekContent").css('width','98%');
	} );
</script>
