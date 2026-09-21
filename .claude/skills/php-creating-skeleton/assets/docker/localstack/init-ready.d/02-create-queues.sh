#!/usr/bin/env bash
set -euo pipefail

# 02-create-queues.sh: Create the <service-name> consumer SQS queues in LocalStack.
#
# The consumer queue is the subscription of <service-name> to <subscribed-channel>: it feeds the
# internal dispatch endpoint that runs the use case mapped to each delivered fact. The name follows
# the {channel}.{consumer} convention, materialized with hyphens, so it derives from
# the subscribed channel and not from the service that owns the stack.
#
# The region comes from the environment, the same value the application reads, so the mock cannot
# diverge from it. Every queue ARN built here carries that region, and so must the topic the
# subscription script points at.
#
# Fill <cloud-region> below with the region the application reads.
#
# Usage: invoked by the LocalStack ready.d init hook.
# Arguments: none. Reads AWS_REGION from the environment.

readonly REGION="${AWS_REGION:-<cloud-region>}"
: "${REGION:?set AWS_REGION in the compose environment, there is no default region to fall back to}"
readonly ACCOUNT_ID="000000000000"
readonly QUEUE_NAME="<subscribed-channel>-<service-name>"
readonly RETENTION_SECONDS="1209600"
readonly DEAD_LETTER_QUEUE_NAME="<subscribed-channel>-<service-name>-dlq"

create_queue() {
    local queue_name="${1:?Usage: create_queue <queue_name> <attributes>}"
    local attributes="${2:?Usage: create_queue <queue_name> <attributes>}"

    awslocal sqs create-queue \
        --region="${REGION}" \
        --queue-name "${queue_name}" \
        --attributes "${attributes}"
}

main() {
    echo "[localstack-init] Creating <service-name> consumer queues"

    create_queue "${DEAD_LETTER_QUEUE_NAME}" "{\"MessageRetentionPeriod\":\"${RETENTION_SECONDS}\"}"

    local dead_letter_queue_arn="arn:aws:sqs:${REGION}:${ACCOUNT_ID}:${DEAD_LETTER_QUEUE_NAME}"
    local redrive_policy="{\\\"deadLetterTargetArn\\\":\\\"${dead_letter_queue_arn}\\\",\\\"maxReceiveCount\\\":\\\"3\\\"}"

    create_queue "${QUEUE_NAME}" "{\"RedrivePolicy\":\"${redrive_policy}\",\"MessageRetentionPeriod\":\"${RETENTION_SECONDS}\"}"

    echo "[localstack-init] <service-name> consumer queues created"
}

main "$@"
