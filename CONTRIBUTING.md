# Contributing

1. Add or update tests for behavior changes.
2. Preserve the semantic distinction between business failure, unknown truth and Sentinel execution errors.
3. Do not add synchronous network calls to the default request-path trigger flow.
4. Do not weaken evidence redaction or remediation safety defaults.
5. Run `composer test` and `composer lint` before opening a pull request.
