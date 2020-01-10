{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div style="display:inline-block;vertical-align:middle;">
	<div id="{$uniqid}" class="bubble cerb-filter-editor" style="padding:5px;display:block;">
		<div>
			<b>{$prompt.label}</b>
		</div>
		<div>
			<input type="text" class="cerb-text-prompt" name="prompts[{$prompt.placeholder}]" value="{$prompt_value}">
		</div>
	</div>
</div>