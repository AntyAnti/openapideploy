#!/usr/bin/env bash
# bash boilerplate
set -euo pipefail # strict mode
readonly SCRIPT_NAME="$(basename "$0")"
readonly SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"
function l { # Log a message to the terminal.
    echo
    echo -e "[$SCRIPT_NAME] ${1:-}"
}

# File to copy from Openapideploy
OPENAPI_FILE=./openapideploy/api/Developers/openapi/openapi.yaml

# if the file exists in Openapideploy, copy it to Openapireceive repo
if [ -f "$OPENAPI_FILE" ]; then
    echo "Copying $OPENAPI_FILE"
    cp -R ./openapideploy/api/Developers/openapi/openapi.yaml $DESTINATION_PATH
fi

echo "OpenAPI file copied to $DESTINATION_PATH"
