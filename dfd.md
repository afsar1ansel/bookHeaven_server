# Data Flow Diagrams (DFD) - BookHeaven

This document contains the Data Flow Diagrams (DFD) for the BookHeaven application at Level 0, Level 1, and Level 2.

---

## 🔝 1. DFD Level 0: Context Diagram

The Context Diagram shows the entire system as a single process and its interactions with external entities.

```mermaid
graph TD
    %% Entities
    Customer((Customer))
    Admin((Administrator))
    PaymentGateway((Payment Gateway))

    %% System
    System[("BookHeaven System")]

    %% Interactions - Customer
    Customer -- "Search/Browse Books" --> System
    Customer -- "Register / Login Info" --> System
    Customer -- "Cart Updates / Checkout" --> System
    System -- "Book List / Details" --> Customer
    System -- "Auth Status / Profile" --> Customer
    System -- "Order Confirmation" --> Customer

    %% Interactions - Admin
    Admin -- "Login Info" --> System
    Admin -- "Add/Edit/Delete Books" --> System
    Admin -- "Update Order Status" --> System
    System -- "Sales Reports / Order List" --> Admin

    %% Interactions - Payment Gateway
    System -- "Process Transaction" --> PaymentGateway
    PaymentGateway -- "Payment Success/Failure" --> System
```

---

## 📂 2. DFD Level 1: Functional Breakdown

Level 1 breaks down the system into its primary functional processes and shows the data flow between them and the data stores.

```mermaid
graph TD
    %% Entities
    U((Customer))
    A((Admin))
    PG((Payment Gateway))

    %% Processes
    P1["1.0 User Management"]
    P2["2.0 Catalog Browsing"]
    P3["3.0 Cart Management"]
    P4["4.0 Order Processing"]
    P5["5.0 Catalog Administration"]

    %% Data Stores
    D1[("D1 - users")]
    D2[("D2 - books")]
    D3[("D3 - carts")]
    D4[("D4 - orders")]

    %% User Management (Login/Register)
    U -- "Credentials" --> P1
    P1 -- "Verify/Store" <--> D1
    P1 -- "Auth Result" --> U

    %% Catalog Browsing
    U -- "Search Query" --> P2
    P2 -- "Fetch Books" --> D2
    P2 -- "Book Metadata" --> U

    %% Cart Management
    U -- "Add/Remove Item" --> P3
    P3 -- "Store State" <--> D3
    P3 -- "Cart Total" --> U

    %% Order Processing
    U -- "Checkout/Payment" --> P4
    P4 -- "Create Order" --> D4
    P4 -- "Update Inventory" --> D2
    P4 -- "Transaction" --> PG
    PG -- "Status" --> P4
    P4 -- "Clear Cart" --> D3
    P4 -- "Order Status" --> U

    %% Catalog Administration
    A -- "Book Data" --> P5
    P5 -- "Update Catalog" <--> D2
```

---

## 🔍 3. DFD Level 2: Detailed Order Checkout Process (Process 4.0)

Level 2 provides a detailed view of the **Order Processing** function, showing how the system handles a checkout request.

```mermaid
graph TD
    %% Entities
    U((Customer))
    PG((Payment Gateway))

    %% Detailed Processes (Subprocesses of 4.0)
    P4_1["4.1 Validate Inventory"]
    P4_2["4.2 Calculate Total/Tax"]
    P4_3["4.3 Initiate Payment"]
    P4_4["4.4 Record Order"]
    P4_5["4.5 Clear User Cart"]

    %% Data Stores
    D2[("D2 - books")]
    D3[("D3 - carts")]
    D4[("D4 - orders")]

    %% Flow
    U -- "Initiate Checkout" --> P4_1
    P4_1 -- "Check Stock" --> D2
    P4_1 -- "Confirm Availability" --> P4_2
    
    P4_1 -- "Out of Stock Error" --> U

    P4_2 -- "Total Amount" --> P4_3
    
    P4_3 -- "Payment Request" --> PG
    PG -- "Payment Authorized" --> P4_4
    PG -- "Payment Declined" --> U

    P4_4 -- "Save Transaction" --> D4
    P4_4 -- "Decrement Stock" --> D2
    P4_4 -- "Success Signal" --> P4_5

    P4_5 -- "Remove Items" --> D3
    P4_5 -- "Order Success Notification" --> U
```

---

### *Key Details:*
- **D1 (users)**: Stores both Customer and Administrator profiles (unified model).
- **D2 (books)**: Contains the catalog, price data, and stock quantities.
- **D3 (carts)**: Session-based or persistent JSON store of active cart items.
- **D4 (orders)**: Immutable record of transactions, including snapshots of purchased items.
