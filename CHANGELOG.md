## [1.1.0] - 2026-07-22
- Pluggable response formatter so a host can reshape the envelope without forking
- Before/after middleware hooks per action so a host can adapt requests and responses (owner injection, payload enrichment, pagination shape)

## [1.0.0] - 2026-06-27
- Channel-agnostic campaigns scoped per owner via owner-access
- Source-tagged event log (clicks, opens, impressions, signups, conversions, ...) with optional value, subject, visitor hash and flexible metadata
- Per-source attribution analytics: engagements, unique visitors, conversions, conversion rate, value, plus by-source / by-type / by-day breakdowns
- One recordEvent entry point on the campaign for any channel, and an authenticated events endpoint
- Dependency-free user-agent classification and privacy-preserving visitor signature helpers
