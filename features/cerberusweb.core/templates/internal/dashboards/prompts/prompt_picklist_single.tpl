{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div style="display:inline-block;vertical-align:middle;">
	<div class="bubble cerb-filter-editor" style="padding:5px;display:block;">
		<b>{$prompt.label}</b> 
		
		<div>
			<select name="prompts[{$prompt.placeholder}]">
				{foreach from=$prompt.params.options item=option key=option_key}
					<option value="{$option}" {if $prompt_value == $option}selected='selected'{/if}>
						{if is_string($option_key)}
							{$option_key}
						{else}
							{$option}
						{/if}
					</option>
				{/foreach}
			</select>
		</div>
	</div>
</div>