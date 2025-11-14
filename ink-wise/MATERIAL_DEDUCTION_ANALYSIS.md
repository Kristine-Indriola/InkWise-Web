# Material Deduction Analysis

## Current Implementation Status

### ✅ What's Working:
The system **DOES** have material deduction logic implemented in:
- `app/Services/OrderFlowService.php::syncMaterialUsage()` (line ~1783)
- `app/Services/OrderFlowService.php::adjustMaterialStock()` (line ~2396)

### 🔄 How It Works:
1. When an order is created in `OrderFlowController::saveFinalStep()`:
   - Checks stock availability via `checkStockFromSummary()`
   - Creates order and calls `initializeOrderFromSummary()`
   - `initializeOrderFromSummary()` calls `syncMaterialUsage()`
   - `syncMaterialUsage()` calls `adjustMaterialStock()` to deduct inventory

2. The deduction happens in TWO places:
   - `materials.stock_qty` - main material stock
   - `inventory.stock_level` - if inventory record exists

### ⚠️ Potential Issues:

1. **Missing Inventory Records**
   - If a material doesn't have an `inventory` record, only `materials.stock_qty` is updated
   - Dashboard/reports might read from `inventory.stock_level` instead

2. **Product Materials Not Linked**
   - If products don't have `product_materials` records, no deduction occurs
   - The system checks for `ProductMaterial` records linked to the product

3. **Source Type Filtering**
   - `syncMaterialUsage` looks for `ProductMaterial` records where `source_type = 'custom'`
   - Product-level materials need `source_type IN ('product', 'paper_stock', 'envelope', 'addon')`

## Verification Steps:

### Check if materials are linked to products:
```sql
SELECT p.id, p.name, COUNT(pm.id) as material_count
FROM products p
LEFT JOIN product_materials pm ON p.id = pm.product_id AND pm.order_id IS NULL
GROUP BY p.id, p.name;
```

### Check if materials have inventory records:
```sql
SELECT m.material_id, m.material_name, m.stock_qty, i.stock_level, i.inventory_id
FROM materials m
LEFT JOIN inventory i ON m.material_id = i.material_id;
```

### Check recent orders with material usage:
```sql
SELECT o.id, o.order_number, o.created_at, pm.material_id, pm.quantity_required, pm.quantity_used, pm.deducted_at
FROM orders o
LEFT JOIN product_materials pm ON o.id = pm.order_id
WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY o.created_at DESC;
```

## Recommended Fixes:

### 1. Ensure all materials have inventory records
### 2. Verify product-material relationships
### 3. Add logging to track deductions
### 4. Create missing inventory records automatically

