# Oh Dear Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 5.2.2 - 2025-05-13

### Added
- Added default description to queue health check job

## 5.2.1 - 2024-11-21

### Changed
- Moved plugin registrations depending on Craft's initialization into onInit() method

## 5.2.0 - 2024-07-23

### Added
- New health check: CVE (Monitor installed packages for known vulnerabilities using Composer Audit)
- New health check: Abandoned (Monitor installed packages for abandoned packages using Composer Audit)

### Changed
- Optimized wording in existing health checks

## 5.1.0 - 2024-07-19

### Added
- Added new Lighthouse check 💡🏠

### Changed
- Improved Broken Link and Mixed Content checks

## 5.0.0 - 2024-07-18

Craft Oh Dear for Craft CMS 5 is now available.

### Fixed

- Missing custom plugin name in widget
