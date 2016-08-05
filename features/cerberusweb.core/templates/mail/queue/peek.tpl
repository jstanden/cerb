<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formDraftPeek" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="draft">
<input type="hidden" name="action" value="saveDraftsPeek">
<input type="hidden" name="id" value="{$draft->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	{if is_null($workers)}{$workers = DAO_Worker::getAll()}{/if}
	{$worker = $workers.{$draft->worker_id}}
	{if !empty($worker)}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{'message.header.from'|devblocks_translate|capitalize}:</b> </td>
		<td width="100%">
			{$worker->getName()} &lt;{$worker->getEmailString()}&gt;
		</td>
	</tr>
	{/if}
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{'message.header.to'|devblocks_translate|capitalize}:</b> </td>
		<td width="100%">
			{$draft->hint_to}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{'message.header.subject'|devblocks_translate|capitalize}:</b> </td>
		<td width="100%">
			{$draft->subject}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{'message.header.date'|devblocks_translate|capitalize}:</b> </td>
		<td width="100%">
			{$draft->updated|devblocks_date}
		</td>
	</tr>
</table>

<div id="draftPeekContent" style="width:400;height:250px;overflow:auto;border:1px solid rgb(180,180,180);padding:5px;background-color:rgb(255,255,255);">
<pre class="emailbody">{$draft->body|trim|escape|devblocks_hyperlinks|devblocks_hideemailquotes nofilter}</pre>
</div>

<br>

{if $active_worker->id==$draft->worker_id || $active_worker->is_superuser}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>
{/if}
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formDraftPeek');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','{if $draft->is_queued}Queued Message{else}Draft{/if}');
		$('#formDraftPeek :input:text:first').focus().select();
		$("#draftPeekContent").css('width','98%');
	});
});
</script>
