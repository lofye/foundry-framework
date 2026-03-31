<?php

declare(strict_types=1);

namespace Foundry\Generate;

use Foundry\Support\FoundryError;

final class GenerationPlanner
{
    public function __construct(private readonly GeneratorRegistry $registry) {}

    public function plan(GenerationContextPacket $context): GenerationPlan
    {
        $ranked = [];

        foreach ($this->registry->all() as $generator) {
            if (!$generator->generator->supports($context->model, $context->intent)) {
                continue;
            }

            $ranked[] = [
                'generator' => $generator,
                'score' => $this->score($generator, $context),
            ];
        }

        if ($ranked === []) {
            throw new FoundryError(
                'GENERATE_GENERATOR_NOT_FOUND',
                'not_found',
                [
                    'mode' => $context->intent->mode,
                    'target' => $context->intent->target,
                    'packs' => $context->suggestedPacks,
                ],
                'No generator could plan that intent safely.',
            );
        }

        usort($ranked, function (array $left, array $right): int {
            /** @var RegisteredGenerator $leftGenerator */
            $leftGenerator = $left['generator'];
            /** @var RegisteredGenerator $rightGenerator */
            $rightGenerator = $right['generator'];

            return ((int) ($right['score'] ?? 0) <=> (int) ($left['score'] ?? 0))
                ?: strcmp($leftGenerator->origin, $rightGenerator->origin)
                ?: strcmp((string) ($leftGenerator->extension ?? ''), (string) ($rightGenerator->extension ?? ''))
                ?: strcmp($leftGenerator->id, $rightGenerator->id);
        });

        $topScore = (int) ($ranked[0]['score'] ?? 0);
        $top = array_values(array_filter(
            $ranked,
            static fn(array $row): bool => (int) ($row['score'] ?? 0) === $topScore,
        ));

        if ($top === []) {
            throw new FoundryError(
                'GENERATE_GENERATOR_NOT_FOUND',
                'not_found',
                ['mode' => $context->intent->mode],
                'No generator could plan that intent safely.',
            );
        }

        $plans = [];
        foreach ($top as $row) {
            /** @var RegisteredGenerator $registered */
            $registered = $row['generator'];
            $plan = $registered->generator->plan($context->model, $context->intent);
            $plans[] = $this->decoratePlan($plan, $registered);
        }

        if (count($plans) === 1) {
            return $plans[0];
        }

        return GenerationPlan::merge($plans);
    }

    private function score(RegisteredGenerator $generator, GenerationContextPacket $context): int
    {
        $score = $generator->priority;
        $subject = $context->model->subject;
        $subjectExtension = trim((string) ($subject['extension'] ?? ''));
        $subjectKind = trim((string) ($subject['kind'] ?? ''));

        if ($generator->origin === 'pack') {
            $score += 20;
        }

        if ($generator->extension !== null && in_array($generator->extension, $context->intent->packHints, true)) {
            $score += 100;
        }

        if ($generator->extension !== null && $subjectExtension !== '' && $generator->extension === $subjectExtension) {
            $score += 80;
        }

        if ($generator->origin === 'pack' && $subjectKind === 'pack') {
            $score += 60;
        }

        if ($generator->origin === 'core' && $subjectKind === 'feature') {
            $score += 15;
        }

        return $score;
    }

    private function decoratePlan(GenerationPlan $plan, RegisteredGenerator $registered): GenerationPlan
    {
        $actions = [];
        foreach ($plan->actions as $action) {
            $action['origin'] = $registered->origin;
            $action['extension'] = $registered->extension;
            $actions[] = $action;
        }

        return new GenerationPlan(
            actions: $actions,
            affectedFiles: $plan->affectedFiles,
            risks: $plan->risks,
            validations: $plan->validations,
            origin: $registered->origin,
            generatorId: $registered->id,
            extension: $registered->extension,
            metadata: $plan->metadata,
        );
    }
}
