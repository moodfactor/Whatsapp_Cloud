<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeder.
     *
     * @return void
     */
    public function run()
    {
        // Create super admin
        DB::table('whatsapp_admins')->insert([
            'name' => 'Super Administrator',
            'email' => 'admin@connect.al-najjarstore.com',
            'password' => Hash::make('admin123'), // Change this password!
            'role' => 'super_admin',
            'permissions' => json_encode([
                'manage_users',
                'view_all_conversations',
                'assign_conversations',
                'delete_conversations',
                'view_reports',
                'manage_settings'
            ]),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Create sample admin
        DB::table('whatsapp_admins')->insert([
            'name' => 'WhatsApp Admin',
            'email' => 'whatsapp-admin@connect.al-najjarstore.com',
            'password' => Hash::make('whatsapp123'), // Change this password!
            'role' => 'admin',
            'permissions' => json_encode([
                'view_all_conversations',
                'assign_conversations',
                'view_reports'
            ]),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1
        ]);
        
        // Create sample supervisor
        DB::table('whatsapp_admins')->insert([
            'name' => 'WhatsApp Supervisor',
            'email' => 'supervisor@connect.al-najjarstore.com',
            'password' => Hash::make('supervisor123'), // Change this password!
            'role' => 'supervisor',
            'permissions' => json_encode([]),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1
        ]);
        
        // Create sample agent
        DB::table('whatsapp_admins')->insert([
            'name' => 'WhatsApp Agent',
            'email' => 'agent@connect.al-najjarstore.com',
            'password' => Hash::make('agent123'), // Change this password!
            'role' => 'agent',
            'permissions' => json_encode([]),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
            'created_by' => 1
        ]);
    }
}