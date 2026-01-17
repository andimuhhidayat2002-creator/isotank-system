<?php

namespace App\Services;

class YardLayoutConfig
{
    public static function getConfig()
    {
        $path = storage_path('app/yard_layout.json');
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [
            "canvas" => [
                "width" => 2400,
                "height" => 1600,
                "background_color" => "#f8fafc",
            ],
            "areas" => [
                // --- MAIN YARD ZONES ---
                [
                    "name" => "ZONA_1",
                    "type" => "yard",
                    "blocks" => ["A", "B", "C", "D", "E"],
                    "rows_per_block" => 24,
                    "tiers" => 4,
                    "visual" => ["x" => 100, "y" => 600, "width" => 600, "height" => 400, "color" => "#e2e8f0"]
                ],
                [
                    "name" => "ZONA_2",
                    "type" => "yard",
                    "blocks" => ["A", "B", "C", "D", "E"],
                    "rows_per_block" => 20,
                    "tiers" => 4,
                    "visual" => ["x" => 750, "y" => 600, "width" => 500, "height" => 400, "color" => "#e2e8f0"]
                ],
                [
                    "name" => "ZONA_3",
                    "type" => "yard",
                    "blocks" => ["A", "B", "C", "D", "E"],
                    "rows_per_block" => 24,
                    "tiers" => 4,
                    "visual" => ["x" => 1300, "y" => 600, "width" => 600, "height" => 400, "color" => "#e2e8f0"]
                ],

                // --- FILLING COMPLEX (CENTER TOP) ---
                [
                    "name" => "FILLING_AREA", 
                    "type" => "container", 
                    "visual" => ["x" => 750, "y" => 100, "width" => 1100, "height" => 350, "color" => "#f1f5f9"]
                ],
                [
                    "name" => "BUFFER_FILLING_GENAP",
                    "type" => "buffer",
                    "blocks" => ["A"], 
                    "rows_per_block" => 10, 
                    "tiers" => 1,
                    "visual" => ["x" => 770, "y" => 120, "width" => 200, "height" => 80, "color" => "#ffffff"]
                ],
                [
                    "name" => "BUFFER_FILLING_GANJIL",
                    "type" => "buffer",
                    "blocks" => ["A"],
                    "rows_per_block" => 10,
                    "tiers" => 1,
                    "visual" => ["x" => 770, "y" => 220, "width" => 200, "height" => 80, "color" => "#ffffff"]
                ],
                 [
                    "name" => "WASHING_GENAP",
                    "type" => "process",
                    "blocks" => ["A"],
                    "rows_per_block" => 6,
                    "tiers" => 1,
                    "visual" => ["x" => 1000, "y" => 120, "width" => 150, "height" => 80, "color" => "#ffffff"]
                ],
                [
                    "name" => "WASHING_GANJIL",
                    "type" => "process",
                    "blocks" => ["A"],
                    "rows_per_block" => 6,
                    "tiers" => 1,
                    "visual" => ["x" => 1000, "y" => 220, "width" => 150, "height" => 80, "color" => "#ffffff"]
                ],
                [
                    "name" => "FILLING_STATION",
                    "type" => "process",
                    "blocks" => ["A"],
                    "rows_per_block" => 6,
                    "tiers" => 1,
                    "visual" => ["x" => 1200, "y" => 120, "width" => 150, "height" => 180, "color" => "#ffffff"]
                ],
                [
                    "name" => "INSPECTION_AREA",
                    "type" => "process",
                    "blocks" => ["A"],
                    "rows_per_block" => 8,
                    "tiers" => 1,
                    "visual" => ["x" => 1400, "y" => 120, "width" => 180, "height" => 80, "color" => "#ffffff"]
                ],
                 [
                    "name" => "LO_LPG_FILLING",
                    "type" => "process",
                    "blocks" => ["A"],
                    "rows_per_block" => 4,
                    "tiers" => 2,
                    "visual" => ["x" => 1600, "y" => 120, "width" => 100, "height" => 80, "color" => "#e0e7ff"]
                ],

                // --- WORKSHOP (RIGHT of Filling) ---
                [
                    "name" => "WORKSHOP",
                    "type" => "workshop",
                    "blocks" => ["A"],
                    "rows_per_block" => 3,
                    "tiers" => 1,
                    "visual" => ["x" => 1900, "y" => 150, "width" => 100, "height" => 100, "color" => "#fff1f2"]
                ],

                // --- STOCK AREAS ---
                [
                    "name" => "STOK_GENAP",
                    "type" => "stock",
                    "blocks" => ["A"],
                    "rows_per_block" => 3, 
                    "tiers" => 2,
                    "visual" => ["x" => 100, "y" => 150, "width" => 150, "height" => 100, "color" => "#f0fdf4"]
                ],
                [
                    "name" => "STOK_GANJIL",
                    "type" => "stock",
                    "blocks" => ["A"],
                    "rows_per_block" => 3,
                    "tiers" => 2,
                    "visual" => ["x" => 280, "y" => 150, "width" => 150, "height" => 100, "color" => "#f0fdf4"]
                ],

                // --- PANCANG AREAS ---
                [
                    "name" => "PANCANG_PAGAR",
                    "type" => "pancang",
                    "blocks" => ["A", "B"], 
                    "rows_per_block" => 8,
                    "tiers" => 2,
                    "visual" => ["x" => 100, "y" => 20, "width" => 2000, "height" => 60, "color" => "#f87171"]
                ],
                [
                    "name" => "PANCANG_BELAKANG",
                    "type" => "pancang",
                    "blocks" => ["A"],
                    "rows_per_block" => 10,
                    "tiers" => 1,
                    "visual" => ["x" => 100, "y" => 480, "width" => 500, "height" => 50, "color" => "#fca5a5"]
                ],
                [
                    "name" => "PANCANG_DEPAN",
                    "type" => "pancang",
                    "blocks" => ["A"],
                    "rows_per_block" => 10,
                    "tiers" => 1,
                    "visual" => ["x" => 100, "y" => 1050, "width" => 500, "height" => 50, "color" => "#fca5a5"]
                ],

                // --- JETTY AREA (Below ZONA_3) ---
                [
                    "name" => "JETTY_AREA",
                    "type" => "jetty",
                    "blocks" => ["A"], 
                    "rows_per_block" => 10,
                    "tiers" => 1,
                    "visual" => ["x" => 1300, "y" => 1100, "width" => 600, "height" => 200, "color" => "#bae6fd"]
                ],
            ],
            "roads" => [
                // MAIN_HORIZONTAL_ROAD (Separates Filling/Stock from Zones)
                ["x" => 50, "y" => 530, "width" => 2300, "height" => 50, "label" => "MAIN HORIZONTAL ROAD"],
                // MAIN_VERTICAL_ROAD (Connects Zones <-> Jetty)
                ["x" => 1250, "y" => 580, "width" => 40, "height" => 800, "label" => "MAIN VERTICAL ROAD"]
            ]
        ];
    }
}
