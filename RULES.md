You are a WordPress plugin review expert. Generate a RULES.md file for a 
WordPress plugin called "Mak8it BotLens" that will be submitted to the 
WordPress.org plugin repository.

This file will be used as a standing reference during AI-assisted development
to ensure every line of code written is compliant with WordPress.org plugin
review guidelines and avoids rejection.

## Format Requirements
- Written for a developer audience
- Every rule must include: the rule itself, why it exists, and a 
  concrete code example (wrong vs correct where applicable)
- Grouped into clear sections
- No fluff, no motivational language
- This file will be read at the start of every coding session

## Sections to Cover

### 1. File & Folder Standards
- Folder name must match plugin slug: mak8it-botlens
- Main file must match folder name: mak8it-botlens.php
- Every PHP file must start with: defined('ABSPATH') || exit;
- No executable code in plugin root except main loader file
- uninstall.php must exist and clean up everything the plugin creates
- readme.txt must follow WordPress.org format exactly (not readme.md)

### 2. Plugin Header Requirements
- Required fields: Plugin Name, Description, Version, Author, 
  Author URI, Text Domain, License
- Plugin Name must include author brand: "Mak8it BotLens"
- Text Domain must match folder name exactly: mak8it-botlens
- License must be GPLv2 or later
- No external URLs in plugin header that are not owned by the author
- Version must follow semantic versioning: 1.0.0

### 3. Prefixing Rules (Critical — most common rejection reason)
- Every function must be prefixed: mbl_
- Every class must be prefixed: MBL_
- Every hook (add_action/add_filter custom hooks) must be prefixed: mbl_
- Every option name (get_option/update_option) must be prefixed: mbl_
- Every transient name must be prefixed: mbl_
- Every global variable must be prefixed: mbl_
- Every database table must be prefixed: {wp_prefix}mbl_
- No generic names allowed: no function named "init()", "setup()", 
  "helper()" — must be mbl_init(), mbl_setup(), mbl_helper()
- Include wrong vs correct examples for each

### 4. Security Requirements
- All user input sanitized before use
  - Text: sanitize_text_field()
  - Email: sanitize_email()
  - URL: esc_url_raw()
  - Integer: absint() or intval()
  - HTML content: wp_kses_post()
  - Textarea: sanitize_textarea_field()
- All database queries use $wpdb->prepare() without exception
- All output escaped before rendering in HTML
  - Text: esc_html()
  - URLs: esc_url()
  - HTML attributes: esc_attr()
  - JavaScript: esc_js()
  - Textarea values: esc_textarea()
- All AJAX handlers must:
  - Verify nonce with wp_verify_nonce() or check_ajax_referer()
  - Check user capability with current_user_can('manage_options')
  - Die with wp_die() not exit() or die()
- All admin page callbacks must check current_user_can()
- No use of $_REQUEST — use $_POST or $_GET explicitly
- Nonces must be specific per action, not generic

### 5. Database Rules
- Always use $wpdb->prefix for table names
- Always use dbDelta() for table creation (not raw CREATE TABLE)
- Always check if table exists before creating
- Always add database version to options for future migration support
- Always clean up: drop tables and delete options in uninstall.php
- Never store sensitive data (passwords, API keys) in plain text
  in wp_options — use encryption or note as a Pro concern

### 6. WordPress API Usage Rules
- Never use PHP mail() — use wp_mail()
- Never use PHP curl directly — use WP_Http / wp_remote_get()
- Never use PHP session functions — WordPress does not use sessions
- Never use die() or exit() in normal flow — use wp_die() in admin
- Never use extract() — banned in WordPress coding standards
- Never use eval() — security risk, instant rejection
- Never use base64_encode/decode to obfuscate code — instant rejection
- Never suppress errors with @ operator
- Use wp_json_encode() not json_encode()
- Use wp_remote_get() not file_get_contents() for remote URLs
- Use wp_schedule_event() not custom cron solutions

### 7. Enqueueing Assets (Common Rejection)
- Never use <script> or <style> tags directly in PHP output
- Always use wp_enqueue_scripts() / admin_enqueue_scripts()
- Always register before enqueue
- Always include version parameter to bust cache
- Always use plugins_url() or plugin_dir_url() for asset URLs
- Never hardcode URLs
- Load admin assets only on plugin pages using $hook parameter
- Include wrong vs correct example

