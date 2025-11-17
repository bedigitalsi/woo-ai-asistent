#!/bin/bash
# Script to create a properly named release ZIP file
# Usage: ./create-release-zip.sh [version]
# Example: ./create-release-zip.sh 1.0.6

VERSION=${1:-$(grep "Version:" ai-store-assistant.php | head -1 | sed 's/.*Version: *\([0-9.]*\).*/\1/')}

if [ -z "$VERSION" ]; then
    echo "Error: Could not determine version. Please specify version as argument."
    echo "Usage: ./create-release-zip.sh 1.0.6"
    exit 1
fi

echo "Creating release ZIP for version $VERSION..."

# Create temporary directory
TEMP_DIR=$(mktemp -d)
PLUGIN_DIR="$TEMP_DIR/ai-store-assistant"

# Copy all plugin files to properly named directory
mkdir -p "$PLUGIN_DIR"
rsync -av --exclude='.git' --exclude='.DS_Store' --exclude='create-release-zip.sh' --exclude='*.zip' ./ "$PLUGIN_DIR/"

# Create ZIP file
ZIP_NAME="ai-store-assistant-v${VERSION}.zip"
cd "$TEMP_DIR"
zip -r "$ZIP_NAME" ai-store-assistant/ > /dev/null

# Move ZIP to original directory
mv "$ZIP_NAME" "$(dirname "$0")/"

# Cleanup
rm -rf "$TEMP_DIR"

echo "✅ Created: $ZIP_NAME"
echo "📦 This ZIP file contains the 'ai-store-assistant' folder (no version number)"
echo "🚀 You can upload this ZIP directly to WordPress or attach it to GitHub release"

