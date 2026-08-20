{* `scale` is Model_AgentModel::getRatingScale() -- the single source for the value<->label mapping. *}
{$current_label = 'Unrated'}
{foreach from=$scale key=tier item=tier_label}{if $value == $tier}{$current_label = $tier_label}{/if}{/foreach}
<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{$label}</label>
	<input type="hidden" name="{$name}" id="rating_{$key}_{$form_id}" value="{$value|intval}">
	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<div class="cerb-ui-rating" data-cerb-input="rating_{$key}_{$form_id}" data-cerb-label="rating_{$key}_label_{$form_id}" data-cerb-labels="{','|implode:$scale|capitalize}">
			{foreach from=$scale key=tier item=tier_label}
				<button type="button" data-value="{$tier}" title="{$tier_label|capitalize}" aria-label="{$tier_label|capitalize}"{if $value >= $tier} class="cerb-ui-rating--on"{/if}><span class="cerb-icons cerb-icon-{$icon}"></span></button>
			{/foreach}
		</div>
		<span class="cerb-ui-rating--label" id="rating_{$key}_label_{$form_id}">{$current_label|capitalize}</span>
	</div>
	<div class="cerb-ui-form--hint">{$hint}</div>
</div>
