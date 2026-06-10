{* Shared sparkline-column header: the column label + a window switcher in place of a sort link. This is
   the default for every worklist sparkline column. One preset per unit, symmetrical: 2h (24x5min, for
   near-real-time gauge monitoring), 1d (24x1h), 1mo (30x1d). A view that needs a different set of windows
   can inline its own <th> markup instead of including this. Params: header (column key), view_fields. *}
<th class="no-sort" style="width:170px;">
	<span style="margin-right:6px;">{$view_fields.$header->db_label|capitalize}</span>
	<span class="cerb-ui-switcher cerb-ui-switcher--xs" data-cerb-spark-switcher>
		<button type="button" data-value="2h">2h</button>
		<button type="button" data-value="1d" class="cerb-ui-switcher--active">1d</button>
		<button type="button" data-value="30d">30d</button>
	</span>
</th>
