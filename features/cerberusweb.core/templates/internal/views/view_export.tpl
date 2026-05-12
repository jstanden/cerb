<form action="{devblocks_url}{/devblocks_url}" method="post" id="frm{$view_id}_export">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worklists">
<input type="hidden" name="action" value="saveExport">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="export_mode" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="export-settings">

<h3>{'common.export'|devblocks_translate|capitalize}</h3>

<div style="margin-top:10px;">
	<b>Export rows as:</b>
	<div style="margin-left:10px;">
		<label><input type="radio" name="export_as" value="csv" checked="checked"> Comma-separated values (.csv)</label>
		<label><input type="radio" name="export_as" value="jsonl"> JSON Lines (.jsonl)</label>
		<label><input type="radio" name="export_as" value="json"> JSON (.json)</label>
		<label><input type="radio" name="export_as" value="xml"> XML (.xml)</label>
	</div>
</div>

{function tree level=0}
	{foreach from=$keys item=data key=idx}
		{if is_array($data->children) && !empty($data->children)}
			<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
				{if $data->key}
					<div style="font-weight:bold;">{$data->l|capitalize}</div>
				{else}
					<div>{$idx|capitalize}</div>
				{/if}
				<ul>
					{tree keys=$data->children level=$level+1}
				</ul>
			</li>
		{elseif $data->key}
			<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
		{/if}
	{/foreach}
{/function}

<div id="export{$view_id}_tabs" style="margin-top:10px;display:none;">
	<ul>
		<li><a href="#export{$view_id}_tabFields">{'common.fields'|devblocks_translate|capitalize}</a></li>
		<li><a href="#export{$view_id}_tabBuild">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	
	<div id="export{$view_id}_tabFields">
		<table cellpadding="10" cellspacing="0">
			<tr>
				<td valign="top">
					<b>Selected:</b>

					<ul class="bubbles sortable" style="display:block;padding:0;">
						{foreach from=$tokens item=token}
							<li style="display: block; cursor: move; margin: 5px;"><input type="hidden" name="tokens[]" value="{$token}">{$token}<a style="position: absolute; visibility: hidden; top: -7px; right: -6px; display: block;"><span class="glyphicons glyphicons-circle-remove"></span></a></li>
						{/foreach}
					</ul>
				</td>

				<td valign="top">
					<b>{'common.add'|devblocks_translate|capitalize}:</b>
					
					<ul class="menu" style="width:250px;">
						{tree keys=$placeholders}
					</ul>
				</td>
			</tr>
		</table>

		<div style="margin-bottom:10px;">
			<b>Format dates as:</b>
			<div style="margin-left:10px;">
				<label><input type="radio" name="format_timestamps" value="1" checked="checked"> Text</label>
				<label><input type="radio" name="format_timestamps" value="0"> Unix Timestamps</label>
			</div>
		</div>

		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.export'|devblocks_translate|capitalize}</button>
		<button type="button" class="cancel"><span class="glyphicons glyphicons-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
	</div>
	
	<div id="export{$view_id}_tabBuild">
		<div class="cerb-code-editor-toolbar">
			<button type="button" title="{'common.placeholders'|devblocks_translate|capitalize}" class="cerb-code-editor-toolbar-button cerb-editor-button-event-placeholders"><span class="glyphicons glyphicons-tags"></span></button>
			<ul class="cerb-code-editor-toolbar-menu-placeholders cerb-float" style="width:250px;display:none;">
				{tree keys=$placeholders}
			</ul>
		</div>
		<textarea name="export_kata" data-editor-mode="ace/mode/cerb_kata">{$export_kata}</textarea>
		
		<div style="margin-top:10px;">
			<button type="button" class="submit-build"><span class="glyphicons glyphicons-circle-ok"></span> {'common.export'|devblocks_translate|capitalize}</button>
			<button type="button" class="cancel"><span class="glyphicons glyphicons-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
		</div>
	</div>
</div>

</div>

