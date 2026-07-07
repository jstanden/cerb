<div id="widget{$widget->id}Config" class="cerb-ui-form" style="margin-top:10px;">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
			<div class="cerb-ui-header--title-sm">Run this data query:</div>
			<div class="cerb-ui-header--right">
				{include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/data-queries/"}
			</div>
		</div>

		<textarea id="widget{$widget->id}DataQuery" class="placeholders" name="params[data_query]" data-editor-lines="12" spellcheck="false">{$widget->extension_params.data_query}</textarea>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Chart options:</div>
		</div>

		<div class="cerb-ui-form">
			{$formats = ['text'=>'Text','number'=>'Number','number.minutes'=>'Time elapsed (minutes)','number.seconds'=>'Time elapsed (seconds)']}

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Format x-axis values as <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
				<select name="params[xaxis_format]">
					{foreach from=$formats item=label key=k}
					<option value="{$k}" {if $k == $widget->extension_params.xaxis_format}selected="selected"{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>

			{$formats = ['number'=>'Number','number.minutes'=>'Time elapsed (minutes)','number.seconds'=>'Time elapsed (seconds)']}

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Format y-axis values as <span class="cerb-ui-form--hint">{'common.optional'|devblocks_translate|lower}</span></label>
				<select name="params[yaxis_format]">
					{foreach from=$formats item=label key=k}
					<option value="{$k}" {if $k == $widget->extension_params.yaxis_format}selected="selected"{/if}>{$label}</option>
					{/foreach}
				</select>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">The chart height is</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
					<input type="text" size="5" maxlength="4" name="params[height]" placeholder="(auto)" value="{$widget->extension_params.height}" style="width:6em;flex:0 0 auto;">
					<span class="cerb-u-text-muted">pixels</span>
				</div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	const dq = new CerbUI.DataQuery($config.find('#widget{$widget->id}DataQuery')[0], {
		onAutocomplete: CerbUI.DataQuery.dataQueryFieldSource(),
		toolbar: true,
	});
});
</script>