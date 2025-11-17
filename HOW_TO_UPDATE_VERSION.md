# How to Update Plugin Version

## Quick Steps

To update the plugin version, you need to change it in **ONE file** in **TWO places**:

### File: `ai-store-assistant.php`

1. **Line 6** - Plugin header version:
   ```php
   * Version: 1.0.5
   ```

2. **Line 29** - Version constant:
   ```php
   define( 'ASA_VERSION', '1.0.5' );
   ```

## Step-by-Step Guide

### 1. Open the Main Plugin File
- Navigate to: `ai-store-assistant.php` (root of plugin folder)
- Open it in your code editor

### 2. Update Plugin Header (Line ~6)
Find this section at the top:
```php
/**
 * Plugin Name: AI Store Assistant
 * Plugin URI: https://github.com/bedigitalsi/woo-ai-asistent
 * Description: A WordPress plugin that integrates with OpenAI API...
 * Version: 1.0.4    ← CHANGE THIS
 * Author: beDigital SI
 * ...
 */
```

Change the `Version:` line to your new version.

### 3. Update Version Constant (Line ~29)
Find this section:
```php
/**
 * Currently plugin version.
 */
define( 'ASA_VERSION', '1.0.4' );    ← CHANGE THIS
```

Change the version number inside the quotes.

### 4. Save the File
- Save your changes
- Commit to git if using version control

### 5. Create GitHub Release (for Updates)
After updating the version:

1. **Commit and push your changes:**
   ```bash
   git add ai-store-assistant.php
   git commit -m "Version 1.0.5 - [describe your changes]"
   git push
   ```

2. **Create a GitHub release:**
   - Go to: https://github.com/bedigitalsi/woo-ai-asistent/releases
   - Click "Create a new release"
   - **Tag version**: `v1.0.5` (must start with `v`)
   - **Release title**: `Version 1.0.5`
   - **Description**: List your changes
   - Click "Publish release"

3. **Test the update:**
   - Go to WordPress admin → WooCommerce → AI Store Assistant
   - Click "Check for Updates"
   - You should see the new version available

## Version Numbering Guide

Follow **semantic versioning** (major.minor.patch):

- **Major** (1.0.0 → 2.0.0): Breaking changes, major new features
- **Minor** (1.0.0 → 1.1.0): New features, backward compatible
- **Patch** (1.0.0 → 1.0.1): Bug fixes, small improvements

### Examples:
- `1.0.4` → `1.0.5` = Patch (bug fix)
- `1.0.5` → `1.1.0` = Minor (new feature)
- `1.1.0` → `2.0.0` = Major (breaking change)

## Important Notes

✅ **Always update BOTH places** (header and constant)  
✅ **Keep versions in sync** - they must match  
✅ **GitHub tag must match** - use `v1.0.5` format  
✅ **Test after updating** - use "Check for Updates" button  

## Quick Reference

**File to edit:** `ai-store-assistant.php`  
**Line 1:** `* Version: X.X.X`  
**Line 2:** `define( 'ASA_VERSION', 'X.X.X' );`  

That's it! Just two lines to change. 🎉

