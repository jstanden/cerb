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
							<li style="display: block; cursor: move; margin: 5px;"><input type="hidden" name="tokens[]" value="{$token}">{$token}<a style="position: absolute; visibility: hidden; top: -7px; right: -6px; display: block;"><span class="cerb-icons cerb-icon-circle-remove"></span></a></li>
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

		<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.export'|devblocks_translate|capitalize}</button>
		<button type="button" class="cancel cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
	</div>
	
	<div id="export{$view_id}_tabBuild">
		<div class="cerb-code-editor-toolbar">
			<button type="button" title="{'common.placeholders'|devblocks_translate|capitalize}" class="cerb-code-editor-toolbar-button cerb-editor-button-event-placeholders"><span class="cerb-icons cerb-icon-placeholders"></span></button>
			<ul class="cerb-code-editor-toolbar-menu-placeholders cerb-float" style="width:250px;display:none;">
				{tree keys=$placeholders}
			</ul>
		</div>
		<textarea name="export_kata" data-editor-lines="15" spellcheck="false">{$export_kata}</textarea>
		
		<div style="margin-top:10px;">
			<button type="button" class="submit-build"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.export'|devblocks_translate|capitalize}</button>
			<button type="button" class="cancel cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
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
	
	let $tabs = $('#export{$view_id}_tabs');
	$tabs.find('> ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });
	
	let editor_columns_kata = new CerbUI.KataEditor($tab_build.find('textarea[name=export_kata]')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataSchemaWorklistExport)
	});
	
	new CerbUI.Menu($editor_toolbar.find('.cerb-code-editor-toolbar-menu-placeholders').hide()[0], {
		clickTrigger: $editor_toolbar.find('.cerb-editor-button-event-placeholders')[0],
		selectableParents: true,
		filter: true,
		onSelect: function(li, src) {
			editor_columns_kata.insertSnippet(src.getAttribute('data-token'));
			editor_columns_kata.focus();
		}
	});
	
	new CerbUI.Menu($frm.find('ul.menu').hide()[0], {
		inline: true,
		selectableParents: true,
		filter: true,
		onSelect: function(li, src) {
			var token = src.getAttribute('data-token');
			var label = src.getAttribute('data-label');

			if(null == token || null == label)
				return;
			
			var $bubble = $('<li style="display:block;"></li>')
				.css('cursor', 'move')
				.css('margin', '5px')
			;
			
			var $hidden = $('<input>');
			$hidden.attr('type', 'hidden');
			$hidden.attr('name', 'tokens[]');
			$hidden.attr('value', token);
			
			var $a = $('<a style="position: absolute; visibility: hidden; top: -7px; right: -6px; display: block;"><span class="cerb-icons cerb-icon-circle-remove"></span></a>');
			
			$bubble.append($hidden);
			$bubble.append(token);
			$bubble.append($a);
			$bubbles.append($bubble);
		}
	});
	
	$bubbles.on('click', function(e) {
		var $target = $(e.target);
		if($target.is('.cerb-icon-circle-remove')) {
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
	
	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($frm.find('ul.bubbles.sortable').get(0), {
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
				cerbOpenQueueJobPeek(json.job_id, '{$view_id}');
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