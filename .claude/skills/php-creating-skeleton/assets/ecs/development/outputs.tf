output "health_url" {
  value       = module.service.health_url
  description = "Public health endpoint polled by the post deploy smoke test."
}

output "service_name" {
  value       = module.service.service_name
  description = "Name of the <service-name> ECS service."
}

output "facts_queue_arn" {
  value       = module.service_messaging.facts_queue_arn
  description = "ARN of the <service-name> facts SQS queue."
}

output "events_topic_arn" {
  value       = module.service_messaging.events_topic_arn
  description = "ARN of the <service-name>-events SNS topic consumed by downstream contexts."
}

output "task_definition_arn" {
  value       = module.service.task_definition_arn
  description = "ARN of the Terraform-managed task definition revision."
}

output "edge_target_group_arn" {
  value       = module.service.edge_target_group_arn
  description = "ARN of the target group attached to the public edge ALB."
}

output "internal_target_group_arn" {
  value       = module.service.internal_target_group_arn
  description = "ARN of the target group attached to the internal ALB."
}
