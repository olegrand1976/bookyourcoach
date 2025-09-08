<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$club = App\Models\Club::first();
echo "Club existant: " . $club->name . PHP_EOL;

$user = App\Models\User::where('email', 'club@bookyourcoach.com')->first();
if (!$club->users()->where('user_id', $user->id)->exists()) {
    $club->users()->attach($user->id, [
        'role' => 'owner',
        'is_admin' => true,
        'joined_at' => now()
    ]);
    echo "Utilisateur associé au club" . PHP_EOL;
} else {
    echo "Utilisateur déjà associé au club" . PHP_EOL;
}
