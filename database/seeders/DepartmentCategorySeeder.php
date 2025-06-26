<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seedData = config('seed.departments', []);

        foreach ($seedData as $departmentData) {
            $departmentId = DB::table('departments')->insertGetId([
                'name' => $departmentData['name'],
                'slug' => Str::slug($departmentData['name']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($departmentData['categories'] as $categoryData) {
                $categoryId = DB::table('categories')->insertGetId([
                    'name' => $categoryData['name'],
                    'slug' => Str::slug($categoryData['name']),
                    'department_id' => $departmentId,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (!empty($categoryData['sub_categories'])) {
                    foreach ($categoryData['sub_categories'] as $subCategoryData) {
                        DB::table('categories')->insert([
                            'name' => $subCategoryData['name'],
                            'slug' => Str::slug($subCategoryData['name']),
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'department_id' => $departmentId,
                            'parent_id' => $categoryId,
                        ]);
                    }
                }
            }
        }
    }
}
