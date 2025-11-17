# AI Store Assistant

A WordPress plugin that integrates with the OpenAI API to provide a product-aware chatbot on WooCommerce stores.

## Features

- 🤖 **AI-Powered Chatbot**: Integrates with OpenAI API (GPT-4, GPT-4o-mini, GPT-3.5-turbo, etc.)
- 🛍️ **Product Recommendations**: Suggests WooCommerce products with images, prices, and links
- 📚 **Knowledge Base**: Built-in knowledge base management for FAQ and store information
- 💬 **Floating Chat Widget**: Modern, responsive chat interface on the frontend
- ⚙️ **Customizable**: Configurable system prompts, model selection, and settings
- 🔒 **Secure**: Nonce-based security for REST API endpoints
- 🌍 **Translation Ready**: All strings are wrapped for internationalization

## Requirements

- WordPress 6.0 or higher
- WooCommerce 8.0 or higher (tested up to 10.2)
- PHP 8.1 or higher
- OpenAI API key ([Get one here](https://platform.openai.com/api-keys))

## Installation

### Option 1: Install from GitHub (Recommended)

1. **Download the latest release**
   - Go to [Releases](https://github.com/bedigitalsi/woo-ai-asistent/releases)
   - Download the latest `vX.X.X.zip` file

2. **Prepare the ZIP file** (Important!)
   
   **Option A - Use the release script** (Recommended):
   - After creating a GitHub release, use the included script to create a properly named ZIP:
   - Mac/Linux: `./create-release-zip.sh 1.0.6`
   - Windows: `create-release-zip.bat 1.0.6`
   - This creates `ai-store-assistant-v1.0.6.zip` with correct folder structure
   
   **Option B - Manual rename**:
   - Extract the downloaded ZIP file
   - You'll see a folder named `woo-ai-asistent-vX.X.X`
   - **Rename this folder to `ai-store-assistant`** (this is required!)
   - Create a new ZIP file from the renamed `ai-store-assistant` folder

3. **Upload to WordPress**
   - Go to WordPress admin → Plugins → Add New
   - Click "Upload Plugin"
   - Choose the newly created ZIP file (with `ai-store-assistant` folder)
   - Click "Install Now" then "Activate"

4. **Automatic Updates**
   - Once installed, WordPress will automatically check for updates
   - You'll see update notifications in Plugins page when new versions are released
   - Click "Update now" to update directly from WordPress admin
   - **Note**: Automatic updates handle folder renaming automatically - you only need to rename for initial manual installation

### Option 2: Manual Installation

1. **Download or clone the repository**
   ```bash
   git clone https://github.com/bedigitalsi/woo-ai-asistent.git
   ```

2. **Upload to WordPress**
   - Upload the `ai-store-assistant` folder to `/wp-content/plugins/`
   - Or use WordPress admin: Plugins → Add New → Upload Plugin

3. **Activate the plugin**
   - Go to WordPress admin → Plugins
   - Find "AI Store Assistant" and click "Activate"
   - Ensure WooCommerce is installed and active

## Configuration

### 1. Set Up OpenAI API

1. Go to **WooCommerce → AI Store Assistant**
2. Enter your **OpenAI API Key** (get one at https://platform.openai.com/api-keys)
3. Select your **Model Name** (default: `gpt-4o-mini`)
4. Customize the **System Prompt** to match your brand voice
5. Configure other settings:
   - Enable/disable product suggestions
   - Enable/disable order creation (experimental)
   - Set maximum knowledge items for context
6. Click **Save Changes**

### 2. Build Your Knowledge Base

1. Go to **Chatbot Knowledge** in WordPress admin menu
2. Click **Add New**
3. Enter a **Title** (e.g., "Shipping Policy", "Return Policy")
4. Add detailed **Content** that the chatbot can use to answer questions
5. Click **Publish**
6. Repeat for all your store policies, FAQs, and information

### 3. Test the Chatbot

1. Visit your store frontend
2. Look for the floating chat button (bottom-right corner)
3. Click to open the chat widget
4. Ask questions about your products or store

## Usage

### For Store Owners

The chatbot automatically:
- Answers questions using your knowledge base
- Suggests relevant WooCommerce products
- Provides product information (name, price, image, link)
- Maintains conversation context

### Customization

**System Prompt**: Customize the assistant's behavior, tone, and instructions in the settings page.

**Knowledge Base**: Add unlimited knowledge items covering:
- Shipping and delivery information
- Return and refund policies
- Product care instructions
- Store policies
- FAQs
- Brand information

**Product Suggestions**: The chatbot automatically includes your WooCommerce products in context and can suggest them based on customer queries.

## File Structure

```
ai-store-assistant/
├── ai-store-assistant.php      # Main plugin file
├── uninstall.php               # Cleanup on uninstall
├── README.md                   # This file
├── .gitignore                 # Git ignore rules
├── includes/                   # Core functionality
│   ├── class-asa-loader.php
│   ├── class-asa-settings.php
│   ├── class-asa-knowledge-base.php
│   ├── class-asa-chat-endpoint.php
│   ├── class-asa-product-recommender.php
│   └── class-asa-order-handler.php
├── admin/                     # Admin interface
│   ├── class-asa-admin-page.php
│   ├── css/admin.css
│   └── js/admin.js
└── public/                    # Frontend interface
    ├── class-asa-frontend.php
    ├── css/chat-widget.css
    └── js/chat-widget.js
```

## API Endpoints

### Chat Endpoint
- **URL**: `/wp-json/ai-store-assistant/v1/chat`
- **Method**: POST
- **Auth**: Nonce required
- **Payload**: 
  ```json
  {
    "messages": [
      {"role": "user", "content": "Your message"}
    ]
  }
  ```

### Order Creation Endpoint (Experimental)
- **URL**: `/wp-json/ai-store-assistant/v1/create-order`
- **Method**: POST
- **Auth**: Nonce required
- **Status**: Experimental feature

## Security

- All REST API endpoints use WordPress nonces for authentication
- Input sanitization and validation on all user inputs
- Secure API key storage in WordPress options
- No direct file access

## Development

### Code Standards
- Follows WordPress Coding Standards
- Object-oriented PHP architecture
- Translation-ready (text domain: `ai-store-assistant`)
- PHPDoc comments for all classes and methods

### Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Troubleshooting

### Chat widget not appearing
- Ensure OpenAI API key is configured and valid
- Check browser console for JavaScript errors
- Verify WooCommerce is active

### No product suggestions
- Ensure "Enable Product Suggestions" is checked in settings
- Verify you have published WooCommerce products
- Check that products are in stock

### Chat not responding
- Verify OpenAI API key is valid and has credits
- Check WordPress debug log for errors
- Ensure REST API is accessible

## Updates

This plugin supports **automatic updates from GitHub**. When you install from a GitHub release:

- WordPress automatically checks for new versions
- Update notifications appear in the Plugins page
- One-click updates directly from WordPress admin
- No need to manually download and reinstall

**Note**: Updates work best when installed from a GitHub release ZIP file. See [GITHUB_RELEASES.md](GITHUB_RELEASES.md) for information on creating releases.

## Changelog

### 1.0.0
- Initial release
- OpenAI API integration
- Product recommendations
- Knowledge base management
- Floating chat widget
- Order creation infrastructure (experimental)
- Automatic updates from GitHub releases

## License

This plugin is licensed under GPL v2 or later.

## Credits

- Built for WooCommerce stores
- Powered by OpenAI API
- WordPress plugin architecture

## Support

For issues, feature requests, or contributions, please visit:
https://github.com/bedigitalsi/woo-ai-asistent

## Author

beDigital SI

---

**Note**: This plugin requires an active OpenAI API key with sufficient credits. OpenAI API usage is subject to OpenAI's pricing and terms of service.


