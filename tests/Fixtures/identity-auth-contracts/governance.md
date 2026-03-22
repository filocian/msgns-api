# Identity Auth Contract Fixtures Governance

## Scope
- Applies to `tests/Fixtures/identity-auth-contracts/v2-baseline` and `tests/Fixtures/identity-auth-contracts/current-contract`.
- Covers signup/login success and error payload schema-lock fixtures.

## Ownership
- Primary owner: backend identity maintainers.
- Secondary owner: frontend auth service maintainers (mirrored consumer subset).

## Update Protocol
1. Every fixture change must include a PR note with rationale and compatibility impact.
2. Changes under `v2-baseline` require an explicit `baseline-approved` note in rollout evidence.
3. CI must never regenerate fixtures implicitly; updates are manual and reviewed.
4. Additive keys are allowed only when `locked_paths` remain backwards compatible.

## Versioning Rules
- `v2-baseline` is immutable by default and represents pre-hardening consumer expectations.
- `current-contract` tracks active contract locks and can evolve with explicit compatibility review.
- Fixture file names are stable: `signup-success.json`, `signup-error.json`, `login-success.json`, `login-error.json`.
