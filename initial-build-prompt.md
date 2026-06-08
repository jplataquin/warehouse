# PROJECT NAME: "Warehousing Information System"

## ROLE
You are a Laravel web development expert. Your goal is to develop a warehousing information system that complies with user specs and requirements.

## RULES, TOOLS, AND SOFWARE TO USE
- Laravel version 13
- Bootstrap version 5.3.8
- MySQL
- Javascript
- PHP 8.3
- Mobile first responsive design 

## THE APPLICATION 
Build a Web based application for warehousing invetory and item movement control for a construction company with multiple central and site warehouses. This application will not record the in and out of invetory in real time but will rely on historical input from designated loggers. 

## There will be three users:

1. Admin - They can access all areas and can create, edit, update, delete users.
2. Supervisor - They can create, edit, update, and delete warehouses, projects, items, and allocations.
3. Logger - They are in recording input of items.

## There will be two types of items:

1. Consumable - Items that doesn't need to return after it is logged out.
2. Recoverable - Items that can return to the warehouse after it is logged out.

## There will be two types of warehouse:

1. Central Warehouse
2. Site Warehouse

## Model relationships
- Project has many Site Warehouses
- Project has many Allocations
- Site Warehouse belongs to one Project
- Site Warehouse can have many Loggers
- Site Warehouse belongs to one Project
- Loggers can have many Site Warehouses and Central Warehouse
- Central Warehouse does not belong to any Project


## TABLES STRUCTURES

### items (Has Soft Delete)
- id (primary key)
- type (ENUM: CONSUMABLE / RECOVERABLE)
- name
- specification
- unit

### warehouses (Has Soft Delete)
- id
- project_id
- type (ENUM: SITE / CENTRAL)
- name
- status

### allocations (Has Soft Delete)
- id (primary key)
- warehouse_id (foreign key)
- component_id (foreign key)
- name

### warehouse_loggers
- id
- user_id (foreign key)
- warehouse_id (foreign key)


### ledger (Has Soft Delete)
- id (primary key)
- type (ENUM: IN / OUT)
- action (ENUM: TRANSFER / DELIVERY / DIRECT / ALLOCATE / DISPOSE / LOST / RETURN / MAINTENANCE / CORRECTON)
- item_id (foreign key)
- quantity
- status (ENUM: PENDING / APPROVED)
- po_number (string, nullable)
- offical_receipt (string,nullable)
- delivery_receipt (string,nullable)
- warehouse_id (foreign key)
- destination_warehouse_id (nullable, foreign key)
- source_warehouse_id (nullable, foreign key)
- allocation_id (nullable, foreign key)
- assigned_to (nullable,string)
- remarks (text)


## Ledger Action Defenition
TRANSFER    - When an item is transfered from warehouse to warehouse
DELIVERY    - When an item is logged in from a supplier or vendor to a warehouse
DIRECT      - When an recoverable item is logged back in to a warehouse after maintenance or repair
ALLOCATE    - When a consumable item is logged out for use in a construction work
DISPOSE     - When an item is no longer usable and needs to be dispoed
LOST        - When an item is logged out because it was stolen or missing
RETURN      - When an item is logged out so it can be returned to a supplier or vendor 
MAINTENANCE - When a recoverable item is logged out for repair and maintenance
CORRECTON   - Can only be done by an admin to correct or offset a quantity log in or log out 


## Objective
- The system must track the in and out allocation quantity of consumable items.
- The system must use only a single chronological ledger table to record inventory for all warehouses.
- The system must track the in and out of recoverable items.
- The system must validate complex dynamic rules when items are logged in or logged out depending on the selected action.


## Dynamic validation of ledger record will depend on the following conditions
OUT TRANSFER CONSUMABLE     = destination_warehouse_id
IN  TRANSFER CONSUMABLE     = source_warehouse_id, allocation_id, log_entry_id

OUT TRANSFER RECOVERABLE    = destination_warehouse_id
IN  TRANSFER RECOVERABLE    = source_warehouse_id, assigned_to, log_entry_id

-------------------------------------------------------------------------

IN  DELIVERY    CONSUMABLE                = allocation_id, delivery_receipt, po_number
IN  CORRECTION  CONSUMABLE                = remarks

OUT ALLOCATE    CONSUMABLE                = allocation_id
OUT DISPOSE     CONSUMABLE                = remarks
OUT LOST        CONSUMABLE                = remarks
OUT RETURN      CONSUMABLE                = remarks, delivery_receipt, po_number
OUT CORRECTION  CONSUMABLE                = remarks

-------------------------------------------------------------------------

IN DELIVERY     RECOVERABLE               = assigned_to, delivery_receipt, po_number
IN DIRECT       RECOVERABLE               = remarks
IN CORRECTION   RECOVERABLE               = remarks

OUT LOST        RECOVERABLE               = remarks
OUT DISPOSE     RECOVERABLE               = remarks
OUT MAINTENANCE RECOVERABLE               = remarks
OUT RETURN      RECOVERABLE               = remarks, delivery_receipt, po_number
OUT CORRECTION  RECOVERABLE               = remarks

