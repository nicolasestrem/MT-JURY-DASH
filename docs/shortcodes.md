# Shortcodes

This document lists the shortcodes available in the Mobility Trailblazers plugin.

## `[mt_jury_dashboard]`

Displays the jury dashboard, which allows jury members to view and evaluate their assigned candidates.

**Usage:**

```
[mt_jury_dashboard]
```

## `[mt_candidates_grid]`

Displays a grid of candidates.

**Attributes:**

*   `category` (string): The candidate category to display.
*   `columns` (int): The number of columns in the grid. Default: `3`.
*   `limit` (int): The maximum number of candidates to display. Default: `-1` (all).
*   `orderby` (string): The field to order the candidates by. Default: `title`.
*   `order` (string): The order to display the candidates in (`ASC` or `DESC`). Default: `ASC`.
*   `show_bio` (string): Whether to show the candidate's biography. Default: `yes`.
*   `show_category` (string): Whether to show the candidate's category. Default: `yes`.

**Usage:**

```
[mt_candidates_grid category="innovators" columns="4"]
```

## `[mt_evaluation_stats]`

Displays evaluation statistics.

**Attributes:**

*   `type` (string): The type of statistics to display (`summary`, `by-category`, `by-jury`). Default: `summary`.
*   `show_chart` (string): Whether to display a chart. Default: `yes`.

**Usage:**

```
[mt_evaluation_stats type="by-category"]
```

## `[mt_winners_display]`

Displays the winners of the awards.

**Attributes:**

*   `category` (string): The category to display winners for.
*   `year` (int): The year to display winners for. Default: current year.
*   `limit` (int): The number of winners to display. Default: `3`.
*   `show_scores` (string): Whether to show the winners' scores. Default: `no`.

**Usage:**

```
[mt_winners_display category="innovators" year="2024"]
```
