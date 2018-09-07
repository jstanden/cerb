{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div style="display:inline-block;vertical-align:middle;">
	<div class="bubble cerb-filter-editor" style="padding:5px;display:block;">
		<b>{$prompt.label}</b> 
		
		<div>
			<select name="prompts[{$prompt.placeholder}]">
				{foreach from=$prompt.params.options item=option}
					{if is_string($option)}
					<option value="{$option}" {if $prompt_value == $option}selected='selected'{/if}>{$option}</option>
					{elseif is_array($option)}
					<option value="{$option.value}" {if $prompt_value == $option.value}selected='selected'{/if}>{$option.label}</option>
					{else}
					{/if}
				{/foreach}
			</select>
		</div>
	</div>
</div>