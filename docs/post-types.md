# Custom Post Types

This document describes the custom post types registered by the Mobility Trailblazers plugin.

## Candidate (`mt_candidate`)

Represents a candidate in the awards program.

*   **Post Type:** `mt_candidate`
*   **Supports:** Title, Editor, Thumbnail, Excerpt
*   **Capabilities:** `mt_candidate`, `mt_candidates`

### Meta Fields

*   `_mt_organization`: The candidate's organization.
*   `_mt_position`: The candidate's position.
*   `_mt_linkedin_url`: The URL to the candidate's LinkedIn profile.
*   `_mt_website_url`: The URL to the candidate's website.

## Jury Member (`mt_jury_member`)

Represents a member of the jury.

*   **Post Type:** `mt_jury_member`
*   **Public:** False
*   **Supports:** Title, Editor, Thumbnail
*   **Capabilities:** `mt_jury_member`, `mt_jury_members`

### Meta Fields

*   `_mt_user_id`: The ID of the associated WordPress user.
*   `_mt_expertise`: The jury member's area of expertise.
