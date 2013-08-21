{* Capture the form, since we might drop it inside a tab set if this is a new behavior *}
{capture name=behavior_build}
<form id="frmDecisionBehavior{$trigger->id}" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="trigger_id" value="{if isset($trigger->id)}{$trigger->id}{else}0{/if}">
{if empty($trigger->id)}
<input type="hidden" name="va_id" value="{$va->id}">
{/if}

<div>
	<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
	<input type="text" name="title" value="{$trigger->title}" style="width:100%;"><br>
</div>

<div style="margin-top:10px;">
	<b>{'common.event'|devblocks_translate|capitalize}:</b><br>
	{if empty($ext)}
		<select name="event_point">
			{foreach from=$events item=available_event key=available_event_id name=available_events}
			{if $smarty.foreach.available_events.first}{$event = $available_event}{/if}
			<option value="{$available_event_id}" {if $available_event->params.macro_context}is_macro="true"{/if}>{$available_event->name}</option>
			{/foreach}
		</select>
		<br>
	{else}
		{$ext->name}
	{/if}
</div>

<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:10px;">
	<tr>
		<td width="50%">
			<b>{'common.status'|devblocks_translate|capitalize}:</b>
			<br>
			<label><input type="radio" name="is_disabled" value="0" {if empty($trigger->is_disabled)}checked="checked"{/if}> {'common.enabled'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="is_disabled" value="1" {if !empty($trigger->is_disabled)}checked="checked"{/if}> {'common.disabled'|devblocks_translate|capitalize}</label>
		</td>
		<td width="50%">
			<div class="behavior-visibility" style="margin-top:10px;{if !$event->manifest->params.macro_context}display:none;{/if}">
				<b>Visibility:</b>
				<br>
				<label><input type="radio" name="is_private" value="0" {if empty($trigger->is_private)}checked="checked"{/if}> {'common.public'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="is_private" value="1" {if !empty($trigger->is_private)}checked="checked"{/if}> {'common.private'|devblocks_translate|capitalize}</label>
			</div>
		</td>
	</tr>
</table>

<h3>Variables</h3>

<div id="cerb-variables">
{foreach from=$trigger->variables key=k item=var name=vars}
	{$seq = uniqid()}
	{include file="devblocks:cerberusweb.core::internal/decisions/editors/trigger_variable.tpl" seq=$seq}
{/foreach}
</div>

<div style="margin:5px 0px 10px 20px;">
	<button type="button" class="add-variable cerb-popupmenu-trigger">Add Variable &#x25be;</button>
	
	<ul class="cerb-popupmenu add-variable-menu" style="border:0;">
		<li><a href="javascript:;" field_type="S">Text</a></li>
		<li><a href="javascript:;" field_type="D">Picklist</a></li>
		<li><a href="javascript:;" field_type="N">Number</a></li>
		<li><a href="javascript:;" field_type="E">Date</a></li>
		<li><a href="javascript:;" field_type="C">True/False</a></li>
		<li><a href="javascript:;" field_type="W">Worker</a></li>
		{foreach from=$list_contexts item=list_context key=list_context_id}
		<li><a href="javascript:;" field_type="ctx_{$list_context_id}">(List) {$list_context->name}</a></li>
		{/foreach}
	</ul>
</div>

</form>

{if isset($trigger->id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this trigger?</legend>
	<p>Are you sure you want to permanently delete this behavior and all of its effects?</p>
	<button type="button" class="green" onclick="genericAjaxPost('frmDecisionBehavior{$trigger->id}','','c=internal&a=saveDecisionDeletePopup',function() { genericAjaxPopupDestroy('node_trigger{$trigger->id}'); genericAjaxGet('decisionTree{$trigger->id}','c=internal&a=showDecisionTree&id={$trigger->id}'); });"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('form.toolbar').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<form class="toolbar">
	{if !empty($trigger->id)}
		<button type="button" onclick="genericAjaxPost('frmDecisionBehavior{$trigger->id}','','c=internal&a=saveDecisionPopup',function() { genericAjaxPopupDestroy('node_trigger{$trigger->id}'); genericAjaxGet('decisionTree{$trigger->id}','c=internal&a=showDecisionTree&id={$trigger->id}'); });"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{else}
		<button type="button" onclick="genericAjaxPost('frmDecisionBehavior','','c=internal&a=saveDecisionPopup&json=1',function(json) { $popup = genericAjaxPopupFetch('node_trigger'); event = jQuery.Event('trigger_create'); event.trigger_id = json.trigger_id; event.event_point = json.event_point; $popup.trigger(event); genericAjaxPopupDestroy('node_trigger');  });"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{/if}
	{if isset($trigger->id)}<button type="button" onclick="$(this).closest('form').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>
{/capture}

{* Draw tabs if we're adding a new behavior *}
{if empty($trigger_id)}
<div class="tabs">
	<ul>
		<li><a href="#tabBehavior{$trigger->id}Build">Build</a></li>
		<li><a href="#tabBehavior{$trigger->id}Import">Import</a></li>
	</ul>
	
	<div id="tabBehavior{$trigger->id}Build">
		{$smarty.capture.behavior_build nofilter}
	</div>
	
	<div id="tabBehavior{$trigger->id}Import">
		<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmBehaviorImport" onsubmit="return false;">
		<input type="hidden" name="c" value="internal">
		<input type="hidden" name="a" value="">
		<input type="hidden" name="va_id" value="{$va->id}">

		<div class="import">
			<b>Import:</b> (.json format)
			<br>
			<textarea name="import_json" style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false"></textarea>
		</div>
		
		<div class="config"></div>
		
		<div style="margin-top:10px;">
			<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.continue'|devblocks_translate|capitalize}</button>
		</div>
		</form>
	</div>
</div>
{else}{* Otherwise, just draw the form to edit an existing behavior *}
	{$smarty.capture.behavior_build nofilter}
{/if}

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('node_trigger{$trigger->id}');
	$popup.one('popup_open', function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title',"{if empty($trigger->id)}New {/if}Behavior");
		$this.find('input:text').first().focus();
		
		$this.find('#frmDecisionBehavior{$trigger->id}')
			.sortable({ 'items':'FIELDSET', 'placeholder':'ui-state-highlight', 'handle':'legend' })
			;
		
		$this.find("SELECT[name=event_point]").change(function() {
			var $li = $(this).find('option:selected');
			var $frm = $(this).closest('form');
			
			if($li.attr('is_macro'))
				$frm.find('DIV.behavior-visibility').fadeIn();
			else
				$frm.find('DIV.behavior-visibility').fadeOut();
		});
		
		$this.find('BUTTON.add-variable').click(function() {
			var $button = $(this);
			$button.next('ul.cerb-popupmenu').toggle();
		});
		
		$this.find('UL.add-variable-menu LI').click(function(e) {
			var $menu = $(this).closest('ul.cerb-popupmenu');
			var field_type = $(this).find('a').attr('field_type');
			
			genericAjaxGet('', 'c=internal&a=addTriggerVariable&type=' +  encodeURIComponent(field_type), function(o) {
				var $container = $('#cerb-variables');
				var $html = $(o).appendTo($container);
			});
			
			$menu.hide();
		});
		
		{if empty($trigger_id)}
			$this.find('div.tabs').tabs();
			
			var $frm_import = $('#frmBehaviorImport');
			
			$frm_import.find('button.submit').click(function() {
				genericAjaxPost('frmBehaviorImport','','c=internal&a=saveBehaviorImportJson', function(json) {
					
					$popup = genericAjaxPopupFetch('node_trigger');
					
					if(json.config_html) {
						var $frm_import = $('#frmBehaviorImport');
						$frm_import.find('div.import').hide();
						$frm_import.find('div.config').hide().html(json.config_html).fadeIn();
						
					} else {
						event = jQuery.Event('trigger_create');
						event.trigger_id = json.trigger_id;
						event.event_point = json.event_point;
						$popup.trigger(event);
						
						genericAjaxPopupDestroy('node_trigger');
					}
					
				});
			});
		{/if}
	});
</script>
