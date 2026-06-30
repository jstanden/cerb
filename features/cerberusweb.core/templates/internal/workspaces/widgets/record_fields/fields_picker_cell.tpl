{*
	One preview cell for the record-fields picker — a cerb-ui-tile acting as a checkbox label.
	Params:
	  k        - field key (property token)
	  v        - field array (label/type/value/params) with a real-or-dummy value
	  group    - save group: 0 for base/standalone, fieldset id for custom-fieldset fields
	  selected - bool: this field is currently included
	The whole tile is the checkbox label (clicking toggles); base tiles are also drag-reorderable
	(cursor:grab, no handle). The form posts params[properties][group][] unchanged.
*}
<label class="cerb-ui-tile cerb-ui-tile--block cerb-fieldpicker-cell{if $selected} is-selected{/if}" data-token="{$k}">
	<input type="checkbox" class="cerb-fieldpicker-cell--cb" name="params[properties][{$group}][]" value="{$k}"{if $selected} checked="checked"{/if}>
	<div class="cerb-fieldpicker-cell--body">
		{if !empty($v.value)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/profile_cell_renderer.tpl"}
		{else}
			<div style="margin-bottom:5px;max-width:200px;">
				<div><b style="font-size:.9em;">{$v.label|capitalize}</b></div>
				<span class="cerb-fieldpicker-cell--placeholder">&mdash;</span>
			</div>
		{/if}
	</div>
</label>
