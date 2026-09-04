<div class="cerb-uiref-component" id="node-editor">
	<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-branch"></span>NodeEditor</div>

	<div class="cerb-ui-header"><div class="cerb-ui-header--label">A visual node-graph editor (<code>CerbUI.NodeEditor</code>) for building automations point-and-click; the graph serializes to JSON now (KATA later) and the backend transpiles + runs it. Nodes render from a schema: <b>inline form fields</b>, <b>property sockets</b>, <b>flow branch outlets</b>, and functional <b>+ Add output/outcome</b> buttons. <b>Start</b> is a singleton (its palette tile hatches out once placed); deleting a configured node asks to confirm. <b>Comment</b> nodes float &amp; resize. The palette's <b>Variables</b> tab is the blackboard: host-seeded variables are locked (immutable), and <b>+ Add variable</b> opens a cascading type picker (<code>Dictionary ▸ Credentials ▸ API Key</code>). Double-click the purple <b>Expression</b> node to open the <b>Expression Builder</b> &mdash; the same editor in a dialog (scripting palette + an <code>Output</code> singleton) that <b>shares the blackboard</b>.</div></div>

	{* The data-type system. Pure logic (no DOM): named types with single inheritance + a compatibility gate. *}
	<div class="cerb-ui-header"><div class="cerb-ui-header--label"><code>CerbUI.nodeTypes</code> &mdash; inheritance-based data types drive which outlet may connect to which inlet (an inlet requiring <code>credentials</code> accepts an <code>api_key</code>, but not a bare <code>dictionary</code>). Now wired into edge validation: typed handles show green/red while dragging.</div></div>
	<div class="cerb-uiref-example">
		<div class="cerb-uiref-demo">
			<div class="cerb-ui-node-editor-types" id="uiref-nodetypes-out"></div>
			<div class="cerb-uiref-result">Compatibility gate: <b id="uiref-nodetypes-summary">&mdash;</b></div>
		</div>

		<div class="cerb-uiref-code">
			<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
			<pre data-cerb-uiref-source>{literal}// Build an inheritance chain, then ask the connection gate about it.
CerbUI.nodeTypes.register('credentials', 'dictionary', { color:'#ed8936', label:'Credentials' });
CerbUI.nodeTypes.register('api_key',      'credentials');
CerbUI.nodeTypes.register('record',       'dictionary');
CerbUI.nodeTypes.register('ticket',       'record');

CerbUI.nodeTypes.isCompatible('credentials', 'api_key');   // true  — api_key IS-A credentials
CerbUI.nodeTypes.isCompatible('api_key', 'credentials');   // false — a bare credentials isn't an api_key
CerbUI.nodeTypes.isCompatible('dictionary', 'ticket');     // true  — ticket → record → dictionary
CerbUI.nodeTypes.isCompatible('list<record>', 'list<ticket>'); // true — covariant generic
CerbUI.nodeTypes.getInheritanceChain('ticket'); // ['ticket','record','dictionary','any']{/literal}</pre>
		</div>
	</div>

	{* The live editor — palette + canvas + edges. Drag tiles in, drag outlet→inlet to chain. *}
	<div class="cerb-ui-header"><div class="cerb-ui-header--label"><code>CerbUI.NodeEditor</code> &mdash; drag a tile from the palette onto the canvas; drag from a node's <b>bottom-right outlet</b> to another node's <b>top-left inlet</b> to chain them (drop on empty canvas to pick + connect a new node). Pan by dragging the background, zoom with the wheel, <b>Fit</b> to frame all. Double-click an edge or select + press Delete to remove. <b>Export</b>/<b>Import</b> round-trips the graph JSON.</div></div>
	<div class="cerb-uiref-example">
		<div class="cerb-uiref-demo">
			<div id="uiref-nodeeditor" style="height:540px;"></div>
			<div class="cerb-uiref-result">Graph: <b id="uiref-nodeeditor-out">&mdash;</b></div>
		</div>

		<div class="cerb-uiref-code">
			<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
			<pre data-cerb-uiref-source>{literal}// Node "types" are plain serializable schema objects (mirrors js-flow's registerBlockType).
