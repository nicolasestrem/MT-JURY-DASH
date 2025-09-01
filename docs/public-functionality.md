# Public-Facing Functionality

This document describes the public-facing functionality of the Mobility Trailblazers plugin, including asset loading, shortcode rendering, and widgets.

## Public Assets

The `MobilityTrailblazers\Public\MT_Public_Assets` class is responsible for loading CSS and JavaScript assets on the frontend. It conditionally loads assets only on pages where they are needed, such as pages containing plugin-specific shortcodes or custom post types.

This class also handles the loading of the v4 CSS framework, which is a modern CSS framework that replaces the legacy CSS system.

## Shortcode Renderer

The `MobilityTrailblazers\Public\Renderers\MT_Shortcode_Renderer` class is a shared renderer for all of the plugin's shortcodes and Elementor widgets. It contains the logic for rendering the HTML output for each shortcode and widget.

This centralized approach ensures a consistent look and feel across all of the plugin's frontend components.

## Language Switcher Widget

The `MobilityTrailblazers\Widgets\MT_Language_Switcher` class provides a frontend language switcher widget. This widget allows users to change their language preference, which is then stored in a cookie and in the user's meta data if they are logged in.

The language switcher can be displayed as a dropdown or as an inline list of languages. It can be added to any page using the `[mt_language_switcher]` shortcode.
