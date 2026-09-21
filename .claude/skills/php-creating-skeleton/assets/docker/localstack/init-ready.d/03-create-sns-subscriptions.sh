#!/usr/bin/env bash
set -euo pipefail

# 03-create-sns-subscriptions.sh: Subscribe the <service-name> consumer queue to <subscribed-channel> in LocalStack.
#
# Filters on the fact types that dispatch a use case. The relay (Maxwell) publishes the raw outbox
# row as the message body, so the filter scopes to the message body instead of message attributes.
#
# The region comes from the environment, the same value the application reads, so the mock cannot
# diverge from it. Both ARNs assembled below carry that region, and they resolve only when the topic
# and the queue were created in it too.
#
# Fill <cloud-region> below with the region the application reads.
#
# Usage: invoked by the LocalStack ready.d init hook.
# Arguments: none. Reads AWS_REGION from the environment.

readonly REGION="${AWS_REGION:-<cloud-region>}"
: "${REGION:?set AWS_REGION in the compose environment, there is no default region to fall back to}"
readonly ACCOUNT_ID="000000000000"

subscribe_queue() {
    local topic_arn="${1:?Usage: subscribe_queue <topic_arn> <queue_arn> <attributes>}"
    local queue_arn="${2:?Usage: subscribe_queue <topic_arn> <queue_arn> <attributes>}"
    local attributes="${3:?Usage: subscribe_queue <topic_arn> <queue_arn> <attributes>}"

    awslocal sns subscribe \
        --region="${REGION}" \
        --topic-arn="${topic_arn}" \
        --protocol sqs \
        --notification-endpoint "${queue_arn}" \
        --attributes "${attributes}"
}

main() {
    echo "[localstack-init] Creating SNS subscriptions for <service-name> service"

    local topic_arn="arn:aws:sns:${REGION}:${ACCOUNT_ID}:<subscribed-channel>"
    local consumer_queue_arn="arn:aws:sqs:${REGION}:${ACCOUNT_ID}:<subscribed-channel>-<service-name>"
    # Placeholder event types. PascalCase names of the facts that dispatch a use case.
    local filter_policy='{\"data\":{\"event_type\":[\"<DomainEventType>\",\"<DomainEventType>\"]}}'

    subscribe_queue "${topic_arn}" "${consumer_queue_arn}" \
        "{\"RawMessageDelivery\":\"true\",\"FilterPolicyScope\":\"MessageBody\",\"FilterPolicy\":\"${filter_policy}\"}"

    echo "[localstack-init] SNS subscriptions created"
}

main "$@"