const editor = new CerbUI.NodeEditor('#my-editor', {
	mode: 'flow',                 // 'flow' (start node) | 'expression' (output node)
	nodeTypes: [
		{ id:'start',        label:'Start',        icon:'play',   headerColor:'#48bb78', category:'trigger', start:true },
		{ id:'http_request', label:'HTTP Request', icon:'cloud',  headerColor:'#dd6b20', category:'action',
			fields: [                                                            // inline form rows (left param inlet each)
				{ name:'url',    label:'url',    control:'text' },
				{ name:'method', label:'method', control:'select', options:['GET','POST'] },
				{ name:'timeout', label:'timeout', control:'number', dataType:'int' },
				{ name:'followRedirects', label:'followRedirects', control:'toggle', value:true },
			],
			inlets:   [ { name:'credentials', dataType:'credentials' } ],        // property socket: [pill] → CREDENTIALS
			outputs:  [ { name:'response', dataType:'http_response' } ],         // property socket: RESPONSE → [pill]
			branches: [ { name:'success' }, { name:'error' } ] },               // flow branch outlets (right edge)
		{ id:'error',        label:'Error',        icon:'alert',  headerColor:'#e53e3e', category:'terminal', terminal:true,
			canAddOutputs:true },                                               // renders a dashed "+ Add output"
		// …more, or register later via editor.registerNodeType(schema)
	],
});
editor.loadJSON(graph);   // { nodes:[{id,type,position,data}], edges:[{source,sourceHandle,target,targetHandle}] }
editor.connect(a, '_next', b, '_in');                 // main flow outlet → main flow inlet
editor.connect(http, 'branch:error', err, '_in');     // a branch outlet → another node's inlet
editor.connect(cred, 'out:value', http, 'in:credentials');   // typed data outlet → typed inlet{/literal}</pre>
		</div>
	</div>

	{* The read-only viewer — CerbUI.NodeGraph (chrome-less: canvas + API; the host builds any toolbar). *}
	<div class="cerb-ui-header"><div class="cerb-ui-header--label"><code>CerbUI.NodeGraph</code> &mdash; the <b>read-only</b> sibling of NodeEditor: same node schema, but you can't edit it. Drag a node to declutter, <b>double-click</b> to open it (<code>onNodeOpen</code>), and a node with <code>container:true</code> nests its <b>children</b> inside its own panel. It's <b>chrome-less</b> &mdash; just a canvas + API, so the host builds its own toolbar via <code>fit()</code> / <code>fitLane(i)</code> / <code>focusLane(i)</code> / <code>getLaneCount()</code> and <code>g.canvas.zoomIn()/zoomOut()</code>. Disconnected components are <b>lanes</b> (the green flag cycles them, focusing each at 100%); an optional <b>minimap</b> sits bottom-right (collapsed by default &mdash; click the map button to expand, then drag inside it to pan). It can also <b>enhance authored <code>data-*</code> markup</b> (<code>data-node-id</code>/<code>data-node-type</code>/&hellip;, nested elements = contained children, <code>data-edge</code> elements) in place of <code>loadJSON()</code> &mdash; same progressive-enhancement pattern as RecordChooser/Menu.</div></div>
	<div class="cerb-uiref-example">
		<div class="cerb-uiref-demo">
			<div id="uiref-nodegraph" style="height:440px;border:1px solid var(--cerb-color-background-contrast-225);border-radius:8px;overflow:hidden;">
				<div data-node-id="f1" data-node-type="start" data-label="On lane A" data-tier="0"></div>
				<div data-node-id="s1" data-node-type="step"  data-label="step A"    data-tier="0"></div>
				<div data-node-id="g1" data-node-type="group" data-label="Group A"   data-tier="1">
					<div data-node-id="a1" data-node-type="item" data-label="item 1"></div>
					<div data-node-id="a2" data-node-type="item" data-label="item 2"></div>
					<div data-node-id="a3" data-node-type="item" data-label="item 3"></div>
				</div>
				<div data-node-id="f2" data-node-type="start" data-label="On lane B" data-tier="0"></div>
				<div data-node-id="s2" data-node-type="step"  data-label="step B"    data-tier="0"></div>
				<div data-edge data-source="f1" data-target="s1"></div>
				<div data-edge data-source="s1" data-target="g1" data-curve></div>
				<div data-edge data-source="f2" data-target="s2"></div>
			</div>
			<div class="cerb-uiref-result">Lanes: <b id="uiref-nodegraph-out">&mdash;</b> &middot; built from the markup below (no <code>loadJSON</code>) &middot; double-click a node, expand the minimap</div>
		</div>

		<div class="cerb-uiref-code">
			<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
			<pre data-cerb-uiref-source>{literal}// This demo is built from the data-* markup below — NodeGraph reads it on construction (no loadJSON).
