# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2026-08-20

### Added

- Laravel 13 support: the `illuminate/*` constraints now allow `^13.0` and `orchestra/testbench` accepts `^11.0`. Laravel 12 remains fully supported — the suite passes against both.

### Changed

- CI now runs the test suite against Laravel 12 and Laravel 13.
