{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-text" id="{$element_id}">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		<input name="prompts[{$var}]" {if 'password' == $type}type="password"{else}type="text"{/if} placeholder="{$placeholder}" value="{$value|default:$default}" autocomplete="off">
	</div>
</div>