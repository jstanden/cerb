{$cfg_id = uniqid()}
<div class="cerb-ui-form">
	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<label class="cerb-ui-toggle">
			<input type="checkbox" name="params[auth_register_disabled]" id="{$cfg_id}_register" value="1" {if $params['auth.register.disabled']}checked="checked"{/if}>
			<span class="cerb-ui-toggle--slider"></span>
		</label>
		<label for="{$cfg_id}_register">Disable new account registration</label>
	</div>

	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
		<label class="cerb-ui-toggle">
			<input type="checkbox" name="params[auth_recover_disabled]" id="{$cfg_id}_recover" value="1" {if $params['auth.recover.disabled']}checked="checked"{/if}>
			<span class="cerb-ui-toggle--slider"></span>
		</label>
		<label for="{$cfg_id}_recover">Disable account recovery</label>
	</div>
</div>
