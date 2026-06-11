# Fomotoko Fullstack Engineer Assessment

A PHP-based solution for the PT Fomo Inovasi Teknologi fullstack engineer assessment, covering a race-condition-safe online store API and a CLI-based hidden item navigation game.

---

## Table of Contents

- [Task 1 — Online Store API](#task-1--online-store-api)
- [Task 2 — Hidden Item Game](#task-2--hidden-item-game)
- [Project Structure](#project-structure)
- [Requirements](#requirements)
- [Getting Started](#getting-started)

---

## Task 1 — Online Store API

A RESTful JSON API that handles flash sale orders with inventory management and race condition prevention.

### Business Rules

- An **Order** must contain at least one **Order Item**
- A **Product** inventory can never go below zero
- The system safely handles concurrent burst orders during a flash sale

### Technical Highlights

| Concern | Solution |
|---|---|
| Race condition | Pessimistic locking (`SELECT ... FOR UPDATE`) inside a transaction |
| Inventory safety | Database-level constraint + application-level guard |
| Response format | JSON with proper HTTP status codes |
| Concurrency test | Command-line functional test using parallel requests |

### Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/products` | List all products |
| `GET` | `/products/{id}` | Get a single product with inventory |
| `POST` | `/orders` | Create a new order |
| `GET` | `/orders/{id}` | Get order details |

### Example Request

```bash
curl -X POST http://localhost:8000/orders \
  -H "Content-Type: application/json" \
  -d '{
    "customer_name": "John Doe",
    "items": [
      { "product_id": 1, "quantity": 2 }
    ]
  }'
```

### Example Response

```json
{
  "status": "success",
  "data": {
    "order_id": 42,
    "customer_name": "John Doe",
    "total_price": 199.98,
    "items": [
      {
        "product_id": 1,
        "product_name": "Flash Sale Item",
        "quantity": 2,
        "unit_price": 99.99
      }
    ]
  }
}
```

### Running the Race Condition Test

```bash
php tests/race_condition_test.php
```

This spawns N concurrent requests all targeting the same limited-stock product and asserts that inventory never drops below zero.

---

## Task 2 — Hidden Item Game

A command-line game where the player navigates a fixed grid using a sequence of directional steps to locate a hidden item.

### Grid Layout

```
# # # # # # # #
# . . . . . . #
# . # # # . . #
# . . . # . # #
# X # . . . . #
# # # # # # # #
```

| Symbol | Meaning |
|---|---|
| `#` | Wall / obstacle |
| `.` | Clear path |
| `X` | Player start position |
| `$` | Probable item location |

### Navigation Rules

The player navigates in a fixed order:

1. **Up** — A steps north
2. **Right** — B steps east  
3. **Down** — C steps south

If any step hits a wall or goes out of bounds, that path is invalid.

### Demo

```
  HIDDEN ITEM GAME
────────────────────────────────────────────
  Grid legend:  # wall   . path   X start  $ item
────────────────────────────────────────────
  Starting position: row 4, col 1

  Enter movement steps:

  Up    (A): 3
  Right (B): 4
  Down  (C): 1

────────────────────────────────────────────
  [+] Item location found:

      Row 2, Column 5

 0  # # # # # # # #
 1  # . . . . . . #
 2  # . # # # $ . #
 3  # . . . # . # #
 4  # X # . . . . #
 5  # # # # # # # #
    0 1 2 3 4 5 6 7

  Path taken:  Up 3 → Right 4 → Down 1
────────────────────────────────────────────
```

### Running the Game

```bash
php task-2-game/hidden_item_game.php
```

---

## Project Structure

```
.
├── task-1-api/
│   ├── index.php               # API entry point
│   ├── routes/
│   │   ├── products.php
│   │   └── orders.php
│   ├── models/
│   │   ├── Product.php
│   │   ├── Order.php
│   │   └── OrderItem.php
│   ├── database/
│   │   ├── connection.php
│   │   └── migrations.sql
│   └── tests/
│       └── race_condition_test.php
│
├── task-2-game/
│   └── hidden_item_game.php
│
└── README.md
```

---

## Requirements

- PHP `>= 8.1`
- MySQL `>= 8.0` (Task 1)
- No external PHP libraries required

---

## Getting Started

### Task 1 — API

```bash
# 1. Import the database schema
mysql -u root -p < task-1-api/database/migrations.sql

# 2. Configure DB credentials
cp task-1-api/database/connection.example.php task-1-api/database/connection.php
# Edit connection.php with your credentials

# 3. Start the development server
cd task-1-api
php -S localhost:8000 index.php

# 4. Run the race condition test
php tests/race_condition_test.php
```

### Task 2 — Game

```bash
php task-2-game/hidden_item_game.php
```

---

## Author

Submitted as part of the PT Fomo Inovasi Teknologi fullstack engineer assessment.