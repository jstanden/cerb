<div class="cerb-interaction-panel--container">
    <div class="cerb-interaction-panel--header">
        <div class="cerb-interaction-panel--close"></div>
        <div class="cerb-interaction-panel--title">{$interaction_label}</div>
    </div>
    <form class="cerb-interaction-panel--form" method="POST" onsubmit="return false;">
        <input type="hidden" name="continuation_token" value="{$continuation_token}">
        <div class="cerb-interaction-panel--form-elements"></div>
    </form>
</div>    
