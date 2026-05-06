# Execution Spec: 002-marketplace-identity-and-authentication

## Purpose

Introduce deterministic Marketplace identity and authentication infrastructure for Foundry.

This spec establishes:

- Marketplace user identity
- CLI authentication
- token storage
- authenticated Marketplace API access
- deterministic auth inspection behavior

## CLI Commands

```bash
foundry login
foundry logout
foundry whoami
```

## Acceptance Criteria

- users can authenticate
- identity is inspectable
- logout clears credentials
- authenticated API requests function
- all CLI output deterministic
