{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div id="{$uniqid}" class="cerb-ui-form--field cerb-filter-editor" style="flex:1 1 12em;">
	<label class="cerb-ui-form--label">{$prompt.label}</label>
	<input type="text" class="cerb-text-prompt" name="prompts[{$prompt.placeholder}]" value="{$prompt_value}">
</div>
