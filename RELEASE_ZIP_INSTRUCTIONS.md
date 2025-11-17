# GitHub Release ZIP - Folder Name Fix

## Problem

When you download a GitHub release ZIP file, it extracts to a folder named `woo-ai-asistent-v1.0.6` (repository name + tag). This causes WordPress to see it as a different plugin each time you update.

## Solution

After downloading and extracting the ZIP file, **rename the folder** to `ai-store-assistant` before uploading to WordPress.

## Step-by-Step Instructions

### Option 1: Rename After Extraction (Recommended)

1. **Download the release ZIP** from GitHub
   - Go to: https://github.com/bedigitalsi/woo-ai-asistent/releases
   - Download the `vX.X.X.zip` file

2. **Extract the ZIP file**
   - Extract it to your computer
   - You'll see a folder like `woo-ai-asistent-1.0.6`

3. **Rename the folder**
   - Rename `woo-ai-asistent-1.0.6` to `ai-store-assistant`
   - This is the plugin folder name WordPress expects

4. **Upload to WordPress**
   - Create a new ZIP file from the `ai-store-assistant` folder
   - Upload via WordPress admin → Plugins → Add New → Upload Plugin
   - Or upload the folder via FTP to `/wp-content/plugins/`

### Option 2: Use Git Clone (For Developers)

If you're cloning the repository:

```bash
git clone https://github.com/bedigitalsi/woo-ai-asistent.git ai-store-assistant
cd ai-store-assistant
git checkout v1.0.6  # or latest tag
```

Then upload the `ai-store-assistant` folder to WordPress.

### Option 3: Use the Release Script (Easiest!)

We've included a script to create properly named ZIP files:

**For Mac/Linux:**
```bash
chmod +x create-release-zip.sh
./create-release-zip.sh 1.0.6
```

**For Windows:**
```cmd
create-release-zip.bat 1.0.6
```

This will create `ai-store-assistant-v1.0.6.zip` with the correct folder structure.

### Option 4: Manual ZIP Creation (Advanced)

To create a properly structured release ZIP manually:

1. **Clone the repository:**
   ```bash
   git clone https://github.com/bedigitalsi/woo-ai-asistent.git
   cd woo-ai-asistent
   git checkout v1.0.6
   ```

2. **Create a properly named folder:**
   ```bash
   cd ..
   cp -r woo-ai-asistent ai-store-assistant
   ```

3. **Create ZIP file:**
   ```bash
   zip -r ai-store-assistant-v1.0.6.zip ai-store-assistant/
   ```

4. **Upload this ZIP** to GitHub as a release asset, or use it directly for WordPress installation.

## Important Notes

✅ **Plugin folder name must be:** `ai-store-assistant`  
✅ **This matches the plugin slug** in WordPress  
✅ **Consistent folder name** ensures updates work correctly  
✅ **WordPress uses folder name** to identify the plugin  

## Quick Reference

**Repository name:** `woo-ai-asistent`  
**Plugin folder name:** `ai-store-assistant`  
**After extraction, rename:** `woo-ai-asistent-X.X.X` → `ai-store-assistant`

## Why This Matters

WordPress identifies plugins by their folder name. If the folder name changes with each version:
- WordPress sees it as a new plugin
- Settings and data may not transfer
- Update system won't work correctly
- You'll have duplicate plugins

Keeping the folder name consistent (`ai-store-assistant`) ensures:
- ✅ Updates work correctly
- ✅ Settings are preserved
- ✅ No duplicate plugins
- ✅ Smooth upgrade process

