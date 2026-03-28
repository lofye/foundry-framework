<?php

declare(strict_types=1);

namespace Foundry\Documentation;

use Foundry\Compiler\ApplicationGraph;
use Foundry\Compiler\IR\GraphNode;
use Foundry\Support\ApiSurfaceRegistry;
use Foundry\Support\CliCommandPrefix;
use Foundry\Support\Json;
use Foundry\Support\Paths;

final class CommandPlaygroundPage
{
    public function __construct(
        private readonly Paths $paths,
        private readonly ApiSurfaceRegistry $apiSurfaceRegistry,
    ) {}

    public function content(ApplicationGraph $graph): string
    {
        $dataJson = htmlspecialchars(Json::encode($this->data($graph), true), ENT_NOQUOTES);

        $template = <<<'HTML'
<section class="command-playground" id="command-playground">
  <div class="command-playground__header">
    <h1>Command Playground</h1>
    <p>Static, browser-based CLI reference driven by the Foundry command registry and deterministic preview data. Commands are never executed here.</p>
  </div>

  <div class="command-playground__toolbar">
    <label class="command-playground__field">
      <span>Search</span>
      <input id="command-playground-search" type="search" placeholder="Search commands, descriptions, or usage">
    </label>
    <label class="command-playground__field">
      <span>Stability</span>
      <select id="command-playground-stability">
        <option value="">All stability levels</option>
        <option value="stable">Stable</option>
        <option value="experimental">Experimental</option>
        <option value="internal">Internal</option>
      </select>
    </label>
  </div>

  <p class="command-playground__summary" id="command-playground-summary">Loading commands...</p>

  <div class="command-playground__layout">
    <aside class="command-playground__list-panel">
      <div id="command-playground-list"></div>
    </aside>
    <article class="command-playground__details" id="command-playground-details">
      <h2>Command Details</h2>
      <p>Select a command to inspect its usage, deterministic JSON preview, related docs, explain targets, and graph links.</p>
    </article>
  </div>
</section>

<script id="command-playground-data" type="application/json">__COMMAND_PLAYGROUND_DATA__</script>
<script>
(() => {
  const dataElement = document.getElementById('command-playground-data');
  const listElement = document.getElementById('command-playground-list');
  const detailsElement = document.getElementById('command-playground-details');
  const summaryElement = document.getElementById('command-playground-summary');
  const searchInput = document.getElementById('command-playground-search');
  const stabilitySelect = document.getElementById('command-playground-stability');

  if (!dataElement || !listElement || !detailsElement || !summaryElement || !searchInput || !stabilitySelect) {
    return;
  }

  const payload = JSON.parse(dataElement.textContent || '{}');
  const commands = Array.isArray(payload.commands) ? payload.commands : [];
  const commandBySignature = new Map(commands.map((command) => [command.signature, command]));
  const initialSignature = initialCommandFromLocation();

  const state = {
    search: '',
    stability: '',
    selectedSignature: commandBySignature.has(initialSignature)
      ? initialSignature
      : (commands[0] ? String(commands[0].signature || '') : null)
  };

  searchInput.addEventListener('input', () => {
    state.search = String(searchInput.value || '').trim().toLowerCase();
    render();
  });

  stabilitySelect.addEventListener('change', () => {
    state.stability = String(stabilitySelect.value || '');
    render();
  });

  listElement.addEventListener('click', (event) => {
    const button = event.target.closest('[data-command-signature]');
    if (!(button instanceof HTMLElement)) {
      return;
    }

    const signature = button.getAttribute('data-command-signature');
    if (!signature || !commandBySignature.has(signature)) {
      return;
    }

    state.selectedSignature = signature;
    render();
  });

  detailsElement.addEventListener('click', (event) => {
    const button = event.target.closest('[data-related-command]');
    if (!(button instanceof HTMLElement)) {
      return;
    }

    const signature = button.getAttribute('data-related-command');
    if (!signature || !commandBySignature.has(signature)) {
      return;
    }

    state.selectedSignature = signature;
    render();
  });

  render();

  function render() {
    const filteredCommands = commands.filter(matchesFilters);

    if (state.selectedSignature !== null && !filteredCommands.some((command) => command.signature === state.selectedSignature)) {
      state.selectedSignature = filteredCommands[0] ? String(filteredCommands[0].signature || '') : null;
    }

    const selectedCommand = state.selectedSignature !== null
      ? commandBySignature.get(state.selectedSignature) || null
      : null;

    renderList(filteredCommands, selectedCommand);
    renderDetails(selectedCommand);
    renderSummary(filteredCommands.length, selectedCommand);
    syncLocation();
  }

  function renderList(filteredCommands, selectedCommand) {
    if (filteredCommands.length === 0) {
      listElement.innerHTML = '<p class="command-playground__empty">No commands match the current filters.</p>';
      return;
    }

    const groups = {
      stable: [],
      experimental: [],
      internal: []
    };

    filteredCommands.forEach((command) => {
      const stability = String(command.stability || 'internal');
      groups[stability] = Array.isArray(groups[stability]) ? groups[stability] : [];
      groups[stability].push(command);
    });

    const sections = [
      renderGroup('Stable Commands', groups.stable || [], selectedCommand),
      renderGroup('Experimental Commands', groups.experimental || [], selectedCommand),
      renderGroup('Internal Commands', groups.internal || [], selectedCommand)
    ].filter(Boolean);

    listElement.innerHTML = sections.join('');
  }

  function renderGroup(title, entries, selectedCommand) {
    if (!Array.isArray(entries) || entries.length === 0) {
      return '';
    }

    const items = entries.map((command) => {
      const isSelected = selectedCommand && selectedCommand.signature === command.signature;
      const selectedClass = isSelected ? ' is-selected' : '';

      return ''
        + '<button type="button" class="command-playground__list-item' + selectedClass + '" data-command-signature="' + escapeHtml(command.signature) + '">'
        + '<span class="command-playground__list-signature">' + escapeHtml(command.signature) + '</span>'
        + '<span class="command-playground__list-summary">' + escapeHtml(command.description || '') + '</span>'
        + '<code class="command-playground__list-usage">' + escapeHtml(command.usage || '') + '</code>'
        + '</button>';
    }).join('');

    return ''
      + '<section class="command-playground__group">'
      + '<h2>' + escapeHtml(title) + '</h2>'
      + items
      + '</section>';
  }

  function renderDetails(command) {
    if (!command) {
      detailsElement.innerHTML = '<h2>Command Details</h2><p>Select a command to inspect its usage, deterministic JSON preview, related docs, explain targets, and graph links.</p>';
      return;
    }

    const docs = renderLinkList(command.docs, 'No related docs linked.');
    const relatedNodes = renderLinkList(command.relatedNodes, 'No related graph nodes are attached to this command preview.');
    const relatedCommands = renderRelatedCommands(command.relatedCommands);
    const explainTargets = renderExplainTargets(command.explainTargets);
    const examples = Array.isArray(command.examples) && command.examples.length > 0
      ? '<ul class="command-playground__code-list">' + command.examples.map((example) => '<li><code>' + escapeHtml(example) + '</code></li>').join('') + '</ul>'
      : '<p class="command-playground__muted">No examples published.</p>';

    detailsElement.innerHTML = ''
      + '<h2>' + escapeHtml(command.signature) + '</h2>'
      + '<p class="command-playground__meta">'
      + '<span>' + escapeHtml(command.stability || 'internal') + '</span>'
      + '<span>' + escapeHtml(command.availability === 'pro' ? 'Foundry Pro' : 'Core') + '</span>'
      + '</p>'
      + '<p>' + escapeHtml(command.description || '') + '</p>'
      + '<h3>Usage Signature</h3>'
      + '<pre class="command-playground__pre"><code>' + escapeHtml(command.usage || '') + '</code></pre>'
      + '<h3>Usage Examples</h3>'
      + examples
      + '<h3>Sample JSON Output</h3>'
      + '<p class="command-playground__muted">' + escapeHtml(command.sampleOutputLabel || 'Sample JSON output') + '</p>'
      + '<pre class="command-playground__pre"><code>' + escapeHtml(JSON.stringify(command.sampleOutput || {}, null, 2)) + '</code></pre>'
      + '<h3>Related Documentation</h3>'
      + docs
      + '<h3>Related Explain Targets</h3>'
      + explainTargets
      + '<h3>Related Commands</h3>'
      + relatedCommands
      + '<h3>Related Graph Nodes</h3>'
      + relatedNodes;
  }

  function renderSummary(count, selectedCommand) {
    const commandLabel = selectedCommand ? ' Selected: ' + selectedCommand.signature + '.' : '';
    summaryElement.textContent = 'Showing ' + count + ' commands.' + commandLabel;
  }

  function renderLinkList(items, emptyMessage) {
    if (!Array.isArray(items) || items.length === 0) {
      return '<p class="command-playground__muted">' + escapeHtml(emptyMessage) + '</p>';
    }

    return '<ul class="command-playground__link-list">' + items.map((item) => {
      const title = escapeHtml(item.title || item.label || '');
      const meta = item.meta ? ' <span class="command-playground__muted">(' + escapeHtml(item.meta) + ')</span>' : '';

      return '<li><a href="' + escapeHtml(item.href || '#') + '">' + title + '</a>' + meta + '</li>';
    }).join('') + '</ul>';
  }

  function renderRelatedCommands(items) {
    if (!Array.isArray(items) || items.length === 0) {
      return '<p class="command-playground__muted">No closely related commands were published for this command.</p>';
    }

    return '<div class="command-playground__related-buttons">' + items.map((item) => {
      return '<button type="button" data-related-command="' + escapeHtml(item.signature || '') + '">' + escapeHtml(item.signature || '') + '</button>';
    }).join('') + '</div>';
  }

  function renderExplainTargets(items) {
    if (!Array.isArray(items) || items.length === 0) {
      return '<p class="command-playground__muted">No explain targets are attached to this command preview.</p>';
    }

    return '<ul class="command-playground__code-list">' + items.map((item) => {
      const title = escapeHtml(item.title || item.target || '');
      const meta = item.meta ? '<span class="command-playground__muted"> ' + escapeHtml(item.meta) + '</span>' : '';
      const link = item.href ? '<a href="' + escapeHtml(item.href) + '"><code>' + title + '</code></a>' : '<code>' + title + '</code>';

      return '<li>' + link + meta + '</li>';
    }).join('') + '</ul>';
  }

  function matchesFilters(command) {
    if (state.stability !== '' && String(command.stability || '') !== state.stability) {
      return false;
    }

    if (state.search === '') {
      return true;
    }

    return String(command.searchText || '').includes(state.search);
  }

  function initialCommandFromLocation() {
    const params = new URLSearchParams(window.location.search);
    const fromQuery = params.get('command');
    if (fromQuery) {
      return fromQuery;
    }

    if (window.location.hash.startsWith('#command=')) {
      return decodeURIComponent(window.location.hash.slice('#command='.length));
    }

    return '';
  }

  function syncLocation() {
    if (!state.selectedSignature) {
      return;
    }

    const url = new URL(window.location.href);
    url.searchParams.set('command', state.selectedSignature);
    url.hash = 'command=' + encodeURIComponent(state.selectedSignature);
    window.history.replaceState({}, '', url);
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
})();
</script>

<style>
.command-playground {
  display: grid;
  gap: 1.5rem;
}

.command-playground__header h1 {
  margin-bottom: 0.35rem;
}

.command-playground__header p {
  margin: 0;
  color: #5b645f;
}

.command-playground__toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
}

.command-playground__field {
  display: grid;
  gap: 0.35rem;
  min-width: 16rem;
}

.command-playground__field input,
.command-playground__field select {
  border: 1px solid #cabaa8;
  border-radius: 0.8rem;
  background: #fffdfa;
  padding: 0.7rem 0.9rem;
  font: inherit;
}

.command-playground__summary {
  margin: 0;
  color: #5b645f;
  font-size: 0.95rem;
}

.command-playground__layout {
  display: grid;
  grid-template-columns: minmax(20rem, 26rem) minmax(0, 1fr);
  gap: 1.25rem;
}

.command-playground__list-panel,
.command-playground__details {
  border: 1px solid #d9ccbc;
  border-radius: 1rem;
  background: #fffdfa;
  box-shadow: 0 14px 40px rgba(68, 49, 27, 0.07);
}

.command-playground__list-panel {
  max-height: 70rem;
  overflow: auto;
  padding: 1rem;
}

.command-playground__details {
  padding: 1.25rem;
}

.command-playground__group + .command-playground__group {
  margin-top: 1.25rem;
}

.command-playground__group h2,
.command-playground__details h2,
.command-playground__details h3 {
  margin-top: 0;
}

.command-playground__list-item {
  display: grid;
  gap: 0.35rem;
  width: 100%;
  margin-top: 0.75rem;
  border: 1px solid #e3d8cb;
  border-radius: 0.9rem;
  background: #fff;
  padding: 0.85rem;
  text-align: left;
  cursor: pointer;
}

.command-playground__list-item:hover {
  border-color: #9d6f34;
}

.command-playground__list-item.is-selected {
  border-color: #9d6f34;
  background: #fbf0e2;
}

.command-playground__list-signature {
  font-weight: 700;
  color: #402c16;
}

.command-playground__list-summary {
  color: #5b645f;
  font-size: 0.95rem;
}

.command-playground__list-usage {
  font-size: 0.83rem;
  color: #3f4f6b;
  white-space: normal;
}

.command-playground__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  margin: 0 0 0.75rem;
}

.command-playground__meta span {
  border-radius: 999px;
  background: #f3eadf;
  color: #563a16;
  padding: 0.25rem 0.7rem;
  font-size: 0.85rem;
}

.command-playground__muted {
  color: #5b645f;
}

.command-playground__pre {
  overflow: auto;
  border-radius: 0.85rem;
  background: #f7f1ea;
  padding: 0.9rem 1rem;
}

.command-playground__link-list,
.command-playground__code-list {
  padding-left: 1.1rem;
}

.command-playground__related-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.command-playground__related-buttons button {
  border: 1px solid #d2c2b3;
  border-radius: 999px;
  background: #fff;
  padding: 0.45rem 0.8rem;
  font: inherit;
  cursor: pointer;
}

.command-playground__related-buttons button:hover {
  border-color: #9d6f34;
  background: #fbf0e2;
}

.command-playground__empty {
  margin: 0;
  color: #5b645f;
}

@media (max-width: 960px) {
  .command-playground__layout {
    grid-template-columns: 1fr;
  }

  .command-playground__list-panel {
    max-height: none;
  }
}
</style>
HTML;

