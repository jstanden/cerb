{$element_uid = uniqid('el_')}
{if $is_automation_simulated|default:false}
	{* Design-time preview: don't auto-submit — show an inert marker so the builder reflects the element. *}
	<div id="{$element_uid}" class="cerb-form-builder-prompt cerb-u-text-muted" style="display:flex;align-items:center;gap:0.4em;">
		<span class="cerb-icons cerb-icon-send"></span> Automatic submit
	</div>
{else}
	<div id="{$element_uid}"></div>

	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		const $element = $('#{$element_uid}');
		const $form = $element.closest('.cerb-form-builder');

		$element.hide();

		$form.triggerHandler($.Event('cerb-form-builder-submit'));
	});
	</script>
{/if}