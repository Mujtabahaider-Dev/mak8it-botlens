# Developer Plan: Mak8it BotLens

This document serves as the technical development specification and phase-by-phase implementation plan for **Mak8it BotLens**. 

---

## 1. Developer Context & Stack
*   **Workflow**: Solo developer, AI-assisted vibe coding.
*   **Goal**: Ship a fully functional, free V1 to the WordPress.org plugin repository.
*   **Environment**: Local development (`boldmcqs.test`), no automated CI/CD pipeline.
*   **Stack**: Pure PHP, Vanilla Javascript (ES6), Vanilla CSS, native WordPress APIs. No external npm libraries, bundlers, or SaaS dependencies in the free tier.
*   **Testing**: Each phase must produce a manually testable feature before moving to the next.

---

## 2. Naming Conventions (Strictly Enforced)

*   **Plugin Name**: `Mak8it BotLens`
*   **Folder Name**: `mak8it-botlens`
*   **Main Plugin File**: `mak8it-botlens.php`
*   **Text Domain**: `mak8it-botlens`
*   **WordPress.org Slug**: `mak8it-botlens`
*   **DB Table Prefix**: `wp_mbl_` (e.g. `wp_mbl_bot_logs`)
*   **PHP Class Prefix**: `MBL_` (e.g. `MBL_Bot_Tracker`)
*   **Function Prefix**: `mbl_`
*   **Option Prefix**: `mbl_`
*   **Hook Prefix**: `mbl_`
*   **Constants Prefix**: `MBL_`

---

## 3. File & Folder Structure

```
mak8it-botlens/
├── mak8it-botlens.php              # Main loader, defines constants, hooks activation
├── uninstall.php                   # Cleanup routine: drops DB tables, clears mbl_ options
├── PLAN_OVERVIEW.md                # System design & architecture blueprint
├── DEVELOPMENT_PLAN.md             # Implementation phases & coding guidelines (this file)
├── includes/
│   ├── class-mbl-activator.php    # Setup DB schema, insert default settings, flush rules
│   ├── class-mbl-deactivator.php  # Deactivation logic: clears cache transients, flushes rewrites
│   ├── class-mbl-router.php       # Handles add_rewrite_rule & template_redirect routing
│   ├── class-mbl-llms-generator.php # Compiles posts/pages list into markdown format
│   ├── class-mbl-bot-tracker.php  # Intercepts frontend queries at priority 1 to log crawls
│   ├── class-mbl-ip-registry.php  # Validates IPs using CIDR ranges or reverse DNS methods
│   ├── class-mbl-robots-controller.php # Injects custom disallowed agents in robots_txt filter
│   └── class-mbl-seo-bridge.php   # Extracts existing metadata/noindex state from SEO plugins
├── admin/
│   ├── class-mbl-admin.php        # Registers admin pages, registers assets, hooks views
│   ├── views/
│   │   ├── dashboard.php          # Summary metric cards, alerts, and recent logs UI
│   │   ├── settings.php           # Setup keys, bots, retention periods, and Pro stubs
│   │   └── logs.php               # Paged log table view with filter controls
│   └── assets/
│       ├── admin.css              # Custom styling sheet
│       └── admin.js               # AJAX and pagination logic
└── data/
    └── ip-ranges-fallback.json    # Bundled offline database of validated bot CIDRs
```

---

## 4. Database Schema

### Table: `wp_mbl_bot_logs`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| `bot_name` | VARCHAR(50) | INDEX | Crawler bot key (e.g. `gptbot`, `perplexitybot`) |
| `user_agent` | TEXT | NOT NULL | Raw browser User-Agent string |
| `ip_address` | VARCHAR(45) | NOT NULL | IPv4 or IPv6 string |
| `requested_url` | TEXT | NOT NULL | Path requested by the bot |
| `timestamp` | DATETIME | INDEX | UTC time of request |
| `verification_status`| VARCHAR(20) | NOT NULL | State: `verified`, `unverified`, `spoofed` |

---

## 5. Bot Registry & Verification Logic

