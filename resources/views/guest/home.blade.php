{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('title', 'Incident Feed - IRMS')

@push('styles')
<style>
    .feed-container {
        max-width: 680px;
        margin: 0 auto;
    }
    .feed-card {
        transition: all 0.2s ease;
    }
    .feed-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.08) !important;
    }
    .feed-action-btn {
        transition: all 0.15s ease;
        color: #6b7280;
    }
    .feed-action-btn:hover {
        background: #f3f4f6 !important;
        color: #1a56db !important;
    }
    .feed-action-btn.liked {
        color: #1a56db !important;
    }
    .feed-action-btn.liked i {
        font-weight: 900;
    }
    .loading-spinner {
        display: flex;
        justify-content: center;
        padding: 20px;
    }
    .loading-spinner.hidden {
        display: none;
    }
    .no-more-posts {
        text-align: center;
        padding: 20px;
        color: #9ca3af;
    }
    .no-more-posts.hidden {
        display: none;
    }

    /* Image Viewer Modal */
    .image-viewer-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.95);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .image-viewer-overlay img {
        max-width: 95vw;
        max-height: 95vh;
        object-fit: contain;
    }
    .image-viewer-close {
        position: absolute;
        top: 20px;
        right: 20px;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        z-index: 1;
    }

    @media (max-width: 767.98px) {
        .feed-card {
            border-radius: 0 !important;
            margin-bottom: 8px !important;
        }
        .feed-container {
            padding: 0;
        }
    }
</style>
@endpush

@section('content')
<div class="feed-container px-0 px-md-3 py-3">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3 px-3 px-md-0">
        <h5 class="fw-bold mb-0">
            <i class="fas fa-newspaper text-primary me-2"></i>Incident Feed
        </h5>
        <div class="d-flex gap-2">
            {{-- Search --}}
            <form id="searchForm" class="d-flex" onsubmit="searchIncidents(event)">
                <div class="input-group input-group-sm">
                    <input type="text" id="searchInput" class="form-control"
                           placeholder="Search incidents..."
                           style="border-radius: 20px 0 0 20px; max-width: 200px;">
                    <button type="submit" class="btn btn-primary" style="border-radius: 0 20px 20px 0;">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Filters --}}
    <div class="d-flex gap-2 mb-3 px-3 px-md-0 overflow-auto pb-1" style="scrollbar-width: none;">
        <button class="btn btn-light btn-sm rounded-pill filter-btn active" data-filter="all" onclick="filterFeed('all', this)">
            All
        </button>
        <button class="btn btn-light btn-sm rounded-pill filter-btn" data-filter="open" onclick="filterFeed('open', this)">
            Open
        </button>
        <button class="btn btn-light btn-sm rounded-pill filter-btn" data-filter="in_progress" onclick="filterFeed('in_progress', this)">
            In Progress
        </button>
        <button class="btn btn-light btn-sm rounded-pill filter-btn" data-filter="resolved" onclick="filterFeed('resolved', this)">
            Resolved
        </button>
        @foreach($departments as $dept)
            <button class="btn btn-light btn-sm rounded-pill filter-btn"
                    data-filter="dept_{{ $dept->id }}"
                    onclick="filterFeed('dept_{{ $dept->id }}', this)">
                {{ $dept->code }}
            </button>
        @endforeach
    </div>

    {{-- Feed Items --}}
    <div id="feedContainer">
        @forelse($incidents as $incident)
            @include('guest.partials.incident-feed-card', ['incident' => $incident])
        @empty
            <div class="text-center py-5">
                <i class="fas fa-newspaper fa-3x text-muted mb-3 d-block"></i>
                <h6 class="text-muted">No incidents to show</h6>
                <p class="text-muted small">Check back later for updates</p>
            </div>
        @endforelse
    </div>

    {{-- Loading Spinner --}}
    <div class="loading-spinner hidden" id="loadingSpinner">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    {{-- No More Posts --}}
    <div class="no-more-posts hidden" id="noMorePosts">
        <i class="fas fa-check-circle text-success mb-2 d-block"></i>
        <small>No more incidents to load</small>
    </div>
</div>

