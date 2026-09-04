<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">Subscription</div>
		<div class="cerb-ui-header--subtitle">{if $is_cerb_cloud}Your plan is managed by Cerb Cloud{else}Configure an optional subscription to increase your queue slots{/if}</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced" id="setupLicenseActive">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-key"></span> Active Subscription</div>
		{if $license->key && !$is_cerb_cloud}
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button" data-cerb-button-update-license><span class="cerb-icons cerb-icon-edit"></span> {{'common.edit'|devblocks_translate|capitalize}}</button>
		</div>
		{/if}
	</div>

	{if $is_cerb_cloud}
		<div>
			<div class="cerb-ui-chip cerb-ui-chip--blue">
				<div class="cerb-ui-chip--head"><span class="cerb-icons cerb-icon-cloud cerb-u-mr-1"></span> Cerb Cloud</div>
				{if $cerb_cloud_subdomain}<div><div class="cerb-ui-chip--label">Instance</div><div class="cerb-ui-chip--value">{$cerb_cloud_subdomain}</div></div>{/if}
				{if $cerb_cloud_subscriber}<div><div class="cerb-ui-chip--label">Subscriber</div><div class="cerb-ui-chip--value">{$cerb_cloud_subscriber}</div></div>{/if}
				<div><div class="cerb-ui-chip--label">Workers / Seats</div><div class="cerb-ui-chip--value">Unlimited</div></div>
				<div><div class="cerb-ui-chip--label">Concurrency slots</div><div class="cerb-ui-chip--value">{$max_concurrency_slots}</div></div>
				<div><div class="cerb-ui-chip--label">Expires</div><div class="cerb-ui-chip--value">Never</div></div>
			</div>
		</div>
	{elseif !$license->key}
		<div>
			<div class="cerb-ui-chip">
				<div class="cerb-ui-chip--head"><span class="cerb-icons cerb-icon-users cerb-u-mr-1"></span> Community License</div>
				<div><div class="cerb-ui-chip--label">Workers / Seats</div><div class="cerb-ui-chip--value">Unlimited</div></div>
				<div><div class="cerb-ui-chip--label">Concurrency slots</div><div class="cerb-ui-chip--value">{$max_concurrency_slots}</div></div>
				<div><div class="cerb-ui-chip--label">Expires</div><div class="cerb-ui-chip--value">Never</div></div>
			</div>
		</div>
	{else}
		<div>
			<div class="cerb-ui-chip{if !$license_is_expired} cerb-ui-chip--green{else} cerb-ui-chip--orange{/if}">
				<div class="cerb-ui-chip--head"><span class="cerb-icons {if !$license_is_expired}cerb-icon-circle-ok{else}cerb-icon-clock{/if} cerb-u-mr-1"></span> {$license->company}</div>
				<div><div class="cerb-ui-chip--label">Workers / Seats</div><div class="cerb-ui-chip--value">Unlimited</div></div>
				<div><div class="cerb-ui-chip--label">Concurrency slots</div><div class="cerb-ui-chip--value">{$max_concurrency_slots}</div></div>
				<div><div class="cerb-ui-chip--label">Serial #</div><div class="cerb-ui-chip--value">{$license->key}</div></div>
				<div><div class="cerb-ui-chip--label">Expires</div><div class="cerb-ui-chip--value">{$license->upgrades|devblocks_date:'F d, Y':true}</div></div>
			</div>

			{if $license_is_expired}
			<div class="cerb-ui-panel cerb-ui-panel--warn cerb-u-mt-2">
				<div class="cerb-ui-header">
					<div class="cerb-ui-callout">
						<span class="cerb-icons cerb-icon-clock cerb-ui-callout--icon"></span>
						<div>
							<div class="cerb-ui-header--title-sm">Your subscription ended on {$license->upgrades|devblocks_date:'F d, Y':true}</div>
							<div class="cerb-ui-header--subtitle">This install is running at Community concurrency ({$max_concurrency_slots} slot{if $max_concurrency_slots != 1}s{/if}). Your workers and seats are still unlimited, and every feature keeps working. <a href="https://cerb.ai/#/renew&serial={$license->key}" target="_blank" rel="noopener">Renew to increase your concurrency slots</a>.</div>
						</div>
					</div>
				</div>
			</div>
			{/if}
		</div>
	{/if}
</div>

