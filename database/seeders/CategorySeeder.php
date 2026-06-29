<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Development', 'icon' => 'code-2', 'skills' => [
                'Website Development', 'Software Development', 'Mobile App Development',
                'E-Commerce Development', 'Database Management', 'Cyber Security', 'API Development',
            ]],
            ['name' => 'Design', 'icon' => 'palette', 'skills' => [
                'UI/UX Design', 'Graphic Design', 'Logo Design', 'Branding',
            ]],
            ['name' => 'Marketing', 'icon' => 'megaphone', 'skills' => [
                'SEO', 'Social Media Marketing', 'Digital Marketing', 'Lead Generation',
            ]],
            ['name' => 'Writing', 'icon' => 'pen-line', 'skills' => [
                'Content Writing', 'Technical Writing', 'Blog Writing', 'Translation', 'Proofreading',
            ]],
            ['name' => 'Business', 'icon' => 'briefcase', 'skills' => [
                'Consulting', 'Market Research', 'Legal Support', 'Project Management',
            ]],
            ['name' => 'Media', 'icon' => 'film', 'skills' => [
                'Video Editing', 'Animation', 'Voice Over', 'Audio Production',
            ]],
            ['name' => 'Architecture', 'icon' => 'building-2', 'skills' => [
                'Interior Design', '3D Modelling', 'Rendering',
            ]],
        ];

        foreach ($categories as $i => $cat) {
            $catId = DB::table('categories')->insertGetId([
                'name'       => $cat['name'],
                'slug'       => Str::slug($cat['name']),
                'icon'       => $cat['icon'],
                'sort_order' => $i,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($cat['skills'] as $skill) {
                DB::table('skills')->insertOrIgnore([
                    'name'        => $skill,
                    'slug'        => Str::slug($skill),
                    'category_id' => $catId,
                    'is_approved' => true,
                    'created_at'  => now(),
                ]);
            }
        }
    }
}
