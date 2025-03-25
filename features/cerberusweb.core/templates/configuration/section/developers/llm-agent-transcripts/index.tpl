{$div_uid = uniqid('div')}

<h2>LLM Agent Transcripts</h2>

<div id="{$div_uid}" style="display:flex;">
    <div style="flex:1 1 300px;margin-right:2px;" data-cerb-sidebar-limit="{$limit}">
        <div style="max-height:90vh;overflow-y:auto;">
            <div class="cerb-code-editor-toolbar">
                <button type="button" data-cerb-button="refresh"><span class="glyphicons glyphicons-refresh"></span> {{'common.refresh'|devblocks_translate|capitalize}}</button>
                <button type="button" data-cerb-button="unread"><span class="glyphicons glyphicons-envelope"></span> {{'common.unread'|devblocks_translate|capitalize}}</button>
            </div>

            <table class="worklistBody" style="width:100%;" cellpadding="0" cellspacing="0">
                {include file="devblocks:cerberusweb.core::configuration/section/developers/llm-agent-transcripts/transcripts.tpl" transcripts=$transcripts}
            </table>
        </div>
    </div>
    <div style="flex:2 2 100%;padding-left:1em;"></div>
</div>

<style nonce="{DevblocksPlatform::getRequestNonce()}">
#{$div_uid} > div:nth-child(1) .worklistBody > tbody:nth-child(even) > tr {
    background-color: var(--cerb-color-background-contrast-240);
}

#{$div_uid} > div:nth-child(1) .worklistBody td {
    cursor:pointer;
    padding:0.5em;
    position:relative;
    font-size:1em;
}

#{$div_uid} > div:nth-child(2) details {
    cursor:pointer;
}

#{$div_uid} > div:nth-child(2) details > pre {
    margin-left:1em;
    max-width:100%;
    white-space:break-spaces;
    overflow-x:auto;
}

#{$div_uid} > div:nth-child(2) pre > code {
    max-width:100%;
    white-space:break-spaces;
    overflow-x:auto;
}

#{$div_uid} > div:nth-child(2) .cerb-llm-transcript-fields {
    display:flex;
    flex-flow: row wrap;
}

