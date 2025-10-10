<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('debug:token {token}', function ($token) {
    $tokenRecord = DB::table('personal_access_tokens')
        ->where('token', 'like', $token . '%')
        ->first();
    
    if ($tokenRecord) {
        $user = DB::table('users')->where('id', $tokenRecord->tokenable_id)->first();
        $this->info("Token VALID for user: {$user->email}");
        $this->info("Token name: {$tokenRecord->name}");
        $this->info("Created: {$tokenRecord->created_at}");
        $this->info("Last used: {$tokenRecord->last_used_at}");
    } else {
        $this->error("Token NOT FOUND in database");
    }
})->purpose('Debug token');
