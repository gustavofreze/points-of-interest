variable "tags" {
  type        = map(string)
  description = "Additional tags merged into resources on top of the provider default tags."
  default     = {}
}

variable "prefix" {
  type        = string
  description = "Resource name prefix applied to every resource name and to the Project tag (e.g., <vendor>). Required on purpose. A default here silently brands the resources of whatever organization runs the stack, so terraform refusing to plan is the better outcome."
}

variable "image_tag" {
  type        = string
  description = "Tag of the <service-name> image referenced by the Terraform-managed task definition revision. Deploys register newer revisions outside Terraform, so this stays at the seeded bootstrap tag."
  default     = "bootstrap"
}

variable "aws_region" {
  type        = string
  description = "AWS region where the <service-name> service is provisioned."
  default     = "<cloud-region>"
}

variable "domain_name" {
  type        = string
  description = "Public root domain managed in Route 53 (e.g., <root-domain>)."
}

variable "ecr_registry" {
  type        = string
  description = "ECR registry hosting the <vendor>/<service-name> and <base-images>/nginx repositories (e.g., 123456789012.dkr.ecr.<cloud-region>.amazonaws.com)."
}

variable "state_bucket" {
  type        = string
  description = "S3 bucket name used for Terraform remote state references."
}

variable "database_name" {
  type        = string
  description = "MySQL schema name the <service-name> service connects to."
  default     = "<service_name>"
}

variable "desired_count" {
  type        = number
  description = "Number of ECS tasks the service keeps running."
  default     = 1
}

variable "task_cpu_units" {
  type        = number
  description = "CPU units reserved by the Fargate task (256 equals 0.25 vCPU)."
  default     = 256
}

variable "task_memory_mb" {
  type        = number
  description = "Memory in MiB reserved by the Fargate task."
  default     = 512
}

variable "nginx_image_tag" {
  type        = string
  description = "Tag of the <base-images>/nginx sidecar image, always a full versioned tag, never a moving tag, never latest. Required on purpose. A tag published in one registry resolves nowhere else, and the default would fail at task start instead of at plan time."
}

variable "capacity_provider" {
  type        = string
  description = "Fargate capacity provider used by the service. Development runs on FARGATE_SPOT."
  default     = "FARGATE_SPOT"

  validation {
    condition     = contains(["FARGATE", "FARGATE_SPOT"], var.capacity_provider)
    error_message = "capacity_provider must be FARGATE or FARGATE_SPOT."
  }
}

variable "<provider>_api_base_url" {
  type        = string
  description = "Base URL of the <provider> API. Development targets the sandbox."
  default     = "https://<provider-sandbox-host>/<api-version>"
}

variable "edge_rule_priority" {
  type        = number
  description = "Priority of the <service-name> listener rule on the public edge ALB HTTPS listener."
  default     = 100
}

variable "internal_rule_priority" {
  type        = number
  description = "Priority of the <service-name> listener rule on the internal ALB HTTPS listener."
  default     = 100
}

variable "enable_public_dns_record" {
  type        = bool
  description = "Creates the public Route 53 alias record for the <service-name> hostname on the edge ALB. Requires the public zone to live in this account."
  default     = true
}

variable "cpu_high_threshold_percent" {
  type        = number
  description = "ECS service CPUUtilization percentage that triggers the high CPU alarm."
  default     = 80
}

variable "<provider>_read_timeout_seconds" {
  type        = number
  description = "Read timeout in seconds for <provider> API calls."
  default     = 10
}

variable "memory_high_threshold_percent" {
  type        = number
  description = "ECS service MemoryUtilization percentage that triggers the high memory alarm."
  default     = 80
}

variable "<provider>_connect_timeout_seconds" {
  type        = number
  description = "Connect timeout in seconds for <provider> API calls."
  default     = 3
}
