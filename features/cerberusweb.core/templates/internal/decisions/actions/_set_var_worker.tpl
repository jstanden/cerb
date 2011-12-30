{*
<select name="{$namePrefix}[mode]">
	<option value="random" {if $params.mode=='random'}selected='selected'{/if}>Randomly</option>
	<option value="seq" {if $params.mode=='seq'}selected='selected'{/if}>Sequentially</option>
</select>
*}
<input type="hidden" name="{$namePrefix}[mode]" value="random">
Randomly pick one of these workers:<br>

<button type="button" class="chooser_worker unbound"><span class="cerb-sprite sprite-view"></span></button>
<ul class="chooser-container bubbles" style="display:block;">
{if isset($params.worker_id)}
{foreach from=$params.worker_id item=worker_id}
	{$context_worker = $workers.$worker_id}
	{if !empty($context_worker)}
	<li>{$context_worker->getName()}<input type="hidden" name="{$namePrefix}[worker_id][]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
	{/if}
{/foreach}
{/if}
