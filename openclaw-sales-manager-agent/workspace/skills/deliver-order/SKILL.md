---
name: deliver-order
description: "E2E browser test: Deliver an existing confirmed order through the UI. Navigates to the Orders page, finds a confirmed order, clicks Deliver, confirms the dialog, and verifies the status changes to delivered."
metadata: { "openclaw": { "emoji": "📦", "requires": { "config": ["browser.enabled"] } } }
allowed-tools: ["browser", "message"]
---

# Deliver Order — E2E Browser Test

Test the complete "Deliver Order" flow through the UI using the browser tool.

## Pre-conditions

- The UI must be accessible:
  - **Local:** `http://localhost:3000` (headless browser uses host networking)
  - **Production:** `https://inventory-service-eventdrivenmicroservice.up.railway.app`
- At least one order must exist in `confirmed` status
- If no confirmed order exists, run the `new-order` skill first to create one
- The browser tool must be enabled and running

## Test Steps

### 1. Navigate to the Orders page

```
browser navigate <BASE_URL>/orders
```

Wait for the page to fully load. Take a snapshot to verify the Orders page with the orders list.

### 2. Find a confirmed order

Scan the orders table/list for an order with:
- Status: `confirmed` (or `completed`)
- Look for a "Deliver" button/action associated with that order

If no confirmed order exists:
- Report that no deliverable order was found
- Suggest running the `new-order` test first

### 3. Click the Deliver button

Click the "Deliver" button/icon next to the confirmed order.

### 4. Confirm the delivery dialog

A confirmation dialog should appear asking:
> "Mark order #X for [Customer Name] as delivered? Reserved inventory will be deducted (goods shipped)."

Click "OK" or "Confirm" to proceed.

### 5. Verify delivery success

After confirmation:
- A success message should appear indicating the order was delivered
- The order status should change to `delivered`
- The success message may include a correlation ID

### 6. Verify cross-service effects

The delivery triggers:
- **Inventory Service**: Reserved stock is deducted (shipped)
- The order status in the list should update to `delivered`

Take a snapshot to verify the updated status.

## Expected Result

- ✅ Confirmed order is visible in the list with a Deliver action
- ✅ Clicking Deliver shows a confirmation dialog
- ✅ After confirming, a success message appears
- ✅ Order status changes to `delivered`
- ✅ The page updates to reflect the new status

## On Failure

- Take a screenshot of the current page state
- Check if the confirmation dialog appeared
- Check browser console for JavaScript errors
- Check if the order was in the correct state for delivery
- Report the exact step where the failure occurred
- Include any error messages visible on the page

## Notes

- The deliver action triggers the `DeliverOrderWorkflow` in Sales Service
- This publishes an `OrderDelivered` event to the event bus
- Inventory Service consumes this event and runs `DeductInventoryWorkflow`
- The full workflow may take a few seconds to complete across services
