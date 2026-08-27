{* One simulator prime-input field, rendered from its descriptor ($field) as a cerb-ui-form control. The posted
   name is always prompts[<key>]; submitPrimeState maps it back to state by descriptor. A `required` descriptor
   (automation #inputs: `required@bool: yes`) gets a red asterisk + a submit-time non-empty check (popup JS). *}
{$ttype = $field.text_type|default:''}
{$req = $field.required|default:false}
<div class="cerb-ui-form--field"{if $req} data-prime-required data-prime-name="prompts[{$field.key}]"{/if}>
	<label class="cerb-ui-form--label">{$field.label}{if $req} <span class="cerb-ui-form--required">*</span>{/if}</label>

	{if $field.component == 'chooser'}
		{$chooser_id = uniqid('primeChooser')}
		<div class="cerb-ui-record-chooser"
			id="{$chooser_id}"
			data-prime-chooser
			data-context="{$field.record_type}"
			data-name="prompts[{$field.key}]"
			{if $field.query|default:''}data-query="{$field.query}"{/if}
			data-multiple="{if $field.multiple|default:false}1{else}0{/if}">
			{foreach from=$field.selected item=sel}
			<li data-context="{$field.record_type}" data-context-id="{$sel.id}" data-label="{$sel.label}" data-image="{$sel.image}"></li>
			{/foreach}
		</div>

	{elseif $field.component == 'context_chooser'}
		{$ctxchooser_id = uniqid('primeCtxChooser')}
		<div class="cerb-ui-record-chooser"
			id="{$ctxchooser_id}"
			data-prime-context-chooser
			data-name="prompts[{$field.key}]"
			data-contexts="{$field.contexts_json}"
			data-multiple="0">
			{foreach from=$field.selected item=sel}
			<li data-context="{$sel.context}" data-context-id="{$sel.id}" data-label="{$sel.label}" data-image="{$sel.image}"></li>
			{/foreach}
		</div>

	{elseif $field.component == 'select'}
		{$sel_default = $field.default|default:''}
		<select name="prompts[{$field.key}]" data-prime-select>
			{foreach from=$field.options item=opt}
			<option value="{$opt}"{if $opt == $sel_default} selected{/if}>{$opt}</option>
			{/foreach}
		</select>

	{elseif $field.component == 'text_chooser'}
		<input type="text" name="prompts[{$field.key}]" value="{$field.default|default:''}" autocomplete="off" data-prime-text-chooser data-suggestions="{$field.options_json}"{if $req} required{/if}>

	{elseif $field.component == 'scripting_editor'}
		<textarea name="prompts[{$field.key}]" data-prime-scripting-editor spellcheck="false"{if $req} required{/if}>{$field.default|default:''}</textarea>

	{elseif $field.component == 'textarea'}
		<textarea name="prompts[{$field.key}]" rows="4"{if $req} required{/if}>{$field.default|default:''}</textarea>

	{elseif $field.emit == 'bool'}
		<label class="cerb-ui-toggle">
			<input type="checkbox" name="prompts[{$field.key}]" value="yes"{if $field.default|default:false} checked{/if}>
			<span class="cerb-ui-toggle--slider"></span>
		</label>

	{elseif $ttype == 'date'}
		<input type="text" name="prompts[{$field.key}]" value="{$field.default|default:''}" autocomplete="off" data-prime-date{if $req} required{/if}>

	{else}
		<input type="{if $ttype == 'password'}password{else}text{/if}" name="prompts[{$field.key}]" value="{$field.default|default:''}" autocomplete="off"{if $req} required{/if}>
	{/if}
</div>
