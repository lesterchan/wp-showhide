# WP-ShowHide
Contributors: GamerZ  
Donate link: http://lesterchan.net/site/donation/  
Tags: show, hide, content, visibility, press release, toggle  
Requires at least: 3.0  
Tested up to: 5.3  
Stable tag: trunk  

Allows you to embed content within your blog post via WordPress ShortCode API and toggling the visibility of the content via a link.

## Description
By default the content is hidden and user will have to click on the "Show Content" link to toggle it. Similar to what Engadget is doing for their press releases. Example usage: `[showhide type="pressrelease"]Press Release goes in here.[/showhide]`

### Usage
1. By default, content within the showhide shortcode will be hidden.
2. Example: `[showhide]Press release content goes in here.[/showhide]`
3. Default Values: `[showhide type="pressrelease" more_text="Show Press Release (%s More Words)" less_text="Hide Press Release (%s Less Words)" hidden="yes"]`

1. You can have multiple showhide content within a post or a page, just by having a new type.
2. Example: `[showhide type="links" more_text="Show Links (%s More Words)" less_text="Hide Links (%s Less Words)"]Links will go in here.[/showhide]`

1. If you want to set the default visibility to display.
2. Example: `[showhide hidden="no"]Press release content goes in here.[/showhide]`

1. You can style the content via CSS that is generated by the plugin. Here is a sample of the generated HTML. Note that pressrelease is the default type.
```
<div id="pressrelease-link-1" class="sh-link pressrelease-link sh-hide">  
  <a href="#">  
    <span id="pressrelease-toggle-1">Show Press Release (4 More Words)</span>  
  </a>  
</div>  
<div id="pressrelease-content-1" class="sh-content pressrelease-content sh-hide" style="display: none;">Content</div>
```

2. With the example above, here are the following styles you can use in your CSS:
```
.sh-link A { }  
.sh-content { }  
.pressrelease-link { }  
.pressrelease-link.sh-hide A { }  
.pressrelease-link.sh-show A { }  
.pressrelease-content { }  
.pressrelease-content.sh-hide { }  
.pressrelease-content.sh-show { }
```

### Build Status
[![Build Status](https://travis-ci.org/lesterchan/wp-showhide.svg?branch=master)](https://travis-ci.org/lesterchan/wp-showhide)

### Development
[https://github.com/lesterchan/wp-showhide](https://github.com/lesterchan/wp-showhide "https://github.com/lesterchan/wp-showhide")

### Translations
[http://dev.wp-plugins.org/browser/wp-showhide/i18n/](http://dev.wp-plugins.org/browser/wp-showhide/i18n/ "http://dev.wp-plugins.org/browser/wp-showhide/i18n/")

### Credits
* Plugin icon by [Freepik](http://www.freepik.com) from [Flaticon](http://www.flaticon.com)

### Donations
I spent most of my free time creating, updating, maintaining and supporting these plugins, if you really love my plugins and could spare me a couple of bucks, I will really appreciate it. If not feel free to use it without any obligations.

## Changelog
### Version 1.04
* NEW: Added aria-hidden and aria-expanded to elements

### Version 1.03
* NEW: Added `.sh-link` and `.sh-content` as a standard class name on top of the type specific class name.
* NEW: Added do_shortcode() to allow shortcode to be parsed within ShowHide

### Version 1.02
* FIXED: Some theme uses `.hide` as `display: none` and hence I have changed `.show` to `.sh-show` and `.hide` to `.sh-hide` to advoid conflicts.

### Version 1.01
* NEW: Added additional `show` or `hide` class to the link and content depending on the visiblity of the content to allow more precise CSS styling.

### Version 1.00 (01-05-2011)
* FIXED: Initial Release
	
## Upgrade Notice

N/A

## Screenshots

1. Show More - Press Release
2. Hide More - Press Release
3. Editor - Short Code

## Frequently Asked Questions

N/A
