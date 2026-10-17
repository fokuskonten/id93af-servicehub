// Main JavaScript for TechFix Pro Application

$(document).ready(function() {
    // Initialize DataTables
    if ($('.data-table').length) {
        $('.data-table').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
            },
            pageLength: 10,
            order: [[0, 'desc']],
            columnDefs: [
                { orderable: false, targets: 'no-sort' }
            ]
        });
    }

    // Auto-hide alerts after 5 seconds
    $('.alert').each(function() {
        const alert = $(this);
        setTimeout(function() {
            alert.fadeOut('slow');
        }, 5000);
    });

    // Confirm delete actions
    $('.btn-delete').on('click', function(e) {
        e.preventDefault();
        const url = $(this).attr('href');
        const title = $(this).data('title') || 'Hapus Data';
        const text = $(this).data('text') || 'Apakah Anda yakin ingin menghapus data ini?';
        
        Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    // Form validation
    $('.needs-validation').on('submit', function(e) {
        const form = $(this)[0];
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
        }
        $(this).addClass('was-validated');
    });

    // Auto-refresh ticket status every 30 seconds
    if ($('.ticket-status').length) {
        setInterval(function() {
            refreshTicketStatus();
        }, 30000);
    }

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Initialize popovers
    $('[data-bs-toggle="popover"]').popover();

    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $($(this).attr('href'));
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500);
        }
    });

    // File upload preview
    $('.file-input').on('change', function() {
        const file = this.files[0];
        const preview = $(this).siblings('.file-preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    preview.html(`<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`);
                } else {
                    preview.html(`<p class="text-muted"><i class="fas fa-file"></i> ${file.name}</p>`);
                }
            };
            reader.readAsDataURL(file);
        } else {
            preview.empty();
        }
    });

    // Search functionality
    $('.search-input').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        const target = $(this).data('target');
        
        $(target + ' .searchable').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Status update functionality
    $('.status-select').on('change', function() {
        const ticketId = $(this).data('ticket-id');
        const newStatus = $(this).val();
        const currentElement = $(this);
        
        if (ticketId && newStatus) {
            updateTicketStatus(ticketId, newStatus, currentElement);
        }
    });

    // Print functionality
    $('.btn-print').on('click', function(e) {
        e.preventDefault();
        window.print();
    });

    // Export functionality
    $('.btn-export').on('click', function(e) {
        e.preventDefault();
        const format = $(this).data('format');
        const url = $(this).attr('href');
        
        if (format && url) {
            window.open(url, '_blank');
        }
    });
});

// Function to refresh ticket status
function refreshTicketStatus() {
    $('.ticket-status').each(function() {
        const ticketId = $(this).data('ticket-id');
        const statusElement = $(this);
        
        if (ticketId) {
            $.ajax({
                url: BASE_URL + 'api/get_ticket_status.php',
                method: 'GET',
                data: { ticket_id: ticketId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateStatusDisplay(statusElement, response.data.status);
                    }
                },
                error: function() {
                    console.log('Error refreshing ticket status');
                }
            });
        }
    });
}

// Function to update ticket status
function updateTicketStatus(ticketId, newStatus, element) {
    const originalValue = element.data('original-value');
    
    // Show loading state
    element.prop('disabled', true);
    
    $.ajax({
        url: BASE_URL + 'api/update_ticket_status.php',
        method: 'POST',
        data: {
            ticket_id: ticketId,
            status: newStatus
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Status tiket berhasil diperbarui',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Update UI
                updateStatusDisplay(element.closest('.ticket-card'), newStatus);
                element.data('original-value', newStatus);
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Gagal memperbarui status',
                    icon: 'error'
                });
                
                // Revert to original value
                element.val(originalValue);
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan sistem',
                icon: 'error'
            });
            
            // Revert to original value
            element.val(originalValue);
        },
        complete: function() {
            element.prop('disabled', false);
        }
    });
}

// Function to update status display
function updateStatusDisplay(element, status) {
    const statusBadge = element.find('.status-badge');
    const statusText = getStatusText(status);
    const statusClass = getStatusClass(status);
    
    if (statusBadge.length) {
        statusBadge.removeClass().addClass('badge status-badge ' + statusClass).text(statusText);
    }
    
    // Update progress bar if exists
    const progressBar = element.find('.progress-bar');
    if (progressBar.length) {
        const progress = getStatusProgress(status);
        progressBar.css('width', progress + '%').attr('aria-valuenow', progress);
    }
}

// Function to get status text
function getStatusText(status) {
    const statusTexts = {
        'pending': 'Menunggu Konfirmasi',
        'in_progress': 'Sedang Dikerjakan',
        'waiting_parts': 'Menunggu Spare Part',
        'testing': 'Tahap Testing',
        'completed': 'Selesai',
        'delivered': 'Sudah Diambil',
        'cancelled': 'Dibatalkan'
    };
    
    return statusTexts[status] || 'Unknown';
}

// Function to get status CSS class
function getStatusClass(status) {
    const statusClasses = {
        'pending': 'bg-warning',
        'in_progress': 'bg-info',
        'waiting_parts': 'bg-secondary',
        'testing': 'bg-primary',
        'completed': 'bg-success',
        'delivered': 'bg-dark',
        'cancelled': 'bg-danger'
    };
    
    return statusClasses[status] || 'bg-secondary';
}

// Function to get status progress percentage
function getStatusProgress(status) {
    const statusProgress = {
        'pending': 10,
        'in_progress': 40,
        'waiting_parts': 60,
        'testing': 80,
        'completed': 100,
        'delivered': 100,
        'cancelled': 0
    };
    
    return statusProgress[status] || 0;
}

// Function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

// Function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Function to show loading state
function showLoading(element) {
    const originalText = element.html();
    element.data('original-text', originalText);
    element.html('<span class="loading-spinner"></span> Loading...');
    element.prop('disabled', true);
}

// Function to hide loading state
function hideLoading(element) {
    const originalText = element.data('original-text');
    element.html(originalText);
    element.prop('disabled', false);
}

// Function to copy text to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        Swal.fire({
            title: 'Berhasil!',
            text: 'Teks berhasil disalin ke clipboard',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }).catch(function() {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        
        Swal.fire({
            title: 'Berhasil!',
            text: 'Teks berhasil disalin ke clipboard',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    });
}

// Global variables
const BASE_URL = window.location.origin + '/service-repair/';