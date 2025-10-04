Goal
----
Help detect and clean duplicate rows in the `inks` table so a unique index can be added on `material_name`.

Recommended steps
-----------------
1. Backup your database.

2. Run the detection query to list duplicates:
   mysql> SOURCE tools/duplicates_report.sql;

3. For each duplicate group, decide whether to: 
   - merge data (e.g., combine occasions, sum stock quantities, preserve the most complete row), or
   - keep one row and delete the others.

4. Example merge approach (manual):
   - Identify the ids to keep and ids to remove from the report's `ids` column (e.g. "3,7,12").
   - Collect combined values, for example:
       SELECT GROUP_CONCAT(DISTINCT occasion ORDER BY occasion) FROM inks WHERE id IN (3,7,12);
       SELECT SUM(stock_qty) FROM inks WHERE id IN (3,7,12);
   - Update the row you will keep with the combined values, then delete the other rows by id.

5. Example delete (after backing up) - replace ids with the ones to remove:
   DELETE FROM inks WHERE id IN (7,12);

6. After cleaning duplicates, re-run your migration to add the unique index.

Notes
-----
- Do not run deletes without a DB backup.
- If data relationships reference inks.id (FKs), ensure to update referencing rows or preserve ids accordingly.
- I can prepare a safer SQL script to auto-merge simple cases (e.g., identical material_name, combine occasions CSV and sum stock_qty) if you'd like.
