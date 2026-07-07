<div id="tab{$tab->id}Config" style="margin-top:10px;">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.layout'|devblocks_translate|capitalize}</div>
		</div>

		<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-2">
			<label class="cerb-layout-choice">
				<input type="radio" name="params[layout]" value="" {if empty($tab->params.layout)}checked="checked"{/if}>
				<svg width="100" height="80">
					<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="5" y="5" width="90" height="70" />
					</g>
				</svg>
			</label>

			<label class="cerb-layout-choice">
				<input type="radio" name="params[layout]" value="sidebar_left" {if 'sidebar_left' == $tab->params.layout}checked="checked"{/if}>
				<svg width="100" height="80">
					<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="5" y="5" width="30" height="70" />
						<rect x="40" y="5" width="55" height="70" />
					</g>
				</svg>
			</label>

			<label class="cerb-layout-choice">
				<input type="radio" name="params[layout]" value="sidebar_right" {if 'sidebar_right' == $tab->params.layout}checked="checked"{/if}>
				<svg width="100" height="80">
					<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="5" y="5" width="55" height="70" />
						<rect x="65" y="5" width="30" height="70" />
					</g>
				</svg>
			</label>

			<label class="cerb-layout-choice">
				<input type="radio" name="params[layout]" value="halves" {if 'halves' == $tab->params.layout}checked="checked"{/if}>
				<svg width="100" height="80">
					<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="5" y="5" width="42" height="70" />
						<rect x="53" y="5" width="42" height="70" />
					</g>
				</svg>
			</label>

			<label class="cerb-layout-choice">
				<input type="radio" name="params[layout]" value="thirds" {if 'thirds' == $tab->params.layout}checked="checked"{/if}>
				<svg width="100" height="80">
					<g style="fill:var(--cerb-color-background-contrast-220);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="1" y="1" width="98" height="78" />
					</g>
					<g style="fill:var(--cerb-color-background-contrast-190);stroke:var(--cerb-color-background-contrast-180);stroke-width:1">
						<rect x="4" y="5" width="28" height="70" />
						<rect x="36" y="5" width="28" height="70" />
						<rect x="68" y="5" width="28" height="70" />
					</g>
				</svg>
			</label>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--label">{'common.prompts'|devblocks_translate|capitalize} (KATA)</div>
		</div>
		<ul class="cerb-ui-toolbar" id="promptsToolbar_{$tab->id}">
			<li data-icon="play" data-value="run" title="{'common.preview'|devblocks_translate|capitalize}"></li>
			<li></li>
			<li data-icon="circle-plus" title="{'common.add'|devblocks_translate|capitalize}">
				<ul>
					<li data-value="chooser">Chooser</li>
					<li data-value="date_range">Date Range</li>
					<li data-value="picklist">Picklist</li>
					<li data-value="text">Text</li>
				</ul>
			</li>
			<li data-icon="placeholders" title="Insert placeholder">
				<ul>
				{function tree level=0}
					{foreach from=$keys item=data key=idx}
						{if is_array($data->children) && !empty($data->children)}
							<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
								{if $data->key}{$data->l|capitalize}{else}{$idx|capitalize}{/if}
								<ul>
									{tree keys=$data->children level=$level+1}
								</ul>
							</li>
						{elseif $data->key}
							<li data-token="{$data->key}" data-label="{$data->label}">{$data->l|capitalize}</li>
						{/if}
					{/foreach}
				{/function}
				{tree keys=$placeholders}
				</ul>
			</li>
			<li data-icon="circle-question-mark" data-value="help" title="{'common.help'|devblocks_translate|capitalize}"></li>
		</ul>

		<textarea id="promptsEditor_{$tab->id}" name="params[prompts_kata]" data-editor-lines="4" spellcheck="false">{$tab->params.prompts_kata}</textarea>

		<div class="cerb-code-editor-preview-output"></div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#tab{$tab->id}Config');
	let $placeholder_output = $frm.find('.cerb-code-editor-preview-output');

	// Prompts Editor
	let editor = new CerbUI.KataEditor($frm.find('#promptsEditor_{$tab->id}')[0], {
		onAutocomplete: CerbUI.KataEditor.kataFieldSource(CerbUI.editorCore.autocompleteSchemas.kataSchemaDashboardFilters)
	});

	let runPreview = function() {
		$placeholder_output.html('');

		Devblocks.getSpinner().appendTo($placeholder_output);

		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'workspace_tab');
		formData.set('action', 'previewDashboardPrompts');
		formData.set('kata', editor.getValue());

		genericAjaxPost(formData, null, null, function (html) {
			$placeholder_output.html(html);
		});
	};

	// The "Add a prompt" snippets — Ace-style numbered tabstops; aceSnippetToCerb lands the caret at the first stop
	let getSnippet = function(type) {
		{literal}
		switch(type) {
			case 'date_range':
				return "date_range/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
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
			case 'picklist':
				return "picklist/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
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
			case 'chooser':
				return "chooser/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
					"  label: ${2:Chooser}:\n" +
					"  params:\n" +
					"    context: ${3:worker}\n" +
					"    single@bool: yes\n" +
					"\n"
				;
			case 'text':
				return "text/prompt_${1:" + Devblocks.uniqueId() + "}:\n" +
					"  label: ${2:Text}:\n" +
					"  default: ${3:text}\n" +
					"\n"
				;
		}
		{/literal}
		return '';
	};

	// Toolbar
	let prompts_toolbar = $frm.find('#promptsToolbar_{$tab->id}')[0];
	if(prompts_toolbar && window.CerbUI && CerbUI.Toolbar) {
		new CerbUI.Toolbar(prompts_toolbar, {
			onSelect: function(item, sourceLi) {
				switch(item.value) {
					case 'run':
						runPreview();
						break;
					case 'help':
						window.open('https://cerb.ai/docs/dashboards/#prompts', '_blank');
						break;
					case 'chooser':
					case 'date_range':
					case 'picklist':
					case 'text':
						editor.insertSnippet(CerbUI.editorCore.aceSnippetToCerb(getSnippet(item.value)));
						break;
					default:
						// Placeholders tree leaf — insert the placeholder token at the caret
						if(sourceLi && sourceLi.dataset && sourceLi.dataset.token)
							editor.insertSnippet({literal}'{{'{/literal} + sourceLi.dataset.token + {literal}'}}'{/literal});
						break;
				}
			}
		});
	}
});
</script>