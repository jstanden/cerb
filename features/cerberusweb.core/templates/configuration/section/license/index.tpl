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
				<div><div class="cerb-ui-chip--label">Expires</div><div class="cerb-ui-chip--value">Never</div></div>
			</div>
		</div>
	{elseif !$license->key}
		<div>
			<div class="cerb-ui-chip">
				<div class="cerb-ui-chip--head"><span class="cerb-icons cerb-icon-users cerb-u-mr-1"></span> Community License</div>
				<div><div class="cerb-ui-chip--label">Workers / Seats</div><div class="cerb-ui-chip--value">Unlimited</div></div>
				<div><div class="cerb-ui-chip--label">Expires</div><div class="cerb-ui-chip--value">Never</div></div>
			</div>
		</div>
	{else}
		<div>
			<div class="cerb-ui-chip{if !$license_is_expired} cerb-ui-chip--green{else} cerb-ui-chip--orange{/if}">
				<div class="cerb-ui-chip--head"><span class="cerb-icons {if !$license_is_expired}cerb-icon-circle-ok{else}cerb-icon-clock{/if} cerb-u-mr-1"></span> {$license->company}</div>
				<div><div class="cerb-ui-chip--label">Workers / Seats</div><div class="cerb-ui-chip--value">Unlimited</div></div>
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
	</div>

	<div class="cerb-u-flex cerb-u-items-stretch cerb-u-gap-2">
		<div class="cerb-u-flex-2 cerb-u-flex cerb-u-flex-column cerb-u-gap-2 cerb-u-py-2">
			{* Aligns this column's rows with the value columns' headings. *}
			<div class="cerb-u-text-uppercase">&nbsp;</div>
			<div>Concurrency slots</div>
			<div>Slots that run agent turns</div>
			<div>Agent turns per slot</div>
			<div>Agent turns at once</div>
		</div>

		<div class="cerb-u-flex-1 cerb-u-flex cerb-u-flex-column cerb-u-gap-2 cerb-u-py-2 cerb-u-px-3 cerb-u-text-center">
			<div class="cerb-u-text-uppercase cerb-u-text-muted">Community</div>
			<div class="cerb-u-text-muted">{$entitlements.community.slots}</div>
			<div class="cerb-u-text-muted">{$entitlements.community.agent_slots}</div>
			<div class="cerb-u-text-muted">{$entitlements.community.turns}</div>
			<div class="cerb-u-text-muted">{$entitlements.community.turns_at_once}</div>
		</div>

		{if $is_licensed}
		<div class="cerb-u-flex-1 cerb-u-flex cerb-u-flex-column cerb-u-gap-2 cerb-u-py-2 cerb-u-px-3 cerb-u-text-center cerb-u-bgg-2 cerb-u-rounded-3">
			<div class="cerb-u-text-uppercase cerb-u-text-muted">{if $is_cerb_cloud}Your plan{else}Your license{/if}</div>
			<div class="cerb-u-bold">{$entitlements.current.slots}</div>
			<div class="cerb-u-bold">{$entitlements.current.agent_slots}</div>
			<div class="cerb-u-bold">{$entitlements.current.turns}</div>
			<div class="cerb-u-bold">{$entitlements.current.turns_at_once}</div>
		</div>
		{/if}
	</div>

	{if $max_concurrency_slots != $max_concurrency_slots_licensed}
	<div class="cerb-u-mt-3 cerb-u-text-muted">
		<span class="cerb-icons cerb-icon-circle-info cerb-u-mr-1"></span> This subscription allows {$max_concurrency_slots_licensed} slots. <code>APP_QUEUE_CONCURRENCY_SLOTS</code> in <code>framework.config.php</code> caps them at {$max_concurrency_slots} on this host.
	</div>
	{/if}

	<div class="cerb-u-mt-3 cerb-u-text-muted">
		Slots are split into lanes so that agent turns can't crowd out interactive work like bulk updates,
		exports and imports.
		{if $lane_width}Of {$entitlements.current.slots} slots, {$lane_width} {if $lane_width == 1}is{else}are{/if} reserved for interactive work,
		{$lane_width} {if $lane_width == 1}is{else}are{/if} reserved for agent turns, and {$lane_slots_shared} {if $lane_slots_shared == 1}is{else}are{/if} shared by both.{else}Below three slots there are no lanes and every slot is shared.{/if}
		That leaves {$entitlements.current.agent_slots} of {$entitlements.current.slots} slots able to run agent turns.
	</div>

	<div class="cerb-u-mt-3 cerb-u-text-muted">
		Turns at once is a ceiling rather than a reservation: scheduled jobs draw from the same lane, and a
		provider's own rate limits apply on top of it.
	</div>

	<div class="cerb-u-mt-3">
		<a href="https://cerb.ai/pricing" target="_blank" rel="noopener">See plans</a> to raise concurrency. Workers and seats are unlimited on every plan.
	</div>
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
