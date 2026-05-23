import $ from 'jquery';
window.$ = $;
window.jQuery = $;

import './bootstrap';
import 'bootstrap';
import '@fortawesome/fontawesome-free/js/all';
import Swal from 'sweetalert2';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

// Moment.js for date formatting
import moment from 'moment';

// Chart.js
import Chart from 'chart.js/auto';

// Configure toastr
toastr.options = {
    closeButton: true,
    debug: false,
    newestOnTop: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    preventDuplicates: true,
    onclick: null,
    showDuration: '300',
    hideDuration: '1000',
    timeOut: '5000',
    extendedTimeOut: '1000',
    showEasing: 'swing',
    hideEasing: 'linear',
    showMethod: 'fadeIn',
    hideMethod: 'fadeOut'
};

// Global CSRF setup (This will now work safely!)
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Make globally available
window.Swal = Swal;
window.toastr = toastr;
window.moment = moment;

// Custom JavaScript Functions
window.IRMS = {
    // Show notification
    showNotification: function(type, message) {
        toastr[type](message);
    },

    // Confirm dialog
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
            if (result.isConfirmed && callback) {
                callback();
            }
        });
    },

    // Format date
    formatDate: function(date) {
        return moment(date).format('MMM DD, YYYY HH:mm');
    },

    // Time ago
    timeAgo: function(date) {
        return moment(date).fromNow();
    },

    // Infinite scroll
    initInfiniteScroll: function(container, loadMoreCallback) {
        let page = 1;
        let loading = false;

        $(window).scroll(function() {
            if ($(window).scrollTop() + $(window).height() >= $(document).height() - 100) {
                if (!loading) {
                    loading = true;
                    page++;
                    loadMoreCallback(page, function() {
                        loading = false;
                    });
                }
            }
        });
    },

    // Image preview
    previewImage: function(input, previewContainer) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(previewContainer).attr('src', e.target.result).show();
            }
            reader.readAsDataURL(input.files[0]);
        }
    },

    // Dark mode toggle
    toggleDarkMode: function() {
        const html = document.documentElement;
        const currentTheme = html.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    }
};

// Initialize tooltips and popovers
$(function() {
    $('[data-bs-toggle="tooltip"]').tooltip();
    $('[data-bs-toggle="popover"]').popover();

    // Check for saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }
});
