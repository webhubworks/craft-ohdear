# Oh Dear Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 5.4.0 - 2026-03-17

### Added
- Added a health check for whether the `allowAdminChanges` general config setting is enabled.

## 5.3.1 - 2025-08-29

### Fixed
- Fixed and improved the handling of errors related to the plugin settings.

## 5.3.0 - 2025-08-28

### Added
- Support the latest version of the Oh Dear API and PHP SDK.

## 5.2.3 - 2025-06-02

### Fixed
- Prevent unnecessary API calls against the Oh Dear API.

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
