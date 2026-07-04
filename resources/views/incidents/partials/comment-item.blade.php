{{-- resources/views/incidents/partials/comment-item.blade.php --}}
<div class="comment-thread mb-3 pb-3 border-bottom" id="comment-{{ $comment->id }}">
    <div class="d-flex gap-2">
        <img src="{{ $comment->user?->avatar_url ?? '/images/default-avatar.png' }}" 
             class="rounded-circle flex-shrink-0" width="36" height="36">
        <div class="flex-grow-1 min-width-0">
            <div class="d-flex gap-2 align-items-center mb-1">
                <strong style="font-size:0.8125rem;">{{ $comment->user?->name ?? 'Unknown' }}</strong>
                <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                @if($comment->is_internal)
                    <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Internal</span>
                @endif
            </div>

            {{-- Comment Content --}}
            <div class="comment-text mb-1" id="comment-content-{{ $comment->id }}" style="font-size:0.8125rem;word-break:break-word;">
                {!! nl2br(e($comment->content)) !!}
            </div>

            {{-- Comment Attachments --}}
            @if($comment->attachments && count($comment->attachments) > 0)
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($comment->attachments as $attachment)
                        @php
                            $url = Storage::url($attachment['path']);
                            $isImage = str_starts_with($attachment['type'] ?? '', 'image/');
                            $fileName = $attachment['name'] ?? 'File';
                            $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                        @endphp
                        
                        @if($isImage)
                            <img src="{{ $url }}" 
                                 style="max-width:150px;max-height:150px;object-fit:cover;border-radius:8px;cursor:pointer;" 
                                 class="border comment-attachment-img"
                                 data-url="{{ $url }}"
                                 data-name="{{ $fileName }}"
                                 data-type="image"
                                 loading="lazy"
                                 onclick="openCommentMediaInModal('{{ $url }}', '{{ addslashes($fileName) }}', 'image'); return false;">
                        @else
                            @php
                                $icon = 'fa-file';
                                if (in_array($ext, ['pdf'])) $icon = 'fa-file-pdf';
                                elseif (in_array($ext, ['doc', 'docx'])) $icon = 'fa-file-word';
                                elseif (in_array($ext, ['xls', 'xlsx', 'csv'])) $icon = 'fa-file-excel';
                                elseif (in_array($ext, ['zip', 'rar', '7z'])) $icon = 'fa-file-archive';
                                elseif (in_array($ext, ['txt', 'log'])) $icon = 'fa-file-alt';
                            @endphp
                            <span class="badge bg-light text-dark text-decoration-none d-inline-flex align-items-center gap-1 comment-file-attachment"
                                 style="font-size:0.7rem;padding:6px 10px;cursor:pointer;"
                                 data-url="{{ $url }}"
                                 data-name="{{ $fileName }}"
                                 data-type="document"
                                 onclick="openCommentMediaInModal('{{ $url }}', '{{ addslashes($fileName) }}', 'document'); return false;">
                                <i class="fas {{ $icon }} text-muted"></i>{{ $fileName }}
                            </span>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Reply Button --}}
            <div class="d-flex gap-2 mt-1">
                <button class="btn btn-link btn-sm text-muted p-0 reply-toggle-btn" style="font-size:0.6875rem;" 
                        data-comment-id="{{ $comment->id }}" data-username="{{ $comment->user?->name ?? 'User' }}">
                    <i class="fas fa-reply me-1"></i>Reply
                </button>
            </div>

            {{-- Replies Container --}}
            <div class="replies-container mt-2" id="replies-{{ $comment->id }}">
                @if($comment->replies && $comment->replies->count() > 0)
                    @foreach($comment->replies as $reply)
                        @include('incidents.partials.comment-item', ['comment' => $reply, 'isReply' => true])
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>