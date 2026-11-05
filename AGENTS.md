# Agent Guidelines

## Ledger Ordering Standard
- **Ordering Rule:** Whenever listing or querying `Ledger` records to be displayed (across all user types and views), the query MUST ALWAYS follow this exact sort order:
  1. `entry_date` (DESC) (`->latest('entry_date')`)
  2. `created_at` (DESC) (`->latest('created_at')`)
  3. `id` (DESC) (`->latest('id')`)
