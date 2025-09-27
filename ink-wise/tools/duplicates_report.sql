-- Find duplicate material_name entries in inks table
-- Lists the material_name, count, and comma-separated ids
SELECT material_name, COUNT(*) AS cnt, GROUP_CONCAT(id ORDER BY id SEPARATOR ',') AS ids
FROM inks
GROUP BY material_name
HAVING cnt > 1
ORDER BY cnt DESC;

-- For a quick preview of duplicates with full rows for a specific material_name (replace 'INK')
-- SELECT * FROM inks WHERE material_name = 'INK' ORDER BY id;

-- Note: Do not run destructive deletes until you back up your DB.
