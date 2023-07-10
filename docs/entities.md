
# Entities

## Connection

Connects to external API/Sources

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Encrypted Config (JSON)
- Encrypted Data (JSON)

## Automation

Create a link between two connections

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Endpoint (string 255) slug for this automation URL.
- Flow (ManyToOne Relation to Flow)
- Config (JSON)
- Data (JSON)

## Flow

Creates a dataflow.

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Steps (JSON) *array of step id's
- Config (JSON)

## Step

Creates a single step that can be used in a dataflow

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Config (JSON)

## Dataset

Stores various types of data

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Type (string 255)
- Module (string 255)
- Config (JSON)
- Data (JSON)

---

# Would like to have:

## Trail

A group of steps in order.

Props
- ID (int)
- Name (string 255)
- Description (string 255)
- Steps (Mutli-Relationship to steps)
