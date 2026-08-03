<form data-cerb-fork-form style="min-width:380px;">
    <div class="cerb-u-mb-2 cerb-u-text-muted">
        Fork this transcript into a new session on the chosen provider. Messages are re-encoded into
        the target's native format; the original transcript is left unchanged.
    </div>

    <div class="cerb-field-cell-label">Provider</div>
    <div class="cerb-fork-provider-grid cerb-u-mb-2">
        {foreach from=$chat_providers item=provider}
        <label class="cerb-fork-provider">
            <input type="radio" name="target_provider" value="{$provider.id}"{if $provider.id == $llm_session->provider} checked{/if}>
            <span class="cerb-icons cerb-icon-{$provider.icon}"></span>
            <span>{$provider.id}{if $provider.id == $llm_session->provider} <em class="cerb-u-text-muted">(current)</em>{/if}</span>
        </label>
        {/foreach}
    </div>

    <div class="cerb-field-cell-label">Model <span class="cerb-u-text-muted">(optional)</span></div>
    <input type="text" name="model" value="{$llm_session->getModel()}" placeholder="e.g. claude-sonnet-5, gpt-4o-mini" class="cerb-u-mb-2" style="width:100%;box-sizing:border-box;">

    <div class="cerb-ui-toolbar-strip cerb-u-mt-2">
        <button type="submit" class="cerb-ui-button cerb-ui-button--primary"><span class="cerb-icons cerb-icon-branch"></span> Fork</button>
        <button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-cerb-button="fork-cancel">{'common.cancel'|devblocks_translate|capitalize}</button>
    </div>
</form>

<style nonce="{DevblocksPlatform::getRequestNonce()}">
[data-cerb-fork-form] .cerb-fork-provider-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.35em 1em;
}
[data-cerb-fork-form] .cerb-fork-provider {
    display: flex;
    align-items: center;
    gap: 0.4em;
    cursor: pointer;
}
</style>
