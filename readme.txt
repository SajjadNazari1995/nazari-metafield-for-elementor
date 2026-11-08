=== Elementor Metafield ===
Contributors: sajjadnazari
Tags: custom fields, meta box, elementor, meta field, dynamic tags
Requires at least: 5.8
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Elementor Metafield (متا فیلد المنتور): visual meta box builder, 20 field types, Elementor widgets, dynamic tags and conditional logic. Works with or without Elementor.

== Description ==

Elementor Metafield is a lightweight, JetEngine-style meta box builder that works with or without Elementor (without it, show values with a shortcode). Create field groups from a visual builder, fill them in on the post edit screen, and show the values anywhere in Elementor.

**متا فیلد المنتور (فارسی):** سازنده متاباکس و فیلد سفارشی برای وردپرس با پشتیبانی کامل از المنتور: ویجت، تگ‌های داینامیک، شورت‌کد، منطق شرطی و اعتبارسنجی. رابط افزونه فارسی و راست‌چین است.

**Features**

* Visual field group builder: drag to reorder, duplicate fields, copy each field's shortcode.
* 20 field types: text, textarea, number, email, URL, select, radio, checkbox, switcher, date, time, date and time, color, range, WYSIWYG, image, gallery, file, link and embed.
* Validation (required, min/max length, unique) and conditional logic between fields.
* Show fields as sortable columns in the post list, with filters.
* Import and export field groups as JSON; duplicate a group in one click.
* Choose the post types, position and priority of every meta box.
* "Meta Field" and "Meta Field Link" Elementor widgets (work with free Elementor).
* Meta Field Visibility: show or hide any Elementor element by a meta field.
* Loop query rules: filter and sort Elementor Pro Posts / Loop Grid by a meta field (Query ID).
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
3. Open "Elementor Metafield" in the admin menu and create your first field group.

If the older "Meta Fields for Elementor" 1.0.0 is active, it is deactivated automatically. Your field groups and saved values are kept.

== Frequently Asked Questions ==

= Do I need Elementor Pro? =

No. The widgets and Meta Field Visibility work with free Elementor. Dynamic tags and the Query ID option of loops are Elementor Pro features.

= Does it send data anywhere? =

No. The only outgoing message is the feedback form, which emails the author when you press Send. Nothing is sent automatically.

= Where are the values stored? =

As normal post meta under the meta key you choose for each field.

= What happens to my data when I delete the plugin? =

Nothing, unless you enable "Delete data on uninstall" in the settings. Even then, values saved on posts are kept.

== Screenshots ==

1. Dashboard with stats, field groups and system status.
2. Visual field group builder with validation, admin columns and conditional logic.
3. The meta box on the post edit screen (conditional fields are hidden until needed).
4. Loop query rules for Elementor Pro Query IDs.

== Upgrade Notice ==

= 1.2.1 =
Renamed to Elementor Metafield and hardened input cleaning. Your data is untouched.

= 1.2.0 =
Adds 6 field types, validation, conditional logic, admin columns, import/export and new Elementor features. Existing data is untouched.

== Changelog ==

= 1.2.1 =
* Renamed to Elementor Metafield (Persian: متا فیلد المنتور). Works with or without Elementor.
* Security: stricter cleaning of saved field definitions; the menu icon style is now enqueued.

= 1.2.0 =
* Elementor: Meta Field Visibility on sections, columns, containers and widgets.
* Elementor: loop query rules by Query ID (Settings > Loop Queries).
* Elementor: new Meta Field Link widget; dynamic tags support the new field types.

= 1.1.0 =
* New field types: file, link, time, date and time, embed and range.
* Validation: required, min/max length and unique values.
* Conditional logic between fields.
* Admin list columns (sortable) and filters.
* JSON import/export and duplicate group.

= 1.0.2 =
* New name and branding, top-level menu and dashboard.
* Settings page with a feedback form.
* Builder: type icons, duplicate field, expand/collapse all, copy shortcode.
* Optional "delete data on uninstall".
* Security and coding-standards fixes reported by Plugin Check.

= 1.0.0 =
* First release.
