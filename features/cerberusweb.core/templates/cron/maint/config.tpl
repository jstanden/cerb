<div class="cerb-ui-form--section">
    <div class="cerb-ui-form--section-head">{{'common.options'|devblocks_translate|capitalize}}</div>
    <div class="cerb-ui-form--section-body">
        <div class="cerb-ui-form--row">
            <div class="cerb-ui-form--field">
                <label class="cerb-ui-form--label">Purge deleted tickets from the database after</label>
                <div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
                    <input type="text" name="purge_waitdays" size="4" maxlength="3" value="{$purge_waitdays}"> days of inactivity
                </div>
            </div>
        </div>
    </div>
</div>