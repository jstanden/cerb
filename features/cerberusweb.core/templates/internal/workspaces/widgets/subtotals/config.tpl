{$st_uid = uniqid()}
{$div_popup_worklist = uniqid()}
{$worklist_ctx_id = $widget->params.worklist_model.context}

<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-u-mt-3" id="widget{$widget->id}ConfigTabDatasource">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Style</label>
			<div>
				<input type="hidden" name="params[style]" id="stStyle{$st_uid}" value="{if $widget->params.style == 'pie'}pie{else}list{/if}">
				<div class="cerb-ui-switcher" data-cerb-input="stStyle{$st_uid}">
					<button type="button" data-value="list"{if empty($widget->params.style) || $widget->params.style == 'list'} class="cerb-ui-switcher--active"{/if}>List</button>
					<button type="button" data-value="pie"{if $widget->params.style == 'pie'} class="cerb-ui-switcher--active"{/if}>Pie chart</button>
				</div>
			</div>
		</div>

		<div id="widget{$widget->id}Datasource">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Display</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
					<select class="context">
						<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
						{foreach from=$context_mfts item=context_mft key=context_id}
						<option value="{$context_id}" {if $worklist_ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
						{/foreach}
					</select>
					<span class="cerb-u-text-muted">subtotals using</span>
					<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>
					<input type="hidden" name="params[worklist_model_json]" value="{$widget->params.worklist_model|json_encode}" class="model">
				</div>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Limit</label>
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					<span class="cerb-u-text-muted">to the top</span>
					{$limit_to = $widget->params.limit_to|default:20}
					<input type="text" name="params[limit_to]" value="{$limit_to}" placeholder="20" style="width:5em;flex:0 0 auto;">
					<span class="cerb-u-text-muted">subtotals</span>
				</div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}ConfigTabDatasource');

	if(window.CerbUI && CerbUI.Switcher) {
		var $style = $config.find('#stStyle{$st_uid}');
		var styleEl = $config.find('[data-cerb-input="stStyle{$st_uid}"]')[0];
		if(styleEl)
			new CerbUI.Switcher(styleEl, { value: $style.val(), onSelect: function(value) { $style.val(value); } });
	}

	$('#popup{$div_popup_worklist}').click(function(e) {
		var $select = $(this).siblings('select.context');
		var context = $select.val();

		if(context.length == 0) {
			if(window.CerbUI && CerbUI.effects) CerbUI.effects.flash($select);
			return;
		}

		var $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist"}',null,true,'750');
		$chooser.bind('chooser_save',function(event) {
			if(null != event.worklist_model) {
				$('#popup{$div_popup_worklist}').parent().find('input:hidden.model').val(event.worklist_model);
			}
		});
	});
});
</script>
