# development

> This stack assumes an environment foundation that already publishes as remote-state outputs everything the platform
> modules read through `foundation` (networking, the ECS cluster, the edge and internal load balancers with their HTTPS
> listeners, the DNS zones, and the database endpoint) plus the `alerts_sns_topic_arn` this stack reads by name.

Provisions the service data of the <service-name> service in the development environment. The service-level plumbing
(task definition and ECS service, target groups and listener rules on the foundation load balancers, SSM parameters, the
<service-name>.events messaging with its consumer queue and DLQ polled by the environment relay dispatcher, and the
service-scope CloudWatch alarms) is provided by the `service` and `service-messaging` platform modules of
`<platform-iac>`, referenced at `main`. This stack passes only the <service-name>-specific inputs. The environment
foundation is consumed through `terraform_remote_state` with key `<cloud-region>/environments/development.tfstate`, a
data source key that `-backend-config` cannot override, so a wrong value reads an empty state instead of failing the
plan. This stack keeps its own state at `services/<service-name>/<cloud-region>/development/<service-name>.tfstate`.

## Messaging

The `service-messaging` module has two independent sides, each behind a flag set in `main.tf`, both defaulting to
`true`. What the context does is a domain question the spec answers, so fill both flags from the spec and never from the
template default.

- `publishes_events`. Creates the `<service-name>-events` SNS topic that downstream contexts subscribe to. Set it to
  `false` when the context owns no `outbox_events` table and emits no domain fact. The `events_topic_arn` output is null
  in that case.
- `consumes_events`. Creates the consumer queue, its DLQ, the queue policy, the filtered SNS subscription, and the queue
  alarms. Set it to `false` when the context emits facts but projects none. The `facts_queue_arn` output is null in that
  case.

The queue subscribes to the service's own topic by default, and `dispatch_event_types` filters it by the fact names that
dispatch a use case. A context that consumes another context's channel passes `subscribed_topic_arn`, which is required
whenever `consumes_events` is `true` and `publishes_events` is `false`, because the module then creates no topic of its
own to subscribe to. Delivery belongs to the relay dispatcher of the environment relay task, which polls the queue and
posts each fact to the internal hostname of the consumer over the private zone, so this stack creates no EventBridge
resource and takes no endpoint or rate limit input.

## Inputs

| Name                                 | Type          | Description                                                                               |
|--------------------------------------|---------------|-------------------------------------------------------------------------------------------|
| `tags`                               | `map(string)` | Additional tags merged into resources on top of the provider default tags.                |
| `prefix`                             | `string`      | Resource name prefix and Project tag value. Required, no default (e.g., <vendor>).        |
| `image_tag`                          | `string`      | Tag of the <service-name> image referenced by the Terraform-managed task definition.      |
| `aws_region`                         | `string`      | AWS region where the <service-name> service is provisioned.                               |
| `domain_name`                        | `string`      | Public root domain managed in Route 53 (e.g., <root-domain>).                             |
| `ecr_registry`                       | `string`      | ECR registry hosting the <vendor>/<service-name> and <base-images>/nginx repositories.    |
| `state_bucket`                       | `string`      | S3 bucket name used for Terraform remote state references.                                |
| `database_name`                      | `string`      | MySQL schema name the <service-name> service connects to.                                 |
| `desired_count`                      | `number`      | Number of ECS tasks the service keeps running.                                            |
| `task_cpu_units`                     | `number`      | CPU units reserved by the Fargate task (256 equals 0.25 vCPU).                            |
| `task_memory_mb`                     | `number`      | Memory in MiB reserved by the Fargate task.                                               |
| `nginx_image_tag`                    | `string`      | Tag of the <base-images>/nginx sidecar image. Full version, never latest. Required.       |
| `capacity_provider`                  | `string`      | Fargate capacity provider used by the service.                                            |
| `<provider>_api_base_url`            | `string`      | Base URL of the <provider> API.                                                           |
| `edge_rule_priority`                 | `number`      | Priority of the <service-name> listener rule on the public edge ALB HTTPS listener.       |
| `internal_rule_priority`             | `number`      | Priority of the <service-name> listener rule on the internal ALB HTTPS listener.          |
| `enable_public_dns_record`           | `bool`        | Creates the public Route 53 alias record for the <service-name> hostname on the edge ALB. |
| `cpu_high_threshold_percent`         | `number`      | ECS service CPUUtilization percentage that triggers the high CPU alarm.                   |
| `<provider>_read_timeout_seconds`    | `number`      | Read timeout in seconds for <provider> API calls.                                         |
| `memory_high_threshold_percent`      | `number`      | ECS service MemoryUtilization percentage that triggers the high memory alarm.             |
| `<provider>_connect_timeout_seconds` | `number`      | Connect timeout in seconds for <provider> API calls.                                      |

## Outputs

| Name                        | Type     | Description                                                                 |
|-----------------------------|----------|-----------------------------------------------------------------------------|
| `health_url`                | `string` | Public health endpoint polled by the post deploy smoke test.                |
| `service_name`              | `string` | Name of the <service-name> ECS service.                                     |
| `facts_queue_arn`           | `string` | ARN of the <service-name> facts SQS queue.                                  |
| `events_topic_arn`          | `string` | ARN of the <service-name>-events SNS topic consumed by downstream contexts. |
| `task_definition_arn`       | `string` | ARN of the Terraform-managed task definition revision.                      |
| `edge_target_group_arn`     | `string` | ARN of the target group attached to the public edge ALB.                    |
| `internal_target_group_arn` | `string` | ARN of the target group attached to the internal ALB.                       |
