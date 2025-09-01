# Database Schema

This document describes the custom database tables used by the Mobility Trailblazers plugin.

## `wp_mt_candidates`

Stores the data for the candidates.

| Column                 | Type                | Description                                      |
| ---------------------- | ------------------- | ------------------------------------------------ |
| `id`                   | `bigint(20)`        | Primary key.                                     |
| `slug`                 | `varchar(255)`      | The candidate's slug.                            |
| `name`                 | `varchar(255)`      | The candidate's name.                            |
| `organization`         | `varchar(255)`      | The candidate's organization.                    |
| `position`             | `varchar(255)`      | The candidate's position.                        |
| `country`              | `varchar(255)`      | The candidate's country.                         |
| `linkedin_url`         | `varchar(255)`      | The URL to the candidate's LinkedIn profile.     |
| `website_url`          | `varchar(255)`      | The URL to the candidate's website.              |
| `article_url`          | `varchar(255)`      | A URL to an article about the candidate.         |
| `description_sections` | `longtext`          | A JSON-encoded array of the description sections.|
| `photo_attachment_id`  | `bigint(20)`        | The ID of the candidate's photo in the media library. |
| `post_id`              | `bigint(20)`        | The ID of the associated WordPress post.         |
| `import_id`            | `varchar(255)`      | The ID of the candidate from the import file.    |
| `created_at`           | `timestamp`         | The date and time the record was created.        |
| `updated_at`           | `timestamp`         | The date and time the record was last updated.   |

## `wp_mt_jury_assignments`

Stores the assignments of candidates to jury members.

| Column           | Type         | Description                                      |
| ---------------- | ------------ | ------------------------------------------------ |
| `id`             | `bigint(20)` | Primary key.                                     |
| `jury_member_id` | `bigint(20)` | The ID of the jury member (post ID).             |
| `candidate_id`   | `bigint(20)` | The ID of the candidate (post ID).               |
| `assigned_at`    | `datetime`   | The date and time the assignment was made.       |
| `assigned_by`    | `bigint(20)` | The ID of the user who made the assignment.      |

## `wp_mt_evaluations`

Stores the evaluations of candidates by jury members.

| Column                 | Type            | Description                                      |
| ---------------------- | --------------- | ------------------------------------------------ |
| `id`                   | `bigint(20)`    | Primary key.                                     |
| `jury_member_id`       | `bigint(20)`    | The ID of the jury member (post ID).             |
| `candidate_id`         | `bigint(20)`    | The ID of the candidate (post ID).               |
| `status`               | `varchar(20)`   | The status of the evaluation (`draft` or `completed`). |
| `comments`             | `longtext`      | The jury member's comments.                      |
| `courage_score`        | `decimal(3,1)`  | The score for the "Courage & Pioneer Spirit" criterion. |
| `innovation_score`     | `decimal(3,1)`  | The score for the "Innovation Degree" criterion. |
| `implementation_score` | `decimal(3,1)`  | The score for the "Implementation & Impact" criterion. |
| `relevance_score`      | `decimal(3,1)`  | The score for the "Mobility Transformation Relevance" criterion. |
| `visibility_score`     | `decimal(3,1)`  | The score for the "Role Model & Visibility" criterion. |
| `total_score`          | `decimal(3,1)`  | The total score for the evaluation.              |
| `created_at`           | `datetime`      | The date and time the evaluation was created.    |
| `updated_at`           | `datetime`      | The date and time the evaluation was last updated. |

## `wp_mt_audit_log`

Stores a log of all significant events in the plugin.

| Column        | Type            | Description                                      |
| ------------- | --------------- | ------------------------------------------------ |
| `id`          | `bigint(20)`    | Primary key.                                     |
| `user_id`     | `bigint(20)`    | The ID of the user who performed the action.     |
| `action`      | `varchar(255)`  | The action that was performed.                   |
| `object_type` | `varchar(255)`  | The type of object that was affected.            |
| `object_id`   | `bigint(20)`    | The ID of the object that was affected.          |
| `details`     | `longtext`      | A JSON-encoded array of additional details.      |
| `ip_address`  | `varchar(100)`  | The IP address of the user.                      |
| `user_agent`  | `varchar(255)`  | The user agent of the user.                      |
| `created_at`  | `datetime`      | The date and time the event occurred.            |
