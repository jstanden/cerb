<form id="frmDecisionBehavior{$model->id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="behavior">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="id" value="{$model->id|default:0}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		{if !$model->id}
		<tr>
			<td width="100%" colspan="2">
				<label><input type="radio" name="mode" value="build" checked="checked"> Build</label>
				<label><input type="radio" name="mode" value="import"> {'common.import'|devblocks_translate|capitalize}</label>
			</td>
		</tr>
		{/if}
		
		<tr>
			<td width="1%" nowrap="nowrap"><b>{'common.bot'|devblocks_translate|capitalize}:</b></td>
			<td width="99%">
				{if !$model->id}
					<button type="button" class="chooser-abstract" data-field-name="bot_id" data-context="{CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT}" data-single="true" data-autocomplete="if-null"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{if $bot}
							<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=virtual_attendant&context_id={$bot->id}{/devblocks_url}?v={$bot->updated_at}"><input type="hidden" name="bot_id" value="{$bot->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT}" data-context-id="{$bot->id}">{$bot->name}</a></li>
						{/if}
					</ul>
				{else}
					{if $bot}
						<ul class="bubbles chooser-container">
							<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=virtual_attendant&context_id={$bot->id}{/devblocks_url}?v={$bot->updated_at}"><input type="hidden" name="bot_id" value="{$bot->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT}" data-context-id="{$bot->id}">{$bot->name}</a></li>
						</ul>
					{/if}
				{/if}
			</td>
		</tr>
		
		<tbody class="behavior-import" style="display:none;">
			<tr>
				<td width="100%" colspan="2">
					<textarea name="import_json" style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste a behavior in JSON format"></textarea>
				</td>
			</tr>
		</tbody>
		
		<tbody class="behavior-build">
			<tr class="behavior-event">
				<td width="1%" valign="top" nowrap="nowrap"><b>{'common.event'|devblocks_translate|capitalize}:</b></td>
				<td width="99%">
					{if empty($ext)}
						<select name="event_point">
							{foreach from=$events item=available_event key=available_event_id name=available_events}
							<option value="{$available_event_id}" {if $available_event->params.macro_context}is_macro="true"{/if}>{$available_event->name}</option>
							{/foreach}
						</select>
						<br>
					{else}
						<ul class="bubbles chooser-container">
							<li>{$ext->name}</li>
						</ul>
					{/if}
					
					<div class="event-params">
					{if $ext && method_exists($ext,'renderEventParams')}
					{$ext->renderEventParams($model)}
					{/if}
					</div>
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
				<td width="99%">
					<input type="text" name="title" value="{$model->title}" style="width:100%;" autocomplete="off" spellcheck="false" autofocus="autofocus"><br>
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap"><b>{'common.priority'|devblocks_translate|capitalize}:</b></td>
				<td width="99%">
					<input type="text" name="priority" value="{$model->priority|default:50}" placeholder="50" maxlength="2" style="width:50px" autocomplete="off" spellcheck="false">
				</td>
			</tr>
			
			<tr style="margin-top:10px;">
				<td width="1%" nowrap="nowrap"><b>Visibility:</b></td>
				<td width="99%">
					<label><input type="radio" name="is_private" value="0" {if empty($model->is_private)}checked="checked"{/if}> {'common.public'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="is_private" value="1" {if !empty($model->is_private)}checked="checked"{/if}> {'common.private'|devblocks_translate|capitalize}</label>
				</td>
			</tr>
			
			<tr>
				<td width="1%" nowrap="nowrap"><b>{'common.status'|devblocks_translate|capitalize}:</b></td>
				<td width="99%">
					<label><input type="radio" name="is_disabled" value="0" {if empty($model->is_disabled)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="is_disabled" value="1" {if !empty($model->is_disabled)}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
				</td>
			</tr>
			
		</tbody>
		
	</table>
</fieldset>

<fieldset class="peek behavior-variables">
	<legend style="color:inherit;">{'common.variables'|devblocks_translate|capitalize}</legend>
	
	<div id="divBehaviorVariables{$model->id}">
	{foreach from=$model->variables key=k item=var name=vars}
		{$seq = uniqid()}
		{include file="devblocks:cerberusweb.core::internal/decisions/editors/trigger_variable.tpl" seq=$seq}
	{/foreach}
	</div>
	
	<div style="margin:5px 0px 10px 20px;">
		<button type="button" class="add-variable cerb-popupmenu-trigger">{'common.add'|devblocks_translate|capitalize} &#x25be;</button>
		
		<ul class="cerb-popupmenu add-variable-menu" style="border:0;">
			<li><a href="javascript:;" field_type="S">Text</a></li>
			<li><a href="javascript:;" field_type="D">Picklist</a></li>
			<li><a href="javascript:;" field_type="N">Number</a></li>
			<li><a href="javascript:;" field_type="E">Date</a></li>
			<li><a href="javascript:;" field_type="C">Yes/No</a></li>
			<li><a href="javascript:;" field_type="W">Worker</a></li>
			{foreach from=$list_contexts item=list_context key=list_context_id}
			<li><a href="javascript:;" field_type="ctx_{$list_context_id}">(List) {$list_context->name}</a></li>
			{/foreach}
		</ul>
	</div>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_BEHAVIOR context_id=$model->id}

{if isset($model->id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this behavior?</legend>
	<p>Are you sure you want to permanently delete this behavior and all of its effects?</p>
	
	<button type="button" class="delete red"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="config"></div>

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $model->id}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmDecisionBehavior{$model->id}');
	var $popup = genericAjaxPopupFind($frm);
	var $events = $popup.find('select[name=event_point]');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.behavior'|devblocks_translate|capitalize|escape:'javascript'}");
		
		$popup.find('.chooser-abstract')
			.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
				var $btn = $(e.target);
				var $ul = $btn.siblings('ul.chooser-container');
				
				$events.closest('tr').hide();
				$events.find('option').remove();
				
				if($ul.find('li').length > 0) {
					// Load the events from Ajax by bot ID
					var $hidden = $ul.find('li input[name=bot_id]');
					var bot_id = $hidden.val();
					
					genericAjaxGet('', 'c=profiles&a=handleSectionAction&section=behavior&action=getEventsByBotJson&bot_id=' + bot_id, function(json) {
						for(k in json) {
							$('<option/>').attr('value',k).text(json[k]).appendTo($events);
						}
					});
					
					$events.closest('tr').fadeIn();
				}
			})
			;
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		var checkForConfigForm = function(json) {
			if(json.config_html) {
				//$frm.find('div.import').hide();
				$frm.find('div.config').hide().html(json.config_html).fadeIn();
			}
		};
		
		$popup.find('button.submit').click({ after: checkForConfigForm }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		$popup.find('fieldset.behavior-variables')
			.sortable({ 'items':'FIELDSET', 'placeholder':'ui-state-highlight', 'handle':'legend' })
			;
		
		$events.change(function() {
			var $select = $(this);
			var $li = $select.find('option:selected');
			var $frm = $select.closest('form');
			
			genericAjaxGet('', 'c=internal&a=getTriggerEventParams&id=' + encodeURIComponent($select.val()), function(o) {
				var $params = $frm.find('div.event-params');
				$params.html(o);
			});
		});
		
		$popup.find('BUTTON.add-variable').click(function() {
			var $button = $(this);
			$button.next('ul.add-variable-menu').toggle();
		});
		
		$popup.find('UL.add-variable-menu LI').click(function(e) {
			var $menu = $(this).closest('ul.add-variable-menu');
			var field_type = $(this).find('a').attr('field_type');
			
			genericAjaxGet('', 'c=internal&a=addTriggerVariable&type=' +  encodeURIComponent(field_type), function(o) {
				var $container = $('#divBehaviorVariables{$model->id}');
				var $html = $(o).appendTo($container);
			});
			
			$menu.hide();
		});
		
		$popup.find('input:radio[name=mode]').change(function() {
			var $radio = $(this);
			var mode = $radio.val();
			
			if(mode == 'import') {
				$frm.find('fieldset.behavior-variables').hide();
				$frm.find('tbody.behavior-build').hide();
				$frm.find('tbody.behavior-import').fadeIn();
			} else {
				$frm.find('tbody.behavior-import').hide();
				$frm.find('tbody.behavior-build').fadeIn();
				$frm.find('fieldset.behavior-variables').fadeIn();
			}
		});

	});
	
});
</script>