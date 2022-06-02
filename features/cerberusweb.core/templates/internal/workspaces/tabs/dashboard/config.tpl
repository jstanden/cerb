<div id="tab{$tab->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>{'common.layout'|devblocks_translate|capitalize}</legend>

		<div style="margin:5px;display:inline-block;">
			<label>
				<input type="radio" name="params[layout]" value="" {if empty($tab->params.layout)}checked="checked"{/if}>
				<svg width="100" height="80" style="vertical-align:middle;">
					<g style="fill:lightgray;stroke:gray;stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:rgb(180,180,180);stroke:gray;stroke-width:1">
						<rect x="5" y="5" width="90" height="70" />
					</g>
				</svg>
			</label>
		</div>

		<div style="margin:5px;display:inline-block;">
			<label>
				<input type="radio" name="params[layout]" value="sidebar_left" {if 'sidebar_left' == $tab->params.layout}checked="checked"{/if}>
				<svg width="100" height="80" style="vertical-align:middle;">
					<g style="fill:lightgray;stroke:gray;stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:rgb(180,180,180);;stroke:gray;stroke-width:1">
						<rect x="5" y="5" width="30" height="70" />
						<rect x="40" y="5" width="55" height="70" />
					</g>
				</svg>
			</label>
		</div>

		<div style="margin:5px;display:inline-block;">
			<label>
				<input type="radio" name="params[layout]" value="sidebar_right" {if 'sidebar_right' == $tab->params.layout}checked="checked"{/if}>
				<svg width="100" height="80" style="vertical-align:middle;">
					<g style="fill:lightgray;stroke:gray;stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:rgb(180,180,180);;stroke:gray;stroke-width:1">
						<rect x="5" y="5" width="55" height="70" />
						<rect x="65" y="5" width="30" height="70" />
					</g>
				</svg>
			</label>
		</div>

		<div style="margin:5px;display:inline-block;">
			<label>
				<input type="radio" name="params[layout]" value="thirds" {if 'thirds' == $tab->params.layout}checked="checked"{/if}>
				<svg width="100" height="80" style="vertical-align:middle;">
					<g style="fill:lightgray;stroke:gray;stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:rgb(180,180,180);;stroke:gray;stroke-width:1">
						<rect x="4" y="5" width="28" height="70" />
						<rect x="36" y="5" width="28" height="70" />
						<rect x="68" y="5" width="28" height="70" />
					</g>
				</svg>
			</label>
		</div>

	</fieldset>

	<fieldset class="peek">
		<legend>{'common.prompts'|devblocks_translate|capitalize}: <small>(KATA)</small></legend>

		<div class="cerb-code-editor-toolbar">
			<button type="button" class="cerb-code-editor-toolbar-button cerb-editor-button-run"><span class="glyphicons glyphicons-play"></span></button>
			<div class="cerb-code-editor-toolbar-divider"></div>
			
			<button type="button" class="cerb-code-editor-toolbar-button cerb-editor-button-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
			<ul class="cerb-float" style="display:none;">
				<li data-type="chooser"><div>Chooser</div></li>
				<li data-type="date_range"><div>Date Range</div></li>
				<li data-type="picklist"><div>Picklist</div></li>
				<li data-type="text"><div>Text</div></li>
			</ul>
			
			<button type="button" title="Insert placeholder" class="cerb-code-editor-toolbar-button cerb-editor-button-placeholders"><span class="glyphicons glyphicons-sampler"></span></button>
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
			
			<ul class="cerb-float" style="display:none;">
			{tree keys=$placeholders}
			</ul>
			
			<button type="button" style="float:right;" class="cerb-code-editor-toolbar-button cerb-editor-button-help"><a href="https://cerb.ai/docs/dashboards/#prompts" target="_blank"><span class="glyphicons glyphicons-circle-question-mark"></span></a></button>
		</div>
		<textarea name="params[prompts_kata]" class="cerb-code-editor" data-editor-mode="ace/mode/cerb_kata" style="width:95%;height:50px;">{$tab->params.prompts_kata}</textarea>
		<div class="cerb-code-editor-preview-output"></div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#tab{$tab->id}Config');
	var $textarea = $frm.find('.cerb-code-editor');

	var $editor = $textarea
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteKata({
			autocomplete_suggestions: cerbAutocompleteSuggestions.kataSchemaDashboardFilters
		})
		.nextAll('pre.ace_editor')
	;

	var editor = ace.edit($editor.attr('id'));

	var $placeholder_output = $frm.find('.cerb-code-editor-preview-output');

	$frm.find('.cerb-editor-button-run').on('click', function (e) {
		$placeholder_output.html('');

		Devblocks.getSpinner().appendTo($placeholder_output);

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_tab');
		formData.set('action', 'previewDashboardPrompts');
		formData.set('kata', editor.getValue());

		genericAjaxPost(formData, null, null, function (html) {
			$placeholder_output.html(html);
		});
	});

	var $button_add = $frm.find('.cerb-editor-button-add');
	var $menu_add = $button_add.next('ul');

	$button_add.on('click', function(e) {
		$menu_add.toggle();
	});

	$menu_add.menu({
		select: function(e, ui) {
			e.stopPropagation();

			var $li = $(ui.item);
			var type = $li.attr('data-type');
			var snippet = '';

			$menu_add.hide();

			{literal}
			if('date_range' === type) {
				snippet = "date_range/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
					"  label: ${2:Date range}:\n" +
					"  default: ${3:-1 month to now}\n" +
					"  params:\n" +
					"    presets:\n" +
					"      ${4:1d}:\n" +
					"        label: today\n" +
					"        query: today to now\n" +
					"      1mo:\n" +
					"        query: -1 month\n" +
					"      ytd:\n" +
					"        query: jan 1 to now\n" +
					"      all:\n" +
					"        query: big bang to now\n" +
					"\n"
				;
			} else if('picklist' === type) {
				snippet = "picklist/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
					"  label: ${2:Picklist}:\n" +
					"  default: ${3:month}\n" +
					"  params:\n" +
					"    options@list:\n" +
					"      ${4:day}\n" +
					"      week\n" +
					"      month\n" +
					"      year\n" +
					"\n"
				;
			} else if('chooser' === type) {
				snippet = "chooser/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
					"  label: ${2:Chooser}:\n" +
					"  params:\n" +
					"    context: ${3:worker}\n" +
					"    single@bool: yes\n" +
					"\n"
				;
			} else if('text' === type) {
				snippet = "text/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
					"  label: ${2:Text}:\n" +
					"  default: ${3:text}\n" +
					"\n"
				;
			}
			{/literal}

			$editor.triggerHandler($.Event('cerb.appendText', { content: snippet } ));
		}
	});
	
	var $button_placeholders = $frm.find('.cerb-editor-button-placeholders');
	var $button_placeholders_menu = $frm.find('.cerb-editor-button-placeholders').next('ul');
	
	$button_placeholders.on('click', function(e) {
		$button_placeholders_menu
			.toggle()
			.position({ my:'left top', at:'left bottom', of:$button_placeholders, collision: 'fit' })
		;
	});

	$button_placeholders_menu.menu({
		'select': function(e, ui) {
			$editor.triggerHandler($.Event('cerb.insertAtCursor', { content: {literal}'{{'{/literal} + ui.item.attr('data-token') + {literal}'}}'{/literal} } ));
			$button_placeholders_menu.hide();
		}
	});
});
</script>