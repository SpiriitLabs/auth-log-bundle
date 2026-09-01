---
description: "Run the test suite, code style checks and static analysis — the bundle enforces 100% line coverage in CI."
---

# Testing

The bundle ships with a full test suite and enforces 100% line coverage in CI.

```bash
composer test              # Run the test suite
composer cs-check          # Check code style (dry-run)
composer cs-fix            # Fix code style
vendor/bin/phpstan analyse # Static analysis
```

Contributions are welcome: [open an issue or a pull request](https://github.com/SpiriitLabs/auth-log-bundle/issues), or write to [dev@spiriit.com](mailto:dev@spiriit.com). Released under the MIT License.
