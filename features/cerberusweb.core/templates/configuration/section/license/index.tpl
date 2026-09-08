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
				<div><div class="cerb-ui-chip--label">Concurrency slots</div><div class="cerb-ui-chip--value">{if $is_slots_unlimited}Unlimited{else}{$max_concurrency_slots}{/if}</div></div>
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
   table and no prices, so there is nothing that can go stale against cerb.ai.

   Shown to Community and to Cloud, where the pool is a real ceiling somebody might want raised. A
   self-hosted subscription has no ceiling left to draw, and its split is edited on Setup > Configure >
   Queues rather than read here -- so repeating it would only invite the reading that the subscription
   chose those numbers. *}
{if !$is_slots_unlimited}
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


	{if $max_concurrency_slots > 0}
		{include file="devblocks:cerberusweb.core::internal/queues/slot_lanes.tpl" id="setupLicenseLanes"}
	{/if}

	<div class="cerb-u-mt-3 cerb-u-text-muted">
		<a href="https://cerb.ai/pricing" target="_blank" rel="noopener" class="cerb-u-text-muted">See plans</a> to raise concurrency. Workers and seats are unlimited on every plan.
	</div>
</div>
{/if}

{* Self-reported. Nothing here is enforced: no login is refused and no session is ended over seats. *}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div>
			<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-users"></span> Seat usage</div>
			<div class="cerb-ui-header--subtitle">Different workers who signed in each month. Seats are never enforced; this is here so you can report an honest number.</div>
		</div>
		{if $seats_average}
		<div class="cerb-ui-header--summary"><b>{$seats_average}</b> seat{if $seats_average != 1}s{/if} average over {$seat_months_count} month{if $seat_months_count != 1}s{/if}</div>
		{/if}
	</div>

	{if !$seat_rows_count}
	<div class="cerb-u-mt-3 cerb-u-text-muted">
		<span class="cerb-icons cerb-icon-circle-info cerb-u-mr-1"></span> No worker activity has been recorded yet. This fills in as the scheduler runs.
	</div>
	{else}
	<div id="setupLicenseSeats" class="cerb-u-mt-3"></div>

	<div class="cerb-u-mt-3 cerb-u-text-muted">
		<span class="cerb-icons cerb-icon-circle-info cerb-u-mr-1"></span> Counted from signing in and from sending mail, so an API integration acting as a worker is counted even though it never signs in. Someone who worked any part of a month counts once for that month, whether or not they overlapped with anyone else.
	</div>
	{/if}
</div>

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

{if $seat_rows_count}
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
const cerbSeatRows = {$seat_rows_json nofilter};
const cerbSeatAxisMax = {$seats_axis_max};
{literal}
$(function() {
	const el = document.getElementById('setupLicenseSeats');

	if(!el || !(window.CerbUI && CerbUI.Gantt))
		return;

	// One block per seat against a fixed track, so the unused remainder stays visible. Every row is the
	// same measure, so they share one color -- the palette's own sequence would read as 12 categories.
	//
	// No axis: the number a reader acts on is the average in the header, and numbering seats invites
	// counting blocks to find it. The blocks carry the shape; the header carries the figure.
	new CerbUI.Gantt(el, {
		xScale: 'linear',
		domain: [1, cerbSeatAxisMax],
		step: 1,
		segment: true,
		axis: false,
		rowHeight: 24,
		barHeight: 12,
		labelWidth: 96,
		rows: cerbSeatRows.map((r) => ({ label: r.label, color: '#0088e6', spans: r.spans })),
	});
});
{/literal}
</script>
{/if}
