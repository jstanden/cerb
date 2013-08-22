<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmVirtualAttendantPeek">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="virtual_attendant">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;">
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				<select name="owner">
					<option value="{CerberusContexts::CONTEXT_APPLICATION}:0"  context="{CerberusContexts::CONTEXT_APPLICATION}" {if $model->owner_context==CerberusContexts::CONTEXT_APPLICATION}selected="selected"{/if}>Application: Cerb</option>

					{foreach from=$roles item=role key=role_id}
						<option value="{CerberusContexts::CONTEXT_ROLE}:{$role_id}"  context="{CerberusContexts::CONTEXT_ROLE}" {if $model->owner_context==CerberusContexts::CONTEXT_ROLE && $role_id==$model->owner_context_id}selected="selected"{/if}>Role: {$role->name}</option>
					{/foreach}
					
					{foreach from=$groups item=group key=group_id}
						<option value="{CerberusContexts::CONTEXT_GROUP}:{$group_id}"  context="{CerberusContexts::CONTEXT_GROUP}" {if $model->owner_context==CerberusContexts::CONTEXT_GROUP && $group_id==$model->owner_context_id}selected="selected"{/if}>Group: {$group->name}</option>
					{/foreach}
					
					{foreach from=$workers item=worker key=worker_id}
						{$is_selected = $model->owner_context==CerberusContexts::CONTEXT_WORKER && $worker_id==$model->owner_context_id}
						{if $is_selected || !$worker->is_disabled}
						<option value="{CerberusContexts::CONTEXT_WORKER}:{$worker_id}"  context="{CerberusContexts::CONTEXT_WORKER}" {if $is_selected}selected="selected"{/if}>Worker: {$worker->getName()}</option>
						{/if}
					{/foreach}
				</select>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.status'|devblocks_translate}:</b></td>
			<td width="99%">
				<label><input type="radio" name="is_disabled" value="0" {if empty($model->is_disabled)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="is_disabled" value="1" {if !empty($model->is_disabled)}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		
	</table>
	
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT context_id=$model->id}

<fieldset class="peek va-fieldset-events">
	<legend>Events</legend>
	
	<div>
		<label><input type="radio" name="allowed_events" value="all" {if !$model->params.events.mode || $model->params.events.mode == 'all'}checked="checked"{/if}> Allow all</label>
		<label><input type="radio" name="allowed_events" value="allow" {if $model->params.events.mode == 'allow'}checked="checked"{/if}> Allow only these:</label>
		<label><input type="radio" name="allowed_events" value="deny" {if $model->params.events.mode == 'deny'}checked="checked"{/if}> Deny only these:</label>
	</div>
	
	<div style="margin:3px 0px 0px 10px;{if empty($model->params.events.mode) || $model->params.events.mode == 'all'}display:none;{/if}" class="va-events">
		{foreach from=$event_extensions item=event_ext key=event_ext_id}
			<label contexts="{if isset($event_ext->params['contexts'][0])}{$event_ext->params['contexts'][0]|array_keys|implode:' '}{/if}"><input type="checkbox" name="itemized_events[]" value="{$event_ext_id}" {if is_array($model->params.events.items) && in_array($event_ext_id, $model->params.events.items)}checked="checked"{/if}> {$event_ext->name}<br></label>
		{/foreach}
	</div>
</fieldset>

<fieldset class="peek va-fieldset-actions">
	<legend>Action Extensions</legend>
	
	<div>
		<label><input type="radio" name="allowed_actions" value="all" {if !$model->params.actions.mode || $model->params.actions.mode == 'all'}checked="checked"{/if}> Allow all</label>
		<label><input type="radio" name="allowed_actions" value="allow" {if $model->params.actions.mode == 'allow'}checked="checked"{/if}> Allow only these:</label>
		<label><input type="radio" name="allowed_actions" value="deny" {if $model->params.actions.mode == 'deny'}checked="checked"{/if}> Deny only these:</label>
	</div>
	
	<div style="margin:3px 0px 0px 10px;{if empty($model->params.actions.mode) || $model->params.actions.mode == 'all'}display:none;{/if}" class="va-actions">
		{foreach from=$action_extensions item=action_ext key=action_ext_id}
			<label events="{if isset($action_ext->params['events'][0])}{$action_ext->params['events'][0]|array_keys|implode:' '}{/if}"><input type="checkbox" name="itemized_actions[]" value="{$action_ext->id}" {if is_array($model->params.actions.items) && in_array($action_ext_id, $model->params.actions.items)}checked="checked"{/if}> {$action_ext->params.label}<br></label>
		{/foreach}
	</div>
</fieldset>

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to delete this virtual attendant and all of its behaviors?
	</div>
	
	<button type="button" class="delete" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"><span class="cerb-sprite2 sprite-tick-circle"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmVirtualAttendantPeek','{$view_id}', false, 'virtual_attendant_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

{if !empty($model->id)}
<div style="float:right;">
	<a href="{devblocks_url}c=profiles&type=virtual_attendant&id={$model->id}-{$model->name|devblocks_permalink}{/devblocks_url}">view full record</a>
</div>
<br clear="all">
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'Virtual Attendant'}");
		
		$('#vaPeekTabs').tabs();
		
		$(this).find('select[name=owner]').change(function() {
			var $this = $(this);
			var $owner = $this.find('option:selected');
			var owner_context = $owner.attr('context');
			var $frm = $this.closest('form');
			var $events_container = $frm.find('div.va-events');
			var $events = $events_container.find('label');
			
			$events.each(function() {
				var contexts = $(this).attr('contexts').split(' ');
				
				if($.inArray(owner_context, contexts) != -1)
					$(this).show();
				else
					$(this).hide();
			});
		}).trigger('change');
		
		$(this).find('input:radio[name=allowed_events]').change(function() {
			var $this = $(this);
			var $frm = $this.closest('form');
			var $events_container = $frm.find('div.va-events');

			if($this.val() == 'all')
				$events_container.hide();
			else
				$events_container.show();
		});
		
		$(this).find('input:radio[name=allowed_actions]').change(function() {
			var $this = $(this);
			var $frm = $this.closest('form');
			var $actions_container = $frm.find('div.va-actions');

			if($this.val() == 'all')
				$actions_container.hide();
			else
				$actions_container.show();
		});
		
		$(this).find('button.chooser_watcher').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','add_watcher_ids', { autocomplete:true });
		});
		
		$(this).find('textarea[name=comment]').keyup(function() {
			if($(this).val().length > 0) {
				$(this).next('DIV.notify').show();
			} else {
				$(this).next('DIV.notify').hide();
			}
		});
		
		$('#frmVirtualAttendantPeek button.chooser_notify_worker').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.worker','notify_worker_ids', { autocomplete:true });
		});
		
		$(this).find('input:text:first').focus();
	} );
</script>
