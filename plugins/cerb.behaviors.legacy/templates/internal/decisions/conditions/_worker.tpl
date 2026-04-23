<select name="{$namePrefix}[oper]">
	<option value="in" {if $params.oper=='in'}selected="selected"{/if}>is any of</option>
	<option value="!in" {if $params.oper=='!in'}selected="selected"{/if}>is not any of</option>
</select>
<br>

<button type="button" class="chooser_worker unbound"><span class="glyphicons glyphicons-search"></span></button>
<ul class="chooser-container bubbles" style="display:block;">
{if isset($params.worker_id)}
{foreach from=$params.worker_id item=worker_id}
	{$context_worker = $workers.$worker_id}
	{if !empty($context_worker)}
	<li>{$context_worker->getName()}<input type="hidden" name="{$namePrefix}[worker_id][]" value="{$context_worker->id}"><a data-cerb-link="remove_parent"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
	{/if}
{/foreach}
{/if}
</ul>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $condition = $('#{$namePrefix}_{$nonce}');
	$condition.find('.chooser-container [data-cerb-link=remove_parent]').on('click', Devblocks.onClickRemoveParent);
})
</script>