        return str_replace('__COMMAND_PLAYGROUND_DATA__', $dataJson, $template);
    }

    /**
     * @return array<string,mixed>
     */
    private function data(ApplicationGraph $graph): array
    {
        $commands = array_values($this->apiSurfaceRegistry->cliCommands());

        $rows = [];
        foreach ($commands as $command) {
            if (!is_array($command)) {
                continue;
            }

            $rows[] = $this->commandRow($command, $graph, $commands);
        }

        return [
            'summary' => $this->apiSurfaceRegistry->cliHelpIndex()['summary'] ?? [],
            'commands' => $rows,
        ];
    }

    /**
     * @param array<string,mixed> $command
     * @param array<int,array<string,mixed>> $allCommands
     * @return array<string,mixed>
     */
    private function commandRow(array $command, ApplicationGraph $graph, array $allCommands): array
    {
        $signature = (string) ($command['signature'] ?? '');
        $usage = (string) ($command['usage'] ?? '');
        $description = (string) ($command['summary'] ?? '');
        $preview = $this->sampleOutputPreview($command, $graph);
        $docs = $this->docsLinks($signature);
        $relatedNodes = $this->relatedGraphNodes($signature, $graph);

        return [
            'signature' => $signature,
            'usage' => $usage,
            'description' => $description,
            'stability' => (string) ($command['stability'] ?? 'internal'),
            'availability' => (string) ($command['availability'] ?? 'core'),
            'classification' => (string) ($command['classification'] ?? 'internal_api'),
            'semverPolicy' => (string) ($command['semver_policy'] ?? ''),
            'examples' => $this->usageExamples($signature, $graph),
            'sampleOutputLabel' => (string) ($preview['label'] ?? 'Sample JSON output'),
            'sampleOutput' => $preview['payload'] ?? [],
            'docs' => $docs,
            'explainTargets' => $this->relatedExplainTargets($signature, $relatedNodes),
            'relatedCommands' => $this->relatedCommands($signature, $allCommands),
            'relatedNodes' => $relatedNodes,
            'searchText' => strtolower(implode(' ', array_filter([
                $signature,
                $usage,
                $description,
                (string) ($command['stability'] ?? ''),
                (string) ($command['availability'] ?? ''),
                implode(' ', array_map(static fn(array $doc): string => (string) ($doc['title'] ?? ''), $docs)),
            ]))),
        ];
    }

    /**
     * @return array<int,string>
     */
    private function usageExamples(string $signature, ApplicationGraph $graph): array
    {
        $prefix = CliCommandPrefix::foundry($this->paths);
        $examples = [
            $prefix . ' ' . $this->sampleInvocation($signature, $graph),
            $prefix . ' ' . $this->helpInvocation($signature),
        ];

        return array_values(array_unique(array_filter(array_map('trim', $examples))));
    }

    private function sampleInvocation(string $signature, ApplicationGraph $graph): string
    {
        $examples = $this->exampleValues($graph);

        return match (true) {
            $signature === 'help' => 'help inspect graph --json',
            $signature === 'compile graph' => 'compile graph --json',
            $signature === 'cache inspect' => 'cache inspect --json',
            $signature === 'cache clear' => 'cache clear --json',
            $signature === 'doctor' => 'doctor --graph --json',
            $signature === 'upgrade-check' => 'upgrade-check --json',
            $signature === 'observe:trace' => 'observe:trace ' . $examples['feature'] . ' --json',
            $signature === 'observe:profile' => 'observe:profile ' . $examples['feature'] . ' --json',
            $signature === 'observe:compare' => 'observe:compare run-a run-b --json',
            $signature === 'history' => 'history --json',
            $signature === 'regressions' => 'regressions --json',
            $signature === 'graph inspect' => 'graph inspect --format=json --json',
            $signature === 'graph visualize' => 'graph visualize --format=svg --json',
            $signature === 'prompt' => 'prompt Plan the ' . $examples['feature'] . ' feature --dry-run --json',
            $signature === 'pro' => 'pro status --json',
            $signature === 'pro enable' => 'pro enable FOUND-LOCAL-TEST --json',
            $signature === 'pro status' => 'pro status --json',
            $signature === 'explain' => 'explain ' . $examples['feature'] . ' --json',
            $signature === 'diff' => 'diff --json',
            $signature === 'trace' => 'trace ' . $examples['feature'] . ' --json',
            $signature === 'generate <prompt>' => 'generate Add tags to ' . $examples['feature'] . ' --feature-context --deterministic --dry-run --json',
            $signature === 'serve' => 'serve --json',
            $signature === 'queue:work' => 'queue:work default --json',
            $signature === 'queue:inspect' => 'queue:inspect default --json',
            $signature === 'schedule:run' => 'schedule:run --json',
            $signature === 'trace:tail' => 'trace:tail --json',
            $signature === 'affected-files' => 'affected-files ' . $examples['feature'] . ' --json',
            $signature === 'impacted-features' => 'impacted-features event:' . $examples['event'] . ' --json',
            $signature === 'new' => 'new demo-app --starter=standard --json',
            $signature === 'init app' => 'init app demo-app --starter=standard --json',
            $signature === 'migrate definitions' => 'migrate definitions --dry-run --json',
            $signature === 'codemod run' => 'codemod run example-codemod --dry-run --json',
            $signature === 'export graph' => 'export graph --format=json --json',
            $signature === 'export openapi' => 'export openapi --format=json --json',
            $signature === 'preview notification' => 'preview notification welcome_email --json',
            str_starts_with($signature, 'inspect node') => 'inspect node ' . $examples['node'] . ' --json',
            str_starts_with($signature, 'inspect dependents') => 'inspect dependents ' . $examples['node'] . ' --json',
            str_starts_with($signature, 'inspect affected-tests') => 'inspect affected-tests ' . $examples['node'] . ' --json',
            str_starts_with($signature, 'inspect affected-features') => 'inspect affected-features ' . $examples['node'] . ' --json',
            str_starts_with($signature, 'inspect dependencies') => 'inspect dependencies ' . $examples['feature'] . ' --json',
            str_starts_with($signature, 'inspect execution-plan') => 'inspect execution-plan ' . $examples['feature'] . ' --json',
            str_starts_with($signature, 'inspect guards') => 'inspect guards ' . $examples['feature'] . ' --json',
            str_starts_with($signature, 'inspect interceptors') => 'inspect interceptors --stage=' . $examples['pipeline_stage'] . ' --json',
            str_starts_with($signature, 'inspect impact') => 'inspect impact ' . $examples['node'] . ' --json',
            str_starts_with($signature, 'inspect subgraph') => 'inspect subgraph ' . $examples['feature'] . ' --json',
            str_starts_with($signature, 'inspect extension') => 'inspect extension ' . $examples['extension'] . ' --json',
            str_starts_with($signature, 'inspect pack') => 'inspect pack ' . $examples['pack'] . ' --json',
            str_starts_with($signature, 'inspect definition-format') => 'inspect definition-format workflow --json',
            str_starts_with($signature, 'inspect api-surface') => 'inspect api-surface --command=compile graph --json',
            str_starts_with($signature, 'inspect cli-surface') => 'inspect cli-surface --json',
            $signature === 'inspect graph' => 'inspect graph --json',
            $signature === 'inspect build' => 'inspect build --json',
            $signature === 'inspect pipeline' => 'inspect pipeline --json',
            $signature === 'inspect extensions' => 'inspect extensions --json',
            $signature === 'inspect packs' => 'inspect packs --json',
            $signature === 'inspect compatibility' => 'inspect compatibility --json',
            $signature === 'inspect migrations' => 'inspect migrations --json',
            $signature === 'inspect graph-spec' => 'inspect graph-spec --json',
            $signature === 'inspect node-types' => 'inspect node-types --json',
            $signature === 'inspect edge-types' => 'inspect edge-types --json',
            $signature === 'inspect graph-integrity' => 'inspect graph-integrity --json',
            $signature === 'inspect feature' => 'inspect feature ' . $examples['feature'] . ' --json',
            $signature === 'inspect auth' => 'inspect auth ' . $examples['feature'] . ' --json',
            $signature === 'inspect cache' => 'inspect cache ' . $examples['feature'] . ' --json',
            $signature === 'inspect events' => 'inspect events ' . $examples['feature'] . ' --json',
            $signature === 'inspect jobs' => 'inspect jobs ' . $examples['feature'] . ' --json',
            $signature === 'inspect context' => 'inspect context ' . $examples['feature'] . ' --json',
            $signature === 'inspect notification' => 'inspect notification welcome_email --json',
            $signature === 'inspect api' => 'inspect api posts --json',
            $signature === 'inspect resource' => 'inspect resource posts --json',
            $signature === 'inspect route' => 'inspect route ' . $examples['route_method'] . ' ' . $examples['route_path'] . ' --json',
            $signature === 'inspect billing' => 'inspect billing --provider=stripe --json',
            $signature === 'inspect workflow' => 'inspect workflow ' . $examples['workflow'] . ' --json',
            $signature === 'inspect orchestration' => 'inspect orchestration publish_flow --json',
            $signature === 'inspect search' => 'inspect search posts --json',
            $signature === 'inspect streams' => 'inspect streams --json',
            $signature === 'inspect locales' => 'inspect locales --json',
            $signature === 'inspect roles' => 'inspect roles --json',
            $signature === 'generate feature' => 'generate feature definitions/list-posts.yaml --json',
            $signature === 'generate tests' => 'generate tests ' . $examples['feature'] . ' --json',
            $signature === 'generate context' => 'generate context ' . $examples['feature'] . ' --json',
            $signature === 'generate starter' => 'generate starter server-rendered --force --json',
            $signature === 'generate resource' => 'generate resource posts --definition=definitions/posts.resource.yaml --json',
            $signature === 'generate admin-resource' => 'generate admin-resource posts --force --json',
            $signature === 'generate uploads' => 'generate uploads images --force --json',
            $signature === 'generate notification' => 'generate notification welcome_email --force --json',
            $signature === 'generate api-resource' => 'generate api-resource posts --definition=definitions/posts.api-resource.yaml --json',
            $signature === 'generate docs' => 'generate docs --format=markdown --json',
            $signature === 'generate indexes' => 'generate indexes --json',
            $signature === 'generate migration' => 'generate migration definitions/posts.migration.yaml --json',
            $signature === 'generate billing' => 'generate billing stripe --force --json',
            $signature === 'generate workflow' => 'generate workflow ' . $examples['workflow'] . ' --definition=definitions/' . $examples['workflow'] . '.workflow.yaml --json',
            $signature === 'generate orchestration' => 'generate orchestration publish_flow --definition=definitions/publish_flow.orchestration.yaml --json',
            $signature === 'generate search-index' => 'generate search-index posts --definition=definitions/posts.search.yaml --json',
            $signature === 'generate stream' => 'generate stream posts --force --json',
            $signature === 'generate locale' => 'generate locale en_CA --force --json',
            $signature === 'generate roles' => 'generate roles --force --json',
            $signature === 'generate policy' => 'generate policy manage_posts --force --json',
            $signature === 'generate inspect-ui' => 'generate inspect-ui --json',
            str_starts_with($signature, 'verify feature') => 'verify feature ' . $examples['feature'] . ' --json',
            str_starts_with($signature, 'verify resource') => 'verify resource posts --json',
            $signature === 'verify graph' => 'verify graph --json',
            $signature === 'verify graph-integrity' => 'verify graph-integrity --json',
            $signature === 'verify pipeline' => 'verify pipeline --json',
            $signature === 'verify extensions' => 'verify extensions --json',
            $signature === 'verify compatibility' => 'verify compatibility --json',
            $signature === 'verify notifications' => 'verify notifications --json',
            $signature === 'verify api' => 'verify api --json',
            $signature === 'verify billing' => 'verify billing --json',
            $signature === 'verify workflows' => 'verify workflows --json',
            $signature === 'verify orchestrations' => 'verify orchestrations --json',
            $signature === 'verify search' => 'verify search --json',
            $signature === 'verify streams' => 'verify streams --json',
            $signature === 'verify locales' => 'verify locales --json',
            $signature === 'verify policies' => 'verify policies --json',
            $signature === 'verify contracts' => 'verify contracts --json',
            $signature === 'verify cli-surface' => 'verify cli-surface --json',
            $signature === 'verify auth' => 'verify auth --json',
            $signature === 'verify cache' => 'verify cache --json',
            $signature === 'verify events' => 'verify events --json',
            $signature === 'verify jobs' => 'verify jobs --json',
            $signature === 'verify migrations' => 'verify migrations --json',
            default => $signature . ' --json',
        };
    }

    private function helpInvocation(string $signature): string
    {
        return match ($signature) {
            'generate <prompt>' => 'help generate Add --json',
            default => 'help ' . $signature . ' --json',
        };
    }

    /**
     * @param array<string,mixed> $command
     * @return array{label:string,payload:array<string,mixed>}
     */
    private function sampleOutputPreview(array $command, ApplicationGraph $graph): array
    {
        $signature = (string) ($command['signature'] ?? '');

        return match ($signature) {
            'help' => [
                'label' => 'Sample command JSON output (`help --json` index)',
                'payload' => $this->apiSurfaceRegistry->cliHelpIndex(),
            ],
            'inspect graph', 'graph inspect' => [
                'label' => 'Sample command JSON output',
                'payload' => $this->inspectGraphPreview($graph),
            ],
            'generate docs' => [
                'label' => 'Sample command JSON output',
                'payload' => [
                    'format' => 'markdown',
                    'directory' => 'docs/generated',
                    'files' => [
                        'docs/generated/api-surface.md',
                        'docs/generated/cli-reference.md',
                        'docs/generated/features.md',
                        'docs/generated/graph-overview.md',
                        'docs/generated/routes.md',
                    ],
                ],
            ],
            default => [
                'label' => 'Sample `help <command> --json` output',
                'payload' => ['command' => $command],
            ],
        };
    }

    /**
     * @return array<string,mixed>
     */
    private function inspectGraphPreview(ApplicationGraph $graph): array
    {
        return [
            'framework_version' => $graph->frameworkVersion(),
            'graph_version' => $graph->graphVersion(),
            'compiled_at' => $graph->compiledAt(),
            'source_hash' => $graph->sourceHash(),
            'features' => $graph->features(),
            'node_counts' => $graph->nodeCountsByType(),
            'edge_counts' => $graph->edgeCountsByType(),
        ];
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function docsLinks(string $signature): array
    {
        $links = [
            ['title' => 'CLI Reference', 'href' => 'cli-reference.html'],
        ];

        if (
            in_array($signature, ['compile graph', 'graph inspect', 'graph visualize', 'inspect graph', 'export graph', 'verify graph', 'verify graph-integrity', 'verify pipeline', 'doctor', 'prompt', 'explain', 'diff', 'trace', 'observe:trace', 'observe:profile', 'observe:compare'], true)
            || str_starts_with($signature, 'inspect ')
        ) {
            $links[] = ['title' => 'Architecture Tools', 'href' => 'architecture-tools.html'];
            $links[] = ['title' => 'Architecture Explorer', 'href' => 'architecture-explorer.html'];
        }

        if (
            in_array($signature, ['new', 'init app', 'generate starter', 'generate resource', 'generate admin-resource', 'generate uploads', 'generate notification', 'generate api-resource', 'generate billing', 'generate workflow', 'generate orchestration', 'generate search-index', 'generate stream', 'generate locale', 'generate roles', 'generate policy'], true)
        ) {
            $links[] = ['title' => 'App Scaffolding', 'href' => 'app-scaffolding.html'];
            $links[] = ['title' => 'Example Applications', 'href' => 'example-applications.html'];
        }

        if (
            in_array($signature, ['generate docs', 'help', 'inspect cli-surface', 'verify cli-surface'], true)
        ) {
            $links[] = ['title' => 'Reference', 'href' => 'reference.html'];
        }

        if (in_array($signature, ['upgrade-check', 'migrate definitions', 'codemod run', 'verify compatibility'], true)) {
            $links[] = ['title' => 'Upgrade Reference', 'href' => 'upgrade-reference.html'];
            $links[] = ['title' => 'Upgrade Safety', 'href' => 'upgrade-safety.html'];
        }

        $unique = [];
        foreach ($links as $link) {
            $href = (string) ($link['href'] ?? '');
            if ($href === '' || isset($unique[$href])) {
                continue;
            }

            $unique[$href] = $link;
        }

        return array_values($unique);
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function relatedExplainTargets(string $signature, array $relatedNodes): array
    {
        $targets = [
            [
                'title' => 'command:' . $signature,
                'href' => 'architecture-tools.html',
                'meta' => 'Command explain target',
            ],
        ];

        foreach ($relatedNodes as $node) {
            $target = (string) ($node['explainTarget'] ?? '');
            if ($target === '') {
                continue;
            }

            $targets[] = [
                'title' => $target,
                'href' => 'architecture-tools.html',
                'meta' => 'Graph explain target',
            ];
        }

        $unique = [];
        foreach ($targets as $target) {
            $title = (string) ($target['title'] ?? '');
            if ($title === '' || isset($unique[$title])) {
                continue;
            }

            $unique[$title] = $target;
        }

        return array_values($unique);
    }

    /**
     * @param array<int,array<string,mixed>> $allCommands
     * @return array<int,array<string,string>>
     */
    private function relatedCommands(string $signature, array $allCommands): array
    {
        $desired = match ($signature) {
            'compile graph' => ['inspect graph', 'graph inspect', 'verify graph', 'export graph'],
            'inspect graph', 'graph inspect' => ['graph visualize', 'compile graph', 'verify graph', 'export graph'],
            'graph visualize' => ['graph inspect', 'export graph', 'inspect graph'],
            'export graph' => ['graph inspect', 'graph visualize', 'compile graph'],
            'new' => ['init app', 'generate docs', 'compile graph'],
            'init app' => ['new', 'generate docs', 'compile graph'],
            'help' => ['inspect cli-surface', 'verify cli-surface', 'explain'],
            'generate docs' => ['graph inspect', 'inspect graph', 'help'],
            'explain' => ['doctor', 'graph inspect', 'inspect graph', 'trace'],
            default => $this->familyRelatedSignatures($signature, $allCommands),
        };

        $available = [];
        foreach ($allCommands as $command) {
            if (!is_array($command)) {
                continue;
            }

            $candidate = (string) ($command['signature'] ?? '');
            if ($candidate !== '') {
                $available[$candidate] = true;
            }
        }

        $rows = [];
        foreach ($desired as $candidate) {
            if ($candidate === $signature || !isset($available[$candidate])) {
                continue;
            }

            $rows[] = ['signature' => $candidate];
            if (count($rows) === 4) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @param array<int,array<string,mixed>> $allCommands
     * @return array<int,string>
     */
    private function familyRelatedSignatures(string $signature, array $allCommands): array
    {
        $family = $this->commandFamily($signature);
        if ($family === '') {
            return [];
        }

        $matches = [];
        foreach ($allCommands as $command) {
            if (!is_array($command)) {
                continue;
            }

            $candidate = (string) ($command['signature'] ?? '');
            if ($candidate === '' || $candidate === $signature) {
                continue;
            }

            if ($this->commandFamily($candidate) === $family) {
                $matches[] = $candidate;
            }
        }

        sort($matches);

        return array_slice($matches, 0, 4);
    }

    private function commandFamily(string $signature): string
    {
        return match (true) {
            str_starts_with($signature, 'inspect ') => 'inspect',
            str_starts_with($signature, 'verify ') => 'verify',
            str_starts_with($signature, 'generate ') => 'generate',
            str_starts_with($signature, 'graph ') => 'graph',
            str_starts_with($signature, 'export ') => 'export',
            str_starts_with($signature, 'pro') => 'pro',
            str_starts_with($signature, 'observe:') => 'observe',
            default => strtok($signature, ' ') ?: '',
        };
    }

    /**
     * @return array<int,array<string,string>>
     */
    private function relatedGraphNodes(string $signature, ApplicationGraph $graph): array
    {
        return match (true) {
            in_array($signature, ['compile graph', 'inspect graph', 'graph inspect', 'graph visualize', 'export graph', 'verify graph', 'verify graph-integrity', 'verify pipeline', 'verify contracts', 'doctor', 'prompt', 'explain', 'diff', 'trace', 'generate docs'], true)
                => $this->pickNodeLinks($graph, ['feature', 'route', 'event'], 3),
            in_array($signature, ['inspect feature', 'inspect auth', 'inspect cache', 'inspect events', 'inspect jobs', 'inspect context', 'inspect execution-plan', 'inspect subgraph', 'observe:trace', 'observe:profile', 'affected-files', 'verify feature', 'verify auth'], true)
                => $this->pickNodeLinks($graph, ['feature'], 2),
            in_array($signature, ['impacted-features'], true)
                => $this->pickNodeLinks($graph, ['event', 'cache'], 2),
            in_array($signature, ['inspect route'], true)
                => $this->pickNodeLinks($graph, ['route'], 2),
            in_array($signature, ['verify events'], true)
                => $this->pickNodeLinks($graph, ['event'], 2),
            in_array($signature, ['verify jobs'], true)
                => $this->pickNodeLinks($graph, ['job'], 2),
            in_array($signature, ['verify cache'], true)
                => $this->pickNodeLinks($graph, ['cache'], 2),
            in_array($signature, ['inspect workflow', 'verify workflows', 'generate workflow'], true)
                => $this->pickNodeLinks($graph, ['workflow'], 2),
            in_array($signature, ['inspect extension', 'inspect extensions', 'verify extensions'], true)
                => $this->pickNodeLinks($graph, ['extension'], 2),
            default => [],
        };
    }

    /**
     * @param array<int,string> $types
     * @return array<int,array<string,string>>
     */
    private function pickNodeLinks(ApplicationGraph $graph, array $types, int $limit): array
    {
        $rows = [];

        foreach ($types as $type) {
            foreach ($graph->nodesByType($type) as $node) {
                $rows[] = [
                    'title' => $this->nodeLabel($node),
                    'href' => 'architecture-explorer.html?node=' . rawurlencode($node->id()),
                    'meta' => $node->type(),
                    'explainTarget' => $this->nodeExplainTarget($node),
                ];

                if (count($rows) === $limit) {
                    return $rows;
                }
            }
        }

        return $rows;
    }

    private function nodeLabel(GraphNode $node): string
    {
        $payload = $node->payload();

        return match ($node->type()) {
            'feature' => (string) ($payload['feature'] ?? $node->id()),
            'route' => (string) ($payload['signature'] ?? $node->id()),
            'event' => (string) ($payload['name'] ?? $node->id()),
            'job' => (string) ($payload['name'] ?? $node->id()),
            'schema' => (string) ($payload['path'] ?? $node->id()),
            'cache' => (string) ($payload['key'] ?? $node->id()),
            'workflow' => (string) ($payload['resource'] ?? $payload['name'] ?? $node->id()),
            'extension' => (string) ($payload['name'] ?? $node->id()),
            default => $node->id(),
        };
    }

    private function nodeExplainTarget(GraphNode $node): string
    {
        $payload = $node->payload();

        return match ($node->type()) {
            'feature' => 'feature:' . (string) ($payload['feature'] ?? $node->id()),
            'route' => 'route:' . (string) ($payload['signature'] ?? $node->id()),
            'event' => 'event:' . (string) ($payload['name'] ?? $node->id()),
            'job' => 'job:' . (string) ($payload['name'] ?? $node->id()),
            'schema' => 'schema:' . (string) ($payload['path'] ?? $node->id()),
            'workflow' => 'workflow:' . (string) ($payload['resource'] ?? $payload['name'] ?? $node->id()),
            'extension' => 'extension:' . (string) ($payload['name'] ?? $node->id()),
            default => '',
        };
    }

    /**
     * @return array<string,string>
     */
    private function exampleValues(ApplicationGraph $graph): array
    {
        $featureNode = $this->firstNode($graph, 'feature');
        $routeNode = $this->firstNode($graph, 'route');
        $eventNode = $this->firstNode($graph, 'event');
        $workflowNode = $this->firstNode($graph, 'workflow');
        $extensionNode = $this->firstNode($graph, 'extension');
        $schemaNode = $this->firstNode($graph, 'schema');
        $firstNode = $graph->nodes();
        $firstNode = $firstNode !== [] ? reset($firstNode) : null;

        $routeSignature = $routeNode instanceof GraphNode ? (string) (($routeNode->payload()['signature'] ?? '') ?: 'GET /posts') : 'GET /posts';
        [$routeMethod, $routePath] = $this->splitRouteSignature($routeSignature);

        return [
            'feature' => $featureNode instanceof GraphNode ? (string) (($featureNode->payload()['feature'] ?? '') ?: 'publish_post') : 'publish_post',
            'route_method' => $routeMethod,
            'route_path' => $routePath,
            'event' => $eventNode instanceof GraphNode ? (string) (($eventNode->payload()['name'] ?? '') ?: 'post.created') : 'post.created',
            'workflow' => $workflowNode instanceof GraphNode ? (string) (($workflowNode->payload()['resource'] ?? $workflowNode->payload()['name'] ?? '') ?: 'editorial') : 'editorial',
            'extension' => $extensionNode instanceof GraphNode ? (string) (($extensionNode->payload()['name'] ?? '') ?: 'example.extension') : 'example.extension',
            'pack' => 'example.extension.pack',
            'pipeline_stage' => 'auth',
            'node' => $firstNode instanceof GraphNode ? $firstNode->id() : 'feature:publish_post',
            'schema' => $schemaNode instanceof GraphNode ? (string) (($schemaNode->payload()['path'] ?? '') ?: 'app/features/publish_post/input.schema.json') : 'app/features/publish_post/input.schema.json',
        ];
    }

    private function firstNode(ApplicationGraph $graph, string $type): ?GraphNode
    {
        $nodes = $graph->nodesByType($type);
        $node = $nodes !== [] ? reset($nodes) : null;

        return $node instanceof GraphNode ? $node : null;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitRouteSignature(string $signature): array
    {
        $signature = trim($signature);
        if ($signature === '' || !str_contains($signature, ' ')) {
            return ['GET', '/'];
        }

        [$method, $path] = explode(' ', $signature, 2);

        return [strtoupper(trim($method)), trim($path)];
    }
}
