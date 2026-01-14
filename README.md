# SyncEngine Core

SyncEngine is a modular automation and data synchronization engine designed for building reliable, transparent, and
high-performance integrations between systems.

It combines the flexibility of custom integrations with a structured execution model that provides previews,
execution history, and deep observability out of the box.

SyncEngine is built for people who care not just that an integration runs, but why, how, and with what data it ran.

Whether you’re building advanced automation flows, connecting third-party APIs, or orchestrating entire business
processes, SyncEngine provides the performance, flexibility, and transparency you need.

## What is SyncEngine?

SyncEngine is a modular automation framework that lets you define, execute, and inspect data flows between systems, 
running as a self-hosted service or managed platform.

At its core, it provides:

- Explicit automation flows composed of reusable routines and tasks
- Preview and live execution modes
- Structured traces that record execution history
- An extensible architecture for both prebuilt modules and custom logic

You can use SyncEngine to:

- Synchronize data between platforms (e.g. webshops, PIMs, ERPs, CRMs)
- Orchestrate complex, multi-step business processes
- Build robust integrations without reinventing logging, retries, and execution tracking
- Replace fragile custom scripts with a maintainable integration framework

## Key Capabilities

- **Flows & Routines**  
Build automations from reusable building blocks with predictable execution.
- **Preview & Live Execution Modes**  
Inspect data and behavior before running an automation live.
- **Execution History (Traces)**  
Every run is recorded with inputs, outputs, decisions, and metadata.
Trace retention is configurable and automatically managed.
- **Built-in Observability**  
Understand what happened, when it happened, and why.
- **Extensible by Design**  
Add custom tasks or install modules without modifying Core.

## Core & Modules

SyncEngine is split into two layers:

- Core provides the execution engine, observability, and extensibility.
- Modules add platform-specific connections, blueprints, and templates that reduce setup complexity for common systems.

Modules improve usability without hiding how automations actually work.

## When SyncEngine Is a Good Fit

SyncEngine is well suited if you:

- Maintain integrations that need to be reliable and debuggable
- Work with recurring or high-volume data synchronization
- Want visibility into execution history, not just logs
- Prefer structured integrations over fragile custom scripts

SyncEngine is actively evolving, with a strong focus on improving usability and onboarding through modules and blueprints.  
Today, it is best suited for integrations that need to be understood, maintained, and trusted over time.

## Documentation & Resources

- [Installation Guide](https://docs.syncengine.io/user/installation/)
- [Documentation](https://docs.syncengine.io/)
  - Contributions are very welcome in our [Docs repository](https://github.com/SyncEngine/docs)

## Support

Community support is currently handled via GitHub and documentation contributions.
Additional community channels are planned as the project grows.

Customers using the hosted SyncEngine service have access to direct support via email.

Our goal is to grow a healthy community around SyncEngine while providing reliable,
professional support for production use cases.

## Licensing & Commercial Sustainability

SyncEngine is [fair-code](https://faircode.io) distributed under a **SyncEngine Core License**
for self-hosted use, personal projects, and client-specific integrations.
This license is inspired by the Sustainable Use License and the Business Source License.

Offering the software as SaaS or managed hosted service requires a **Commercial / Enterprise License**.

Our licensing model exists to ensure the platform can be sustainably maintained and improved,
while preventing commercial free-riding that would undermine long-term development.

For full details, see [Core License](LICENSE.md) and [Commercial License](LICENSE_COMMERCIAL.md).  
For a comparison table, see [License Comparison](https://docs.syncengine.io/#license-comparison)

## Contributing

Bug reports 🐛, improvements ✨ and ideas 💡 are very welcome! See the Contributing Guide to get started.

### Join the Team

Help us shape the future of Secure, Scalable and Automated Data Synchronization!
Please contact us at [info@syncengine.io](mailto:info@syncengine.io)

## Why SyncEngine?

Many automation tools focus on getting something running as quickly as possible.
They often trade long-term clarity, debuggability, and performance for convenience.

SyncEngine takes a different approach:

- It treats execution history as a first-class concept, not just logs.
- It makes previewing and live execution explicit choices.
- It favors predictable, inspectable behavior over hidden automation.
- It is designed to handle recurring and bulk synchronizations reliably.
