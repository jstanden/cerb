<div>
    <label>
        <input type="checkbox" name="params[auth_register_disabled]" value="1" {if $params['auth.register.disabled']}checked="checked"{/if}>
        Disable new account registration
    </label>
</div>

<div>
    <label>
        <input type="checkbox" name="params[auth_recover_disabled]" value="1" {if $params['auth.recover.disabled']}checked="checked"{/if}>
        Disable account recovery
    </label>
</div>