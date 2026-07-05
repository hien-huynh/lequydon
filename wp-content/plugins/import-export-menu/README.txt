=== Import Export Menu ===
Contributors: yukyhendiawan
Donate link: https://yukyhendiawan.com/
Tags: import, export, menu, menus, navigation
Requires at least: 6.0
Tested up to: 7.0
Stable tag: 3.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Import, export, and manage WordPress navigation menus. Safely migrate menu structures between sites with hierarchy, locations, and item settings intact.

== Description ==

Import Export Menu makes it easy to move WordPress navigation menus between sites and manage them from one place. Export a menu to a portable file, then import it into another WordPress install — keeping the menu hierarchy, theme location assignments, and per-item settings intact.

Unlike a raw JSON dump, the importer remaps each menu item to the matching content on the target site (by slug, path, then title), so links keep working after a migration instead of pointing at the wrong post ID.

Beyond import and export, manage every menu from one dashboard: duplicate or delete menus, and control who sees each item with per-item visibility rules (everyone, logged-in, or logged-out).

== Key Features ==

* Menus dashboard — see every menu with its item count, theme location, and last-modified date at a glance.
* Duplicate any menu in one click (items, hierarchy, and visibility rules included), or delete one with a safety confirmation.
* Per-item visibility — show or hide each menu item for everyone, logged-in users only, or logged-out visitors only.
* Export one menu, several, or every menu at once to a portable file.
* Import menus back into any WordPress site, and optionally assign the imported menu to a theme location.
* Smart object remapping so menu items resolve to the right content on the target site.
* Preserves menu hierarchy, item order, CSS classes, and theme location assignments.
* Choose how to import: create a new menu, replace an existing one, or merge — with a one-click undo of the last import.
* Works with any theme that uses the standard WordPress menu system.

== Installation ==

1. Upload the `import-export-menu` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress "Plugins" screen.
2. Activate the plugin through the "Plugins" menu in WordPress.
3. Open the "Import Export Menu" entry in the admin sidebar to start importing and exporting menus.

== Frequently Asked Questions ==

= Can I export more than one menu at a time? =

Yes. You can select one menu, several, or export every menu on the site at once.

= What file format does the plugin use? =

Menus are exported as a versioned JSON file, which the importer reads back to reconstruct the menu.

= Will my menu links still work after importing on a different site? =

The importer remaps each item to matching content on the target site by slug, then path, then title. When no match is found you decide whether to keep it as a custom link or skip it.

= Is the plugin compatible with all WordPress themes? =

It works with any theme that supports the standard WordPress navigation menu system.

= Can I use this plugin to migrate menus between different WordPress sites? =

Yes. Export the menu from one site and import the file into another.

= Can I show or hide a menu item based on login status? =

Yes. Every menu item has a Visibility option in the menu editor — show it to everyone, only logged-in users, or only logged-out visitors. The rule travels with the menu when you export and import it.

= Can I duplicate or delete a menu? =

Yes. Open Import Export Menu → Menus and click Duplicate on any menu; the copy keeps every item, the hierarchy, and the visibility rules. You can also delete a menu from the same screen, with a confirmation prompt.

== Screenshots ==

1. The Menus dashboard: every menu with its item count, theme location, and last-modified date, plus Duplicate, Edit, and Delete actions.
2. Exporting menus to a portable JSON file.
3. Importing a menu — choose the mode and an optional theme location.
4. Per-item Visibility control in the native menu editor.

== Changelog ==

= 3.0.0 =
* New: Menus dashboard listing every menu with its item count, theme location, and last-modified date.
* New: Duplicate a menu in one click — items, hierarchy, and visibility rules included.
* New: Delete a menu from the dashboard, with a confirmation prompt.
* New: Per-item visibility — show or hide each menu item for everyone, logged-in users, or logged-out visitors.
* New: Optionally assign an imported menu to a theme location, with a report of any menu it replaces.
* New: Undo the last import to restore your menus from an automatic backup.
* Improved: refreshed admin interface with a consistent button and panel design.

== Upgrade Notice ==

= 3.0.0 =
First public release of the rebuilt plugin: a Menus dashboard, per-item visibility, duplicate/delete, and a safer import/export.
