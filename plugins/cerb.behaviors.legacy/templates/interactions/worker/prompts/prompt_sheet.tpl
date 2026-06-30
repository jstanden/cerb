{$element_id = uniqid('prompt_')}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-sheet" id="{$element_id}">
	<h6>{$label}</h6>

	{include file="devblocks:cerberusweb.core::ui/sheets/render.tpl" sheet_selection_key="prompts[{$var}]" default=$default}
</div>