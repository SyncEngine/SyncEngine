
# Entities

All engine entities have the following props:

## Engine

There are 5 core engine entities, all containing the following properties.

Props
- ID (int)
- Ref (string 255) `A unique reference to this entity.`
- Created (datetime)
- Modified (datetime)
- Name (string 255)
- Description (string 255)
- Supervisor (string 255) `A reference to the object or blueprint that maintains this entity`
- Config (JSON) `User configuration for this entity`
- Data (JSON) `Holds any other information like state, cache etc.`

### Connection
Connects and authenticates to external API/Sources

### Automation
Run actions based on triggers.

Extra props
- Endpoint (string 255) slug for this automation URL.

### Flow
Creates a dataflow.

### Routine
Creates a single step that can be used in a dataflow

### Storage
Stores various types of data

Extra props
- Type (string 255)

## Trace

Props
- ID (int)
- Automation ID (relationship to Automation)
- Created (datetime)
- Status (string 255)
- Trace (JSON)

