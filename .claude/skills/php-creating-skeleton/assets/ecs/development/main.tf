data "terraform_remote_state" "environment" {
  backend = "s3"

  # This key belongs to a data source and not to the backend block, so `-backend-config` never
  # reaches it. A wrong key does not fail the plan, it reads an empty state and every foundation
  # lookup below silently resolves to null. Edit it here when the foundation keys its state
  # differently.
  config = {
    bucket = var.state_bucket
    key    = "<cloud-region>/environments/development.tfstate"
    region = var.aws_region
  }
}

locals {
  service     = "<service-name>"
  environment = "development"

  dns_zone_name = "${local.environment}.${var.domain_name}"

  # The requirement is that the service verifies tokens against a JWKS document. The host that
  # serves it is a binding, and this line derives it from the environment zone.
  identity_jwks_url = "https://identity.${local.dns_zone_name}/.well-known/jwks.json"

  dispatch_event_types = ["<DomainEventType>", "<DomainEventType>"] # PascalCase names of the facts that dispatch a use case
}

# Both modules below come from <platform-iac>, the shared platform repository, and the region is
# part of the module path because that repository partitions its modules by region.
module "service" {
  source = "git::https://github.com/<platform-iac>.git//terraform/aws/<cloud-region>/modules/service?ref=main"

  tags        = var.tags
  prefix      = var.prefix
  service     = local.service
  image_tag   = var.image_tag
  aws_region  = var.aws_region
  foundation  = data.terraform_remote_state.environment.outputs
  environment = local.environment

  ecr_registry  = var.ecr_registry
  database_name = var.database_name
  dns_zone_name = local.dns_zone_name

  desired_count     = var.desired_count
  task_cpu_units    = var.task_cpu_units
  task_memory_mb    = var.task_memory_mb
  nginx_image_tag   = var.nginx_image_tag
  capacity_provider = var.capacity_provider

  # The facts endpoint is not listed here. The relay dispatcher reaches it through the internal
  # ALB, whose listener rule matches on the host header alone, so it never crosses the edge.
  edge_path_patterns       = ["/health/*", "<edge-path-pattern>"]
  edge_rule_priority       = var.edge_rule_priority
  internal_rule_priority   = var.internal_rule_priority
  enable_public_dns_record = var.enable_public_dns_record

  environment_variables = {
    IDENTITY_JWKS_URL                  = local.identity_jwks_url
    <PROVIDER>_API_BASE_URL            = var.<provider>_api_base_url
    <PROVIDER>_READ_TIMEOUT_SECONDS    = tostring(var.<provider>_read_timeout_seconds)
    <PROVIDER>_CONNECT_TIMEOUT_SECONDS = tostring(var.<provider>_connect_timeout_seconds)
  }

  # The <provider> block is illustrative for one gateway. Adjust it or repeat it per service.
  extra_secrets = {
    "<provider>/api-key"              = "<provider> API secret. Set the real value out of band."
    "<provider>/webhook-access-token" = "Shared secret validating inbound <provider> webhooks. Set the real value out of band."
  }

  cpu_high_threshold_percent    = var.cpu_high_threshold_percent
  memory_high_threshold_percent = var.memory_high_threshold_percent
}

# The two sides of the channel are independent, and both default to true. Set publishes_events to
# false when the context owns no outbox and emits no fact, and consumes_events to false when it
# emits facts but projects none. The queue subscribes to the service's own topic by default, so a
# context that only consumes must also pass subscribed_topic_arn with the publisher's topic. There
# is no endpoint input here: the relay dispatcher of the environment relay task polls the queue and
# posts each fact to the internal hostname of the consumer.
module "service_messaging" {
  source = "git::https://github.com/<platform-iac>.git//terraform/aws/<cloud-region>/modules/service-messaging?ref=main"

  tags                 = var.tags
  prefix               = var.prefix
  service              = local.service
  environment          = local.environment
  consumes_events      = true
  publishes_events     = true
  alerts_sns_topic_arn = data.terraform_remote_state.environment.outputs.alerts_sns_topic_arn
  dispatch_event_types = local.dispatch_event_types
}
