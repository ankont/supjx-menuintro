# SuperSoftJx - Menu Intro

Adds an optional, per-menu-item intro block that renders above the component.
You can either pick an Article (rendered via Joomla core layout, with edit icons) or enter custom content.

Author: SuperSoft — Kontarinis Andreas
Version: 1.0.11
Creation date: 2025-08-27
License: GPL-2.0+

---

## Features
- Extra fields on each Menu Item (stored in params).
- Two render modes:
  - Template (clean): inject manually the intro into the template
  - Auto: plugin injects the intro without template edits
- Article rendering via core layout (joomla.content.article) — respects template overrides and shows edit icons.
- Optional custom content (WYSIWYG).

## Installation
1. Install the ZIP via Extensions -> Manage -> Install.
2. Enable the plugin SuperSoftJx - Menu Intro.
3. (Optional) Switch render mode under the plugin options.

## Project Structure
- `plugin/` contains the Joomla plugin files that go into the installable ZIP.
- `build/` contains the build script and generated archives.
- `build/output/` contains generated ZIP artifacts.
- `build/stage/` is a temporary staging area used during packaging.
- `build.bat` is a Windows shortcut for the PowerShell build script.
- `.github/workflows/release.yml` publishes tagged releases to GitHub Releases.

## Build
Run one of the following from the repository root:

```powershell
.\build.bat
```

or:

```powershell
powershell -ExecutionPolicy Bypass -File .\build\build.ps1
```

The generated package is written to `build/output/plg_system_menuintro-v<version>.zip`.
The version is read from `plugin/menuintro.xml`.
The ZIP contains the contents of `plugin/` at the archive root, plus `README.md` and `LICENSE.txt`, so it stays Joomla-installable.

## GitHub Releases
This repository can publish the Joomla installable ZIP automatically through GitHub Actions.

Release flow:
1. Update the version in `plugin/menuintro.xml`.
2. Commit and push your changes.
3. Create a Git tag that matches the manifest version, prefixed with `v`:

```powershell
git tag v1.0.11
git push origin v1.0.11
```

4. GitHub Actions will:
   - validate that the tag matches the manifest version
   - run `build/build.ps1`
   - create or update a GitHub Release for that tag
   - upload the generated ZIP from `build/output/`

Important:
- The tag must match the manifest version. Example: tag `v1.0.11` must match manifest version `1.0.11`.
- The installable ZIP stays out of git history and is distributed through the GitHub Release page instead.

## Usage

### A) Template mode (clean / manual insert)
1. Set plugin — Render mode = Template.
2. In your template, before the main component, add:
   ```php
   <?php
     \Joomla\CMS\Plugin\PluginHelper::importPlugin('system', 'menuintro');
     if (class_exists('PlgSystemMenuintro')) {
       PlgSystemMenuintro::renderActiveMenuIntro();
     }
   ?>
   ```
3. Edit a Menu Item — tab Intro and fill in the fields.

### B) Auto mode (no template edits)
1. Set plugin — Render mode = Auto.
2. In Auto mode, the intro is injected directly into the component buffer during rendering.
   It will always appear above the component output, regardless of the template structure.

### C) Menu module helper text
Enable **Use as menu description** in the menu item's Intro tab to expose a resolved `menuintro_menu_text` value to compatible menu templates. It contains the selected article's accessible `introtext`, or the enabled custom intro text as a fallback. The option is disabled by default and does not affect the normal intro displayed on the page.

In a `mod_menu` template override:

```php
<?php $menuIntro = (string) $item->getParams()->get('menuintro_menu_text', ''); ?>
<?php if ($menuIntro !== '') : ?>
  <span class="menu-item__intro"><?php echo $menuIntro; ?></span>
<?php endif; ?>
```

The value may contain HTML. Escape or strip tags in the override when plain text is required.

## Styling
If you add, let's say, 	4-content-intro in the Container CSS class parameter,
you can put something like the following in your custom CSS:
```css
.t4-content-intro { margin-bottom: 1.5rem; }
.t4-content-intro .menu-intro__title { margin-bottom: .5rem; }
.t4-content-intro .menu-intro__content :is(p, ul, ol) { margin-bottom: .75rem; }
```

## Changelog
- 1.0.11 — 2026-09-23: Prevent the home menu intro from appearing on component-only routes without a matching `Itemid`.
- 1.0.10 — 2026-09-13: Add a per-menu-item option controlling whether intro content is exposed as `menuintro_menu_text`.
- 1.0.9 — 2026-09-13: Expose accessible article introtext or custom intro text to `mod_menu` layouts through `menuintro_menu_text`.
- 1.0.8 — 2026-09-12: Enforce the current user's authorised view levels before rendering the selected intro article.
- 1.0.7 — 2025-10-29: Auto mode — when "Use page title" is ON and "Show title" is enabled, move the page heading before the intro; if a custom title is set, override and use the selected heading tag; avoid duplicate headings.
- 1.0.6 — 2025-08-27: Debug Auto Article not showing.
- 1.0.5 — 2025-08-27: Add Yes/No toggles.
- 1.0.4 — 2025-08-27: Initial public release.

## About

Part of **SuperSoftJx — SuperSoft's Joomla eXtensions**.

Developed with help from ChatGPT.

© 2025-2026 SuperSoft — Kontarinis Andreas
