=== Nazari Metafield For Elementor ===
Contributors: sajjadnazari
Tags: custom fields, meta box, elementor, dynamic tags, metabox
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build custom meta boxes (field groups) for posts and custom post types, and use the fields in Elementor with a widget, dynamic tags or a shortcode.

== Description ==

Nazari Metafield For Elementor is a lightweight, JetEngine-style meta box builder. Create field groups from a visual builder, fill them in on the post edit screen, and show the values anywhere in Elementor.

**Features**

* Visual field group builder: drag to reorder, duplicate fields, copy each field's shortcode.
* 14 field types: text, textarea, number, email, URL, select, radio, checkbox, switcher, date, color, WYSIWYG, image and gallery.
* Choose the post types, position and priority of every meta box.
* "Meta Field" Elementor widget (works with free Elementor).
* Dynamic tags for Elementor Pro: text, number, URL, color, image and gallery.
* Shortcode: `[mfbe field="meta_key"]`.
* Values are stored as regular post meta, so other tools can read them.
* Dashboard, settings and a built-in feedback form.
* RTL ready, Persian (fa_IR) translation included.

**Shortcode attributes**

`field` (required), `post_id`, `format` (html or text), `size`, `columns`, `fallback`.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from Plugins > Add New > Upload Plugin.
2. Activate the plugin.
3. Open "Nazari Metafield" in the admin menu and create your first field group.

If the older "Meta Fields for Elementor" 1.0.0 is active, it is deactivated automatically. Your field groups and saved values are kept.

== Frequently Asked Questions ==

= Do I need Elementor Pro? =

No. The Meta Field widget works with free Elementor. Dynamic tags need Elementor Pro.

= Where are the values stored? =

As normal post meta under the meta key you choose for each field.

= What happens to my data when I delete the plugin? =

Nothing, unless you enable "Delete data on uninstall" in the settings. Even then, values saved on posts are kept.

== Changelog ==

= 1.0.2 =
* New name and branding, top-level menu and dashboard.
* Settings page with a feedback form.
* Builder: type icons, duplicate field, expand/collapse all, copy shortcode.
* Optional "delete data on uninstall".
* Security and coding-standards fixes reported by Plugin Check.

= 1.0.0 =
* First release.
