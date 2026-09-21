#!/usr/bin/env bash
set -euo pipefail

# 01-create-topics.sh: Create the <service-name> events SNS topic in LocalStack.
#
# The <service-name>-events topic carries every fact the service publishes from its outbox. The facts
# queue subscribes to it, so the topic is created before the queues and the subscriptions.
#
# The region comes from the environment, the same value the application reads, so the mock cannot
# diverge from it. The topic is created in that region explicitly, because a topic created in the CLI
# default region while the queues and the subscriptions pin another one yields an ARN that resolves
# to nothing and a subscription that never delivers.
#
# Fill <cloud-region> below with the region the application reads.
#
# Usage: invoked by the LocalStack ready.d init hook.
# Arguments: none. Reads AWS_REGION from the environment.

readonly REGION="${AWS_REGION:-<cloud-region>}"
: "${REGION:?set AWS_REGION in the compose environment, there is no default region to fall back to}"
readonly TOPIC_NAME="<service-name>-events"

main() {
    echo "[localstack-init] Creating SNS topic ${TOPIC_NAME} in ${REGION}"

    awslocal sns create-topic --region="${REGION}" --name "${TOPIC_NAME}"

    echo "[localstack-init] Topic ${TOPIC_NAME} created"
}

main "$@"
