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
					{if !empty($model->id)}
						<option value=""> - transfer - </option>
					{/if}
					
					<option value="w_{$active_worker->id}" {if $model->owner_context==CerberusContexts::CONTEXT_WORKER && $active_worker->id==$model->owner_context_id}selected="selected"{/if}>me</option>
					
					<option value="a_0" {if $model->owner_context==CerberusContexts::CONTEXT_APPLICATION}selected="selected"{/if}>Application: Cerb</option>

					{if !empty($owner_roles)}
					{foreach from=$owner_roles item=role key=role_id}
						<option value="r_{$role_id}" {if $model->owner_context==CerberusContexts::CONTEXT_ROLE && $role_id==$model->owner_context_id}selected="selected"{/if}>Role: {$role->name}</option>
					{/foreach}
					{/if}
					
					{if !empty($owner_groups)}
					{foreach from=$owner_groups item=group key=group_id}
						<option value="g_{$group_id}" {if $model->owner_context==CerberusContexts::CONTEXT_GROUP && $group_id==$model->owner_context_id}selected="selected"{/if}>Group: {$group->name}</option>
					{/foreach}
					{/if}
					
					{if $active_worker->is_superuser}
					{foreach from=$workers item=worker key=worker_id}
						{if empty($worker->is_disabled)}
						<option value="w_{$worker_id}" {if $model->owner_context==CerberusContexts::CONTEXT_WORKER && $worker_id==$model->owner_context_id && $active_worker->id != $worker_id}selected="selected"{/if}>Worker: {$worker->getName()}</option>
						{/if}
					{/foreach}
					{/if}
				</select>
				
				{if !empty($model->id)}
				<ul class="bubbles">
					<li>
					{if $model->owner_context==CerberusContexts::CONTEXT_APPLICATION}
					<b>Application</b>
					{/if}
					
					{if $model->owner_context==CerberusContexts::CONTEXT_ROLE && isset($roles.{$model->owner_context_id})}
					<b>{$roles.{$model->owner_context_id}->name}</b> (Role)
					{/if}
					
					{if $model->owner_context==CerberusContexts::CONTEXT_GROUP && isset($groups.{$model->owner_context_id})}
					<b>{$groups.{$model->owner_context_id}->name}</b> (Group)
					{/if}
					
					{if $model->owner_context==CerberusContexts::CONTEXT_WORKER && isset($workers.{$model->owner_context_id})}
					<b>{$workers.{$model->owner_context_id}->getName()}</b> (Worker)
					{/if}
					</li>
				</ul>
				{/if}
			</td>
		</tr>
		
		{* Watchers *}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{$translate->_('common.watchers')|capitalize}: </td>
			<td width="100%">
				{if empty($model->id)}
					<button type="button" class="chooser_watcher"><span class="cerb-sprite sprite-view"></span></button>
					<ul class="chooser-container bubbles" style="display:block;"></ul>
				{else}
					{$object_watchers = DAO_ContextLink::getContextLinks('cerberusweb.contexts.virtual.attendant', array($model->id), CerberusContexts::CONTEXT_WORKER)}
					{include file="devblocks:cerberusweb.core::internal/watchers/context_follow_button.tpl" context='cerberusweb.contexts.virtual.attendant' context_id=$model->id full=true}
				{/if}
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context='cerberusweb.contexts.virtual.attendant' context_id=$model->id}

{* Comment *}
{if !empty($last_comment)}
	{include file="devblocks:cerberusweb.core::internal/comments/comment.tpl" readonly=true comment=$last_comment}
{/if}

<fieldset class="peek">
	<legend>{'common.comment'|devblocks_translate|capitalize}</legend>
	<textarea name="comment" rows="5" cols="45" style="width:98%;"></textarea>
	<div class="notify" style="display:none;">
		<b>{'common.notify_watchers_and'|devblocks_translate}:</b>
		<button type="button" class="chooser_notify_worker"><span class="cerb-sprite sprite-view"></span></button>
		<ul class="chooser-container bubbles" style="display:block;"></ul>
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
