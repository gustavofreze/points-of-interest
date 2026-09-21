#!/usr/bin/env bash
set -euo pipefail

# dispatch-<service-name>-facts.sh: Deliver <service-name> facts from the local SQS queue to the dispatch endpoint.
#
# Replaces the EventBridge Pipes hop in local development: long-polls the consumer queue
# <subscribed-channel>-<service-name>, translates each relayed outbox row into the dispatch contract,
# and posts it to the service with the SQS MessageId as the Idempotency-Key. A delivery acknowledged
# with 2xx deletes the message, anything else leaves it for redelivery and, past the redrive limit,
# the DLQ.
#
# This file is the <messaging-transport> binding of the local dispatch step, not a transport-neutral
# worker. It speaks the AWS CLI directly and points it at AWS_ENDPOINT_URL, the local mock. Another
# transport replaces this file wholesale instead of parameterizing it. What survives the replacement
# is the step itself: long-poll the consumer channel, translate each relayed outbox row into the
# dispatch contract, post it under a stable idempotency key, and acknowledge only on 2xx.
#
# Usage: dispatch-<service-name>-facts.sh
# Arguments: none. Configuration via environment: QUEUE_URL (required), TARGET_URL (required),
# and the AWS_* variables consumed by the AWS CLI. The dispatch endpoint is an internal route whose
# authentication is deferred to the infrastructure, so deliveries carry no bearer token.

readonly QUEUE_URL="${QUEUE_URL:?QUEUE_URL is required}"
readonly TARGET_URL="${TARGET_URL:?TARGET_URL is required}"
readonly AWS_ENDPOINT_URL="${AWS_ENDPOINT_URL:?AWS_ENDPOINT_URL is required}"

receive_batch() {
    aws sqs receive-message \
        --endpoint-url "${AWS_ENDPOINT_URL}" \
        --queue-url "${QUEUE_URL}" \
        --max-number-of-messages 1 \
        --wait-time-seconds 10 \
        --output json 2>/dev/null || true
}

parse_batch() {
    python3 -c '
import json
import sys

batch = sys.stdin.read().strip()
if not batch:
    sys.exit(3)

messages = json.loads(batch).get("Messages") or []
if not messages:
    sys.exit(3)

message = messages[0]
print(message["MessageId"])
print(message["ReceiptHandle"])
print(message["Body"])
'
}

translate_row() {
    python3 -c '
import json
import sys

row = json.load(sys.stdin)
data = row["data"]
payload = data["payload"]
if isinstance(payload, str):
    payload = json.loads(payload)
print(json.dumps({"event_type": data["event_type"], "payload": payload}))
'
}

delete_message() {
    local receipt_handle="${1:?Usage: delete_message <receipt_handle>}"

    aws sqs delete-message \
        --endpoint-url "${AWS_ENDPOINT_URL}" \
        --queue-url "${QUEUE_URL}" \
        --receipt-handle "${receipt_handle}" > /dev/null
}

deliver() {
    local dispatch_body="${1:?Usage: deliver <dispatch_body> <message_id>}"
    local message_id="${2:?Usage: deliver <dispatch_body> <message_id>}"

    curl -s -o /dev/null -w '%{http_code}' -X POST "${TARGET_URL}" \
        -H 'Content-Type: application/json' \
        -H "Idempotency-Key: ${message_id}" \
        --data "${dispatch_body}" || echo '000'
}

main() {
    echo "[<service-name>-dispatch] Delivering facts from ${QUEUE_URL} to ${TARGET_URL}"

    while true; do
        local parsed
        if ! parsed="$(receive_batch | parse_batch)"; then
            continue
        fi

        local message_id receipt_handle row
        message_id="$(sed -n '1p' <<< "${parsed}")"
        receipt_handle="$(sed -n '2p' <<< "${parsed}")"
        row="$(sed -n '3p' <<< "${parsed}")"

        local dispatch_body
        if ! dispatch_body="$(translate_row <<< "${row}")"; then
            echo "[<service-name>-dispatch] Message ${message_id} does not carry a fact, leaving it for the redrive"
            continue
        fi

        local status
        status="$(deliver "${dispatch_body}" "${message_id}")"

        if [[ "${status}" != 2* ]]; then
            echo "[<service-name>-dispatch] Delivery of ${message_id} answered ${status}, leaving it for the redrive"
            continue
        fi

        delete_message "${receipt_handle}"
        echo "[<service-name>-dispatch] Delivered ${message_id} with ${status}"
    done
}

main "$@"
