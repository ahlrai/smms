<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use App\Notifications\PostScheduleReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PostReminderSchedule implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $posts = Post::where('status', 'scheduled')
            ->whereBetween('scheduled_at', [
                now()->addMinutes(59),
                now()->addMinutes(60),
            ])
            ->get();

        foreach ($posts as $post) {

            // supaya tidak spam notif
            $alreadyNotified = \DB::table('notifications')
                ->where('data->post_id', $post->id)
                ->where('data->type', 'post-reminder')
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            foreach (User::all() as $user) {
                $user->notify(
                    new PostScheduleReminderNotification($post)
                );
            }
        }
    }
}