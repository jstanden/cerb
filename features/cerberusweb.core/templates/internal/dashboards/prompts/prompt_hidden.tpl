{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}
<input type="hidden" class="cerb-hidden-prompt" name="prompts[{$prompt.placeholder}]" value="{$prompt_value}">