#{$div_uid} > div:nth-child(2) .cerb-llm-transcript-fields > div {
    margin: 0 1em 0.5em 0;
}
</style>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
$(function() {
    let $container = $('#{$div_uid}');
    let $sidebar = $container.find('> div').first();
    let $sidebar_transcripts = $sidebar.find('.worklistBody');
    let $viewer = $container.find('> div').last();
    let $current_transcript = null;
    let current_transcript_id = null;

    $sidebar.on('click', '[data-cerb-transcript-id]', function(e) {
        e.stopPropagation();

        if($current_transcript)
            $current_transcript.removeClass('selected');

        $current_transcript = $(this);
        $current_transcript.addClass('selected');
        current_transcript_id = $current_transcript.attr('data-cerb-transcript-id');

        $viewer.html(Devblocks.getSpinner());

        let formData = new FormData();
        formData.set('c', 'config');
        formData.set('a', 'invoke');
        formData.set('module', 'llm_agent_transcripts');
        formData.set('action', 'getTranscript');
        formData.set('transcript_id', current_transcript_id);

        genericAjaxPost(formData, $viewer, null, function(json) {
            Devblocks.clearAlerts();

            if(json && typeof json == 'object') {
                if (json.error) {
                    Devblocks.createAlertError(json.error);

                } else {
                    $viewer.html(json.html);
                    $viewer.find('[data-cerb-peek]').cerbPeekTrigger();
                }
            }
        });
    });

    $sidebar.on('click', 'button[data-cerb-button]', function(e) {
        e.stopPropagation();
        let $button = $(this);
        let button_action = $button.attr('data-cerb-button');

        if ('refresh' === button_action) {
            let $spinner = Devblocks.getSpinner(true);
            $button.attr('disabled', 'disabled');

            $sidebar_transcripts.fadeTo('fast', 0.2);
            $spinner.insertBefore($sidebar_transcripts);

            let is_unread = $sidebar.attr('data-cerb-sidebar-unread') || false;
            let limit = $sidebar.attr('data-cerb-sidebar-limit');

            let formData = new FormData();
            formData.set('c', 'config');
            formData.set('a', 'invoke');
            formData.set('module', 'llm_agent_transcripts');
            formData.set('action', 'loadTranscripts');
            formData.set('limit', limit || '100');
            formData.set('is_unread', is_unread ? '1' : '0');

            if(e.hasOwnProperty('before_id')) {
                formData.set('before_id', e['before_id']);
            }

            genericAjaxPost(formData, $viewer, null, function(json) {
                Devblocks.clearAlerts();
                $spinner.remove();
                $button.attr('disabled', null);

                if(json && typeof json == 'object') {
                    if (json.error) {
                        Devblocks.createAlertError(json.error);

                    } else {
                        // Partial
                        if(e.hasOwnProperty('before_id')) {
                            let $new_transcripts = $(json.html);
                            let $more = $sidebar.find('[data-cerb-button=more]');
                            $new_transcripts.insertBefore($more.closest('tbody'));
                            $more.closest('tbody').remove();
                            $sidebar_transcripts.fadeTo('fast', 1.0);
                        } else {
                            $sidebar_transcripts.html(json.html).fadeTo('fast', 1.0);
                        }
                    }
                }
            });

        } else if ('more' === button_action) {
            let $oldest_transcript = $sidebar_transcripts.find('[data-cerb-transcript-id]').last();

            $sidebar.find('[data-cerb-button=refresh]').trigger(
                $.Event('click', { 'before_id': $oldest_transcript.attr('data-cerb-transcript-id') })
            );

        } else if ('unread' === button_action) {
            let is_unread = $button.is('.cerb-code-editor-toolbar-button--enabled');

            $sidebar.attr('data-cerb-sidebar-unread', !is_unread ? 'true' : null);

            $button.toggleClass('cerb-code-editor-toolbar-button--enabled');

            $sidebar.find('[data-cerb-button=refresh]').trigger(
                $.Event('click', { 'is_unread': !is_unread })
            );
        }
    });

    $viewer.on('click', 'button[data-cerb-button]', function(e) {
        e.stopPropagation();
        let $button = $(this);
        let button_action = $button.attr('data-cerb-button');

        if('delete' === button_action) {
            confirmPopup(
                'Delete Transcript',
                'Are you sure you want to permanently delete this transcript?',
                function() {
                    let formData = new FormData();
                    formData.set('c', 'config');
                    formData.set('a', 'invoke');
                    formData.set('module', 'llm_agent_transcripts');
                    formData.set('action', 'deleteTranscript');
                    formData.set('transcript_id', current_transcript_id);

                    genericAjaxPost(formData, null, null, function(json) {
                        Devblocks.clearAlerts();

                        if(json && typeof json == 'object') {
                            if (json.error) {
                                Devblocks.createAlertError(json.error);

                            } else {
                                $viewer.empty();

                                let $tbody = $current_transcript.closest('tbody');

                                // Select the next transcript if we can
                                if($tbody.next('tbody')) {
                                    $current_transcript = null;
                                    current_transcript_id = null;
                                    $tbody.next('tbody').find('[data-cerb-transcript-id]').click();
                                } else {
                                    $current_transcript = null;
                                    current_transcript_id = null;
                                }

                                $tbody.remove();
                            }
                        }
                    });
                }
            );

        } else if ('mark-read' === button_action) {
            let formData = new FormData();
            formData.set('c', 'config');
            formData.set('a', 'invoke');
            formData.set('module', 'llm_agent_transcripts');
            formData.set('action', 'markTranscriptRead');
            formData.set('transcript_id', current_transcript_id);

            genericAjaxPost(formData, null, null, function(json) {
                Devblocks.clearAlerts();

                if(json && typeof json == 'object') {
                    if (json.error) {
                        Devblocks.createAlertError(json.error);

                    } else {
                        $viewer.empty();
                        $button.remove();

                        let $tbody = $current_transcript.closest('tbody');

                        if(0 === $tbody.find('.glyphicons-circle-ok').length) {
                            let $span = $('<span class="glyphicons glyphicons-circle-ok" />');
                            $span.prependTo($tbody.find('td').first());
                        }

                        $tbody.find('> tr').removeClass('selected');

                        // Select the next transcript if we can
                        if($tbody.next('tbody')) {
                            $current_transcript = null;
                            current_transcript_id = null;
                            $tbody.next('tbody').find('[data-cerb-transcript-id]').click();
                        } else {
                            $current_transcript = null;
                            current_transcript_id = null;
                        }
                    }
                }
            });
        }
    });
});
</script>