{* Every number here is derived at render time from the same policy the drain enforces -- no tier
   table and no prices, so there is nothing that can go stale against cerb.ai. *}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div>
			<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-gauge"></span> Concurrency</div>
			<div class="cerb-ui-header--subtitle">How much work this install runs at the same time.</div>
		</div>
		{if $max_concurrency_slots > 0}
		<div class="cerb-ui-header--summary"><b>{$agent_slots}</b> of <b>{$max_concurrency_slots}</b> slot{if $max_concurrency_slots != 1}s{/if} can run agent turns ({$turns_per_slot} sessions each, max {$turns_at_once})</div>
		{/if}
	</div>

	{if $max_concurrency_slots != $max_concurrency_slots_licensed}
	<div class="cerb-u-mt-3 cerb-u-text-muted">
		<span class="cerb-icons cerb-icon-circle-info cerb-u-mr-1"></span> This subscription allows {$max_concurrency_slots_licensed} slots. <code>APP_QUEUE_CONCURRENCY_SLOTS</code> in <code>framework.config.php</code> caps them at {$max_concurrency_slots} on this host.
	</div>
	{/if}

	{if $max_concurrency_slots > 0}
	<div class="cerb-ui-gantt" id="setupLicenseLanes"
		data-spans-fast="{$lane_spans_fast_json}"
		data-spans-slow="{$lane_spans_slow_json}"></div>
	{/if}

	<div class="cerb-u-mt-3 cerb-u-text-muted">
		<a href="https://cerb.ai/pricing" target="_blank" rel="noopener" class="cerb-u-text-muted">See plans</a> to raise concurrency. Workers and seats are unlimited on every plan.
	</div>
</div>

{* Outside the license form below, which Cloud never renders -- this panel does. *}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const el = document.getElementById('setupLicenseLanes');

	if(!el || !(window.CerbUI && CerbUI.Gantt))
		return;

	// `step: 1` makes the axis discrete, so a span's end slot is INCLUSIVE and each slot is one cell.
	// Without it, [1,6] would draw five slots instead of six.
	//
	// `segment: true` draws one block per slot rather than one bar per run, because a slot is a unit of
	// capacity and not a duration: the row shows the blocks it holds and leaves the rest as empty cells.
	//
	// No axis: an admin here is reading the SHAPE of the split, and numbering individual slots invites
	// the question of which slot is which -- a question this page cannot answer and does not need to.
	new CerbUI.Gantt(el, {
		xScale: 'linear',
		step: 1,
		segment: true,
		axis: false,
		rowHeight: 26,
		barHeight: 14,
		labelWidth: 100,
		rows: [
			{ label: 'Bulk jobs', color: '#0088e6', spans: JSON.parse(el.dataset.spansFast) },
			{ label: 'Agent turns', color: '#9467bd', spans: JSON.parse(el.dataset.spansSlow) }
		]
	});
});
</script>

{if !$is_cerb_cloud}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmLicense" class="cerb-ui-form" {if $license->key && empty($error)}style="display:none;"{/if}>
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="license">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Update Subscription</div>
	</div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Enter your company name <u>exactly</u> as it appears on your order</label>
			<label class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-building-office"></span>
				<input type="text" name="company" value="">
			</label>
		</div>
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Enter your email address <u>exactly</u> as it appears on your order</label>
			<label class="cerb-ui-form--control">
				<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-mail"></span>
				<input type="text" name="email" value="">
			</label>
		</div>
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Paste the license information you received with your order</label>
			<textarea rows="8" name="key"></textarea>
		</div>
	</div>
</div>

<div class="cerb-u-flex cerb-u-gap-2">
	<button type="button" id="btnSaveLicense" class="cerb-ui-button cerb-u-anim-group"><span class="cerb-icons cerb-icon-circle-ok cerb-u-anim-pulse-hover"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if $license->key}<button type="button" id="btnRemoveLicense" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-circle-minus"></span> Remove License</button>{/if}
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $frm = $('#frmLicense');

	Devblocks.formDisableSubmit($frm);

	const saveLicense = function() {
		Devblocks.saveAjaxForm($frm, {
			success: function() {
				document.location.href = '{devblocks_url}c=config&a=license{/devblocks_url}';
			}
		});
	};

	$frm.find('#btnSaveLicense').on('click', function(e) {
		e.stopPropagation();
		saveLicense();
	});

	$frm.find('#btnRemoveLicense').on('click', function(e) {
		e.stopPropagation();
		if(!(window.CerbUI && CerbUI.Confirm)) return;
		CerbUI.Confirm.open({
			title: 'Remove License',
			body: 'Are you sure you want to remove your license?',
			confirmText: 'Remove',
			cancelText: 'Cancel',
			onConfirm: function() {
				$frm.find('input:hidden[name=do_delete]').val('1');
				saveLicense();
			}
		});
	});

	$('#setupLicenseActive').find('[data-cerb-button-update-license]').on('click', function(e) {
		e.stopPropagation();
		$frm.fadeIn().find('input:text:first').focus();
	});
});
</script>
{/if}
