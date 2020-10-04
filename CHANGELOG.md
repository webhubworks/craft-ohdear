# Oh Dear Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 1.2.1 - 2020-10-04
### Fixed
- Fixed a validation issue that occurred when settings the site ID as env var

## 1.2.0 - 2020-10-04
### Added
- Settings can now be environment variables

### Changed
- Improved uptime heatmap UI
- Settings page has now auto-suggest fields for API token and site ID
    - The new settings page should apply existing settings without problems.
- Available sites are no longer loaded from Oh Dear when an API token is provided on the settings page, instead the user has to provide the site ID directly
    - Here's why: As a provider for Craft maintenance we have a lot of client sites monitored in Oh Dear. When installing Craft Oh Dear on their sites, none of the client's employees should be able to see the site names from other clients on the settings page. Admittedly, there is a permission to not allow the settings page for users, but not for admin users.
    
### Fixed
- Fixed an issue that ignored widget settings on which checks are visible

## 1.1.2 - 2020-10-03
### Fixed
- Fixed incompatibilities with a new version of the Oh Dear API

## 1.1.1 - 2020-08-18
### Fixed
- Fixed a bug where a custom CP trigger leads to broken links (in a broken link monitor OMG) in the control panel
- Fixed minor bug in check cards

## 1.1.0 - 2020-05-28
### Added
- Added error handling if API key in use has been accidentally deleted in Oh Dear
### Changed
- Improved algorithm that tries to find the element edit page of a broken link or mixed content item, it searches in entries, global sets, matrix blocks and assets now
### Fixed
- Fixed typo in the mixed content report component
- Minor UI improvements

## 1.0.2 - 2020-05-27
### Fixed
- Fixed a bug that broke the CLI

## 1.0.1 - 2020-05-27
### Fixed
- Fixed possible UI error if no broken link or mixed content element could be found
- Fixed error if modifying element edit page redirect but request referrer is unavailable
- Prepare broken link and mixed content element finding algorithm for further improvement

## 1.0.0 - 2020-05-26
### Added
- Initial release
