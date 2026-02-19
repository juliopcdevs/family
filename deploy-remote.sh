#!/bin/bash
set -e

REMOTE_USER="${1:-user}"
REMOTE_HOST="${2:-your-server.com}"
REMOTE_PATH="${3:-/home/$REMOTE_USER/apps/family}"

echo "🚀 Deploying Family Hub to $REMOTE_HOST..."

ssh "$REMOTE_USER@$REMOTE_HOST" "cd $REMOTE_PATH && bash deploy.sh"

echo "✅ Remote deploy complete!"
