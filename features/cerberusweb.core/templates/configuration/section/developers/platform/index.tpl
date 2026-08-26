<div class="cerb-ui-header">
    <div>
        <div class="cerb-ui-header--title">{{'common.platform'|devblocks_translate|capitalize}}</div>
        <div class="cerb-ui-header--subtitle">Reload the assets bundled with Cerb after editing them on disk, and flush the server-side cache.</div>
    </div>
</div>

<div id="cerbConfigPlatform">
    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
            <div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-folder"></span> Agent filesystems</div>
            <div class="cerb-ui-header--right">
                <button type="button" class="cerb-ui-button" data-cerb-button="agent_filesystems"><span class="cerb-icons cerb-icon-refresh"></span> {'common.reload'|devblocks_translate|capitalize}</button>
            </div>
        </div>
        <div class="cerb-u-text-muted">Re-sync every bundled volume against its manifest, including one whose manifest hash already matches.</div>
    </div>

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
            <div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-bot-route"></span> {'common.automations'|devblocks_translate|capitalize}</div>
            <div class="cerb-ui-header--right">
                <button type="button" class="cerb-ui-button" data-cerb-button="automations"><span class="cerb-icons cerb-icon-refresh"></span> {'common.reload'|devblocks_translate|capitalize}</button>
            </div>
        </div>
        <div class="cerb-u-text-muted">Re-import the automations in <code>features/cerberusweb.core/assets/automations/</code>.</div>
    </div>

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
            <div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-database"></span> {'common.cache'|devblocks_translate|capitalize}</div>
            <div class="cerb-ui-header--right">
                <button type="button" class="cerb-ui-button" data-cerb-button="cache"><span class="cerb-icons cerb-icon-erase"></span> {'common.clear'|devblocks_translate|capitalize}</button>
            </div>
        </div>
        <div class="cerb-u-text-muted">Flush the server-side cache along with the compiled templates.</div>
    </div>

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
            <div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-cube"></span> {'common.packages'|devblocks_translate|capitalize}</div>
            <div class="cerb-ui-header--right">
                <button type="button" class="cerb-ui-button" data-cerb-button="packages"><span class="cerb-icons cerb-icon-refresh"></span> {'common.reload'|devblocks_translate|capitalize}</button>
            </div>
        </div>
        <div class="cerb-u-text-muted">Re-import the package library in <code>features/cerberusweb.core/packages/library/</code>.</div>
    </div>

    <div class="cerb-ui-panel cerb-ui-panel--spaced">
        <div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
            <div class="cerb-ui-header--title-sm"><span class="cerb-icons cerb-icon-archive"></span> {'common.resources'|devblocks_translate|capitalize}</div>
            <div class="cerb-ui-header--right">
                <button type="button" class="cerb-ui-button" data-cerb-button="resources"><span class="cerb-icons cerb-icon-refresh"></span> {'common.reload'|devblocks_translate|capitalize}</button>
            </div>
        </div>
        <div class="cerb-u-text-muted">Re-import the resources in <code>features/cerberusweb.core/assets/resources/</code>.</div>
    </div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
$(function() {
    const $assets = $('#cerbConfigPlatform');

    const actions = {
        agent_filesystems: { action: 'reloadAgentFilesystems', message: 'Bundled agent filesystems have been reloaded.' },
        automations: { action: 'reloadAutomations', message: 'Bundled automations have been reloaded.' },
        cache: { action: 'clearCache', message: 'Flushed the server-side cache.' },
        packages: { action: 'reloadPackages', message: 'Bundled packages have been reloaded.' },
        resources: { action: 'reloadResources', message: 'Bundled resources have been reloaded.' }
    };

    $assets.find('button[data-cerb-button]').on('click', function() {
        // `this`, not e.target: a click can land on the button's icon <span>
        const $button = $(this);
        const spec = actions[$button.attr('data-cerb-button')];

        if(!spec || $button.prop('disabled'))
            return;

        const formData = new FormData();
        formData.set('c', 'config');
        formData.set('a', 'invoke');
        formData.set('module', 'platform');
        formData.set('action', spec.action);

        // These walk the filesystem and can run for a while; spin the icon until the server answers
        const $icon = $button.find('span.cerb-icons');
        $button.prop('disabled', true);
        $icon.addClass('cerb-u-anim-spin');

        const done = function() {
            $button.prop('disabled', false);
            $icon.removeClass('cerb-u-anim-spin');
        };

        genericAjaxPost(formData, '', '', function() {
            done();
            Devblocks.createAlert(spec.message, 'note', 5000);
        }, { error: done });
    });
});
{/literal}
</script>
