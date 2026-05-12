<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Post;
use App\Models\User;
use App\Notifications\PostScheduleReminderNotification;

class SendPostReminder extends Command
{
    protected $signature = 'posts:reminder';

    protected $description =
        'Send reminder notification for scheduled posts';

    public function handle()
    {
        $posts = Post::where('status', 'scheduled')
            ->whereBetween('scheduled_at', [
                now(),
                now()->addMinutes(10),
            ])
            ->get();

        $users = User::all();

        foreach ($posts as $post) {

            foreach ($users as $user) {

                $alreadySent = $user
                    ->notifications()
                    ->where('data->post_id', $post->id)
                    ->whereDate('created_at', today())
                    ->exists();

                if (!$alreadySent) {

                    $user->notify(
                        new PostScheduleReminderNotification($post)
                    );
                }
            }
        }

        $this->info('Reminder notification sent.');
    }
}