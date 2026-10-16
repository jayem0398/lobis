<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['web']]);
Broadcast::routes(['prefix' => 'api', 'middleware' => ['web']]);

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('team.{teamId}', function ($user, $teamId) {
    return (int) $user->team_id === (int) $teamId || in_array(strtolower($user->role), ['admin', 'superadmin']);
});

Broadcast::channel('admin-channel', function ($user) {
    return in_array(strtolower($user->role), ['admin', 'superadmin']);
});

Broadcast::channel('invoices-channel', function ($user) {
    return true;
});
