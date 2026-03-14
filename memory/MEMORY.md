# SMMS Project Memory

## Stack
- Laravel 12, Filament 5, Spatie Permission 7, MySQL
- PHP 8.2+, Queue: database driver
- URL: http://localhost/smms (Laragon)

## Admin Login
- Email: admin@example.com | Password: password

## Key Architecture
- Filament panel: /admin — AdminPanelProvider @ app/Providers/Filament/
- Resources auto-discovered from app/Filament/Resources/**
- Jobs: PublishPostJob, SyncMetricsJob, SyncCommentsJob, SyncMessagesJob
- Services: FacebookService, InstagramService (app/Services/)
- Cron: all schedules in routes/console.php

## Database
- DB: smms_db (MySQL, root, no password)
- All migrations ran (batch 1). Tables: users, roles, permissions, social_accounts, posts, post_media, messages, message_replies, comments, comment_replies, metrics, custom_notifications, user_social_permissions
- Migration sync issue: files were renamed from 2025_ to 2026_ prefix; fixed by inserting into migrations table manually

## Features Implemented (Complete)
- Models: User, Post, SocialAccount, Comment, CommentReply, Metric, Message, MessageReply, PostMedia, CustomNotification, UserSocialPermission
- Filament Resources: UserResource, PostResource, CommentResource, MessageResource, SocialAccountResource, UserSocialPermissionResource
- Widgets: StatsOverviewWidget, EngagementChartWidget (line chart weekly/monthly), CalendarWidget (scheduled posts calendar with month navigation)
- Landing Page: resources/views/landing.blade.php (modern dark theme, / route)
- OAuth Flow: SocialAuthController → /auth/facebook/callback, /auth/instagram/callback
- Token encryption: SocialAccount model auto-encrypts/decrypts access_token & refresh_token
- Forgot Password: enabled via ->passwordReset() in AdminPanelProvider

## API Config
- Facebook: config/services.php → services.facebook.{app_id, app_secret, callback_url, graph_version}
- Instagram: config/services.php → services.instagram.callback_url
- .env keys: FACEBOOK_APP_ID, FACEBOOK_APP_SECRET, FACEBOOK_CALLBACK_URL, FACEBOOK_GRAPH_VERSION, INSTAGRAM_CALLBACK_URL

## Roles & Permissions
- Roles: admin (all), staff (limited — view/create/schedule posts, reply comments/messages, view analytics)
- UserSocialPermission: per-staff per-account granular permissions (can_view, can_create_post, can_schedule_post, can_publish_post, can_reply_comment, can_reply_message, can_view_analytics)

## Queue / Cron Setup (production)
```
php artisan queue:work --tries=3 --timeout=120
* * * * * php /path/to/artisan schedule:run
```
