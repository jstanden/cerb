{* The simulator's `await:form:` rendered as the ACTUAL interaction popup: the outer genericAjaxPopup dialog IS
   the form dialog (titled via data-cerb-dialog-title with the form's title). On Continue the popup hands its
   filled `<form>` up to the opener (peek_edit), which merges the prompts against the LIVE Run Input state and
   drops the result back into the Input editor. (The opener owns the state — no embedding — so the exact YAML
   that rendered this form is the one merged.) Start over just dismisses. *}
{* Load any preview stylesheets the trigger supplies (e.g. interaction.website's portal CSS) so components render
   with their real front-end styling. Removed with the popup fragment on close (no accumulation). *}
{foreach from=$preview_stylesheets|default:[] item=sheet}
	<link rel="stylesheet" data-cerb-form-preview-css href="{devblocks_url}c=resource&p={$sheet.p}&f={$sheet.f}{/devblocks_url}">
{/foreach}

{$is_portal = (($preview_chrome|default:'dialog') == 'portal')}
{$uniqid = uniqid('formFill')}
<div id="{$uniqid}" data-cerb-dialog-title="{$form_title}">
{if $has_form}
	{if $is_portal}
		{* Render as the customer-facing portal form (the outer popup supplies the window frame + title, so no
		   `--style-embed` sizing here — just the portal skin: font/bg + form-element styling). *}
		<div class="cerb-interaction-popup">
			<div class="cerb-interaction-popup--container">
				<form class="cerb-form-builder cerb-interaction-popup--form">
					<div class="cerb-form-data cerb-interaction-popup--form-elements">{$elements_html nofilter}</div>
				</form>
			</div>
		</div>
	{else}
		<form class="cerb-form-builder">
			<div class="cerb-form-data">{$elements_html nofilter}</div>
		</form>
	{/if}

	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		var $div = $('#{$uniqid}');
		var $popup = genericAjaxPopupFind($div);
		var $form = $div.find('form.cerb-form-builder');

		// Enter in a text field implicitly submits the <form>, which would reload/POST the page. Intercept it and
		// run the form's Continue instead (matching the runtime, where Enter submits the interaction form).
		$form.on('submit', function(e) {
			e.preventDefault();
			var $continue = $form.find('.cerb-form-builder-continue').first();
			if($continue.length)
				$continue.trigger('click');
			else
				$form.triggerHandler('cerb-form-builder-submit');
		});

		// Continue: hand the filled form element up to the opener (which posts it with the live Input state).
		$form.on('cerb-form-builder-submit', function() {
			$popup.trigger($.Event('cerb-automation-form-fill-submit', { form_el: $form[0] }));
		});

		// Start over: dismiss without writing anything back.
		$form.on('cerb-form-builder-reset', function() {
			$popup.trigger($.Event('cerb-automation-form-fill-reset'));
		});
	});
	</script>
{else}
	<div class="cerb-u-p-4 cerb-u-text-muted">The current input isn't at an <code>await:form:</code> step.</div>
{/if}
</div>
