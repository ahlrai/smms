<div style="display:flex; flex-direction:column; gap:12px; padding:4px 0;">

    {{-- Komentar asli --}}
    <div style="display:flex; align-items:flex-start; gap:12px;">

        {{-- Avatar --}}
        <div style="width:36px; height:36px; min-width:36px; border-radius:50%;
                    background:#334155; display:flex; align-items:center; justify-content:center;
                    font-size:13px; font-weight:700; color:#94a3b8;
                    text-transform:uppercase; user-select:none; flex-shrink:0;">
            {{ substr($comment->commenter_username ?? '?', 0, 1) }}
        </div>

        <div style="flex:1; min-width:0;">
            <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin-bottom:4px;">
                <span style="font-size:14px; font-weight:700; color:#f1f5f9;">&#64;{{ $comment->commenter_username }}</span>
                <span style="color:#475569; font-size:12px;">·</span>
                <span style="color:#475569; font-size:12px;">{{ $comment->created_at?->timezone(config('app.timezone'))->diffForHumans() }}</span>
                @if($comment->like_count > 0)
                    <span style="color:#475569; font-size:12px;">· ♥ {{ $comment->like_count }}</span>
                @endif
            </div>
            <p style="font-size:13px; color:#94a3b8; white-space:pre-wrap; word-break:break-word; line-height:1.6; margin:0;">{{ $comment->content }}</p>
        </div>

    </div>

    {{-- Balasan admin --}}
    @foreach($comment->replies as $reply)
        <div style="display:flex; align-items:flex-start; gap:12px;
                    margin-left:18px; padding-left:18px;
                    border-left:2px solid #475569;">

            {{-- Avatar admin --}}
            <div style="width:30px; height:30px; min-width:30px; border-radius:50%;
                        background:#4f46e5; display:flex; align-items:center; justify-content:center;
                        font-size:11px; font-weight:700; color:white;
                        text-transform:uppercase; user-select:none; flex-shrink:0;">
                {{ substr($reply->replier?->name ?? 'A', 0, 1) }}
            </div>

            <div style="flex:1; min-width:0;">
                <div style="display:flex; flex-wrap:wrap; align-items:center; gap:6px; margin-bottom:4px;">
                    <span style="font-size:13px; font-weight:700; color:#f1f5f9;">{{ $reply->replier?->name ?? 'Admin' }}</span>
                    @if($reply->is_sent)
                        <span style="font-size:11px; font-weight:600; color:#4ade80;">✓ Terkirim</span>
                    @else
                        <span style="font-size:11px; font-weight:600; color:#fbbf24;">⚠ Belum terkirim</span>
                    @endif
                    <span style="color:#475569; font-size:12px;">·</span>
                    <span style="color:#475569; font-size:12px;">{{ $reply->created_at?->timezone(config('app.timezone'))->diffForHumans() }}</span>
                </div>
                <p style="font-size:13px; color:#94a3b8; white-space:pre-wrap; word-break:break-word; line-height:1.6; margin:0;">{{ $reply->reply }}</p>
            </div>

        </div>
    @endforeach

</div>