### Verified Bot Configuration
| Bot | User-Agent Key | Verification Method | Source URL (where applicable) |
|---|---|---|---|
| **GPTBot** | `GPTBot` | JSON Ranges | `https://openai.com/gptbot.json` |
| **OAI-SearchBot** | `OAI-SearchBot` | JSON Ranges | `https://openai.com/searchbot.json` |
| **ChatGPT-User** | `ChatGPT-User` | JSON Ranges | `https://openai.com/chatgpt-user.json` |
| **ClaudeBot** | `ClaudeBot` | Reverse DNS | *.anthropic.com |
| **Googlebot** | `Googlebot` | Reverse DNS | *.googlebot.com, *.google.com |
| **Google-Extended** | `Google-Extended` | Reverse DNS | *.googlebot.com, *.google.com |
| **PerplexityBot** | `PerplexityBot` | JSON Ranges | `https://perplexity.ai/perplexitybot.json` |
| **Applebot** | `Applebot` | Reverse DNS | *.apple.com |

*Note: CCBot and Meta-ExternalAgent do not publish verified ranges. Log them by User-Agent string matches only; always mark their status as `unverified`, never `spoofed`.*

---

## 6. Implementation Logic Rules

### IP Verification Pipeline
1.  On frontend init (priority 1), parse the User-Agent header.
2.  If the User-Agent does not match any bot in the registry, immediately abort and allow standard WP page rendering.
3.  If a match is found:
    *   **JSON-Source Verification**: Compare the IP against cached CIDR blocks (fetched from transient `mbl_ip_ranges_{bot_name}`, falling back to `data/ip-ranges-fallback.json` if transient is empty or expired).
    *   **Reverse DNS Verification**:
        1. Resolve hostname: `gethostbyaddr(ip)`.
        2. Verify hostname ends with the designated domain (e.g. `.googlebot.com` or `.google.com`).
        3. Resolve IP from hostname: `gethostbyname(hostname)`.
        4. Confirm resolved IP matches the visitor's IP.
4.  Assign `verification_status`:
    *   `verified`: IP successfully confirmed via JSON range or reverse DNS lookup.
    *   `spoofed`: User-agent matches, but IP verification fails (outside known ranges or reverse DNS fails).
    *   `unverified`: User-agent matches, but DNS check timed out/failed, range fetch is down/stale, or bot doesn't support verification.
5.  Insert the log row into `wp_mbl_bot_logs`.
6.  Continue normal page execution (do not block or send HTTP 403. Block instructions are served purely via the `robots.txt` output file).

### SEO Bridge & Content Generation
*   **Description Source Order**:
    1.  Yoast SEO: `_yoast_wpseo_metadesc`
    2.  Rank Math: `_rank_math_description`
    3.  AIOSEO v4+: Check `aioseo_posts` custom table
    4.  AIOSEO v3 legacy: `_aioseop_description`
    5.  Fallback: Excerpt (`wp_trim_words(get_the_excerpt(), 30)`)
*   **noindex Exclusion**: Check noindex metadata for Yoast (`_yoast_wpseo_meta-robots-noindex`), Rank Math (`_rank_math_robots`), and AIOSEO, plus core site reading options. Exclude any match from `/llms.txt`.
*   **Caching**:
    *   Sitemaps output cached in transients `mbl_llms_txt_cache` and `mbl_llms_full_cache` (24hr expiry).
    *   Cache busted on: `save_post`, `delete_post`, `transition_post_status`, and `mbl_settings_saved`.

---

## 7. Development Phases

### Phase 1: Skeleton & Virtual Routing
*   **Objective**: Setup plugin skeleton, custom activator, and dynamic routing to serve `/llms.txt` and `/llms-full.txt`.
*   **Files**:
    *   `mak8it-botlens.php`
    *   `includes/class-mbl-activator.php`
    *   `includes/class-mbl-deactivator.php`
    *   `includes/class-mbl-router.php`
    *   `uninstall.php`
