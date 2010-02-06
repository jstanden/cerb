<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="viewAutoAssist">
<input type="hidden" name="view_id" value="{$view_id}">

Sort biggest piles by: 
<label><input type="radio" name="mode" value="subjects" {if $mode!="subjects"}onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=subjects');"{/if} {if $mode=="subjects"}checked{/if}>Subject Prefix</label>
<label><input type="radio" name="mode" value="senders" {if $mode!="senders"}onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=senders');"{/if} {if $mode=="senders"}checked{/if}>Senders</label>
<label><input type="radio" name="mode" value="headers" {if $mode!="headers"}onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=headers');"{/if} {if $mode=="headers"}checked{/if}>Headers</label>
<br>
<br>

<h3>Most Common Headers:</h3>
	<blockquote style="margin:5px;">
		<a href="javascript:;" onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=headers&mode_param=to');">to</a><br>
	</blockquote>
	<blockquote style="margin:5px;">
		<a href="javascript:;" onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=headers&mode_param=from');">from</a><br>
	</blockquote>
	<blockquote style="margin:5px;">
		<a href="javascript:;" onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=headers&mode_param=subject');">subject</a><br>
	</blockquote>

<h3>All Headers:</h3>

{foreach from=$headers item=header}
	<blockquote style="margin:5px;">
		<a href="javascript:;" onclick="genericAjaxGet('{$view_id}_tips','c=tickets&a=showViewAutoAssist&view_id={$view_id}&mode=headers&mode_param={$header}');">{$header}</a><br>
	</blockquote>
{/foreach}

<br>

<button type="button" onclick="toggleDiv('{$view_id}_tips','none');$('#{$view_id}_tips').html('');" style="">Do nothing</button>

</form>