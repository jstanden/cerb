<b>From these workers:</b>
<div style="margin-left:10px;">
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
	</ul>
</div>

<b>And the workers from these groups:</b>
<div style="margin-left:10px;">
	<button type="button" class="chooser_group unbound"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
	{if isset($params.group_id)}
	{foreach from=$params.group_id item=group_id}
		{$context_group = $groups.$group_id}
		{if !empty($context_group)}
		<li>{$context_group->name}<input type="hidden" name="{$namePrefix}[group_id][]" value="{$context_group->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
		{/if}
	{/foreach}
	{/if}
	</ul>
</div>

<b>Pick:</b>
<div style="margin-left:10px;">
	<select name="{$namePrefix}[mode]">
		<option value="random" {if $params.mode=='random'}selected='selected'{/if}>A random worker</option>
		<option value="seq" {if $params.mode=='seq'}selected='selected'{/if}>Each worker sequentially (i.e. round robin)</option>
		<option value="load_balance" {if $params.mode=='load_balance'}selected='selected'{/if}>The worker with the fewest open assignments (i.e. load balance)</option>
	</select>
</div>

<b>Where:</b>
<div style="margin-left:10px;">
	<label><input type="checkbox" name="{$namePrefix}[opt_logged_in]" value="1" {if $params.opt_logged_in}checked="checked"{/if}>The worker is currently logged in</label>
</div>
