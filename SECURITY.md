# Security Policy

## Evidence

Treat Sentinel evidence as production diagnostic data. Do not record secrets, access tokens, credentials, raw payment-card data, or unnecessary PII. The default recursive sanitizer redacts common sensitive keys; applications should extend the redaction list for domain-specific fields.

## Dashboard

The dashboard is disabled by default. When enabled, the default middleware requires `web`, `auth`, and `can:viewSentinel`. The host application must define the `viewSentinel` authorization gate.

## Remediation

Remediation is disabled by default. Do not enable global automatic remediation. Remediation implementations must be idempotent, auditable, and safe under retries. Ambiguous remote side effects must be reconciled before an irreversible local repair is attempted.

## Multi-node deployments

Use a shared lock-capable cache such as Redis and a durable queue. File/array cache does not provide cross-node evaluation serialization.

## Reporting

Please report security issues privately to the package maintainer rather than opening a public issue containing exploit details or production evidence.
