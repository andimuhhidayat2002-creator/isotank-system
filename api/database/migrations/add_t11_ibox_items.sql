-- Add IBOX Temperature, Pressure, Level items for T11 (Section C - Right Side)

INSERT INTO inspection_items (code, label, category, input_type, applicable_categories, is_active, `order`, created_at, updated_at) VALUES
('T11_C_05', 'IBOX Temperature #1 (°C)', 'Right Side', 'number', '["T11"]', 1, 305, NOW(), NOW()),
('T11_C_06', 'IBOX Temperature #2 (°C)', 'Right Side', 'number', '["T11"]', 1, 306, NOW(), NOW()),
('T11_C_07', 'IBOX Pressure (Bar)', 'Right Side', 'number', '["T11"]', 1, 307, NOW(), NOW()),
('T11_C_08', 'IBOX Level (%)', 'Right Side', 'number', '["T11"]', 1, 308, NOW(), NOW());
