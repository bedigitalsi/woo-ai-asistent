# Setting Up GitHub Releases for Automatic Updates

To enable automatic plugin updates from GitHub, you need to create **GitHub Releases** with version tags.

## How It Works

1. WordPress checks GitHub API for the latest release
2. Compares it with the installed plugin version
3. Shows update notification if a newer version exists
4. Allows one-click update from WordPress admin

## Step-by-Step Guide

### 1. Create Your First Release (v1.0.0)

1. Go to your GitHub repository: https://github.com/bedigitalsi/woo-ai-asistent
2. Click on **"Releases"** (right sidebar) or go to: https://github.com/bedigitalsi/woo-ai-asistent/releases
3. Click **"Create a new release"**
4. Fill in the release form:
   - **Tag version**: `v1.0.0` (must start with `v`)
   - **Release title**: `Version 1.0.0` or `AI Store Assistant v1.0.0`
   - **Description**: 
     ```
     ## Initial Release
     
     - OpenAI API integration
     - Product recommendations
     - Knowledge base management
     - Floating chat widget
     - Order creation infrastructure (experimental)
     ```
   - **Target**: Select `main` branch
5. Click **"Publish release"**

### 2. Testing Updates

To test the update system:

1. **Create a new version** (e.g., v1.0.1):
   - Make a small change to the plugin
   - Update version in `ai-store-assistant.php`:
     ```php
     Version: 1.0.1
     define( 'ASA_VERSION', '1.0.1' );
     ```
   - Commit and push:
     ```bash
     git add .
     git commit -m "Version 1.0.1 - Bug fixes"
     git push
     ```

2. **Create a new release**:
   - Tag: `v1.0.1`
   - Title: `Version 1.0.1`
   - Description: List of changes
   - Publish release

3. **Check WordPress admin**:
   - Go to **Plugins** page
   - You should see "There is a new version of AI Store Assistant available"
   - Click **"Update now"** to update

### 3. Version Numbering

Follow semantic versioning:
- **Major** (1.0.0 → 2.0.0): Breaking changes
- **Minor** (1.0.0 → 1.1.0): New features, backward compatible
- **Patch** (1.0.0 → 1.0.1): Bug fixes

### 4. Release Checklist

Before creating a release:

- [ ] Update version in `ai-store-assistant.php` (both header and `ASA_VERSION`)
- [ ] Test the plugin thoroughly
- [ ] Update `CHANGELOG.md` or release notes
- [ ] Commit all changes
- [ ] Push to GitHub
- [ ] Create GitHub release with matching tag

## Example Release Workflow

```bash
# 1. Make your changes
# Edit files...

# 2. Update version number
# In ai-store-assistant.php:
# Version: 1.0.1
# define( 'ASA_VERSION', '1.0.1' );

# 3. Commit changes
git add .
git commit -m "Version 1.0.1 - Fixed price display issues"
git push

# 4. Create release on GitHub (via web interface)
# Tag: v1.0.1
# Title: Version 1.0.1
# Description: Fixed HTML entity rendering in product prices
```

## Important Notes

1. **Tag Format**: Tags MUST start with `v` (e.g., `v1.0.0`, not `1.0.0`)
2. **Version Match**: The tag version should match the version in `ai-store-assistant.php`
3. **Public Repository**: The repository must be public for the update system to work (or use a personal access token for private repos)
4. **Cache**: WordPress caches update checks for 12 hours. Use "Check again" button to force refresh.
5. **Folder Name**: GitHub ZIP files extract to `woo-ai-asistent-vX.X.X` folder. The automatic updater handles this correctly, but for manual installation, rename the folder to `ai-store-assistant` before uploading. See `RELEASE_ZIP_INSTRUCTIONS.md` for details.

## Troubleshooting

### Updates not showing
- Verify the release tag starts with `v` (e.g., `v1.0.0`)
- Check that version in plugin file is lower than release version
- Clear WordPress transients: `wp transient delete --all` (WP-CLI)
- Or wait 12 hours for cache to expire

### Update fails
- Ensure repository is public (or configure authentication)
- Check GitHub API rate limits (60 requests/hour for unauthenticated)
- Verify ZIP download URL works: `https://github.com/bedigitalsi/woo-ai-asistent/archive/refs/tags/v1.0.0.zip`

### For Private Repositories

If your repository is private, you'll need to modify the updater to use a Personal Access Token. Contact the developer for instructions.

## Benefits

✅ **Automatic Updates**: Users get notified of new versions  
✅ **One-Click Updates**: Update directly from WordPress admin  
✅ **Version History**: All releases tracked on GitHub  
✅ **Changelog**: Users can see what changed  
✅ **Professional**: Works like plugins from WordPress.org

