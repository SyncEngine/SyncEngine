# Contributing to SyncEngine

Thank you for contributing to SyncEngine! Your efforts help us build a more reliable, transparent, and scalable platform for data synchronization.

---

## How to Contribute

### 1. **Reporting Bugs**

- Open an issue on [GitHub Issues](https://github.com/SyncEngine/SyncEngine/issues).
- Include steps to reproduce, expected vs. actual behavior, and relevant logs or screenshots.

### 2. **Suggesting Features**

- Start a discussion in [GitHub Discussions](https://github.com/SyncEngine/SyncEngine/discussions) to gather feedback and refine your proposal.
- Once aligned, open an issue with the `enhancement` label.

### 3. **Code Contributions**

- Fork the repository and create a feature branch.
- Follow the [coding standards](#coding-standards).
- Submit a pull request (PR) with a clear description and reference any related issues.

### 4. **Documentation**

- Improve or add documentation in the [SyncEngine Docs](https://docs.syncengine.io/).
- Submit PRs to the [docs repository](https://github.com/SyncEngine/SyncEngine-docs).

---

## Coding Standards

- **Formatting**: Use the provided `.editorconfig` file for auto-formatting (based on Symfony with modifications).
- **AI-Generated Code**: Permitted, but must be manually verified and fully understood by the contributor.
- **Backward Compatibility**: Breaking changes must be documented and include a migration script.

---

## Development Setup

SyncEngine is fully Symfony-based. For setup, review the [Symfony Development Environment Guide](https://symfony.com/doc/current/setup.html).

1. Clone the repository:
  ```bash
   git clone https://github.com/SyncEngine/SyncEngine.git
  ```
2. Install dependencies:
  ```bash
   composer install
  ```
3. Build frontend assets:
  ```bash
   npm install
   npm run build
  ```

---

## Code Review Process

- All PRs require at least one approval from a maintainer.
- PRs are reviewed for code quality, tests, and alignment with SyncEngine’s goals.
- Address feedback promptly.

---

## Community & Support

- Join [Discussions](https://github.com/SyncEngine/SyncEngine/discussions).
- Report bugs by creating a [Issue](https://github.com/SyncEngine/SyncEngine/issues).
- For urgent matters, contact [info@syncengine.io](mailto:info@syncengine.io).

---

## License

By contributing, you agree your contributions will be licensed under the [SyncEngine Core License](https://github.com/SyncEngine/SyncEngine/blob/main/LICENSE).

---

## Contributor License Agreement

That we do not have any potential problems later it is sadly necessary to sign a [Contributor License Agreement](CONTRIBUTOR_LICENSE_AGREEMENT.md). That can be done literally with the push of a button.

We used the most simple one that exists. It is from [Indie Open Source](https://indieopensource.com/forms/cla) which uses plain English and is literally only a few lines long.

Once a pull request is opened, an automated bot will promptly leave a comment requesting the agreement to be signed. The pull request can only be merged once the signature is obtained.

---

**Thank you for helping build the future of data synchronization!**
