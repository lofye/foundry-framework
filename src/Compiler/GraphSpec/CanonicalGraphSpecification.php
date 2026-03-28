<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

use Foundry\Compiler\GraphCompiler;
use Foundry\Compiler\IR\AdminResourceNode;
use Foundry\Compiler\IR\ApiResourceNode;
use Foundry\Compiler\IR\AuthNode;
use Foundry\Compiler\IR\BillingNode;
use Foundry\Compiler\IR\CacheNode;
use Foundry\Compiler\IR\ContextManifestNode;
use Foundry\Compiler\IR\EventNode;
use Foundry\Compiler\IR\ExecutionPlanNode;
use Foundry\Compiler\IR\FeatureNode;
use Foundry\Compiler\IR\FormDefinitionNode;
use Foundry\Compiler\IR\GuardNode;
use Foundry\Compiler\IR\InspectUiNode;
use Foundry\Compiler\IR\InterceptorNode;
use Foundry\Compiler\IR\JobNode;
use Foundry\Compiler\IR\ListingConfigNode;
use Foundry\Compiler\IR\LocaleBundleNode;
use Foundry\Compiler\IR\NotificationNode;
use Foundry\Compiler\IR\OrchestrationNode;
use Foundry\Compiler\IR\PermissionNode;
use Foundry\Compiler\IR\PipelineStageNode;
use Foundry\Compiler\IR\PolicyNode;
use Foundry\Compiler\IR\QueryNode;
use Foundry\Compiler\IR\RateLimitNode;
use Foundry\Compiler\IR\ResourceNode;
use Foundry\Compiler\IR\RoleNode;
use Foundry\Compiler\IR\RouteNode;
use Foundry\Compiler\IR\SchemaNode;
use Foundry\Compiler\IR\SchedulerNode;
use Foundry\Compiler\IR\SearchIndexNode;
use Foundry\Compiler\IR\StarterKitNode;
use Foundry\Compiler\IR\StreamNode;
use Foundry\Compiler\IR\TestNode;
use Foundry\Compiler\IR\UploadProfileNode;
use Foundry\Compiler\IR\WebhookNode;
use Foundry\Compiler\IR\WorkflowNode;

final class CanonicalGraphSpecification
{
    private static ?GraphSpecification $instance = null;

