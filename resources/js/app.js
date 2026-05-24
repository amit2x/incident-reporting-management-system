// resources/js/app.js

// ==========================================
// 1. IMPORT JQUERY (Version 3.x required for Bootstrap)
// ==========================================
import $ from 'jquery';
window.$ = window.jQuery = $;

// Verify jQuery version
console.log('jQuery version:', $.fn.jquery);

// ==========================================
// 2. IMPORT POPPER (Required by Bootstrap)
// ==========================================
import { createPopper } from '@popperjs/core';
window.createPopper = createPopper;

// ==========================================
// 3. IMPORT BOOTSTRAP JS
// ==========================================
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// ==========================================
// 4. IMPORT SELECT2 - THE CORRECT WAY
// ==========================================
// Select2 needs jQuery globally available first, then import
import select2 from 'select2';

// Initialize Select2 plugin on jQuery
select2($);

// Verify Select2 is available
console.log('Select2 available:', typeof $.fn.select2 !== 'undefined');

// ==========================================
// 5. IMPORT FONT AWESOME
// ==========================================
import '@fortawesome/fontawesome-free/js/all';

// ==========================================
// 6. IMPORT SWEETALERT2
// ==========================================
import Swal from 'sweetalert2';
window.Swal = Swal;

// ==========================================
// 7. IMPORT TOASTR
// ==========================================
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';
window.toastr = toastr;

toastr.options = {
    closeButton: true,
    debug: false,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    preventDuplicates: true,
    showDuration: '300',
    hideDuration: '1000',
    timeOut: '5000',
    extendedTimeOut: '1000',
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut'
};

// ==========================================
// 8. IMPORT MOMENT.JS
// ==========================================
import moment from 'moment';
window.moment = moment;

// ==========================================
// 9. IMPORT CHART.JS
// ==========================================
import Chart from 'chart.js/auto';
window.Chart = Chart;

// ==========================================
// 10. CSRF SETUP
// ==========================================
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// ==========================================
// 11. HELPER FUNCTIONS
// ==========================================
window.IRMS = {
    showNotification: function(type, message) {
        if (typeof toastr !== 'undefined' && toastr[type]) {
            toastr[type](message);
        }
    },

    confirmAction: function(title, text, callback) {
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#2563eb',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Yes, proceed!'
        }).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    },

    formatDate: function(date) {
        return moment(date).format('MMM DD, YYYY HH:mm');
    },

    timeAgo: function(date) {
        return moment(date).fromNow();
    },

    toggleDarkMode: function() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    }
};

// ==========================================
// 12. FILE UPLOAD HELPER
// ==========================================
window.initializeFileUpload = function(options) {
    const {
        fileInputId = 'fileInput',
        dropZoneId = 'dropZone',
        previewContainerId = 'previewContainer',
        maxFileSize = 20971520
    } = options;

    const fileInput = document.getElementById(fileInputId);
    const dropZone = document.getElementById(dropZoneId);
    const previewContainer = document.getElementById(previewContainerId);

    if (!fileInput) return;

    let dataTransfer = new DataTransfer();

    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });

    if (dropZone) {
        dropZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#3b82f6';
            this.style.background = '#f0f5ff';
        });

        dropZone.addEventListener('dragleave', function() {
            this.style.borderColor = '#d1d5db';
            this.style.background = '#fafafa';
        });

        dropZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '#d1d5db';
            this.style.background = '#fafafa';
            handleFiles(e.dataTransfer.files);
        });

        dropZone.addEventListener('click', function() {
            fileInput.click();
        });
    }

    function handleFiles(files) {
        Array.from(files).forEach(function(file) {
            if (file.size > maxFileSize) {
                alert('File "' + file.name + '" is too large. Max 20MB.');
                return;
            }

            dataTransfer.items.add(file);

            const reader = new FileReader();
            reader.onload = function(e) {
                const div = document.createElement('div');
                div.className = 'preview-item';
                div.style.cssText = 'width:90px;height:90px;border-radius:10px;position:relative;border:2px solid #e5e7eb;overflow:hidden;display:inline-block;';

                if (file.type.startsWith('image/')) {
                    div.innerHTML =
                        '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;">' +
                        '<button type="button" class="btn-remove" style="position:absolute;top:-4px;right:-4px;width:22px;height:22px;background:#ef4444;color:white;border:2px solid white;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:10px;z-index:2;" onclick="this.parentElement.remove()">&times;</button>';
                } else {
                    let icon = 'fa-file';
                    if (file.type.startsWith('video/')) icon = 'fa-video';
                    else if (file.type.includes('pdf')) icon = 'fa-file-pdf';

                    const shortName = file.name.length > 12 ? file.name.substring(0, 10) + '..' : file.name;

                    div.innerHTML =
                        '<div style="width:100%;height:100%;background:#f3f4f6;display:flex;flex-direction:column;align-items:center;justify-content:center;">' +
                            '<i class="fas ' + icon + '" style="font-size:1.5rem;color:#6b7280;"></i>' +
                            '<span style="font-size:0.5rem;color:#9ca3af;">' + shortName + '</span>' +
                        '</div>' +
                        '<button type="button" class="btn-remove" style="position:absolute;top:-4px;right:-4px;width:22px;height:22px;background:#ef4444;color:white;border:2px solid white;border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:10px;z-index:2;" onclick="this.parentElement.remove()">&times;</button>';
                }

                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });

        fileInput.files = dataTransfer.files;
    }
};

// ==========================================
// 13. DOM READY INITIALIZATION
// ==========================================
$(document).ready(function() {
    console.log('=== IRMS Initialized ===');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Bootstrap:', typeof bootstrap !== 'undefined' ? '✓' : '✗');
    console.log('Select2:', typeof $.fn.select2 !== 'undefined' ? '✓' : '✗');
    console.log('========================');

    // Initialize Bootstrap tooltips natively
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });

    // Initialize Bootstrap popovers natively
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(function(el) {
        new bootstrap.Popover(el);
    });

    // Initialize Select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: 'Search...',
            allowClear: true,
            width: '100%'
        });
        console.log('✓ Select2 initialized on', $('.select2').length, 'elements');
    }

    // Check saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
});


// ======================================
// Image compression
// =====================================

import imageCompression from 'browser-image-compression';
window.imageCompression = imageCompression;
