# IBAW Custom Plugins for Cornerstone Landscape Supply

This repository contains the suite of custom-coded WordPress plugins developed specifically for **Cornerstone Landscape Supply**.

These plugins are designed to enrich the Elementor page builder with custom addon widgets and to register custom post types (CPTs) programmatically—eliminating the need for bulkier CPT plugins and working seamlessly with ACF Pro.

Additionally, these plugins are tailored for a **semi-headless WooCommerce architecture**, handling specialized checkout logic and bypassing native WooCommerce shipping and tax rules.

---

## 🚀 Stack & Environment

* **Hosting Environment:** Cloudways
* **Core Platform:** WordPress + WooCommerce (Semi-Headless Configuration)
* **Page Builder:** Elementor Pro
* **Custom Fields & CPTs:** ACF Pro (Advanced Custom Fields)
* **Author:** Erick Villeta
* **URI:** [ericksonvilleta.com](https://ericksonvilleta.com)

---

## 📦 Repository Structure

This repository uses a **Monorepo** structure. All custom plugins are isolated in their respective folders under the root directory:

```text
cornerstone-custom-plugins/
├── .github/
│   └── workflows/
│       └── deploy.yml                 # GitHub Actions automated SFTP deployment
├── ibaw-power-tool-inventory/         # ECHO Power Tool CPT & eCommerce bridge
│   ├── ibaw-power-tool-inventory.php  # Main plugin file
│   └── acf-json/                      # Version-controlled ACF field groups
├── ibaw-tractor-inventory/            # Tractors CPT & layout compliance
│   ├── ibaw-tractor-inventory.php
│   └── acf-json/
├── .gitignore                         # OS junk and secret exclusions
└── README.md                          # Repository documentation