    public static function instance(): GraphSpecification
    {
        if (self::$instance instanceof GraphSpecification) {
            return self::$instance;
        }

        $currentGraphVersion = GraphCompiler::GRAPH_VERSION;
        $compatibility = self::compatibility($currentGraphVersion);

        $nodeTypes = [];
        foreach ([
            self::node('feature', FeatureNode::class, 'structural', 'both', ['feature', 'kind'], ['route', 'auth', 'database', 'cache', 'events', 'jobs', 'tests', 'permissions', 'queries', 'scheduler', 'webhooks', 'context_manifest', 'rate_limit', 'csrf', 'resource', 'listing', 'uploads', 'ui', 'action_class', 'source_files'], ['feature' => 'string', 'kind' => 'string'], true, true, $compatibility, true, true),
            self::node('route', RouteNode::class, 'interface', 'runtime', ['method', 'path', 'signature', 'features'], [], ['method' => 'string', 'path' => 'string', 'signature' => 'string', 'features' => 'array'], true, true, $compatibility, true, true),
            self::node('schema', SchemaNode::class, 'contract', 'both', ['path', 'role'], ['feature', 'notification', 'document'], ['path' => 'string', 'role' => 'string'], false, true, $compatibility),
            self::node('permission', PermissionNode::class, 'policy', 'runtime', ['name', 'features', 'declared_by', 'referenced_by'], [], ['name' => 'string', 'features' => 'array', 'declared_by' => 'array', 'referenced_by' => 'array'], false, true, $compatibility),
            self::node('query', QueryNode::class, 'integration', 'runtime', ['feature', 'name', 'defined', 'referenced'], ['sql', 'placeholders'], ['feature' => 'string', 'name' => 'string', 'defined' => 'bool', 'referenced' => 'bool'], false, true, $compatibility, true, true),
            self::node('event', EventNode::class, 'integration', 'both', ['name', 'emitters', 'subscribers', 'schemas'], [], ['name' => 'string', 'emitters' => 'array', 'subscribers' => 'array', 'schemas' => 'array'], false, true, $compatibility, true, true),
            self::node('job', JobNode::class, 'integration', 'runtime', ['name', 'features', 'definitions'], [], ['name' => 'string', 'features' => 'array', 'definitions' => 'array'], true, true, $compatibility, true, true),
            self::node('cache', CacheNode::class, 'integration', 'runtime', ['key', 'features', 'entries', 'invalidated_by'], [], ['key' => 'string', 'features' => 'array', 'entries' => 'array', 'invalidated_by' => 'array'], false, true, $compatibility, true),
            self::node('scheduler', SchedulerNode::class, 'execution', 'runtime', ['feature', 'name', 'cron', 'job'], [], ['feature' => 'string', 'name' => 'string', 'cron' => 'string', 'job' => 'string'], true, true, $compatibility, true, true),
            self::node('webhook', WebhookNode::class, 'integration', 'runtime', ['feature', 'direction', 'name'], ['path', 'method', 'event', 'schema'], ['feature' => 'string', 'direction' => 'string', 'name' => 'string'], true, true, $compatibility, true, true),
            self::node('test', TestNode::class, 'observational', 'compile_time', ['feature', 'kind', 'name'], [], ['feature' => 'string', 'kind' => 'string', 'name' => 'string'], false, true, $compatibility, true),
            self::node('context_manifest', ContextManifestNode::class, 'observational', 'both', ['feature'], ['document'], ['feature' => 'string'], false, true, $compatibility, true),
            self::node('auth', AuthNode::class, 'policy', 'runtime', ['feature'], ['required', 'public', 'strategies', 'permissions'], ['feature' => 'string'], true, true, $compatibility, true, true),
            self::node('rate_limit', RateLimitNode::class, 'policy', 'runtime', ['feature'], ['strategy', 'bucket', 'cost', 'buckets', 'limits'], ['feature' => 'string'], true, true, $compatibility, true, true),
            self::node('pipeline_stage', PipelineStageNode::class, 'execution', 'runtime', ['name', 'order', 'priority', 'extension'], ['after_stage', 'before_stage'], ['name' => 'string', 'order' => 'int', 'priority' => 'int', 'extension' => 'string'], true, false, $compatibility, true, true),
            self::node('guard', GuardNode::class, 'policy', 'runtime', ['feature', 'type'], ['required', 'public', 'strategies', 'permission', 'strategy', 'bucket', 'cost', 'schema', 'mode'], ['feature' => 'string', 'type' => 'string'], true, true, $compatibility, true, true),
            self::node('interceptor', InterceptorNode::class, 'execution', 'runtime', ['id', 'stage', 'priority', 'dangerous'], [], ['id' => 'string', 'stage' => 'string', 'priority' => 'int', 'dangerous' => 'bool'], true, false, $compatibility, true, true),
            self::node('execution_plan', ExecutionPlanNode::class, 'execution', 'runtime', ['feature', 'stages', 'guards', 'interceptors', 'action_node', 'plan_version'], ['route_signature', 'route_node'], ['feature' => 'string', 'stages' => 'array', 'guards' => 'array', 'interceptors' => 'array', 'action_node' => 'string', 'plan_version' => 'int'], true, true, $compatibility, true, true),
            self::node('starter_kit', StarterKitNode::class, 'structural', 'compile_time', ['starter', 'version', 'features'], ['auth_mode', 'pipeline_defaults'], ['starter' => 'string', 'version' => 'int', 'features' => 'array'], false, true, $compatibility),
            self::node('resource', ResourceNode::class, 'resource', 'both', ['resource', 'version', 'style', 'fields', 'operations', 'feature_map', 'auth'], ['model'], ['resource' => 'string', 'version' => 'int', 'style' => 'string', 'fields' => 'array', 'operations' => 'array', 'feature_map' => 'array', 'auth' => 'array'], false, true, $compatibility, true),
            self::node('admin_resource', AdminResourceNode::class, 'resource', 'both', ['resource', 'version', 'columns', 'filters', 'bulk_actions', 'row_actions', 'feature_map'], [], ['resource' => 'string', 'version' => 'int', 'columns' => 'array', 'filters' => 'array', 'bulk_actions' => 'array', 'row_actions' => 'array', 'feature_map' => 'array'], false, true, $compatibility, true),
            self::node('upload_profile', UploadProfileNode::class, 'resource', 'runtime', ['profile', 'version', 'disk', 'visibility', 'allowed_mime_types', 'max_size_kb', 'ownership', 'feature_map'], [], ['profile' => 'string', 'version' => 'int', 'disk' => 'string', 'visibility' => 'string', 'allowed_mime_types' => 'array', 'max_size_kb' => 'int', 'ownership' => 'array', 'feature_map' => 'array'], false, true, $compatibility, true),
            self::node('listing_config', ListingConfigNode::class, 'resource', 'runtime', ['resource', 'version', 'search', 'filters', 'sort', 'pagination'], [], ['resource' => 'string', 'version' => 'int', 'search' => 'array', 'filters' => 'array', 'sort' => 'array', 'pagination' => 'array'], false, true, $compatibility, true),
            self::node('form_definition', FormDefinitionNode::class, 'resource', 'runtime', ['resource', 'intent', 'feature', 'fields'], [], ['resource' => 'string', 'intent' => 'string', 'feature' => 'string', 'fields' => 'array'], false, true, $compatibility, true),
            self::node('notification', NotificationNode::class, 'integration', 'runtime', ['notification', 'version', 'channel', 'queue', 'template', 'template_path', 'input_schema_path', 'input_schema', 'dispatch_features'], [], ['notification' => 'string', 'version' => 'int', 'channel' => 'string', 'queue' => 'string', 'template' => 'string', 'template_path' => 'string', 'input_schema_path' => 'string', 'input_schema' => 'array', 'dispatch_features' => 'array'], true, true, $compatibility, true, true),
            self::node('api_resource', ApiResourceNode::class, 'resource', 'runtime', ['resource', 'version', 'style', 'fields', 'auth', 'operations', 'feature_map', 'response_convention'], ['model'], ['resource' => 'string', 'version' => 'int', 'style' => 'string', 'fields' => 'array', 'auth' => 'array', 'operations' => 'array', 'feature_map' => 'array', 'response_convention' => 'array'], false, true, $compatibility, true),
            self::node('billing', BillingNode::class, 'integration', 'runtime', ['provider', 'version', 'plans', 'feature_map', 'webhook_signing_secret_env'], [], ['provider' => 'string', 'version' => 'int', 'plans' => 'array', 'feature_map' => 'array', 'webhook_signing_secret_env' => 'string'], true, true, $compatibility, true, true),
            self::node('workflow', WorkflowNode::class, 'execution', 'runtime', ['resource', 'version', 'states', 'transitions'], [], ['resource' => 'string', 'version' => 'int', 'states' => 'array', 'transitions' => 'array'], true, true, $compatibility, true, true),
            self::node('orchestration', OrchestrationNode::class, 'execution', 'runtime', ['name', 'version', 'steps'], [], ['name' => 'string', 'version' => 'int', 'steps' => 'array'], true, true, $compatibility, true, true),
            self::node('search_index', SearchIndexNode::class, 'integration', 'runtime', ['index', 'version', 'adapter', 'resource', 'source', 'fields', 'filters'], [], ['index' => 'string', 'version' => 'int', 'adapter' => 'string', 'resource' => 'string', 'source' => 'array', 'fields' => 'array', 'filters' => 'array'], false, true, $compatibility, true),
            self::node('stream', StreamNode::class, 'integration', 'runtime', ['stream', 'version', 'transport', 'route', 'auth', 'publish_features', 'payload_schema'], [], ['stream' => 'string', 'version' => 'int', 'transport' => 'string', 'route' => 'array', 'auth' => 'array', 'publish_features' => 'array', 'payload_schema' => 'array'], true, true, $compatibility, true, true),
            self::node('locale_bundle', LocaleBundleNode::class, 'resource', 'runtime', ['bundle', 'version', 'default', 'locales', 'translation_paths'], [], ['bundle' => 'string', 'version' => 'int', 'default' => 'string', 'locales' => 'array', 'translation_paths' => 'array'], false, false, $compatibility),
            self::node('role', RoleNode::class, 'policy', 'runtime', ['set', 'role', 'permissions'], [], ['set' => 'string', 'role' => 'string', 'permissions' => 'array'], false, true, $compatibility),
            self::node('policy', PolicyNode::class, 'policy', 'runtime', ['policy', 'resource', 'rules'], [], ['policy' => 'string', 'resource' => 'string', 'rules' => 'array'], false, true, $compatibility),
            self::node('inspect_ui', InspectUiNode::class, 'observational', 'runtime', ['name', 'version', 'enabled', 'base_path', 'require_auth', 'sections'], [], ['name' => 'string', 'version' => 'int', 'enabled' => 'bool', 'base_path' => 'string', 'require_auth' => 'bool', 'sections' => 'array'], false, false, $compatibility, true),
        ] as $definition) {
            $nodeTypes[$definition->type] = $definition;
        }

        $edgeTypes = [];
        foreach ([
            self::edge('feature_to_route', 'ownership', ['feature'], ['route'], 'many_to_many', roles: ['ownership']),
            self::edge('feature_to_input_schema', 'ownership', ['feature'], ['schema'], 'one_to_many', roles: ['ownership', 'dependency']),
            self::edge('feature_to_output_schema', 'ownership', ['feature'], ['schema'], 'one_to_many', roles: ['ownership', 'dependency']),
            self::edge('feature_to_permission', 'dependency', ['feature'], ['permission'], 'many_to_many', roles: ['dependency']),
            self::edge('feature_to_query', 'dependency', ['feature'], ['query'], 'one_to_many', roles: ['dependency']),
            self::edge('feature_to_event_emit', 'publication', ['feature'], ['event'], 'many_to_many', roles: ['publication']),
            self::edge('feature_to_event_subscribe', 'publication', ['feature'], ['event'], 'many_to_many', roles: ['publication']),
            self::edge('serves', 'ownership', ['feature'], ['route'], 'many_to_many', roles: ['ownership']),
            self::edge('emits', 'publication', ['feature'], ['event'], 'many_to_many', roles: ['publication']),
            self::edge('subscribes', 'publication', ['route'], ['event'], 'many_to_many', roles: ['publication']),
            self::edge('event_publisher_to_subscriber', 'publication', ['feature'], ['feature'], 'many_to_many', true, ['event'], ['event' => 'string'], ['publication', 'observational']),
            self::edge('feature_to_job_dispatch', 'execution', ['feature'], ['job'], 'many_to_many', roles: ['execution', 'dependency']),
            self::edge('feature_to_cache_invalidation', 'invalidation', ['feature'], ['cache'], 'many_to_many', roles: ['invalidation']),
            self::edge('feature_to_scheduler_task', 'execution', ['feature'], ['scheduler'], 'one_to_many', roles: ['execution', 'ownership']),
            self::edge('feature_to_webhook', 'ownership', ['feature'], ['webhook'], 'one_to_many', roles: ['ownership']),
            self::edge('feature_to_test', 'ownership', ['feature'], ['test'], 'one_to_many', roles: ['ownership', 'observational']),
            self::edge('feature_to_context_manifest', 'ownership', ['feature'], ['context_manifest'], 'one_to_one', roles: ['ownership', 'observational']),
            self::edge('feature_to_auth_config', 'ownership', ['feature'], ['auth'], 'one_to_one', roles: ['ownership', 'execution']),
            self::edge('feature_to_rate_limit', 'ownership', ['feature'], ['rate_limit'], 'one_to_one', roles: ['ownership', 'execution']),
            self::edge('feature_to_execution_plan', 'execution', ['feature'], ['execution_plan'], 'one_to_one', roles: ['ownership', 'execution']),
            self::edge('execution_plan_to_feature_action', 'execution', ['execution_plan'], ['feature'], 'one_to_one', roles: ['execution']),
            self::edge('route_to_execution_plan', 'execution', ['route'], ['execution_plan'], 'one_to_one', roles: ['execution']),
            self::edge('execution_plan_to_stage', 'execution', ['execution_plan'], ['pipeline_stage'], 'many_to_many', roles: ['execution']),
            self::edge('execution_plan_to_guard', 'execution', ['execution_plan'], ['guard'], 'many_to_many', roles: ['execution']),
            self::edge('execution_plan_to_interceptor', 'execution', ['execution_plan'], ['interceptor'], 'many_to_many', true, ['stage'], ['stage' => 'string'], ['execution', 'observational']),
            self::edge('feature_to_guard', 'execution', ['feature'], ['guard'], 'one_to_many', roles: ['ownership', 'execution']),
            self::edge('guard_to_pipeline_stage', 'execution', ['guard'], ['pipeline_stage'], 'many_to_many', roles: ['execution']),
            self::edge('pipeline_stage_next', 'execution', ['pipeline_stage'], ['pipeline_stage'], 'one_to_one', roles: ['execution', 'structural']),
            self::edge('interceptor_to_pipeline_stage', 'execution', ['interceptor'], ['pipeline_stage'], 'many_to_many', roles: ['execution']),
            self::edge('resource_to_feature', 'ownership', ['resource'], ['feature'], 'many_to_many', true, ['operation'], ['operation' => 'string'], ['ownership']),
            self::edge('resource_to_form_definition', 'ownership', ['resource'], ['form_definition'], 'one_to_many', true, ['intent'], ['intent' => 'string'], ['ownership']),
            self::edge('form_definition_to_feature', 'ownership', ['form_definition'], ['feature'], 'one_to_one', roles: ['ownership']),
            self::edge('resource_to_listing_config', 'ownership', ['resource'], ['listing_config'], 'one_to_one', roles: ['ownership']),
            self::edge('listing_config_to_feature', 'ownership', ['listing_config'], ['feature'], 'many_to_many', true, ['operation'], ['operation' => 'string'], ['ownership']),
            self::edge('admin_resource_to_resource', 'ownership', ['admin_resource'], ['resource'], 'one_to_one', roles: ['ownership']),
            self::edge('admin_resource_to_feature', 'ownership', ['admin_resource'], ['feature'], 'many_to_many', true, ['operation'], ['operation' => 'string'], ['ownership']),
            self::edge('upload_profile_to_feature', 'ownership', ['upload_profile'], ['feature'], 'many_to_many', true, ['operation'], ['operation' => 'string'], ['ownership']),
            self::edge('starter_kit_to_feature', 'ownership', ['starter_kit'], ['feature'], 'many_to_many', roles: ['ownership']),
            self::edge('notification_to_input_schema', 'dependency', ['notification'], ['schema'], 'one_to_one', roles: ['dependency']),
            self::edge('feature_to_notification_dispatch', 'execution', ['feature'], ['notification'], 'many_to_many', roles: ['execution', 'ownership']),
            self::edge('notification_to_feature', 'ownership', ['notification'], ['feature'], 'many_to_many', roles: ['ownership']),
            self::edge('api_resource_to_resource', 'ownership', ['api_resource'], ['resource'], 'one_to_one', roles: ['ownership']),
            self::edge('resource_to_api_resource', 'ownership', ['resource'], ['api_resource'], 'one_to_one', roles: ['ownership']),
            self::edge('api_resource_to_feature', 'ownership', ['api_resource'], ['feature'], 'many_to_many', true, ['operation'], ['operation' => 'string'], ['ownership']),
            self::edge('feature_to_api_resource', 'ownership', ['feature'], ['api_resource'], 'many_to_many', true, ['operation'], ['operation' => 'string'], ['ownership']),
            self::edge('billing_to_feature', 'ownership', ['billing'], ['feature'], 'many_to_many', roles: ['ownership']),
            self::edge('feature_to_billing', 'ownership', ['feature'], ['billing'], 'many_to_many', roles: ['ownership']),
            self::edge('feature_to_workflow', 'ownership', ['feature'], ['workflow'], 'many_to_many', roles: ['ownership', 'execution']),
            self::edge('workflow_to_permission', 'dependency', ['workflow'], ['permission'], 'many_to_many', roles: ['dependency', 'execution']),
            self::edge('workflow_to_event_emit', 'publication', ['workflow'], ['event'], 'many_to_many', roles: ['publication', 'execution']),
            self::edge('orchestration_to_job', 'execution', ['orchestration'], ['job'], 'many_to_many', roles: ['execution']),
            self::edge('search_index_to_resource', 'ownership', ['search_index'], ['resource'], 'one_to_one', roles: ['ownership']),
            self::edge('resource_to_search_index', 'ownership', ['resource'], ['search_index'], 'one_to_one', roles: ['ownership']),
            self::edge('feature_to_stream', 'publication', ['feature'], ['stream'], 'many_to_many', roles: ['ownership', 'publication']),
            self::edge('stream_to_feature', 'publication', ['stream'], ['feature'], 'many_to_many', roles: ['publication']),
            self::edge('role_to_permission', 'dependency', ['role'], ['permission'], 'many_to_many', roles: ['dependency']),
            self::edge('policy_to_role', 'dependency', ['policy'], ['role'], 'many_to_many', roles: ['dependency']),
            self::edge('policy_to_permission', 'dependency', ['policy'], ['permission'], 'many_to_many', roles: ['dependency']),
        ] as $definition) {
            $edgeTypes[$definition->type] = $definition;
        }

        return self::$instance = new GraphSpecification(
            specVersion: 1,
            currentGraphVersion: $currentGraphVersion,
            supportedGraphVersions: [1, $currentGraphVersion],
            nodeTypes: $nodeTypes,
            edgeTypes: $edgeTypes,
            invariants: [
                'Node ids must be unique and type-recognized.',
                'Edge ids must be unique and connect recognized node types only.',
                'Execution plans, guards, and interceptors must be connected into execution topology.',
                'Route nodes must have at least one owning feature edge.',
                'Node and edge compatibility markers must include the active graph version.',
                'Unknown node or edge types must fail explicitly; silent coercion is forbidden.',
            ],
            migrationRules: [
                [
                    'from_graph_version' => 1,
                    'to_graph_version' => $currentGraphVersion,
                    'strategy' => 'deterministic_upgrade',
                    'notes' => [
                        'Add graph_spec_version and graph_metadata sections.',
                        'Normalize node compatibility markers to include the active graph version.',
                        'Preserve node and edge ids so observability and diffs remain stable.',
                    ],
                ],
            ],
        );
    }

