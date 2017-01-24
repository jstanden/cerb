{$form_id = "frm{uniqid()}"}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="bot">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl"}
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.status'|devblocks_translate}:</b></td>
			<td width="99%">
				<label><input type="radio" name="is_disabled" value="0" {if empty($model->is_disabled)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="is_disabled" value="1" {if !empty($model->is_disabled)}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		
		<tr>
			<td width="1%" nowrap="nowrap" valign="top"><b>{'common.image'|devblocks_translate|capitalize}:</b></td>
			<td width="99%" valign="top">
				<div style="float:left;margin-right:5px;">
					<img class="cerb-avatar" src="{devblocks_url}c=avatars&context=bot&context_id={$model->id}{/devblocks_url}?v={$model->updated_at}" style="height:50px;width:50px;">
				</div>
				<div style="float:left;">
					<button type="button" class="cerb-avatar-chooser" data-context="{CerberusContexts::CONTEXT_BOT}" data-context-id="{$model->id}">{'common.edit'|devblocks_translate|capitalize}</button>
					<input type="hidden" name="avatar_image">
				</div>
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_BOT context_id=$model->id}

<fieldset class="peek va-fieldset-interactions">
	<legend>Interactions</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'worker.at_mention_name'|devblocks_translate}:</b></td>
			<td width="99%">
				<input type="text" name="at_mention_name" value="{$model->at_mention_name}" style="width:98%;" autofocus="autofocus" spellcheck="false" placeholder="mybot">
			</td>
		</tr>
		
		{if $model->id}
		<tr>
			<td width="1%" nowrap="nowrap"><b>Worker chat:</b></td>
			<td width="99%">
				<button type="button" class="chooser-abstract" data-field-name="interactions[worker]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="bot.id:{$model->id} event:event.interaction.chat.worker"><span class="glyphicons glyphicons-search"></span></button>
				<ul class="bubbles chooser-container">
					{if $model->params.interactions.worker}
						{$behavior = $interaction_behaviors[$model->params.interactions.worker]}
						{if $behavior}
						<li>
							<input type="hidden" name="interactions[worker]" value="{$behavior->id}">
							<a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a>
						</li>
						{/if}
					{/if}
				</ul>
			</td>
		</tr>
		{/if}
	</table>
</fieldset>

<fieldset class="peek va-fieldset-events">
	<legend>Events</legend>
	
	<div>
		<label><input type="radio" name="allowed_events" value="all" {if !$model->params.events.mode || $model->params.events.mode == 'all'}checked="checked"{/if}> Allow all</label>
		<label><input type="radio" name="allowed_events" value="allow" {if $model->params.events.mode == 'allow'}checked="checked"{/if}> Allow only these:</label>
		<label><input type="radio" name="allowed_events" value="deny" {if $model->params.events.mode == 'deny'}checked="checked"{/if}> Deny only these:</label>
	</div>
	
	<div style="margin:3px 0px 0px 10px;{if !in_array($model->params.events.mode,['allow','deny'])}display:none;{/if}" class="va-events">
		{foreach from=$event_extensions item=event_ext key=event_ext_id}
			<label style="{if !isset($event_ext->params['contexts'][0][$model->owner_context])}display:none;{/if}" contexts="{if isset($event_ext->params['contexts'][0])}{$event_ext->params['contexts'][0]|array_keys|implode:' '}{/if}"><input type="checkbox" name="itemized_events[]" value="{$event_ext_id}" {if is_array($model->params.events.items) && in_array($event_ext_id, $model->params.events.items)}checked="checked"{/if}> {$event_ext->name}<br></label>
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
		Are you sure you want to permanently delete this bot and all of its behaviors?
	</div>
	
	<button type="button" class="delete red"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bot'|devblocks_translate|capitalize|escape:'javascript'}");
		$popup.css('overflow', 'inherit');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		var $events_container = $popup.find('div.va-events');
		var $events = $events_container.find('label');
		
		// Owners
		var $owners_menu = $popup.find('ul.owners-menu');
		var $ul = $owners_menu.siblings('ul.chooser-container');
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		$ul.on('bubble-remove', function(e, ui) {
			e.stopPropagation();
			$(e.target).closest('li').remove();
			$ul.hide();
			$owners_menu.show();
			
			$events.each(function() {
				$(this).hide();
			});
		});
		
		$owners_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$owners_menu.hide();
				
				// Build bubble
				
				var context_data = token.split(':');
				var $li = $('<li/>');
				var $label = $('<a href="javascript:;" class="cerb-peek-trigger no-underline" />').attr('data-context',context_data[0]).attr('data-context-id',context_data[1]).text(label);
				$label.cerbPeekTrigger().appendTo($li);
				var $hidden = $('<input type="hidden">').attr('name', 'owner').attr('value',token).appendTo($li);
				ui.item.find('img.cerb-avatar').clone().prependTo($li);
				var $a = $('<a href="javascript:;" onclick="$(this).trigger(\'bubble-remove\');"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
				
				$ul.find('> *').remove();
				$ul.append($li);
				$ul.show();
				
				// Contextual events
				$events.each(function() {
					var contexts = $(this).attr('contexts').split(' ');
					
					if($.inArray(context_data[0], contexts) != -1)
						$(this).show();
					else
						$(this).hide();
				});
			}
		});
		
		$('#vaPeekTabs').tabs();
		
		$popup.find('input:radio[name=allowed_events]').change(function() {
			var $this = $(this);
			var $frm = $this.closest('form');
			var $events_container = $frm.find('div.va-events');

			if($this.val() == 'all')
				$events_container.hide();
			else
				$events_container.show();
		});
		
		$popup.find('input:radio[name=allowed_actions]').change(function() {
			var $this = $(this);
			var $frm = $this.closest('form');
			var $actions_container = $frm.find('div.va-actions');

			if($this.val() == 'all')
				$actions_container.hide();
			else
				$actions_container.show();
		});
		
		// Avatar
		
		var $avatar_chooser = $popup.find('button.cerb-avatar-chooser');
		var $avatar_image = $avatar_chooser.closest('td').find('img.cerb-avatar');
		ajax.chooserAvatar($avatar_chooser, $avatar_image);
	});
});
</script>