*   **Manual Test**: Install the plugin. Visit `/llms.txt` and `/llms-full.txt`. Verify a plain-text response containing test strings is served with correct text/plain headers and no theme HTML headers/footers.
*   **Watchout**: Ensure rewrite rules are flushed on activation. Check if pretty permalinks are enabled; warn users in the admin area if they are on "Plain" permalinks.

### Phase 2: llms.txt Generation & SEO Bridge
*   **Objective**: Populate virtual files with real sitemap data, utilizing meta descriptions from SEO plugins and formatting output as markdown.
*   **Files**:
    *   `includes/class-mbl-seo-bridge.php`
    *   `includes/class-mbl-llms-generator.php`
*   **Update**: `includes/class-mbl-router.php` (swap test outputs with generator calls).
*   **Manual Test**: Check `/llms.txt`. Confirm it outputs formatted Markdown lists. Toggle a post to `noindex` in Yoast/RankMath and verify it immediately disappears from `/llms.txt`. Update a post and confirm caching transients bust correctly.

### Phase 3: IP Registry & Crawler bot Tracker
*   **Objective**: Implement request tracking, log insertion, CIDR lookup helper, and reverse DNS logic.
*   **Files**:
    *   `includes/class-mbl-ip-registry.php`
    *   `includes/class-mbl-bot-tracker.php`
    *   `data/ip-ranges-fallback.json`
*   **Manual Test**: Spoof user-agent (`User-Agent: GPTBot`) from a local browser/cURL. Verify a new log row is created in `wp_mbl_bot_logs` with status `spoofed` (since your local IP is outside OpenAI ranges). Verify a daily cleanup cron job is scheduled.

### Phase 4: robots.txt Controller
*   **Objective**: Dynamically append rules to the site's robots.txt based on allow/deny settings.
*   **Files**:
    *   `includes/class-mbl-robots-controller.php`
*   **Manual Test**: Visit `/robots.txt`. Toggle "GPTBot" to blocked in settings. Refresh `/robots.txt` and verify `User-agent: GPTBot \n Disallow: /` is appended to the output.

### Phase 5: Admin UI & Onboarding Dashboard
*   **Objective**: Build the settings toggles, logging table, and onboarding alerts dashboard.
*   **Files**:
    *   `admin/class-mbl-admin.php`
    *   `admin/views/dashboard.php`
    *   `admin/views/settings.php`
    *   `admin/views/logs.php`
    *   `admin/assets/admin.css`
    *   `admin/assets/admin.js`
*   **Manual Test**: Confirm dashboard cards populate correct counts. Test paginating log entries, filter options by status, and verify the AJAX settings/log clearing handles save successfully with proper security checks.

### Phase 6: Code Hardening & Lints
*   **Objective**: Security auditing. Clean up all direct DB calls with `$wpdb->prepare()`, sanitize all input strings, escape all outputs, verify nonces, and ensure compatibility across multiple PHP/WP environments.

### Phase 7: Repository Submission
*   **Objective**: Finalize documentation and asset preparation.
*   **Files**:
    *   `readme.txt` (valid WordPress.org format)
*   **Manual Test**: Run code through the official Plugin Check (PCP) tool to clean up any review team flags before submitting.

---

## 8. Known Gotchas & Failures

1.  **Page Builders in llms-full.txt**: Page builders store data as shortcodes or JSON. Always strip shortcodes and HTML tags manually using `strip_shortcodes()` and `wp_strip_all_tags()` directly from raw `post_content` to prevent corrupting the Markdown format.
2.  **Multisite Routing**: Rewrite rules hook per subsite. A site-wide `/llms.txt` file is technically distinct per domain. Defer network-wide consolidation to the Pro tier and display a multisite notice.
3.  **DNS Timeouts**: Reverse DNS (`gethostbyaddr`) has no default execution timeout in PHP. If the DNS server hangs, requests can slow down. Implement socket-based verification or set DNS checks to time out after 2 seconds.
4.  **Pretty Permalinks**: Virtual routing depends on pretty permalinks. If settings are "Plain", `/llms.txt` fails with a 404. Check `get_option('permalink_structure')` and output a persistent admin warning if empty.
