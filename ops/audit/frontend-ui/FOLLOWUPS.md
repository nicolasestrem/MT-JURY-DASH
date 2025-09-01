Potential risks and follow-ups
- Remove duplicate v4 enqueues from `class-mt-plugin.php`; keep v4 in `MT_Public_Assets` only.
- Gate legacy CSS on v4 routes; optionally dequeue via `get_legacy_css_handles()`.
- Consolidate assignments handlers into one module; namespace events.
- Replace `all: unset` Elementor override with targeted scope.
- Reduce `!important` in legacy CSS; migrate to v4 component styles.
- Switch to minified assets in production; conditionally enqueue per route.
