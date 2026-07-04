

{{-- resources/views/components/image-editor-modal.blade.php --}}
<div class="modal fade" id="imageEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0">
                <h5 class="modal-title text-white">
                    <i class="fas fa-edit me-2"></i>Image Editor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 position-relative">
                {{-- Toolbar --}}
                <div class="editor-toolbar position-absolute top-0 start-50 translate-middle-x z-3 bg-dark bg-opacity-75 rounded-pill p-2 mt-2 d-flex gap-2 flex-wrap justify-content-center">
                    {{-- Drawing Tools --}}
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light" data-tool="pen" title="Pen">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn btn-light" data-tool="line" title="Line">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button class="btn btn-light" data-tool="circle" title="Circle">
                            <i class="fas fa-circle"></i>
                        </button>
                        <button class="btn btn-light" data-tool="rect" title="Rectangle">
                            <i class="fas fa-square"></i>
                        </button>
                        <button class="btn btn-light" data-tool="text" title="Text">
                            <i class="fas fa-font"></i>
                        </button>
                    </div>

                    {{-- Color Picker --}}
                    <div class="btn-group btn-group-sm">
                        <input type="color" id="drawColor" class="form-control form-control-color p-0" style="width: 32px; height: 32px; border-radius: 50%;" value="#ff0000">
                        <input type="range" id="drawSize" class="form-range" min="1" max="20" value="3" style="width: 80px;">
                    </div>

                    {{-- Actions --}}
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-warning" id="undoDraw" title="Undo">
                            <i class="fas fa-undo"></i>
                        </button>
                        <button class="btn btn-danger" id="clearDraw" title="Clear All">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="btn btn-success" id="saveDraw" title="Save">
                            <i class="fas fa-check"></i>
                        </button>
                    </div>
                </div>

                {{-- Canvas Container --}}
                <div class="editor-canvas-container w-100 h-100 d-flex align-items-center justify-content-center bg-dark" style="min-height: 80vh;">
                    <canvas id="editorCanvas" style="max-width: 100%; max-height: 90vh;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .editor-toolbar {
        backdrop-filter: blur(10px);
    }
    .editor-toolbar .btn {
        border-radius: 50% !important;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0;
    }
    .editor-toolbar .btn:hover {
        transform: scale(1.1);
    }
    .editor-toolbar .btn.active {
        background: #0d6efd;
        color: white;
    }
    .editor-canvas-container {
        padding-top: 80px;
    }
    .form-range {
        height: 4px;
        cursor: pointer;
    }
    .form-range::-webkit-slider-thumb {
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #fff;
        cursor: pointer;
    }
</style>

