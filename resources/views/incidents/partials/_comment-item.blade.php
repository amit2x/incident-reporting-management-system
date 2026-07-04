{{-- resources/views/incidents/partials/comment-item.blade.php --}}
<div class="comment-thread {{ isset($isReply) && $isReply ? 'ms-4 mt-2' : 'mb-3 pb-3 border-bottom' }}" id="comment-{{ $comment->id }}">
    <div class="d-flex gap-2">
        <img src="{{ $comment->user?->avatar_url ?? asset('images/default-avatar.png') }}"
             class="rounded-circle flex-shrink-0" width="36" height="36" style="object-fit: cover;">
        <div class="flex-grow-1 min-width-0">
            <div class="d-flex gap-2 align-items-center mb-1">
                <strong style="font-size: 0.8125rem;">{{ $comment->user?->name ?? 'Unknown' }}</strong>
                <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                @if($comment->is_edited)
                    <small class="text-muted" title="Edited {{ $comment->edited_at?->diffForHumans() }}">
                        <i class="fas fa-pencil-alt"></i> edited
                    </small>
                @endif
                @if($comment->is_internal)
                    <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Internal</span>
                @endif
            </div>

            {{-- Content (editable) --}}
            <div class="comment-content-wrapper" id="comment-content-{{ $comment->id }}">
                @if($comment->content)
                    <div class="comment-text mb-1" style="font-size: 0.8125rem; word-break: break-word;">
                        @php
                            $content = $comment->content;
                            $content = preg_replace('/@(\w+)/', '<span class="text-primary fw-medium">@$1</span>', $content);
                            $content = preg_replace('/#(\w+)/', '<span class="text-success fw-medium">#$1</span>', $content);
                            $content = nl2br(e($content));
                            $content = str_replace(
                                ['&lt;span class=&quot;text-primary fw-medium&quot;&gt;', '&lt;span class=&quot;text-success fw-medium&quot;&gt;', '&lt;/span&gt;'],
                                ['<span class="text-primary fw-medium">', '<span class="text-success fw-medium">', '</span>'],
                                $content
                            );
                        @endphp
                        {!! $content !!}
                    </div>
                @endif

                {{-- Attachments --}}
                @if($comment->attachments && count($comment->attachments) > 0)
                    <div class="mt-2">
                        @php $inlineImages = collect($comment->attachments)->filter(fn($a) => str_starts_with($a['type'] ?? '', 'image/')); @endphp
                        @if($inlineImages->count() > 0)
                            <div class="d-flex flex-wrap gap-2 mb-2">
                                @foreach($inlineImages as $img)
                                    <a href="{{ Storage::url($img['path']) }}" target="_blank">
                                        <img src="{{ Storage::url($img['path']) }}"
                                             style="max-width:200px;max-height:200px;object-fit:cover;border-radius:8px;cursor:pointer;"
                                             class="img-thumbnail border-0 p-0">
                                    </a>
                                @endforeach
                            </div>
                        @endif
                        @php $files = collect($comment->attachments)->filter(fn($a) => !str_starts_with($a['type'] ?? '', 'image/')); @endphp
                        @if($files->count() > 0)
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($files as $file)
                                    <a href="{{ Storage::url($file['path']) }}" target="_blank"
                                       class="badge bg-light text-dark text-decoration-none d-inline-flex align-items-center gap-1"
                                       style="font-size:0.7rem;padding:6px 10px;">
                                        <i class="fas fa-file text-muted"></i>
                                        <span>{{ $file['name'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Edit Form (hidden by default) --}}
            <div class="comment-edit-wrapper d-none" id="comment-edit-{{ $comment->id }}">
                <textarea class="form-control form-control-sm edit-textarea" rows="2"
                          id="edit-textarea-{{ $comment->id }}">{{ $comment->content }}</textarea>
                <div class="d-flex gap-1 mt-1">
                    <button class="btn btn-primary btn-sm save-edit-btn" data-comment-id="{{ $comment->id }}">
                        <i class="fas fa-save me-1"></i>Save
                    </button>
                    <button class="btn btn-light btn-sm cancel-edit-btn" data-comment-id="{{ $comment->id }}">
                        Cancel
                    </button>
                </div>
            </div>

            {{-- Actions --}}
            <div class="d-flex gap-2 mt-1">
                <button class="btn btn-link btn-sm text-muted p-0 reply-toggle-btn" style="font-size:0.6875rem;"
                        data-comment-id="{{ $comment->id }}"
                        data-username="{{ $comment->user?->name ?? 'User' }}">
                    <i class="fas fa-reply me-1"></i>Reply
                </button>

                {{-- Edit/Delete - Only for comment owner or admin --}}
                @if(Auth::id() === $comment->user_id || Auth::user()->isAdmin())
                    <button class="btn btn-link btn-sm text-muted p-0 edit-comment-btn" style="font-size:0.6875rem;"
                            data-comment-id="{{ $comment->id }}">
                        <i class="fas fa-edit me-1"></i>Edit
                    </button>
                    <button class="btn btn-link btn-sm text-muted p-0 delete-comment-btn" style="font-size:0.6875rem;"
                            data-comment-id="{{ $comment->id }}">
                        <i class="fas fa-trash me-1"></i>Delete
                    </button>
                @endif
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
