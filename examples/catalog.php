<?php

declare(strict_types=1);

return [
    'official' => [
        [
            'slug' => 'hello-world',
            'title' => 'Hello World',
            'path' => 'examples/hello-world',
            'kind' => 'focused-app',
            'teaches' => [
                'feature structure',
                'schemas and context manifests',
                'doctor basics',
                'graph and pipeline inspection',
            ],
        ],
        [
            'slug' => 'blog-api',
            'title' => 'Blog API',
            'path' => 'examples/blog-api',
            'kind' => 'focused-app',
            'teaches' => [
                'route-per-feature HTTP design',
                'public versus protected endpoints',
                'route-param inspection',
                'event-backed write flows',
            ],
        ],
        [
            'slug' => 'workflow-events',
            'title' => 'Workflow And Events',
            'path' => 'examples/workflow-events',
            'kind' => 'focused-app',
            'teaches' => [
                'event emit and subscribe edges',
                'workflow definitions',
                'graph inspection by event and workflow',
                'doctor and verify loops',
            ],
        ],
    ],
    'reference' => [
        [
            'slug' => 'extensions-migrations',
            'title' => 'Extensions And Migrations',
            'path' => 'examples/extensions-migrations',
            'kind' => 'reference-pack',
            'teaches' => [
                'extension registration',
                'pack metadata',
                'definition migrations',
                'codemod dry runs',
            ],
        ],
        [
            'slug' => 'reference-blog',
            'title' => 'Reference Blog',
            'path' => 'examples/reference-blog',
            'kind' => 'reference-pack',
            'teaches' => [
                'full blog planning',
                'admin login flow',
                'RSS integration with spatie/laravel-feed',
                'copy-paste commands, prompts, and starter content',
            ],
        ],
    ],
    'framework' => [
        [
            'slug' => 'compiler-core',
            'title' => 'Compiler Core',
            'path' => 'examples/compiler-core',
            'kind' => 'framework-surface',
            'teaches' => [
                'compile outputs',
                'impact analysis',
                'migration flow',
            ],
        ],
        [
            'slug' => 'architecture-tools',
            'title' => 'Architecture Tools',
            'path' => 'examples/architecture-tools',
            'kind' => 'framework-surface',
            'teaches' => [
                'doctor',
                'graph visualize',
                'prompt context',
            ],
        ],
        [
            'slug' => 'execution-pipeline',
            'title' => 'Execution Pipeline',
            'path' => 'examples/execution-pipeline',
            'kind' => 'framework-surface',
            'teaches' => [
                'pipeline topology',
                'execution-plan inspection',
            ],
        ],
        [
            'slug' => 'app-scaffolding',
            'title' => 'App Scaffolding',
            'path' => 'examples/app-scaffolding',
            'kind' => 'framework-surface',
            'teaches' => [
                'starter generation',
                'resource definitions',
                'admin and upload scaffolding',
            ],
        ],
        [
            'slug' => 'integration-tooling',
            'title' => 'Integration Tooling',
            'path' => 'examples/integration-tooling',
            'kind' => 'framework-surface',
            'teaches' => [
                'notifications',
                'API resources',
                'generated docs',
                'test generation notes',
            ],
        ],
    ],
    'thresholds' => [
        'title' => 'Thresholds',
        'position' => 'real-app-reference',
        'summary' => 'Thresholds is the richer end-to-end reference application. Use the smaller examples to learn one idea at a time, then compare against Thresholds for a production-shaped app.',
    ],
];
