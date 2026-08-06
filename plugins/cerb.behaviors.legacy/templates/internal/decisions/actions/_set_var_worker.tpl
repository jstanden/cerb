<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">From these workers</label>
	<div class="cerb-ui-record-chooser chooser_worker unbound">
	{if isset($params.worker_id)}
	{foreach from=$params.worker_id item=worker_id}
		{$context_worker = $workers.$worker_id}
		{if !empty($context_worker)}
		<li data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$context_worker->id}" data-label="{$context_worker->getName()}"></li>
		{/if}
	{/foreach}
	{/if}
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">And the workers from these groups</label>
	<div class="cerb-ui-record-chooser chooser_group unbound">
	{if isset($params.group_id)}
	{foreach from=$params.group_id item=group_id}
		{$context_group = $groups.$group_id}
		{if !empty($context_group)}
		<li data-context="{CerberusContexts::CONTEXT_GROUP}" data-context-id="{$context_group->id}" data-label="{$context_group->name}"></li>
		{/if}
	{/foreach}
	{/if}
	</div>
</div>

{if !empty($worker_variables)}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">And the workers from these variables</label>
	<div>
		{foreach from=$worker_variables item=var key=var_key}
			<label><input type="checkbox" name="{$namePrefix}[vars][]" value="{$var_key}" {if is_array($params.vars) && in_array($var_key, $params.vars)}checked="checked"{/if}> {$var}</label>
		{/foreach}
	</div>
</div>
{/if}

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Where</label>
	<div>
		<label><input type="checkbox" name="{$namePrefix}[opt_is_available]" value="1" {if $params.opt_is_available}checked="checked"{/if}>The worker is available</label>
		<br>
		<label><input type="checkbox" name="{$namePrefix}[opt_logged_in]" value="1" {if $params.opt_logged_in}checked="checked"{/if}>The worker is currently logged in</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Pick</label>
	<select name="{$namePrefix}[mode]">
		<option value="random" {if $params.mode=='random'}selected='selected'{/if}>A random worker</option>
		<option value="seq" {if $params.mode=='seq'}selected='selected'{/if}>Each worker sequentially (i.e. round robin)</option>
		<option value="load_balance" {if $params.mode=='load_balance'}selected='selected'{/if}>The worker with the fewest open assignments (i.e. load balance)</option>
	</select>
</div>