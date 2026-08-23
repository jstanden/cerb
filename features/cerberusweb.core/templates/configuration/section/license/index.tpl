<div class="cerb-ui-header">
	<div>
		<div class="cerb-ui-header--title">License</div>
		<div class="cerb-ui-header--subtitle">Configure an optional license to increase your seat count</div>
	</div>
</div>

<div class="cerb-ui-panel cerb-ui-panel--spaced" id="setupLicenseActive">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-key"></span> Active License</div>
		{if $license->key}
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button" data-cerb-button-update-license><span class="cerb-icons cerb-icon-gear"></span> Update License</button>
		</div>
		{/if}
	</div>

	{if !$license->key}
		<div>
			<span class="cerb-ui-pill"><span class="cerb-icons cerb-icon-users"></span> Community Edition</span>
			<ul style="margin-top:5px;">
				<li>Three free seats with full functionality.</li>
				<li><a href="https://cerb.ai/pricing" target="_blank" rel="noopener">Add more seats with a Cerb license</a></li>
			</ul>
		</div>
	{else}
		<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-gap-4">
			<div>
				<div class="cerb-ui-form--label">Serial #</div>
				<div>{$license->key}</div>
			</div>
			<div>
				<div class="cerb-ui-form--label">Licensed To</div>
				<div>{$license->company}</div>
			</div>
			<div>
				<div class="cerb-ui-form--label">Seats</div>
				<div>{if CerberusLicense::SEATS_UNLIMITED==$license->seats}100+{else}{$license->seats}{/if}</div>
			</div>
			<div>
				<div class="cerb-ui-form--label">Software Updates Expire</div>
				<div>{$license->upgrades|devblocks_date:'F d, Y':true}</div>
			</div>
		</div>
	{/if}
</div>

<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmLicense" class="cerb-ui-form" {if $license->key && empty($error)}style="display:none;"{/if}>
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="license">
<input type="hidden" name="action" value="saveJson">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight">
		<div class="cerb-ui-header--title-sm">Update License</div>
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
			<label class="cerb-ui-form--label">Enter your e-mail address <u>exactly</u> as it appears on your order</label>
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
		$('#setupLicenseActive').fadeOut();
		$frm.fadeIn().find('input:text:first').focus();
	});
});
</script>
