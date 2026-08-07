{$uniqid = uniqid()}
{$is_blank = empty($params.worklist_model.context)}

<div id="div{$uniqid}" class="cerb-ui-form datasource-params">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Using</label>
		<select name="params{$params_prefix}[sync_calendar_id]">
			<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
			{foreach from=$calendars item=sync_calendar}
			{if $params.sync_calendar_id==$sync_calendar->id || CerberusContexts::isReadableByActor(CerberusContexts::CONTEXT_CALENDAR, $sync_calendar, $active_worker)}
			<option value="{$sync_calendar->id}" {if $params.sync_calendar_id==$sync_calendar->id}selected="selected"{/if}>{$sync_calendar->name}</option>
			{/if}
			{/foreach}
		</select>
	</div>
</div>