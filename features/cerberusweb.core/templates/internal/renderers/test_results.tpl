{*
	Shared tester output. A callout panel (alert on failure / success otherwise) holding the rendered
	template output. The dismiss (×) is absolutely positioned in the top-right corner so it never wraps
	below tall/wide output (the cerb-ui-header flex row wraps; this sidesteps it). The failure case keeps
	an "Error!" title; success is title-less (just the green callout + output).
*}
{if !$success}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert" style="position:relative;">
		<span data-cerb-link="remove_div" style="position:absolute;top:0.7em;right:0.7em;cursor:pointer;z-index:1;"><span class="cerb-icons cerb-icon-circle-remove"></span></span>
		<div class="cerb-ui-callout" style="padding-right:1.6em;">
			<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
			<div style="min-width:0;">
				<div class="cerb-ui-header--title-sm">Error!</div>
				<div class="cerb-ui-header--subtitle">
					<pre class="emailbody" dir="auto" style="overflow-x:auto;max-width:100%;">{$output|default:''|escape nofilter}</pre>
				</div>
			</div>
		</div>
	</div>
{else}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--success" style="position:relative;">
		<span data-cerb-link="remove_div" style="position:absolute;top:0.7em;right:0.7em;cursor:pointer;z-index:1;"><span class="cerb-icons cerb-icon-circle-remove"></span></span>
		<div class="cerb-ui-callout" style="padding-right:1.6em;">
			<span class="cerb-icons cerb-icon-circle-ok cerb-ui-callout--icon"></span>
			<div class="cerb-ui-header--subtitle" style="min-width:0;">
				<pre class="emailbody" dir="auto" style="overflow-x:auto;max-width:100%;">{$output|default:''|escape nofilter}</pre>
			</div>
		</div>
	</div>
{/if}
{$script_uid = uniqid('script')}
<script nonce="{DevblocksPlatform::getRequestNonce()}" id="{$script_uid}" type="text/javascript">
$(function() {
	let $script = $('#{$script_uid}');

	$script.prev('div').find('[data-cerb-link=remove_div]').on('click', function(e) {
		$(this).closest('div.cerb-ui-panel').remove();
	});
});
</script>
