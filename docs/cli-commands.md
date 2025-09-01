# CLI Commands

This document details the WP-CLI commands available in the Mobility Trailblazers plugin.

## `wp mt import-candidates`

Imports candidates from an Excel file and attaches their photos.

### Usage

```
wp mt import-candidates [--excel=<path>] [--photos=<path>] [--dry-run] [--backup] [--delete-existing]
```

### Options

*   `--excel=<path>`: Path to the Excel file. 
*   `--photos=<path>`: Path to the directory containing candidate photos.
*   `--dry-run`: Perform a dry run without making any changes to the database.
*   `--backup`: Create a backup of the existing candidates before importing. Defaults to `true`.
*   `--delete-existing`: Delete all existing candidates before importing.

## `wp mt db-upgrade`

Runs the database upgrade process manually.

### Usage

```
wp mt db-upgrade
```

## `wp mt list-candidates`

Lists all candidates in the database.

### Usage

```
wp mt list-candidates [--format=<format>]
```

### Options

*   `--format=<format>`: The output format. Can be `table`, `json`, `csv`, or `yaml`. Default: `table`.
