<?php

declare(strict_types=1);

namespace Foundry\Documentation;

use Foundry\Compiler\ApplicationGraph;
use Foundry\Support\ApiSurfaceRegistry;
use Foundry\Support\Json;
use Foundry\Support\Paths;

final class CliIndexPage
{
    public function __construct(
        private readonly Paths $paths,
        private readonly ApiSurfaceRegistry $apiSurfaceRegistry,
    ) {}

    public function content(ApplicationGraph $graph): string
    {
        $catalog = new CommandCatalog($this->paths, $this->apiSurfaceRegistry);
        $dataJson = htmlspecialchars(Json::encode($catalog->data($graph), true), ENT_NOQUOTES);

        $template = <<<'HTML'
<section class="cli-index" id="cli-index">
  <div class="cli-index__header">
    <h1>Interactive CLI Index</h1>
    <p>Search and filter the full Foundry CLI contract from the same metadata registry that powers <code>help --json</code>. This index is static and deterministic: it does not execute commands.</p>
  </div>

  <div class="cli-index__toolbar">
    <label class="cli-index__field">
      <span>Search</span>
      <input id="cli-index-search" type="search" placeholder="Search command name, description, or category">
    </label>
    <label class="cli-index__field">
      <span>Category</span>
      <select id="cli-index-category">
        <option value="">All categories</option>
      </select>
    </label>
    <label class="cli-index__field">
      <span>Pipeline Stage</span>
      <select id="cli-index-pipeline">
        <option value="">All pipeline stages</option>
      </select>
    </label>
    <label class="cli-index__field">
      <span>Extension</span>
      <select id="cli-index-extension">
        <option value="">All extensions</option>
      </select>
    </label>
    <label class="cli-index__field">
      <span>Command Type</span>
      <select id="cli-index-command-type">
        <option value="">All command types</option>
      </select>
    </label>
  </div>

  <p class="cli-index__summary" id="cli-index-summary">Loading CLI index...</p>

  <div class="cli-index__layout">
    <aside class="cli-index__list-panel">
      <div id="cli-index-list"></div>
    </aside>
    <article class="cli-index__details" id="cli-index-details">
      <h2>Command Details</h2>
      <p>Select a command to inspect its category, usage signature, related docs, command playground view, explain targets, and applicable filters.</p>
    </article>
  </div>
</section>

<script id="cli-index-data" type="application/json">__CLI_INDEX_DATA__</script>
<script>
(() => {
  const dataElement = document.getElementById('cli-index-data');
  const listElement = document.getElementById('cli-index-list');
  const detailsElement = document.getElementById('cli-index-details');
  const summaryElement = document.getElementById('cli-index-summary');
  const searchInput = document.getElementById('cli-index-search');
  const categorySelect = document.getElementById('cli-index-category');
  const pipelineSelect = document.getElementById('cli-index-pipeline');
  const extensionSelect = document.getElementById('cli-index-extension');
  const commandTypeSelect = document.getElementById('cli-index-command-type');

  if (!dataElement || !listElement || !detailsElement || !summaryElement || !searchInput || !categorySelect || !pipelineSelect || !extensionSelect || !commandTypeSelect) {
    return;
  }

  const payload = JSON.parse(dataElement.textContent || '{}');
  const commands = Array.isArray(payload.commands) ? payload.commands : [];
  const filters = payload.filters && typeof payload.filters === 'object' ? payload.filters : {};
  const commandBySignature = new Map(commands.map((command) => [command.signature, command]));
  const initialSignature = initialCommandFromLocation();

  populateSelect(categorySelect, filters.categories || []);
  populateSelect(pipelineSelect, filters.pipeline_stages || []);
  populateSelect(extensionSelect, filters.extensions || []);
  populateSelect(commandTypeSelect, filters.command_types || []);

  const state = {
    search: '',
    category: '',
    pipelineStage: '',
    extension: '',
    commandType: '',
    selectedSignature: commandBySignature.has(initialSignature)
      ? initialSignature
      : (commands[0] ? String(commands[0].signature || '') : null)
  };

  searchInput.addEventListener('input', () => {
    state.search = String(searchInput.value || '').trim().toLowerCase();
    render();
  });

  categorySelect.addEventListener('change', () => {
    state.category = String(categorySelect.value || '');
    render();
  });

  pipelineSelect.addEventListener('change', () => {
    state.pipelineStage = String(pipelineSelect.value || '');
    render();
  });

  extensionSelect.addEventListener('change', () => {
    state.extension = String(extensionSelect.value || '');
    render();
  });

  commandTypeSelect.addEventListener('change', () => {
    state.commandType = String(commandTypeSelect.value || '');
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

  function populateSelect(select, values) {
    const items = Array.isArray(values) ? values : [];
    items.forEach((value) => {
      const option = document.createElement('option');
      option.value = String(value || '');
      option.textContent = String(value || '');
      select.appendChild(option);
    });
  }

  function matchesFilters(command) {
    if (state.category !== '' && String(command.category || '') !== state.category) {
      return false;
    }

    if (state.commandType !== '' && String(command.commandType || '') !== state.commandType) {
      return false;
    }

    if (state.pipelineStage !== '' && !arrayIncludes(command.pipelineStages, state.pipelineStage)) {
      return false;
    }

    if (state.extension !== '' && !arrayIncludes(command.extensions, state.extension)) {
      return false;
    }

    if (state.search === '') {
      return true;
    }

    return String(command.searchText || '').includes(state.search);
  }

  function renderList(filteredCommands, selectedCommand) {
    if (filteredCommands.length === 0) {
      listElement.innerHTML = '<p class="cli-index__empty">No commands match the current filters.</p>';
      return;
    }

    listElement.innerHTML = filteredCommands.map((command) => {
      const isSelected = selectedCommand && selectedCommand.signature === command.signature;
      const selectedClass = isSelected ? ' is-selected' : '';
      const badges = [
        badge(command.category || ''),
        badge(command.commandType || ''),
        badge(command.stability || '')
      ].join('');

      return ''
        + '<button type="button" class="cli-index__list-item' + selectedClass + '" data-command-signature="' + escapeHtml(command.signature) + '">'
        + '<span class="cli-index__list-signature">' + escapeHtml(command.signature) + '</span>'
        + '<span class="cli-index__list-description">' + escapeHtml(command.description || '') + '</span>'
        + '<span class="cli-index__badges">' + badges + '</span>'
        + '</button>';
    }).join('');
  }

  function renderDetails(command) {
    if (!command) {
      detailsElement.innerHTML = '<h2>Command Details</h2><p>Select a command to inspect its category, usage signature, related docs, command playground view, explain targets, and applicable filters.</p>';
      return;
    }

    detailsElement.innerHTML = ''
      + '<h2>' + escapeHtml(command.signature) + '</h2>'
      + '<p class="cli-index__meta">'
      + '<span>' + escapeHtml(command.category || '') + '</span>'
      + '<span>' + escapeHtml(command.commandType || '') + '</span>'
      + '<span>' + escapeHtml(command.stability || 'internal') + '</span>'
      + '</p>'
      + '<p>' + escapeHtml(command.description || '') + '</p>'
      + '<h3>Usage Signature</h3>'
      + '<pre class="cli-index__pre"><code>' + escapeHtml(command.usage || '') + '</code></pre>'
      + '<p><a href="' + escapeHtml(command.playgroundHref || '#') + '">Open in Command Playground</a></p>'
      + '<h3>Detailed Documentation</h3>'
      + renderLinkList(command.docs, 'No related docs linked.')
      + '<h3>Related Explain Targets</h3>'
      + renderExplainTargets(command.explainTargets)
      + '<h3>Related Commands</h3>'
      + renderRelatedCommands(command.relatedCommands)
      + '<h3>Applicable Pipeline Stages</h3>'
      + renderTagList(command.pipelineStages, command.supportsPipelineStageFilter, 'This command does not expose pipeline-stage filtering.')
      + '<h3>Applicable Extensions</h3>'
      + renderTagList(command.extensions, command.supportsExtensionFilter, 'This command does not expose extension filtering.')
      + '<h3>Related Graph Nodes</h3>'
      + renderLinkList(command.relatedNodes, 'No related graph nodes are attached to this command.');
  }

  function renderSummary(count, selectedCommand) {
    const selectedText = selectedCommand ? ' Selected: ' + selectedCommand.signature + '.' : '';
    summaryElement.textContent = 'Showing ' + count + ' commands.' + selectedText;
  }

  function renderLinkList(items, emptyMessage) {
    if (!Array.isArray(items) || items.length === 0) {
      return '<p class="cli-index__muted">' + escapeHtml(emptyMessage) + '</p>';
    }

    return '<ul class="cli-index__link-list">' + items.map((item) => {
      const title = escapeHtml(item.title || item.label || '');
      const meta = item.meta ? ' <span class="cli-index__muted">(' + escapeHtml(item.meta) + ')</span>' : '';

      return '<li><a href="' + escapeHtml(item.href || '#') + '">' + title + '</a>' + meta + '</li>';
    }).join('') + '</ul>';
  }

  function renderExplainTargets(items) {
    if (!Array.isArray(items) || items.length === 0) {
      return '<p class="cli-index__muted">No explain targets are attached to this command.</p>';
    }

    return '<ul class="cli-index__code-list">' + items.map((item) => {
      const title = escapeHtml(item.title || item.target || '');
      const meta = item.meta ? '<span class="cli-index__muted"> ' + escapeHtml(item.meta) + '</span>' : '';
      const link = item.href ? '<a href="' + escapeHtml(item.href) + '"><code>' + title + '</code></a>' : '<code>' + title + '</code>';

      return '<li>' + link + meta + '</li>';
    }).join('') + '</ul>';
  }

  function renderRelatedCommands(items) {
    if (!Array.isArray(items) || items.length === 0) {
      return '<p class="cli-index__muted">No closely related commands were published for this command.</p>';
    }

    return '<div class="cli-index__related-buttons">' + items.map((item) => {
      return '<button type="button" data-related-command="' + escapeHtml(item.signature || '') + '">' + escapeHtml(item.signature || '') + '</button>';
    }).join('') + '</div>';
  }

  function renderTagList(items, supported, emptyMessage) {
    if (!supported) {
      return '<p class="cli-index__muted">' + escapeHtml(emptyMessage) + '</p>';
    }

    if (!Array.isArray(items) || items.length === 0) {
      return '<p class="cli-index__muted">No values are currently published for this filter.</p>';
    }

    return '<div class="cli-index__tag-list">' + items.map((item) => badge(item)).join('') + '</div>';
  }

  function badge(value) {
    return '<span class="cli-index__badge">' + escapeHtml(value) + '</span>';
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

  function arrayIncludes(values, target) {
    return Array.isArray(values) && values.some((value) => String(value || '') === target);
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
.cli-index {
  display: grid;
  gap: 1.5rem;
}

.cli-index__header h1 {
  margin-bottom: 0.35rem;
}

.cli-index__header p {
  margin: 0;
  color: #5b645f;
}

.cli-index__toolbar {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
}

.cli-index__field {
  display: grid;
  gap: 0.35rem;
}

.cli-index__field input,
.cli-index__field select {
  border: 1px solid #cabaa8;
  border-radius: 0.8rem;
  background: #fffdfa;
  padding: 0.7rem 0.9rem;
  font: inherit;
}

.cli-index__summary {
  margin: 0;
  color: #5b645f;
  font-size: 0.95rem;
}

.cli-index__layout {
  display: grid;
  grid-template-columns: minmax(20rem, 28rem) minmax(0, 1fr);
  gap: 1.25rem;
}

.cli-index__list-panel,
.cli-index__details {
  border: 1px solid #d9ccbc;
  border-radius: 1rem;
  background: #fffdfa;
  box-shadow: 0 14px 40px rgba(68, 49, 27, 0.07);
}

.cli-index__list-panel {
  max-height: 72rem;
  overflow: auto;
  padding: 1rem;
}

.cli-index__details {
  padding: 1.25rem;
}

.cli-index__list-item {
  display: grid;
  gap: 0.45rem;
  width: 100%;
  margin-top: 0.75rem;
  border: 1px solid #e3d8cb;
  border-radius: 0.95rem;
  background: #fff;
  padding: 0.9rem;
  text-align: left;
  cursor: pointer;
}

.cli-index__list-item:hover {
  border-color: #9d6f34;
}

.cli-index__list-item.is-selected {
  border-color: #9d6f34;
  background: #fbf0e2;
}

.cli-index__list-signature {
  font-weight: 700;
  color: #402c16;
}

.cli-index__list-description {
  color: #5b645f;
  font-size: 0.95rem;
}

.cli-index__badges,
.cli-index__meta,
.cli-index__tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.cli-index__details h2,
.cli-index__details h3 {
  margin-top: 0;
}

.cli-index__badge,
.cli-index__meta span {
  border-radius: 999px;
  background: #f3eadf;
  color: #563a16;
  padding: 0.25rem 0.7rem;
  font-size: 0.85rem;
}

.cli-index__muted {
  color: #5b645f;
}

.cli-index__pre {
  overflow: auto;
  border-radius: 0.85rem;
  background: #f7f1ea;
  padding: 0.9rem 1rem;
}

.cli-index__link-list,
.cli-index__code-list {
  padding-left: 1.1rem;
}

.cli-index__related-buttons {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.cli-index__related-buttons button {
  border: 1px solid #d2c2b3;
  border-radius: 999px;
  background: #fff;
  padding: 0.45rem 0.8rem;
  font: inherit;
  cursor: pointer;
}

.cli-index__related-buttons button:hover {
  border-color: #9d6f34;
  background: #fbf0e2;
}

.cli-index__empty {
  margin: 0;
  color: #5b645f;
}

@media (max-width: 980px) {
  .cli-index__layout {
    grid-template-columns: 1fr;
  }

  .cli-index__list-panel {
    max-height: none;
  }
}
</style>
HTML;

        return str_replace('__CLI_INDEX_DATA__', $dataJson, $template);
    }
}