### 8. Remote Calls & External Services
- Any external URL the plugin calls must be declared in readme.txt
- For Mak8it BotLens specifically, declare:
  - openai.com/gptbot.json (IP range fetch)
  - openai.com/searchbot.json
  - openai.com/chatgpt-user.json
  - perplexity.ai/perplexitybot.json
- All remote calls must use wp_remote_get() with timeout argument
- Always check is_wp_error() on remote call response before using it
- Never make remote calls on every page load — use transients to cache
- Never make remote calls in the plugin activation hook

### 9. Licensing & Copyright
- All code must be GPLv2 or later compatible
- If including any third-party code or library, it must also be 
  GPLv2 compatible and credited in readme.txt
- No encrypted or obfuscated code of any kind
- No license key checks that phone home on every request
- Pro feature stubs are allowed but must not contain 
  upsell nag screens that cannot be dismissed

### 10. readme.txt Requirements
- Must use WordPress.org format with specific section headers
- Short description: 150 characters maximum, no markup
- Must include Changelog section
- Must include Frequently Asked Questions section
- Screenshots referenced must exist in /assets/ folder in SVN
- Stable tag must match version in plugin header exactly
- Tags: maximum 5 tags, all lowercase, relevant to actual features
- Tested up to: must be accurate WordPress version
- No marketing superlatives in description 
  ("best", "most powerful", "#1") — will be flagged

### 11. Upselling & Pro Features Rules
- Pro feature stubs and upgrade notices are allowed
- Must not redirect users to external pages without their action
- Must not add admin notices that cannot be dismissed
- Must not hijack existing WordPress UI elements
- Must not auto-redirect on activation to a sales page 
  (a one-time welcome page is acceptable)
- No spam: maximum one admin notice at a time
- All admin notices must be dismissible

### 12. Plugin Activation & Deactivation
- Activation hook: create tables, set defaults, flush rewrite rules
- Deactivation hook: flush rewrite rules, clear scheduled cron events
- Never run activation code on every page load — use version flag
- Never auto-redirect on activation (one-time welcome page is ok)
- uninstall.php must remove:
  - All custom database tables (wp_mbl_bot_logs)
  - All options (all mbl_ prefixed options)
  - All transients (all mbl_ prefixed transients)
  - All scheduled cron events (mbl_daily_cleanup, mbl_weekly_ip_refresh)

### 13. Coding Standards
- Follow WordPress PHP Coding Standards
- Use tabs for indentation, not spaces
- Opening braces on same line for classes and functions
- Yoda conditions: if ( 'value' === $variable ) not if ( $variable === 'value' )
- Single quotes for strings unless string contains variable
- Space inside parentheses: if ( $condition ) not if($condition)
- Class files named: class-mbl-{name}.php (lowercase, hyphens)
- No closing PHP tag at end of file

### 14. Things That Cause Instant Rejection
List these clearly as a DO NOT section:
- eval()
- base64_encode/decode used to hide code
- Calling external URLs on every page load without caching
- Hardcoded WordPress credentials or paths
- Modifying core WordPress files
- Writing to files outside the plugin directory without user permission
- Removing or overriding other plugins' functionality
- Tracking users without consent
- Phoning home without disclosure
- Using GPL-incompatible libraries
- Placeholder/test code left in submission
- WordPress.org username in Author field instead of real name/brand

### 15. Pre-Submission Checklist
Generate a checklist the developer runs through manually before 
submitting to WordPress.org:
- Code review checklist (prefixes, security, escaping)
- readme.txt completeness check
- Asset files check (icons, banners, screenshots)
- Clean install test procedure
- Plugin Check (PCP) plugin test procedure
- Common reviewer feedback items to self-check

## Additional Instructions
- Where a rule has a nuance specific to Mak8it BotLens 
  (e.g. the external URL declarations for IP range fetching), 
  call it out explicitly with a note labeled [BotLens Specific]
- Flag the top 5 most commonly cited rejection reasons at the 
  very top of the document as a quick-reference warning box
- The tone is direct and technical — this is a reference document, 
  not a tutorial