<div class="{if 'error' == $style}cerb-form-builder-error{elseif 'say' == $style}cerb-form-builder-response-say{else}cerb-form-builder-response-text{/if}">
{if !in_array($format, ['markdown','html'])}
	{$message|escape|nl2br nofilter}
{else}
	{$message nofilter}
{/if}
</div>