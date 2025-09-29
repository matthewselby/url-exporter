# URL Exporter for WordPress 🚀

A straightforward WordPress plugin that exports all your site's URLs in a clean, alphabetically sorted format. Perfect for site audits, migration planning, SEO analysis, and documentation.

## ✨ Features

### Core Functionality
- **Complete URL Discovery** - Automatically detects and exports all public URLs including:
  - Pages and Posts
  - Custom Post Types (WooCommerce products, portfolios, etc.)
  - Categories, Tags, and Custom Taxonomies
  - Archive pages (category, tag, date, author)
  - Post Type archives
  - Search template URL
- **Smart Organization** - URLs are alphabetically sorted by path structure for easy scanning
- **Duplicate Prevention** - Automatically removes duplicate URLs from the export
- **Zero Configuration** - Works immediately upon activation with no setup required

### Export Options
- **CSV Format** - Includes both full URL and path columns, Excel-compatible with UTF-8 BOM
- **TXT Format** - Simple list with one URL per line, perfect for quick copying
- **Timestamped Files** - Exports include date/time in filename for easy organization

## 🔧 Installation

### Via WordPress Admin
1. Download the plugin ZIP file
2. Navigate to **Plugins → Add New** in your WordPress admin
3. Click **Upload Plugin** and choose the ZIP file
4. Click **Install Now** and then **Activate**

## 💡 Usage

### Admin Dashboard
1. Navigate to **Tools → URL Exporter**
2. Choose your export format:
   - Click **Export as CSV** for spreadsheet-compatible format
   - Click **Export as TXT** for a simple text list
3. File downloads automatically to your browser's download folder

### WP-CLI Commands

Export all URLs as CSV:
```bash
wp url-export --format=csv
```

Export all URLs as TXT:
```bash
wp url-export --format=txt
```

Files are saved to the current directory with timestamp.

## 🎯 Use Cases

- **Site Migrations** - Get a complete URL list for redirect mapping
- **SEO Audits** - Export all URLs for crawling and analysis tools
- **Content Inventory** - Document all pages and posts for content strategy
- **Quality Assurance** - Verify URL structure and identify issues
- **Client Documentation** - Provide clients with a complete site map
- **Broken Link Checking** - Export URLs for external validation tools
- **Development Workflows** - Compare URLs between staging and production

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

## 📝 Changelog

### Version 1.0.0 (2025)
- Initial release
- Complete URL discovery for all content types
- CSV and TXT export formats
- Admin interface and WP-CLI support
- Alphabetical sorting by path
- UTF-8 BOM support for Excel compatibility

## 🐛 Known/Potential Issues

- Large sites (10,000+ URLs) may need increased PHP memory limit
- Some page builders' dynamic URLs may not be detected
- Password-protected pages are included (but not accessible without password)

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 💬 Support

For bugs and feature requests, please [create an issue](https://github.com/yourusername/url-exporter/issues).