<div class="export-status"></div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $frm = $('#frm{$view_id}_export');
	var $bubbles = $frm.find('ul.bubbles');
	var $settings = $frm.find('div.export-settings');
	var $status = $frm.find('div.export-status');
	
	let $tab_build = $('#export{$view_id}_tabBuild');
	let $editor_toolbar = $tab_build.find('.cerb-code-editor-toolbar');
	
	let $tabs = $('#export{$view_id}_tabs').tabs();
	
	let $editor_columns_kata = $tab_build.find('[data-editor-mode]')
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaWorklistExport
		})
		.nextAll('pre.ace_editor')
	;

	let editor_columns_kata = ace.edit($editor_columns_kata.attr('id'));
	
	let $editor_columns_menu = $editor_toolbar.find('.cerb-code-editor-toolbar-menu-placeholders').menu({
		select: function(event, ui) {
			$editor_columns_menu.hide();
			editor_columns_kata.insertSnippet(ui.item.attr('data-token'));
			editor_columns_kata.focus();
		}
	});

	$editor_toolbar.find('.cerb-editor-button-event-placeholders').on('click', function() {
		$editor_columns_menu.toggle();
	});
	
	$frm.find('ul.menu').menu({
		select: function(event, ui) {
			var token = ui.item.attr('data-token');
			var label = ui.item.attr('data-label');
			
			if(undefined === token || undefined === label)
				return;
			
			var $bubble = $('<li style="display:block;"></li>')
				.css('cursor', 'move')
				.css('margin', '5px')
			;
			
			var $hidden = $('<input>');
			$hidden.attr('type', 'hidden');
			$hidden.attr('name', 'tokens[]');
			$hidden.attr('value', token);
			
			var $a = $('<a style="position: absolute; visibility: hidden; top: -7px; right: -6px; display: block;"><span class="glyphicons glyphicons-circle-remove"></span></a>');
			
			$bubble.append($hidden);
			$bubble.append(token);
			$bubble.append($a);
			$bubbles.append($bubble);
		}
	});
	
	$bubbles.on('click', function(e) {
		var $target = $(e.target);
		if($target.is('.glyphicons-circle-remove')) {
			e.stopPropagation();
			$target.closest('li').remove();
		}
	});
	
	$bubbles.on('mouseover', function(e) {
		$bubbles.find('a').css('visibility', 'visible');
	});
	
	$bubbles.on('mouseout', function(e) {
		$bubbles.find('a').css('visibility', 'hidden');
	});
	
	$frm.find('ul.bubbles.sortable').sortable({
		placeholder: 'ui-state-highlight',
		items: 'li',
		distance: 10
	});
	
	let funcSubmit = function() {
		Devblocks.clearAlerts();
		$settings.hide();

		let $html = $('<div style="font-size:18px;font-weight:bold;text-align:center;padding:10px;margin:10px;"/>')
			.text('Submitting export...')
			.append('<br/>')
			.append(Devblocks.getSpinner())
			;

		$status.html($html).fadeIn();

		genericAjaxPost($frm, '', '', function(json) {
			if('object' != typeof json) {
				$status.text('').hide();
				$settings.show();
				return;
			}

			if(json.error) {
				Devblocks.createAlertError(json.error);
				$status.text('').hide();
				$settings.show();
				return;
			}

			if(json.job_id) {
				// Open the queue job peek to monitor progress
				let $trigger = $('<a/>')
					.attr('data-context', 'cerb.contexts.queue.job')
					.attr('data-context-id', String(json.job_id))
					.css('display', 'none')
					.appendTo('body');

				$trigger
					.cerbPeekTrigger({ width: '600' })
					.on('cerb-peek-closed', function(event) {
						event.stopPropagation();
						$trigger.remove();
						genericAjaxGet('view{$view_id}',
							'c=internal&a=invoke&module=worklists&action=refresh&id={$view_id}'
						);
					})
					.trigger('click');

				$('#{$view_id}_tips').html('').hide();
			}
		});
	};

	$frm.find('button.submit').click(function(e) {
		e.stopPropagation();
		$frm.find('input:hidden[name=export_mode]').val('');
		funcSubmit();
	});

	$frm.find('button.submit-build').click(function(e) {
		e.stopPropagation();
		$frm.find('input:hidden[name=export_mode]').val('kata');
		funcSubmit();
	});

	$frm.find('button.cancel').on('click', function(e) {
		e.stopPropagation();
		$('#{$view_id}_tips').html('').hide();
	});
	
	$tabs.show();
});
</script>