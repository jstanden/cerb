<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmGroupProfileEdit">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveTabBuckets">
<input type="hidden" name="group_id" value="{$group->id}">

<div style="margin-bottom:10px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=groups&a=showBucketPeek&group_id={$group->id}',null,false,'550');"><span class="cerb-sprite2 sprite-plus-circle"></span> {'common.add'|devblocks_translate|capitalize}</button>
</div>

<div id="divBucketsList">
{if !empty($buckets)}
{foreach from=$buckets item=bucket key=bucket_id name=buckets}
<fieldset style="border:0;cursor:move;" class="drag">
	<legend>
		<a href="javascript:;" onclick="genericAjaxPopup('peek','c=groups&a=showBucketPeek&id={$bucket_id}',null,false,'550');" {if $bucket->is_default}style="color:rgb(74,110,178);"{/if}>{$bucket->name}</a>
	</legend>

	<table cellpadding="0" cellspacing="5" border="0">
		<tr>
			<td valign="top" style="min-width:75px;">
				<b>From:</b>
			</td>
			<td>
				<input type="hidden" name="bucket_id[]" value="{$bucket_id}">
				{$reply_from = $bucket->getReplyTo()}
				{$reply_personal = $bucket->getReplyPersonal($active_worker)}
				{if !empty($reply_personal)}
				{$reply_personal} 
				{/if}
				&lt;{$reply_from->email}&gt;
			</td>
		</tr>
		
		<tr>
			<td valign="top">
				<b>HTML Template:</b>
			</td>
			<td>
				{$html_template = $bucket->getReplyHtmlTemplate()}
				{if $html_template}
					<a href="{devblocks_url}c=profiles&w=html_template&id={$html_template->id}-{$html_template->name|devblocks_permalink}{/devblocks_url}">{$html_template->name}</a>
				{/if}
			</td>
		</tr>
		
		<tr>
			<td valign="top">
				<b>Signature:</b>
			</td>
			<td>
				<div style="display:inline-block;padding:10px;border:1px {if empty($bucket->reply_signature)}dashed{else}solid{/if} rgb(200,200,200);background-color:rgb(245,245,245);">
				{$bucket->getReplySignature($active_worker)|escape:'html'|devblocks_hyperlinks|nl2br nofilter}
				</div>
			</td>
		</tr>
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