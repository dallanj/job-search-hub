---
paths:
  - 'app/Console/Commands/**'
---

# Commands

## Keep console output ASCII
Laravel's console output pipeline drops some non-ASCII glyphs (U+2192 "→" disappears entirely; em dashes survive). A status line written as "Company — Role → applied" prints as "Company — Role applied", which reads as prose and hides the mapped value.

Use ASCII separators ("/", "->") in command output, and assert the full line in tests (expectsOutputToContain with the whole message, not just one word) so a dropped glyph fails the test.