{{-- Image Viewer --}}
<div id="imageViewer" class="image-viewer-overlay" style="display: none;" onclick="closeImageViewer()">
    <span class="image-viewer-close">&times;</span>
    <img src="" alt="Full size image" id="viewerImage">
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    let currentPage = 1;
    let currentFilter = 'all';
    let isLoading = false;
    let hasMore = {{ isset($incidents) && $incidents->hasMorePages() ? 'true' : 'false' }};
    let nextCursor = '{{ isset($incidents) && $incidents->nextCursor() ? $incidents->nextCursor()->encode() : '' }}';

    // ==========================================
    // INFINITE SCROLL
    // ==========================================
    function setupInfiniteScroll() {
        const options = {
            root: null,
            rootMargin: '100px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting && !isLoading && hasMore) {
                    loadMoreIncidents();
                }
            });
        }, options);

        // Observe loading spinner
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) observer.observe(spinner);
    }

    window.loadMoreIncidents = function() {
        if (isLoading || !hasMore) return;

        isLoading = true;
        const spinner = document.getElementById('loadingSpinner');
        const noMore = document.getElementById('noMorePosts');

        spinner.classList.remove('hidden');
        noMore.classList.add('hidden');

        let url = '/?page=' + (currentPage + 1);

        if (currentFilter !== 'all') {
            if (currentFilter.startsWith('dept_')) {
                url += '&department_id=' + currentFilter.replace('dept_', '');
            } else {
                url += '&status=' + currentFilter;
            }
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                const container = document.getElementById('feedContainer');
                container.insertAdjacentHTML('beforeend', data.html);

                hasMore = data.has_more;
                nextCursor = data.next_cursor || '';
                currentPage++;

                if (!hasMore) {
                    noMore.classList.remove('hidden');
                }
            }
        })
        .catch(function(error) {
            console.error('Error loading more incidents:', error);
        })
        .finally(function() {
            isLoading = false;
            spinner.classList.add('hidden');
        });
    };

    // ==========================================
    // FILTER FEED
    // ==========================================
    window.filterFeed = function(filter, btn) {
        // Update active button
        document.querySelectorAll('.filter-btn').forEach(function(b) {
            b.classList.remove('active');
        });
        if (btn) btn.classList.add('active');

        currentFilter = filter;
        currentPage = 1;
        hasMore = true;

        let url = '/';
        if (filter !== 'all') {
            if (filter.startsWith('dept_')) {
                url += '?department_id=' + filter.replace('dept_', '');
            } else {
                url += '?status=' + filter;
            }
        }

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('feedContainer').innerHTML = data.html;
                hasMore = data.has_more;
                nextCursor = data.next_cursor || '';
                document.getElementById('noMorePosts').classList.add('hidden');

                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        })
        .catch(function(error) {
            console.error('Error filtering:', error);
        });
    };

    // ==========================================
    // SEARCH
    // ==========================================
    window.searchIncidents = function(e) {
        e.preventDefault();
        const query = document.getElementById('searchInput').value.trim();
        if (!query) return;

        fetch('/search?q=' + encodeURIComponent(query), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('feedContainer').innerHTML = data.html;
                hasMore = data.has_more;
                nextCursor = data.next_cursor || '';
                document.getElementById('noMorePosts').classList.add('hidden');
            }
        });
    };

    // ==========================================
    // LIKE INCIDENT
    // ==========================================
    window.handleLike = function(incidentId, btn) {
        @guest
            window.location.href = '{{ route("login") }}';
            return;
        @endguest

        fetch('/api/v1/incidents/' + incidentId + '/like', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                const countSpan = btn.querySelector('span');
                countSpan.textContent = data.likes_count;

                if (data.liked) {
                    btn.classList.add('liked');
                } else {
                    btn.classList.remove('liked');
                }
            }
        });
    };

    // ==========================================
    // SHARE INCIDENT
    // ==========================================
    window.shareIncident = function(incidentId, title) {
        const url = window.location.origin + '/incident/' + incidentId;

        if (navigator.share) {
            navigator.share({
                title: 'Incident #' + incidentId,
                text: title,
                url: url
            }).catch(function() {});
        } else {
            // Fallback - copy to clipboard
            navigator.clipboard.writeText(url).then(function() {
                alert('Link copied to clipboard!');
            }).catch(function() {
                prompt('Copy this link:', url);
            });
        }
    };

    // ==========================================
    // IMAGE VIEWER
    // ==========================================
    window.openImageViewer = function(imageUrl) {
        const viewer = document.getElementById('imageViewer');
        const img = document.getElementById('viewerImage');

        img.src = imageUrl;
        viewer.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    };

    window.closeImageViewer = function() {
        const viewer = document.getElementById('imageViewer');
        viewer.style.display = 'none';
        document.body.style.overflow = '';
    };

    // Close viewer on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeImageViewer();
        }
    });

    // Initialize
    setupInfiniteScroll();
});
</script>
@endpush
