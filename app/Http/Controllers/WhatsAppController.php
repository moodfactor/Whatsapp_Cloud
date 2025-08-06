<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class WhatsAppController extends BaseController
{
    public function dashboard()
    {
        return view("whatsapp.dashboard", [
            "user" => [
                "name" => "Admin User",
                "permissions" => [
                    "role_name" => "Super Admin",
                    "can_see_all" => true,
                    "can_assign" => true,
                    "can_delete" => true,
                    "can_see_phone" => true
                ]
            ]
        ]);
    }
}
