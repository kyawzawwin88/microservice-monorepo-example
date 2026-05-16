---
name: delete-order
description: "E2E browser test: Delete/cancel an order through the UI. Navigates to the Orders page, finds an order, clicks Delete, confirms the dialog, and verifies the order is removed and inventory stock is released."
metadata: { "openclaw": { "emoji": "🗑️", "requires": { "config": ["browser.enabled"] } } }
allowed-tools: ["browser", "message"]
---

# Delete Order — E2E Browser Test

Test the complete "Delete Order" flow through the UI using the browser tool.

## Pre-conditions

- The UI must be accessible:
  - **Local:** `http://localhost:3000` (headless browser uses host networking)
  - **Production:** `https://inventory-service-eventdrivenmicroservice.up.railway.app`
- At least one order must exist that can be deleted (not yet invoiced)
- If no deletable order exists, run the `new-order` skill first to create one
- The browser tool must be enabled and running

## Test Steps

### 1. Navigate to the Orders page

```
browser navigate <BASE_URL>/orders
```

Wait for the page to fully load. Take a snapshot to verify the Orders page with the orders list.

### 2. Find a deletable order

Scan the orders table/list for an order that has a "Delete" button/action.
Orders that can be deleted:
- Status: `pending`, `confirmed`
- Must NOT have a related invoice (guard condition)

Prefer using test orders (customer name: `E2E Test Customer`).

### 3. Note the order details before deletion

Record:
- Order ID
- Customer name
- Order items and quantities (to verify inventory release later)

### 4. Click the Delete button

Click the "Delete" or trash icon button next to the order.

### 5. Confirm the deletion dialog

A confirmation dialog should appear asking:
> "Delete order #X for [Customer Name]? Reserved inventory will be released."

Click "OK" or "Confirm" to proceed.

### 6. Verify deletion success

After confirmation:
- A success message should appear indicating the order was deleted
- The order should disappear from the orders list (or show as `deleted`/`cancelled`)
- The success message may include a correlation ID

### 7. Verify inventory release (optional)

If accessible, check the Inventory page to verify:
- Previously reserved stock has been released back to available
- The available quantity increased by the order's item quantities

## Expected Result

- ✅ Order with Delete action is visible in the list
- ✅ Clicking Delete shows a confirmation dialog
- ✅ After confirming, a success message appears
- ✅ Order is removed from the active orders list
- ✅ The page updates correctly (no stale data)

## On Failure

- Take a screenshot of the current page state
- Check if the confirmation dialog appeared
- Check if the order had a related invoice (which blocks deletion)
- Check browser console for JavaScript errors
- Report the exact step where the failure occurred
- Include any error messages visible on the page

## Notes

- The delete action triggers the `DeleteOrderWorkflow` in Sales Service
- This is a soft-delete (order record is not physically removed)
- The workflow publishes an `OrderDeleted` event to the event bus
- Inventory Service consumes this event and runs `ReleaseInventoryWorkflow`
- Reserved stock is released back to available
- The full workflow may take a few seconds to complete across services
