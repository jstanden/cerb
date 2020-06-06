<fieldset style="margin-top:10px;position:relative;">
	<span class="glyphicons glyphicons-circle-remove" style="position:absolute;right:-5px;top:-10px;cursor:pointer;color:rgb(80,80,80);zoom:1.5;" onclick="$(this).closest('fieldset').remove();"></span>
	<legend>{'common.preview'|devblocks_translate|capitalize}</legend>

	<div>
	{if !$interactions}
		No interactions are available.
	{else}
		<input type="hidden" name="interaction" value="">
		{foreach from=$interactions item=interaction key=interaction_key}
			{if !$interaction.button.hidden}
			<button type="button" data-label="{$interaction_key}">
				{if $interaction.button.icon}<span class="glyphicons glyphicons-{$interaction.button.icon}"></span>{/if}
				{$interaction.button.label|default:$interaction_key}
			</button>
			{/if}
		{/foreach}
	{/if}
	</div>
</fieldset>