const g = new CerbUI.NodeGraph('#my-graph', {
	minimap: true,                         // optional bottom-right overview (collapsed by default)
	nodeTypes: [                           // the pre-defined types the markup's data-node-type refers to
		{ id:'start', label:'Start', icon:'flag', headerColor:'#48bb78', ports:'center', start:true },
		{ id:'step',  label:'Step',  icon:'zap',  headerColor:'#3182ce', ports:'center' },
		{ id:'group', label:'Group', icon:'list', headerColor:'#0987a0', ports:'center', container:true },
		{ id:'item',  label:'Item',  icon:'bot',  headerColor:'#805ad5', terminal:true },
	],
	onNodeOpen: (data, node, e) => { /* e.g. open a record peek */ },
});

// "Lanes" are the graph's disconnected components — drive them via the API:
g.getLaneCount();   // → 2
g.focusLane(0);     // center a lane at 100% (top-aligned)
g.fitLane(1);       // frame a lane (shrinks to fit)
g.fit();            // frame the whole graph
g.canvas.zoomIn();  // the canvas is reachable for pan/zoom
// (equivalently: skip the markup and call g.loadJSON({ nodes:[…{id,type,label,tier,children}], edges:[…] })){/literal}</pre>
		</div>
	</div>

	{* The seed markup shown separately, for reference + copy. *}
	<div class="cerb-ui-header"><div class="cerb-ui-header--label">The <b>seed markup</b> the demo enhances &mdash; server-render this, then <code>new CerbUI.NodeGraph(el, { nodeTypes })</code> reads it. Only <code>data-*</code> is honored; nested node elements are contained children; <code>data-edge</code> elements are edges.</div></div>
	<div class="cerb-uiref-code">
		<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
		<pre data-cerb-uiref-source>&lt;div id="my-graph"&gt;
  &lt;div data-node-id="f1" data-node-type="start" data-label="On lane A" data-tier="0"&gt;&lt;/div&gt;
  &lt;div data-node-id="s1" data-node-type="step"  data-label="step A"    data-tier="0"&gt;&lt;/div&gt;
  &lt;div data-node-id="g1" data-node-type="group" data-label="Group A"   data-tier="1"&gt;
    &lt;!-- nested node elements = this node's contained children --&gt;
    &lt;div data-node-id="a1" data-node-type="item" data-label="item 1"&gt;&lt;/div&gt;
    &lt;div data-node-id="a2" data-node-type="item" data-label="item 2"&gt;&lt;/div&gt;
    &lt;div data-node-id="a3" data-node-type="item" data-label="item 3"&gt;&lt;/div&gt;
  &lt;/div&gt;
  &lt;div data-node-id="f2" data-node-type="start" data-label="On lane B" data-tier="0"&gt;&lt;/div&gt;
  &lt;div data-node-id="s2" data-node-type="step"  data-label="step B"    data-tier="0"&gt;&lt;/div&gt;
  &lt;div data-edge data-source="f1" data-target="s1"&gt;&lt;/div&gt;
  &lt;div data-edge data-source="s1" data-target="g1" data-curve&gt;&lt;/div&gt;
  &lt;div data-edge data-source="f2" data-target="s2"&gt;&lt;/div&gt;
