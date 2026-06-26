# Project Plan Overview: Mak8it BotLens

**Mak8it BotLens** is a Generative Engine Optimization (GEO) and AI Crawler management suite for WordPress. It dynamically generates sitemaps for LLMs (`llms.txt` and `llms-full.txt`), logs crawler bot traffic (with IP verification), and integrates AI access controls directly with `robots.txt`.

---

## 1. Feature List

### Free Tier Features
*   **Dynamic Routing**: Virtual generation of `/llms.txt` and `/llms-full.txt` with zero physical file write overhead.
*   **robots.txt AI Controller**: Control bot crawling behavior using switches linked to the native WordPress `robots_txt` filter hook.
*   **IP-Verified Logging**: Monitors and stores AI crawler traffic logs. Checks incoming requests against a bundled static IP range list.
*   **Retention Engine**: Automatic deletion of database log entries older than 30 days via daily `wp-cron` job.
*   **Text-Meaningful Dashboard**: Simple, high-utility stats overview page showing verified/spoofed/unverified metrics and recent log entries (no charts).
*   **Onboarding Scan**: Detects if standard site settings or other SEO tools are globally blocking AI bots in `robots.txt` and offers a one-click fix.

### Pro Tier Features (Stubs / Placeholders)
*   **Auto-Updating Bot Registry**: Automatically fetches fresh IP lists from official crawler operator JSON feeds weekly via background cron jobs.
*   **Advanced Analytics & Export**: Filters logs by date/bot/type and supports exporting logs to CSV/Excel format.
*   **WooCommerce Product Integration**: Includes products, descriptions, prices, and stock states automatically in the sitemap.
*   **Schema Audit & Scanner**: Scans pages for rich schema markup validity (Product, Article, LocalBusiness) to maximize AI citations.
*   **Multisite Compatibility**: Virtual routing and individual logs configured separately for network-wide installations.

---

## 2. File & Folder Architecture

```
mak8it-botlens/
├── mak8it-botlens.php              # Main loader, defines constants, hooks activator/deactivator
├── uninstall.php                   # Database cleanup (deletes tables/options) on plugin deletion
├── includes/
│   ├── class-activator.php        # Runs on activation: creates database tables, sets initial options
│   ├── class-deactivator.php      # Runs on deactivation: flushes rewrite rules, cleans temp cache
│   ├── class-router.php           # Intercepts rewrite queries for llms.txt and llms-full.txt
│   ├── class-llms-generator.php   # Generates formatted Markdown output for llms.txt & llms-full.txt
│   ├── class-bot-tracker.php      # Compares visitor User-Agent/IP, registers logs in DB
│   ├── class-ip-registry.php      # Stores, parses, and provides IP ranges (handles cache & fallback)
│   ├── class-robots-controller.php# Hooks into WordPress robots_txt filter to write dynamic directives
│   └── class-seo-bridge.php       # Detects and extracts meta descriptions/noindex from Yoast/RankMath/AIOSEO
├── admin/
│   ├── class-admin.php            # Registers menu pages, scripts, styles, and dashboard meta boxes
│   ├── views/
│   │   ├── dashboard.php          # Stats dashboard UI containing cards and onboarding alerts
│   │   ├── settings.php           # Interface for bot toggles and data settings
│   │   └── logs.php               # Detailed paginated view of bot logs
│   └── assets/
│       ├── admin.css              # Custom layout, styling, and glassmorphic variables
│       └── admin.js               # Handles AJAX toggles, test requests, and pagination
└── data/
    └── ip-ranges-fallback.json     # Baseline bundled IP ranges (CIDR blocks) for offline validation
```

---

## 3. Database Schema

We track bot traffic using a custom table to ensure speed and prevent cluttering standard WordPress tables.

### Table Name: `wp_mbl_bot_logs`
Prefix: `wp_mbl_` (or custom WP table prefix + `mbl_`)

| Column Name | Data Type | Key / Index | Nullable | Description / Value Range |
|---|---|---|---|---|
| `id` | BIGINT | PRIMARY KEY | No | Auto-incrementing identifier |
| `bot_name` | VARCHAR(50) | INDEX | No | Slug matching the bot (e.g. `gptbot`, `claudebot`) |
| `user_agent` | TEXT | None | No | Raw User-Agent string sent by visitor |
| `ip_address` | VARCHAR(45) | None | No | IPv4 or IPv6 address of the crawling agent |
| `requested_url`| TEXT | None | No | The relative or absolute path requested by the bot |
| `timestamp` | DATETIME | INDEX | No | UTC timestamp when the request occurred |
| `verification_status`| VARCHAR(20) | None | No | Enumeration: `verified`, `unverified`, `spoofed` |

*Note: The `verification_status` determines bot trust level directly:*
*   `verified`: User-agent matches bot definition AND visitor IP is verified inside known ranges.
*   `spoofed`: User-agent matches but IP falls outside known ranges (malicious parser/scraper).
*   `unverified`: User-agent matches but range list is stale/fetch failed, or bot uses reverse DNS only (e.g. ClaudeBot).

---

## 4. Key Technical Decisions

### 1. Dynamic Virtual Routing
*   **Decisions**: We use WordPress `add_rewrite_rule()` and intercept the request on the `template_redirect` hook instead of writing static `.txt` files to the filesystem.
*   **Why**: File writing can fail on shared hosting setups or secure platforms due to permission blocks. Virtual routing is 100% reliable across all hosting systems.

