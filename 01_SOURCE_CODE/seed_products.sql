
USE lrn_water_db;

INSERT IGNORE INTO products (item_code, item_name, category, price, stock_qty) VALUES
('P1', 'Round 5-Gal Refill',            'Refill',    30.00, 999),
('P2', 'Slim 5-Gal Refill',             'Refill',    35.00, 999),
('P3', 'Brand New Round + Water',       'Container', 220.00, 50),
('P4', 'Brand New Slim + Water',        'Container', 250.00, 50),
('P5', 'Bottled Water 500ml (24s Box)', 'Bottled',   180.00, 100),
('P6', 'Replacement Faucet Spigot',     'Accessory',  30.00, 100),
('P7', 'Non-Spill Dispenser Cap',       'Accessory',  15.00, 100);