&lt;/div&gt;</pre>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.nodeTypes)) return;

	// Exercise the data-type system live so the foundation is verifiable in the gallery.
	(function() {
		const out = document.getElementById('uiref-nodetypes-out');
		const summary = document.getElementById('uiref-nodetypes-summary');
		if(!out) return;
		CerbUI.nodeTypes.reset();
		CerbUI.nodeTypes.register('credentials', 'dictionary', { color: '#ed8936', label: 'Credentials' });
		CerbUI.nodeTypes.register('api_key', 'credentials', { label: 'API Key' });
		CerbUI.nodeTypes.register('oauth2_token', 'credentials', { label: 'OAuth2 Token' });
		CerbUI.nodeTypes.register('record', 'dictionary', { label: 'Record' });
		CerbUI.nodeTypes.register('ticket', 'record', { label: 'Ticket' });

		const cases = [
			['credentials', 'api_key'], ['api_key', 'credentials'], ['dictionary', 'ticket'],
			['ticket', 'dictionary'], ['any', 'ticket'], ['list<record>', 'list<ticket>'],
		];
		let pass = 0;
		out.innerHTML = cases.map(function(c) {
			const ok = CerbUI.nodeTypes.isCompatible(c[0], c[1]);
			if(ok) pass++;
			return '<div style="display:flex;align-items:center;gap:0.5em;margin:0.2em 0;">'
				+ '<span class="cerb-ui-pip" style="background:' + CerbUI.nodeTypes.getColor(c[1]) + ';"></span>'
				+ '<code>' + c[1] + '</code> &rarr; <code>' + c[0] + '</code> '
				+ '<b style="color:var(--cerb-color-tag-' + (ok ? 'green' : 'red') + ');">' + (ok ? 'connect' : 'reject') + '</b>'
				+ '</div>';
		}).join('');
		if(summary) summary.textContent = pass + ' / ' + cases.length + ' edges allowed · chain(ticket) = '
			+ CerbUI.nodeTypes.getInheritanceChain('ticket').join(' → ');
	})();

	// The live node editor.
	(function() {
		const host = document.getElementById('uiref-nodeeditor');
		const out = document.getElementById('uiref-nodeeditor-out');
		if(!host) return;

		Devblocks.loadResources({
			'js': [
				'/resource/cerberusweb.core/js/cerb-ui/node-editor.js?v={$smarty.const.APP_BUILD}'
			]
		}, function() {
			if(!CerbUI.NodeEditor) {
				console.error('CerbUI.NodeEditor failed to load');
				return;
			}

			// Register the demo dialect's data types (idempotent) — an inheritance tree drives the cascading type menu
			// when adding a variable (Dictionary ▸ Credentials ▸ API Key …).
			CerbUI.nodeTypes.register('credentials', 'dictionary', { color: '#ed8936', label: 'Credentials' });
			CerbUI.nodeTypes.register('api_key', 'credentials', { label: 'API Key' });
			CerbUI.nodeTypes.register('oauth2_token', 'credentials', { label: 'OAuth2 Token' });
			CerbUI.nodeTypes.register('http_response', 'dictionary', { color: '#0987a0', label: 'HTTP Response' });
			CerbUI.nodeTypes.register('record', 'dictionary', { label: 'Record' });
			CerbUI.nodeTypes.register('ticket', 'record', { label: 'Ticket' });

			const editor = new CerbUI.NodeEditor(host, {
				mode: 'flow',
				// The host seeds the blackboard at init — these are immutable (like an automation event's inputs);
				// the user can add their own below, and they're all visible in the Expression Builder.
				variables: [
					{ name: 'event', type: 'dictionary' },
					{ name: 'api_credentials', type: 'api_key' },
				],
				nodeTypes: [
					{ id: 'start',        label: 'Start',        icon: 'play',     headerColor: '#48bb78', category: 'trigger', start: true, singleton: true },
					{ id: 'log',          label: 'Log',          icon: 'list',     headerColor: '#dd6b20', category: 'action',
						fields: [ { name: 'message', label: 'message', control: 'text', value: 'Hello, world' } ] },
					{ id: 'http_request', label: 'HTTP Request', icon: 'cloud',    headerColor: '#dd6b20', category: 'action',
						fields: [
							{ name: 'url',    label: 'url',    control: 'text',   value: 'https://api.example.com' },
							{ name: 'method', label: 'method', control: 'select', value: 'GET', options: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] },
							{ name: 'timeout', label: 'timeout', control: 'number', value: '30', dataType: 'int' },
							{ name: 'followRedirects', label: 'followRedirects', control: 'toggle', value: true },
						],
						inlets:   [ { name: 'credentials', dataType: 'credentials' } ],
						outputs:  [ { name: 'response', dataType: 'http_response' } ],
						branches: [ { name: 'success' }, { name: 'error' } ] },
					{ id: 'set_vars',     label: 'Set Variables',icon: 'edit',     headerColor: '#dd6b20', category: 'action',
						fields: [ { name: 'name', label: 'name', control: 'text', value: '' }, { name: 'value', label: 'value', control: 'text', value: '' } ] },
					{ id: 'decision',     label: 'Decision',     icon: 'branch',   headerColor: '#805ad5', category: 'logic',
						branches: [ { name: 'default' } ], canAddOutcomes: true },
					{ id: 'repeat',       label: 'Repeat',       icon: 'refresh',  headerColor: '#805ad5', category: 'logic',
						fields: [ { name: 'each', label: 'each', control: 'text', value: '' } ], branches: [ { name: 'do' } ] },
					{ id: 'return',       label: 'Return',       icon: 'return',   headerColor: '#e53e3e', category: 'terminal', terminal: true,
						canAddOutputs: true },
					{ id: 'error',        label: 'Error',        icon: 'alert',    headerColor: '#e53e3e', category: 'terminal', terminal: true,
						canAddOutputs: true },
					{ id: 'expression',   label: 'Expression',   icon: 'placeholders', headerColor: '#805ad5', category: 'expression', expression: true },
					{ id: 'comment',      label: 'Comment',      icon: 'comments', headerColor: '#718096', category: 'utility', comment: true },
				],
				onChange: function(ed) {
					if(out) { const g = ed.toJSON(); out.textContent = g.nodes.length + ' nodes · ' + g.edges.length + ' edges'; }
				},
			});

			// A small starter graph: a flow chain into the rich HTTP node, whose `error` branch routes to an Error node.
			editor.loadJSON({
				nodes: [
					{ id: 'n1', type: 'start',        position: { x: 40,  y: 80 } },
					{ id: 'n2', type: 'http_request', position: { x: 300, y: 60 } },
					{ id: 'n3', type: 'error',        position: { x: 720, y: 360 } },
					{ id: 'n4', type: 'comment',      position: { x: 40,  y: 360 }, data: { text: 'This flow calls an API and routes failures to Error.' } },
					{ id: 'n5', type: 'expression',   position: { x: 560, y: 70 }, data: { expression: { name: 'isWeekend' } } },
				],
				edges: [
					{ source: 'n1', sourceHandle: '_next',         target: 'n2', targetHandle: '_in' },
					{ source: 'n2', sourceHandle: 'branch:error',  target: 'n3', targetHandle: '_in' },
				],
			});
		});
	})();

	// The read-only NodeGraph viewer — lanes + a container node + minimap, with a small host-built toolbar.
	(function() {
		const host = document.getElementById('uiref-nodegraph');
		const out = document.getElementById('uiref-nodegraph-out');
		if(!host || !CerbUI.NodeGraph) return;

		// The host (#uiref-nodegraph) is seeded with data-* node/edge markup; NodeGraph reads it on construction
		// — no loadJSON. The types its data-node-type refers to are passed here.
		const g = new CerbUI.NodeGraph(host, {
			minimap: true,
			nodeTypes: [
				{ id: 'start', label: 'Start', icon: 'flag', headerColor: '#48bb78', category: 'Flow',  ports: 'center', start: true },
				{ id: 'step',  label: 'Step',  icon: 'zap',  headerColor: '#3182ce', category: 'Step',  ports: 'center' },
				{ id: 'group', label: 'Group', icon: 'list', headerColor: '#0987a0', category: 'Group', ports: 'center', container: true },
				{ id: 'item',  label: 'Item',  icon: 'bot',  headerColor: '#805ad5', category: 'Item',  terminal: true },
			],
		});

		// Host-built toolbar (the component ships none): Fit / zoom + a green flag that cycles lanes at 100%.
		const bar = document.createElement('div');
		bar.className = 'cerb-ui-node-graph--toolbar';
		const btn = function(icon, title, fn) {
			const b = document.createElement('button');
			b.type = 'button';
			b.className = 'cerb-ui-button cerb-ui-node-graph--tool';
			b.title = title;
			const s = document.createElement('span');
			s.className = 'cerb-icons cerb-icon-' + icon;
			if(icon === 'flag') s.style.color = 'var(--cerb-color-tag-green)';
			b.appendChild(s);
			b.addEventListener('click', fn);
			bar.appendChild(b);
			return b;
		};
		btn('move', 'Fit', function() { g.fit(); });
		btn('zoom-in', 'Zoom in', function() { if(g.canvas) g.canvas.zoomIn(); });
		btn('zoom-out', 'Zoom out', function() { if(g.canvas) g.canvas.zoomOut(); });
		const lanes = g.getLaneCount();
		let li = 0;
		btn('flag', 'Next lane', function() { li = (li + 1) % lanes; g.focusLane(li); if(out) out.textContent = (li + 1) + ' / ' + lanes; });
		host.insertBefore(bar, host.firstChild);
		g.focusLane(0);
		if(out) out.textContent = '1 / ' + lanes;
	})();
})();
</script>
