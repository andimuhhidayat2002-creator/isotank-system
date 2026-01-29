SELECT id, isotank_id, source_item, part_damage, damage_type, location, priority, created_at 
FROM maintenance_jobs 
ORDER BY id DESC 
LIMIT 5;
