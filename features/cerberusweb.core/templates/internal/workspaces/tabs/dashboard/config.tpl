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
		<legend>Prompted placeholders: <small>(optional)</small> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/dashboards/filters/"}</legend>
		<textarea name="params[placeholder_prompts]" class="cerb-code-editor" data-editor-mode="ace/mode/yaml" style="width:95%;height:50px;">{$tab->params.placeholder_prompts}</textarea>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#tab{$tab->id}Config');
	var $textarea = $frm.find('.cerb-code-editor');
	
	var $editor = $textarea
		.cerbCodeEditor()
		.cerbCodeEditorAutocompleteYaml({
			autocomplete_suggestions: cerbAutocompleteSuggestions.yamlDashboardFilters
		})
		.nextAll('pre.ace_editor')
	;
});
</script>