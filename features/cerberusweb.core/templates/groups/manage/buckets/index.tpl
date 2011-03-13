<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmGroupProfileEdit">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabBuckets">
<input type="hidden" name="team_id" value="{$group->id}">

<div style="margin-bottom:10px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=groups&a=showBucketPeek&group_id={$group->id}',null,false,'550');"><span class="cerb-sprite sprite-add"></span> {'common.add'|devblocks_translate|capitalize}</button>
</div>

{* Inbox *}
<fieldset style="border:0;">
	<legend><a href="javascript:;" onclick="genericAjaxPopup('peek','c=groups&a=showBucketPeek&group_id={$group->id}&id=0',null,false,'550');" style="color:rgb(74,110,178);">Inbox</a></legend>

	<table cellpadding="5" cellspacing="0" border="0">
		<tr>
			<td valign="top" style="min-width:75px;">
				<b>From:</b>
			</td>
			<td>
				{$reply_from = $group->getReplyTo()}
				{$reply_personal = $group->getReplyPersonal()}
				{if !empty($reply_personal)}
				{$reply_personal} 
				{/if}
				&lt;{$reply_from->email}&gt;
			</td>
		</tr>
		
		{if !empty($group->reply_signature)}
		<tr>
			<td valign="top">
				<b>Signature:</b>
			</td>
			<td>
				<div style="display:inline-block;padding:10px;border:1px solid rgb(200,200,200);background-color:rgb(245,245,245);">
				{$group->reply_signature|escape:'html'|devblocks_hyperlinks|nl2br nofilter}
				</div>
			</td>
		</tr>
		{/if}
	</table>	
</fieldset>

{* Custom Buckets *}
<div id="divBucketsList">
{if !empty($buckets)}
{foreach from=$buckets item=bucket key=bucket_id name=buckets}
<fieldset style="border:0;cursor:move;" class="drag">
	<legend>
		<a href="javascript:;" onclick="genericAjaxPopup('peek','c=groups&a=showBucketPeek&id={$bucket_id}',null,false,'550');">{$bucket->name}</a>
	</legend>

	<table cellpadding="0" cellspacing="5" border="0">
		<tr>
			<td valign="top" style="min-width:75px;">
				<b>From:</b>
			</td>
			<td>
				<input type="hidden" name="bucket_id[]" value="{$bucket_id}">
				{$reply_from = $bucket->getReplyTo()}
				{$reply_personal = $bucket->getReplyPersonal()}
				{if !empty($reply_personal)}
				{$reply_personal} 
				{/if}
				&lt;{$reply_from->email}&gt;
			</td>
		</tr>
		
		<tr>
			<td>
				<b>{'mail.workflow'|devblocks_translate|capitalize}:</b>
			</td>
			<td>
			{if $bucket->is_assignable}
				Show
			{else}
				Hide
			{/if}
			</td>
		</tr>
		
		{if !empty($bucket->reply_signature)}
		<tr>
			<td valign="top">
				<b>Signature:</b>
			</td>
			<td>
				<div style="display:inline-block;padding:10px;border:1px solid rgb(200,200,200);background-color:rgb(245,245,245);">
				{$bucket->reply_signature|escape:'html'|devblocks_hyperlinks|nl2br nofilter}
				</div>
			</td>
		</tr>
		{/if}
	</table>	
</fieldset>
{/foreach}
{/if}
</div>

</form>

<script type="text/javascript">
	$('#divBucketsList').sortable({ 
		items: 'FIELDSET.drag',
		placeholder:'ui-state-highlight',
		update: function() {
			genericAjaxPost('frmGroupProfileEdit','','c=groups&a=saveBucketsOrder');
		}	 
	});
</script>