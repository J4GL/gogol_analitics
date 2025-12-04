# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- Real-time Events table now positioned at the top of the traffic page for immediate visibility
- Privacy-preserving IP address hashing using SHA256 with User-Agent as salt

### Changed
- IP addresses are now hashed before storage instead of storing plain text IPs
- Real-time Events table displays truncated IP hash (first 12 characters) with full hash in tooltip

### Fixed
- Invalid time format in Real-time Events table - now displays as HH:MM:SS instead of locale-dependent format
- Most Viewed Pages now shows only page paths (e.g., `/page`) instead of full URLs
- Top Sources now displays only domain names (TLDs) instead of full URLs
- Top Referring Websites now displays only domain names (TLDs) instead of full URLs


## [0.1.0] - 2025-12-02
### Added
- Pie Chart for "Top Sources" (Direct, Websites, Search Engines) in Traffic view.
- New "Top Referring Websites" table in Traffic view.
- `ReferringSitesStats` field to `TrafficPageData` model.
- Mock data generation for the new analytics fields.

### Changed
- Replaced "User Agent" table with "Browser" table in Traffic view.
