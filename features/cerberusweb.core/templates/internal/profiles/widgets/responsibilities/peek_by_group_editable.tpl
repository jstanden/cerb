<form id="group{$group->id}Responsibilities" action="#">

<div style="column-width:275px;">
{foreach from=$buckets item=bucket}
	<div class="cerb-ui-panel cerb-ui-panel--spaced" style="break-inside:avoid-column;margin:0 0 10px 0;">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BUCKET}" data-context-id="{$bucket->id}">{$bucket->name}</a></div>
		</div>

		<div>
		{foreach from=$members item=member}
		{$worker = $workers.{$member->id}}
		{$responsibility = $responsibilities.{$bucket->id}.{$member->id}}

		{if $worker}
		<div style="width:250px;display:block;margin:0 10px 10px 5px;">
			<label>
				<a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$worker->id}"><b>{$worker->getName()}</b></a> {if $worker->title}({$worker->title}){/if}
			</label>

			<div class="cerb-ui-slider" style="max-width:250px;margin-bottom:10px;" data-worker-id="{$worker->id}" data-bucket-id="{$bucket->id}">
				<input type="hidden" name="responsibilities[{$bucket->id}][{$worker->id}]" value="{$responsibility|default:0}">
			</div>
		</div>
		{/if}

		{/foreach}
		</div>

	</div>
{/foreach}
</div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="cerb-ui-button done"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.done'|devblocks_translate|capitalize}</button>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFetch('peek');
	let $frm = $('#group{$group->id}Responsibilities');

	Devblocks.formDisableSubmit($frm);

	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.responsibilities'|devblocks_translate|capitalize}: {$group->name}");

		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();

		// One slider per worker; inverted scale (red=low, green=high), saves on change
		if(window.CerbUI && CerbUI.Slider) {
			$frm.find('div.cerb-ui-slider').each(function() {
				let worker_id = this.getAttribute('data-worker-id');
				let bucket_id = this.getAttribute('data-bucket-id');

				new CerbUI.Slider(this, {
					min: 0, max: 100, step: 10, midpoint: 50, invert: true,
					onChange: function(value) {
						let form_data = new FormData();
						form_data.append('c', 'profiles');
						form_data.append('a', 'invokeWidget');
						form_data.append('widget_id', '{$widget->id}');
						form_data.append('action', 'savePopupJson');
						form_data.append('worker_id', worker_id);
						form_data.append('bucket_id', bucket_id);
						form_data.append('responsibility', value);

						genericAjaxPost(form_data, '', null, function(err) {
							Devblocks.clearAlerts();

							if(err.error) {
								Devblocks.createAlertError(err.error);
							} else {
								Devblocks.createAlert('Saved!');
							}
						});
					}
				});
			});
		}

		$frm.find('button.done').click(function(e) {
			e.stopPropagation();
			genericAjaxPopupClose($popup, 'responsibilities_save');
		});
	});
});
</script>
