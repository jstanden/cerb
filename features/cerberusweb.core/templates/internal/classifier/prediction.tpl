{$div_id = uniqid()}

<div id="{$div_id}">
	<div>
		<h3>{$prediction.classification.name} ({{$prediction.confidence*100}|number_format:2}%)</h3>
	</div>
	
	{if is_array($prediction.params) && !empty($prediction.params)}
	<div>
		<table cellpadding="5" class="cerb-expression-editor">
			{foreach from=$prediction.params item=param key=param_type}
				{foreach from=$param item=v key=k}
					<tr>
						<td class="expression"><span class="{$param_type}">{$param_type}</span></td>
						<td>
							{if $param_type == 'avail'}
								<i>{$v}</i>
							{elseif $param_type == 'contact_method'}
								<i>{$v}</i>
							{elseif $param_type == 'context'}
								<i>{$v}</i>
							{elseif $param_type == 'status'}
								<i>{$v}</i>
							{else}
								<i>{$k}</i>
							{/if}
						</td>
						<td>
							{if $param_type == 'alias'}
								{* No output *}
							{elseif $param_type == 'avail'}
								{$param|key}
							{elseif $param_type == 'contact'}
								<ul class="bubbles">
								{foreach from=$param.$k item=contact}
									<li>
										<img src="{$contact.image}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
										<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_CONTACT}" data-context-id="{$contact.id}">{$contact.full_name}</a>
									</li>
								{/foreach}
								</ul>
							{elseif $param_type == 'context'}
								{$param|key}
							{elseif $param_type == 'contact_method'}
								{$param|key}
							{elseif $param_type == 'date'}
								{$v.date}
							{elseif $param_type == 'duration'}
								{$v.secs|devblocks_prettysecs}
							{elseif $param_type == 'event'}
								{* No output *}
							{elseif $param_type == 'number'}
								{$v.value}
							{elseif $param_type == 'org'}
								<ul class="bubbles">
								{foreach from=$param.$k item=org}
									<li>
										<img src="{$org.image}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
										<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_ORG}" data-context-id="{$org.id}">{$org.name}</a>
									</li>
								{/foreach}
								</ul>
							{elseif $param_type == 'remind'}
								{* No output *}
							{elseif $param_type == 'status'}
								{$param|key}
							{elseif $param_type == 'temperature'}
								{$v.value} &deg;{$v.unit}
							{elseif $param_type == 'time'}
								{$v.time}
							{elseif $param_type == 'worker'}
								<ul class="bubbles">
								{foreach from=$param.$k item=worker}
									<li>
										<img src="{$worker.image}" style="height:16px;width:16px;vertical-align:middle;border-radius:16px;">
										<a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker.id}">{$worker.full_name}</a>
									</li>
								{/foreach}
								</ul>
							{else}
								{*var_dump($param)*}
							{/if}
						</td>
					</tr>
				{/foreach}
			{/foreach}
		</table>
	</div>
	{/if}
	
	<div>
		{if $is_writeable}
			<button type="button" class="cerb-train-trigger" data-context="{CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE}" data-context-id="0" data-edit="classifier.id:{$prediction.classifier.id} class.id:{$prediction.classification.id} text:{$prediction.text|escape:'url'}"><span class="glyphicons glyphicons-circle-plus"></span> {'common.train'|devblocks_translate|capitalize}</button>

			{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE}.import")}
			<button type="button" class="cerb-peek-import" data-context="{CerberusContexts::CONTEXT_CLASSIFIER}" data-context-id="{$prediction.classifier.id}" data-edit="true"><span class="glyphicons glyphicons-file-import"></span> {'common.import'|devblocks_translate|capitalize}</button>
			{/if}
		{/if}
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $container = $('#{$div_id}');
	var $parent = $container.closest('div.output');
	var $popup = genericAjaxPopupFind($container);
	var $layer = $popup.attr('data-layer');

	$container.find('.cerb-peek-trigger').cerbPeekTrigger();
	
	$container.find('.cerb-train-trigger')
		.cerbPeekTrigger()
			.on('cerb-peek-saved', function() {
				$parent.trigger('cerb-peek-saved');
			})
		;

	{if $is_writeable && $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_CLASSIFIER_EXAMPLE}.import")}
	$container.find('button.cerb-peek-import')
		.click(function() {
			var $import_popup = genericAjaxPopup('classifier_import','c=profiles&a=handleSectionAction&section=classifier&action=showImportPopup&classifier_id={$prediction.classifier.id}',null,false,'50%');

			$import_popup.on('dialogclose', function() {
				genericAjaxPopup($layer,'c=internal&a=showPeekPopup&context={CerberusContexts::CONTEXT_CLASSIFIER}&context_id={$prediction.classifier.id}','reuse',false,'50%');
			});
		})
		;
	{/if}
});
</script>
