
# Entities

## Connection

Connects to external API/Sources

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Config (JSON)
  - For example:
  - Token
  - Secret
  - etc.

## Automation

Create a link between two connections

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Endpoint (string 255)
- SourceConnection (One to Many Relation to Connection)
- TargetConnection (One to Many Relation to Connection)
- Flow (One to Many Relation to Flow)

## Flow

Creates a dataflow.

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- StepsOrder (One to Many Relationship to steps)

## StepOrder

The order in which steps are made within a Flow

Props
- ID (int)
- Flow (One to Many Relation to Flow)
- Step (One to Many Relation to Step)
- Position (Order number)

## Step

Creates a single step that can be used in a dataflow

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Config (JSON)

---

# Would like to have:

## Trail

A group of steps in order.

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Steps (Mutli-Relationship to steps)
