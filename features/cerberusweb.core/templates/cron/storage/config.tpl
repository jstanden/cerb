<div class="cerb-ui-form--section">
    <div class="cerb-ui-form--section-head">{{'common.options'|devblocks_translate|capitalize}}</div>
    <div class="cerb-ui-form--section-body">
        <div class="cerb-ui-form--row">
            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">Archival candidates to enqueue per storage schema each run</label>
                <div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
                    <input type="text" name="archive_batch_size" size="6" maxlength="6" value="{$archive_batch_size}">
                </div>
            </div>
        </div>
    </div>
</div>