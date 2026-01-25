#!/bin/bash

# ClickUp Webhook Test Script
# Tests the webhook endpoint with a ClickUp task ID

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}ClickUp Webhook Test${NC}\n"

# Check if task ID provided
if [ -z "$1" ]; then
    echo -e "${RED}Error: Task ID required${NC}"
    echo "Usage: ./test-webhook.sh <task_id>"
    echo "Example: ./test-webhook.sh abc123xyz"
    exit 1
fi

TASK_ID="$1"
WEBHOOK_URL="http://localhost/webhook/index.php?id=${TASK_ID}"

echo -e "Testing webhook with task ID: ${YELLOW}${TASK_ID}${NC}"
echo -e "Endpoint: ${WEBHOOK_URL}\n"

# Make request
echo -e "${YELLOW}Sending request...${NC}\n"

RESPONSE=$(curl -s -w "\nHTTP_CODE:%{http_code}" "${WEBHOOK_URL}")
HTTP_CODE=$(echo "$RESPONSE" | grep "HTTP_CODE:" | cut -d: -f2)
BODY=$(echo "$RESPONSE" | sed '/HTTP_CODE:/d')

echo -e "${YELLOW}Response:${NC}"
echo "$BODY" | jq '.' 2>/dev/null || echo "$BODY"

echo -e "\n${YELLOW}HTTP Status Code:${NC} ${HTTP_CODE}"

# Check result
if [ "$HTTP_CODE" = "200" ]; then
    echo -e "\n${GREEN}✓ Success!${NC}"
    
    # Show saved file
    if echo "$BODY" | jq -e '.data.file.filename' &>/dev/null; then
        FILENAME=$(echo "$BODY" | jq -r '.data.file.filename')
        echo -e "\n${GREEN}Task saved to: webhook/tasks/${FILENAME}${NC}"
        
        if [ -f "webhook/tasks/${FILENAME}" ]; then
            echo -e "\n${YELLOW}File contents:${NC}"
            head -n 20 "webhook/tasks/${FILENAME}"
        fi
    fi
else
    echo -e "\n${RED}✗ Request failed${NC}"
fi

# Show logs
if [ -f "logs/webhook.log" ]; then
    echo -e "\n${YELLOW}Recent webhook log entries:${NC}"
    tail -n 20 logs/webhook.log
fi
