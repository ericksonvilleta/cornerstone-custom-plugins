# IBAW Custom Plugins for Cornerstone Landscape Supply

This repository serves as a centralized monorepo containing the full suite of custom-coded WordPress plugins developed specifically for **Cornerstone Landscape Supply**. 

These plugins extend site functionality by delivering bespoke **Elementor page builder widgets**, registering custom post types (CPTs) programmatically—eliminating reliance on heavy CPT plugins while seamlessly integrating with **ACF Pro**—and implementing specialized logic tailored for a **semi-headless WooCommerce architecture**.

---

## 🚀 Technical Stack & Environment

* **Hosting Environment:** Cloudways
* **Core Platform:** WordPress + WooCommerce (Semi-Headless Configuration)
* **Page Builder:** Elementor Pro
* **Custom Fields & Data Schema:** ACF Pro (Advanced Custom Fields)
* **Author:** Erick Villeta
* **Plugin URI:** [https://ericksonvilleta.com](https://ericksonvilleta.com)

---

## 📦 Directory & Repository Structure

Each plugin resides in its own root-level directory to maintain isolation and facilitate modular deployments or maintenance.

```text
cornerstone-custom-plugins/
├── .github/
│   └── workflows/
│       └── deploy.yml                       # Automated SFTP deployment via GitHub Actions
│
├── Custom Post Types & Inventory Modules
│   ├── cornerstone-contractor-directory/    # Contractor directory & profile listings
│   ├── ibaw-playset-inventory/              # Playsets CPT & spec management
│   ├── ibaw-shed-inventory/                 # Shed CPT, inventory & detail management
│   ├── ibaw-tractor-inventory/              # Tractor CPT & layout compliance
│   ├── mower-inventory/                     # Commercial & residential mower listings
│   └── power-tool-plugin/                   # ECHO & power tool inventory management
│
├── Custom Elementor Widgets & UI Components
│   ├── IBAW- Text Hover Concepts/           # Dynamic hover effects for typography
│   ├── IBAW-Image-Sequence-Widget/          # Interactive image sequence player
│   ├── IBAW-elementor-video-widget/         # Customized video player widget
│   ├── ibaw-carousel-widget/                # Multi-purpose content carousel
│   ├── ibaw-contact-bar/                    # Sticky/floating contact bar
│   ├── ibaw-content-block/                  # Reusable dynamic content blocks
│   ├── ibaw-elementor-carousel/             # Enhanced Elementor slider & carousel
│   ├── ibaw-features-grid/                  # Grid layout display for product features
│   ├── ibaw-glass-slider/                   # Glassmorphic slider component
│   ├── ibaw-hero-showcase/                  # High-impact hero section widget
│   ├── ibaw-image-card-widget/              # Stylized image & call-to-action cards
│   ├── ibaw-included-features/              # Standard vs. upgrade feature list widget
│   ├── ibaw-linked-carousel/                # Synchronized multi-slider component
│   ├── ibaw-logo-marquee/                   # Infinite logo ticker
│   ├── ibaw-marquee-text/                   # Continuous looping text marquee
│   ├── ibaw-product-card/                   # Custom WooCommerce/CPT product display card
│   ├── ibaw-product-highlight/              # Featured product showcase block
│   ├── ibaw-subtitle-manager/               # Dynamic subtitle management widget
│   ├── ibaw-tractor-slider/                 # Showcase slider for tractor inventory
│   ├── mulch-calculator-widget/             # On-page bulk mulch yardage calculator
│   └── shed-custom-gallery/                 # Specialized photo gallery for shed models
│
├── E-Commerce & Logistics Customizations
│   ├── cls-shed-delivery/                   # Delivery calculation & rules for sheds
│   ├── cornerstone-delivery-waiver/         # On-site delivery waiver collection
│   ├── cornerstone-shed-extras/             # Custom add-ons & options for sheds
│   ├── erick-building-quotes/               # Dynamic custom quote request engine
│   ├── hide-categories-products-woocommerce/# Catalog visibility control rules
│   ├── ibaw shed filters/                   # Faceted inventory filtering system
│   ├── ibaw-auto-open-minicart/             # Auto-trigger minicart upon addition
│   ├── ibaw-cart-custom-buttons/            # Additional utility actions in cart view
│   ├── ibaw-clear-cart-button/              # One-click cart emptying module
│   ├── ibaw-cls-logistics/                  # Freight, delivery zones, and routing rules
│   ├── ibaw-email-stacker/                  # Notification batching & routing
│   ├── ibaw-local-delivery/                 # Distance & zone-based local delivery rules
│   ├── ibaw-restrict-states/                # State-level delivery/sales restriction rules
│   └── ibaw-woocommerce-auth/               # Custom auth flows & checkout bridges
│
├── Performance & Core System Utilities
│   ├── IBAW-Allow-GLB-Uploads/              # Enables 3D model (.glb/.gltf) media uploads
│   ├── erick-menu-svgs/                     # Native SVG support & icon injection for menus
│   ├── erick-speed-optimizer/               # Asset cleanup, script deferral, and tuning
│   ├── erick-video-lightbox/                # Lightweight video modal script
│   ├── ibaw-bulk-status-changer/            # Quick administrative batch status updating
│   ├── ibaw-cls-browser-cache-buster/       # Automatic cache busting for assets
│   ├── ibaw-core-manager/                   # Core settings & plugin suit controller
│   ├── ibaw-elite-menu/                     # Advanced mega-menu system
│   ├── ibaw-header-auth/                    # Header account status & quick login/logout
│   ├── ibaw-lightspeed-force-sync/          # Lightspeed POS inventory synchronization
│   ├── ibaw-mobile-sidebar-fix/             # Off-canvas mobile navigation patches
│   ├── ibaw-prevent-avif/                   # Prevents automatic AVIF conversion issues
│   ├── ibaw-responsive-login/               # Custom responsive login/registration modal
│   ├── ibaw-throttle-heartbeat/             # Custom WP Heartbeat API frequency limits
│   ├── ibaw-ui-protection/                  # Restricts access to key administrative pages
│   ├── ibaw-webp-optimizer/                 # WebP conversion & image optimization handling
│   └── menu-item-images/                    # Custom featured images on navigation items
│
├── .gitignore                               # OS files, temporary logs, and secret exclusions
└── README.md
```
## 🛠️ Key Architectural Highlights

### 1. Semi-Headless WooCommerce Architecture
To maintain maximum site speed and precise control over the checkout funnel, these plugins bypass standard WooCommerce template hooks where appropriate. Custom cart handlers, state restriction rules, and automated shipping calculators process data via AJAX/REST API endpoints, feeding seamless UI components built on top of Elementor.

### 2. Programmatic CPTs & Local ACF JSON
Custom Post Types (such as Sheds, Tractors, Mowers, and Power Tools) are registered programmatically through individual plugin wrappers. Custom field groups are version-controlled via localized `acf-json` directories inside each plugin, ensuring field definitions sync automatically across local, staging, and production environments without manual exports.

### 3. Performance Optimization
* **Asset Loading:** Custom Elementor widgets only enqueue JS/CSS assets when the widget is actively present on the page.
* **Heartbeat & Caching:** `ibaw-throttle-heartbeat` and `ibaw-cls-browser-cache-buster` lower server load on Cloudways and prevent stale assets from being served post-deployment.

---

## ⚙️ Deployment Workflow

This repository uses **GitHub Actions** for continuous integration and automated deployment to the Cloudways hosting server.

* **Branch Strategy:**
  * `main` — Deploys directly to the live production server environment.
  * `staging` — (Optional) Deploys to the Cloudways staging application.
* **Automated SFTP:** The `.github/workflows/deploy.yml` pipeline listens for pushes to `main`, isolates modified plugin files, and deploys them to `/wp-content/plugins/` on the server.

---

## 👨‍💻 Developer Guidelines

When contributing or adding new plugins to this monorepo:

1. **Naming Convention:** Prefix all plugin folders and main entry files with `IBAW-` or `ibaw-` (e.g., `ibaw-new-feature/ibaw-new-feature.php`).
2. **Plugin Header:** Always include full author metadata in the main PHP file:

```php
<?php
/**
 * Plugin Name: IBAW - Feature Name
 * Plugin URI:  [https://ericksonvilleta.com](https://ericksonvilleta.com)
 * Description: Custom functionality for Cornerstone Landscape Supply.
 * Version:     1.0.0
 * Author:      Erick Villeta
 * Author URI:  [https://ericksonvilleta.com](https://ericksonvilleta.com)
 */
```
3. **ACF JSON Sync:** Ensure `acf-json` loading and saving paths are scoped locally to the specific plugin folder using the `acf/settings/save_json` and `acf/settings/load_json` hooks.
