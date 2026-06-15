# Oh Dear Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 5.7.2 - 2026-06-15

### Fixed
- `registerUtilityTypes()` no longer throws `Call to a member function can() on null` on CP requests without a logged-in user (e.g. while an SSO login is in progress). The current-user permission check is now null-safe.

## 5.7.1 - 2026-05-21

### Fixed
- Cache-miss and stale-cache warnings produced by `cachedViaCron` now carry the same `name` and `label` (e.g. `Security Vulnerabilities`, `Abandoned Packages`) as a successfully computed result, instead of the fully-qualified class name. Also fixed `Check::getName()` to return the short class name on Linux, where `basename()` did not split on backslashes.

## 5.7.0 - 2026-05-21

### Added
- The CVE and Abandoned Packages health checks can opt into cron-refreshed caching via `->cachedViaCron(int $staleAfterSeconds)`. When enabled, the health-check endpoint reads the cached result instead of spawning composer subprocesses inline (which can take several seconds per advisory). Run `craft ohdear/health-check/refresh` on cron to populate the cache.
- New console command `craft ohdear/health-check/refresh` iterates registered checks and refreshes the cached result for those that opted into `cachedViaCron`.

### Changed
- `CveCheck` and `AbandonedPackagesCheck` now share a single `composer audit` invocation when both run in the same process (refresh command, or one HTTP request with caching disabled), eliminating the duplicate audit subprocess.

## 5.6.3 - 2026-05-20

### Fixed
- The CVE and Abandoned Packages health checks now copy `composer.phar` to a unique per-call path and validate the copy succeeded. This prevents concurrent checks from clobbering each other's phar (which surfaced as `Cannot open phar archive` errors) and surfaces a clear warning if the phar can't be prepared.

## 5.6.0 - 2026-04-24

### Added
- Added a health check that calls the `backup/monitor` craft command to monitor backups managed by the [Craft Backup](https://github.com/webhubworks/craft-backup) plugin.

## 5.5.0 - 2026-04-20

### Fixed
- The CVE and Abandoned Packages health checks no longer crash with a cryptic TypeError when `composer audit` produces non-JSON output. They now return a warning result carrying Composer's actual error message.

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
