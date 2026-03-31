<?php

declare(strict_types=1);

namespace Vendor\Blog;

use Foundry\Explain\ExplainModel;
use Foundry\Generate\FeaturePlanBuilder;
use Foundry\Generate\GenerationPlan;
use Foundry\Generate\Generator;
use Foundry\Generate\Intent;

final class BlogPostGenerator implements Generator
{
    #[\Override]
    public function supports(ExplainModel $model, Intent $intent): bool
    {
        if ($intent->mode !== 'new') {
            return false;
        }

        if (in_array('foundry/blog', $intent->packHints, true)) {
            return true;
        }

        $tokens = $intent->tokens();

        return in_array('blog', $tokens, true) && (in_array('post', $tokens, true) || in_array('posts', $tokens, true));
    }

    #[\Override]
    public function plan(ExplainModel $model, Intent $intent): GenerationPlan
    {
        $feature = 'blog_post_notes';
        $requiredTests = ['contract', 'feature', 'auth'];

        return new GenerationPlan(
            actions: FeaturePlanBuilder::scaffoldActions($feature, $requiredTests, (string) ($model->subject['id'] ?? 'pack:foundry/blog')),
            affectedFiles: FeaturePlanBuilder::predictedFiles($feature, $requiredTests),
            risks: ['Uses the installed `foundry/blog` pack generator to scaffold a blog-specific feature.'],
            validations: ['compile_graph', 'verify_graph', 'verify_contracts', 'verify_feature'],
            origin: 'pack',
            generatorId: 'generate blog-post',
            extension: 'foundry/blog',
            metadata: [
                'execution' => [
                    'strategy' => 'feature_definition',
                    'feature_definition' => [
                        'feature' => $feature,
                        'description' => 'Create blog post notes through the blog pack.',
                        'kind' => 'http',
                        'owners' => ['content'],
                        'route' => [
                            'method' => 'POST',
                            'path' => '/blog/posts/{id}/notes',
                        ],
                        'input' => [
                            'fields' => [
                                'id' => ['type' => 'string', 'required' => true],
                                'note' => ['type' => 'string', 'required' => true],
                            ],
                        ],
                        'output' => [
                            'fields' => [
                                'id' => ['type' => 'string', 'required' => true],
                                'status' => ['type' => 'string', 'required' => true],
                            ],
                        ],
                        'auth' => [
                            'required' => true,
                            'strategies' => ['bearer'],
                            'permissions' => ['blog.posts.note'],
                        ],
                        'database' => [
                            'reads' => ['blog_posts'],
                            'writes' => ['blog_post_notes'],
                            'transactions' => 'required',
                            'queries' => ['blog_post_notes'],
                        ],
                        'cache' => [
                            'reads' => [],
                            'writes' => [],
                            'invalidate' => ['blog_posts:list'],
                        ],
                        'events' => [
                            'emit' => ['blog.post.noted'],
                            'subscribe' => [],
                        ],
                        'jobs' => [
                            'dispatch' => [],
                        ],
                        'tests' => [
                            'required' => $requiredTests,
                        ],
                        'llm' => [
                            'editable' => true,
                            'risk_level' => 'medium',
                            'notes_file' => 'prompts.md',
                        ],
                    ],
                ],
                'feature' => $feature,
            ],
        );
    }
}
