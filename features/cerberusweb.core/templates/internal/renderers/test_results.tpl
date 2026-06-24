{if !$success}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--alert">
		<div class="cerb-ui-header cerb-ui-header--center">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
				<div>
					<div class="cerb-ui-header--title-sm">Error!</div>
					<div class="cerb-ui-header--subtitle">
						<pre class="emailbody" dir="auto">{$output|default:''|escape nofilter}</pre>
					</div>
				</div>
			</div>
			<div class="cerb-ui-header--right">
				<span data-cerb-link="remove_div" style="cursor:pointer;"><span class="cerb-icons cerb-icon-circle-remove"></span></span>
			</div>
		</div>
	</div>
{else}
	<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--success">
		<div class="cerb-ui-header cerb-ui-header--center">
			<div class="cerb-ui-callout">
				<span class="cerb-icons cerb-icon-circle-ok cerb-ui-callout--icon"></span>
				<div>
					<div class="cerb-ui-header--title-sm">Success!</div>
					<div class="cerb-ui-header--subtitle">
						<pre class="emailbody" dir="auto">{$output|default:''|escape nofilter}</pre>
					</div>
				</div>
			</div>
			<div class="cerb-ui-header--right">
				<span data-cerb-link="remove_div" style="cursor:pointer;"><span class="cerb-icons cerb-icon-circle-remove"></span></span>
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