# Changelog

All notable changes to `laravel-firestore` will be documented in this file.

## [1.0.0] - 2025-05-01

### Added
- Initial release with Laravel integration
- Eloquent-like model layer for Firestore documents
- Service Provider with automatic configuration
- Laravel Facade for easy access
- Model features:
  - Timestamps (createdAt, updatedAt)
  - Soft Deletes
  - Attribute casting
  - Mass assignment protection
  - Custom field types (Timestamp, GeoPoint, Reference)
- Query builder with Eloquent-like syntax
- Collection macros for Firestore data handling
- Nested collection support:
  - HasNestedCollections trait
  - User-scoped models
  - Parent-child relationship traversal
- Authentication support:
  - Service Account authentication
  - Email/Password authentication
  - Anonymous authentication
- Configuration system:
  - Environment-based configuration
  - Model defaults
  - Cache settings
- Artisan command for model generation

### Changed
- Forked from bensontrent/firestore-php
- Restructured codebase for better Laravel integration
- Moved to PSR-4 autoloading
- Updated namespace to Frakt24\LaravelPHPFirestore
- Improved error handling with Laravel-style exceptions

### Removed
- Direct REST API methods in favor of model layer
- Legacy authentication handling
- Non-Laravel specific features
