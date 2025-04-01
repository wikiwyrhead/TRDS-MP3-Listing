# MP3 Listing Plugin

A WordPress plugin for listing and playing MP3 files with download and share functionality. This plugin provides an elegant way to showcase your audio content with a modern, customizable interface.

## Features

- Responsive HTML5 audio player
- Direct download functionality with click tracking
- Social media sharing (Facebook, Twitter, WhatsApp)
- Play count tracking
- Load more functionality for paginated display
- Customizable color scheme
- Mobile-friendly interface

## Installation

1. Download the plugin zip file
2. Upload to your WordPress site through the Plugins menu
3. Activate the plugin
4. Go to "MP3 Files" in your WordPress admin menu
5. Start uploading MP3 files and customize the appearance

## Usage

### Basic Shortcode

Add the MP3 listing to any page or post using the shortcode:

```
[mp3_listing]
```

### Customization

Navigate to MP3 Files > Settings to customize:

- Download button color
- Share button color
- MP3 title color
- Audio player color

## Changelog

### Version 1.2.2 (2024-04-01)

#### Fixed
- Load more functionality now works correctly for both logged-in and logged-out users
- Fixed CSS Google Fonts import rule positioning
- Improved AJAX parameters handling for better security and functionality

### Version 1.2.1 (2024-03-31)

#### Changed
- Updated audio player color scheme to match the overall design
- Refined button colors for better visual consistency
- Improved audio player controls visibility
- Optimized CSS structure by removing redundant files
- Consolidated styling into a single, maintainable file

### Version 1.2.0 (2025-02-23)

#### Added

- Color customization settings for UI elements
- Improved settings page with color pickers
- Enhanced mobile responsiveness
- Better error handling for file operations

#### Changed

- Simplified color settings interface
- Improved button styling consistency
- Enhanced audio player appearance
- Updated social sharing implementation

#### Fixed

- Color picker functionality
- Mobile layout issues
- Share button positioning
- Audio player controls styling

### Version 1.1.0 (2025-02-15)

#### Added

- Social media sharing functionality
- Play count tracking system
- Load more feature for pagination
- Mobile-responsive design improvements

### Version 1.0.0 (2025-02-01)

#### Added

- Basic MP3 file management system
- Custom post type for MP3 files
- Frontend display with [mp3_listing] shortcode
- HTML5 audio player integration
- Basic download functionality
- Simple admin interface for MP3 uploads
- Basic styling for the frontend display
- File size display and format validation
- Security measures for file downloads
- Basic error handling

#### Core Features

- MP3 file upload and management
- Audio playback functionality
- Download tracking system
- Basic admin settings page
- Frontend shortcode integration
- File type validation
- Secure file handling

#### Technical Implementation

- Custom post type registration
- File upload handling
- Download functionality with security checks
- Basic CSS styling
- WordPress admin integration
- Error logging system
- File size calculations
- MIME type validation

## Improvements from Original Plugin

### User Interface

- Added modern color picker interface
- Customizable color options for better branding
- Enhanced mobile responsiveness
- Added load more functionality

### Features

- Added play count tracking
- Enhanced social sharing capabilities
- Improved audio player styling
- Better performance with optimized code

### Code Quality

- Improved security with proper nonce verification
- Better code organization
- Optimized asset loading
- Fixed various bugs and issues

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Modern web browser with HTML5 audio support

## Support

For support, feature requests, or bug reports, please use the GitHub issues page.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Originally forked from TRDS MP3 Listing by Arnel Go. Enhanced and maintained by WikiWyrhead.
