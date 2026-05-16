---
name: new-order
description: "E2E browser test: Create a new order through the UI. Navigates to the Orders page, fills the order form with test customer data and inventory items, submits, and verifies the order appears in the list with correct status."
metadata: { "openclaw": { "emoji": "🛒", "requires": { "config": ["browser.enabled"] } } }
allowed-tools: ["browser", "message"]
---

# New Order — E2E Browser Test

Test the complete "Create New Order" flow through the UI using the browser tool.

## Pre-conditions

- The UI must be accessible:
  - **Local:** `http://localhost:3000` (headless browser uses host networking)
  - **Production:** `https://inventory-service-eventdrivenmicroservice.up.railway.app`
- At least one inventory item must exist with available stock
- The browser tool must be enabled and running

## Test Steps

### 1. Navigate to the Orders page

```
browser navigate <BASE_URL>/orders
```

Wait for the page to fully load. Take a snapshot to verify the Orders page is visible.

### 2. Click "New Order" button

Look for a button labeled "New Order" or "Create Order" and click it.
The order form should appear with fields for:
- Customer Name
- Customer Email
- Order items (inventory item selector, quantity)

### 3. Fill customer details

- **Customer Name**: `E2E Test Customer`
- **Customer Email**: `e2e-test@openclaw-sales-manager.test`

### 4. Select inventory item(s)

- The form shows a dropdown of available inventory items
- Select the first available item
- Set quantity to `1`
- Verify the unit price auto-populates

### 5. Submit the order

Click the "Create Order" or "Submit" button.

### 6. Verify order creation

After submission:
- A success message should appear (or the form should close)
- The new order should appear in the orders list
- The order status should be one of: `pending`, `confirmed`, or `processing`
- The customer name should match `E2E Test Customer`

### 7. Record the order ID

Note the order ID from the list — it will be needed for deliver-order and delete-order tests.

## Expected Result

- ✅ Order form loads with inventory items
- ✅ Form accepts customer details and item selection
- ✅ Order is created successfully (HTTP 200/201 from Sales API)
- ✅ Order appears in the list with correct customer name
- ✅ Order status transitions correctly (pending → confirmed)

## On Failure

- Take a screenshot of the current page state
- Check browser console for JavaScript errors
- Check if the Sales Service API is responding (try `/api/health`)
- Report the exact step where the failure occurred
- Include any error messages visible on the page

## Cleanup

After testing (and after deliver/delete tests if running full suite):
- Delete any test orders created during this run
- Verify inventory stock was properly released