    /**
     * @param array<int,int> $graphCompatibility
     * @param array<int,string> $requiredPayloadKeys
     * @param array<int,string> $optionalPayloadKeys
     * @param array<string,string> $payloadTypes
     */
    private static function node(
        string $type,
        string $className,
        string $semanticCategory,
        string $runtimeScope,
        array $requiredPayloadKeys,
        array $optionalPayloadKeys = [],
        array $payloadTypes = [],
        bool $participatesInExecutionTopology = false,
        bool $participatesInOwnershipTopology = false,
        array $graphCompatibility = [],
        bool $traceable = false,
        bool $profileable = false,
    ): NodeTypeDefinition {
        return new NodeTypeDefinition(
            type: $type,
            className: $className,
            semanticCategory: $semanticCategory,
            runtimeScope: $runtimeScope,
            requiredPayloadKeys: $requiredPayloadKeys,
            optionalPayloadKeys: $optionalPayloadKeys,
            payloadTypes: $payloadTypes,
            participatesInExecutionTopology: $participatesInExecutionTopology,
            participatesInOwnershipTopology: $participatesInOwnershipTopology,
            graphCompatibility: $graphCompatibility,
            traceable: $traceable,
            profileable: $profileable,
        );
    }

    /**
     * @param array<int,string> $allowedSourceTypes
     * @param array<int,string> $allowedTargetTypes
     * @param array<int,string> $requiredPayloadKeys
     * @param array<string,string> $payloadTypes
     * @param array<int,string> $roles
     */
    private static function edge(
        string $type,
        string $semanticClass,
        array $allowedSourceTypes,
        array $allowedTargetTypes,
        string $multiplicity,
        bool $payloadAllowed = false,
        array $requiredPayloadKeys = [],
        array $payloadTypes = [],
        array $roles = [],
    ): EdgeTypeDefinition {
        return new EdgeTypeDefinition(
            type: $type,
            semanticClass: $semanticClass,
            allowedSourceTypes: $allowedSourceTypes,
            allowedTargetTypes: $allowedTargetTypes,
            multiplicity: $multiplicity,
            payloadAllowed: $payloadAllowed,
            requiredPayloadKeys: $requiredPayloadKeys,
            payloadTypes: $payloadTypes,
            roles: $roles,
        );
    }

    /**
     * @return array<int,int>
     */
    private static function compatibility(int $currentGraphVersion): array
    {
        $versions = [1, $currentGraphVersion];
        sort($versions);

        return array_values(array_unique($versions));
    }
}