### 2. IP Verification Strategy
*   **Decisions**: Verification utilizes CIDR block comparison. When a crawler hits the site:
    1. Check if the User-Agent matches a known AI crawler.
    2. Check the visitor IP. If it is within the operator's known ranges (fetched from transient or fallback JSON), status is `verified`.
    3. If the User-Agent claims to be a bot but the IP falls outside the ranges, status is `spoofed`.
    4. If the range database is currently stale, unreachable, or verification relies on unsupported protocols, status defaults to `unverified`.
*   **Why**: Scrapers routinely spoof user-agents to bypass crawl blocks. Strict IP range checking is the only way to log accurate crawler activity.

### 3. SEO Bridge Logic
*   **Decisions**: When compiling sitemaps, we query active post meta fields from:
    *   **Yoast SEO**: `_yoast_wpseo_metadesc`
    *   **Rank Math**: `_rank_math_description`
    *   **AIOSEO v3 (Legacy)**: `_aioseop_description`
    *   **AIOSEO v4+ (Modern)**: Stored in custom query parameters or standard database arrays. We will implement compatibility logic to support both versions of AIOSEO.
    *   *Fallback*: Standard post excerpts, then post content snippets. We also respect their respective `noindex` values.
*   **Why**: Users have already spent effort writing descriptions and configuring privacy settings in their primary SEO plugins. Reusing this metadata keeps the database clean, avoids third-party API costs, and ensures search engine consistency.

### 4. Caching Strategy
*   **Decisions**: The dynamic outputs of `/llms.txt` and `/llms-full.txt` are cached in WordPress transients. The transient is immediately busted using hooks on `save_post`, `delete_post`, `transition_post_status`, and settings updates.
*   **Why**: Reduces database and server processing overhead to zero for repetitive crawler requests, preventing server crashes on high-frequency bot crawls.

---

## 5. AI Bot Registry (Baseline Configuration)

| Bot / Operator | User-Agent Identifier Regex | Purpose | JSON IP Range Source URL |
|---|---|---|---|
| **ChatGPT Search** | `OAI-SearchBot` | Real-time Search Indexing | `https://openai.com/searchbot.json` |
| **GPTBot** | `GPTBot` | AI Training Scraper | `https://openai.com/gptbot.json` |
| **ChatGPT User** | `ChatGPT-User` | Custom User Prompts / Browsing | `https://openai.com/chatgpt-user.json` |
| **Claude Bot** | `ClaudeBot` | AI Search & Training Scraper | *None (Reverse DNS verification only)* |
| **Claude Web** | `Claude-Web` | User-initiated Browsing | *None (Reverse DNS verification only)* |
| **Google Extended** | `Google-Extended` | Google AI Training | `https://developers.google.com/static/crawling/ipranges/common-crawlers.json` |
| **Gemini User** | `Gemini-User` | Google Gemini User Queries | `https://developers.google.com/static/crawling/ipranges/common-crawlers.json` |
| **Perplexity** | `PerplexityBot` | Real-time Search Indexing | *None (Reverse DNS / User-Agent only)* |
| **Applebot Extended**| `Applebot-Extended` | Apple Intelligence Training | `https://www.apple.com/ipranges/applebot.json` |
| **Common Crawl** | `CCBot` | Open-web dataset crawler | *None (User-Agent only)* |

---

## 6. Data Flow Diagram

```
[Incoming Request]
        │
        ▼
[Check User-Agent against bot list]
        │
        ├── No Match ──► [Normal WP Page Execution]
        └── Match Found
                │
                ▼
        [IP Verification against registry]
                │
                ▼
        [Assign status: verified / spoofed / unverified]
                │
                ▼
        [Write log entry to wp_mbl_bot_logs]
                │
                ▼
        [Continue normal WP execution]
        (robots.txt directives handled separately 
         on robots_txt filter hook — not here)
```

---

## 7. WordPress Hooks Used

### Core Registration Hooks
*   `register_activation_hook` / `register_deactivation_hook`: DB setup and rewrite rule configuration.
*   `init`: Register rewrite rules and listen for general crawler requests.
*   `query_vars`: Allow WordPress to recognize `mkit_bl_feed` as a valid query parameter.
*   `template_redirect`: Intercepts virtual `/llms.txt` and `/llms-full.txt` urls and serves raw text output.

### Admin UI & Actions
*   `admin_menu`: Registers the main "Mak8it BotLens" dashboard and settings screens.
*   `admin_enqueue_scripts`: Loads local CSS (`admin.css`) and JS (`admin.js`).
*   `robots_txt`: Appends custom crawler blocks dynamically.
*   `wp_ajax_mbl_save_settings`: AJAX endpoint to save bot configurations.
*   `wp_ajax_mbl_clear_logs`: AJAX endpoint to reset tracking tables.

### Maintenance Cron
*   `mbl_daily_cleanup_cron`: Action hook executed daily to delete logs older than 30 days.

---

## 8. Known Risks & Mitigations

| Risk | Impact | Mitigation Strategy |
|---|---|---|
| **Stale IP Cache** | High (Real bots flagged as "unverified" or "spoofed" if IPs change) | Bundle a robust fallback JSON list in `data/ip-ranges-fallback.json` updated with every release, and refresh the cached ranges weekly. |
| **Multisite Domain Matching** | Medium (llms.txt served globally instead of per subsite) | Filter query routes and check `get_current_blog_id()` during rewrite resolution to serve site-specific page lists. |
| **High Bot Traffic Database Load** | High (Bot logs consuming megabytes of DB space) | Use index optimizations on `bot_name` and `timestamp` fields, and strictly enforce the 30-day cron auto-cleanup query. |
| **SEO Conflict** | Medium (Double indexing if other plugins try to register `/llms.txt`) | Check for existing registered rules on `init`. Add notices if Yoast/RankMath has enabled their native `/llms.txt` feed, and gracefully deactivate our routing for it. |
