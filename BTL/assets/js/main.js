/**
 * Beauty Salon Booking Management System
 * Main JavaScript File
 */

(function () {
    'use strict';

    // ============================================
    // CSRF Token Handling
    // ============================================
    const CSRFToken = {
        get: function () {
            const meta = document.querySelector('meta[name="csrf-token"]');
            if (meta) return meta.getAttribute('content');
            const input = document.querySelector('input[name="csrf_token"]');
            if (input) return input.value;
            return '';
        },

        addToForm: function (form) {
            if (!form.querySelector('input[name="csrf_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = this.get();
                form.appendChild(input);
            }
        },

        addToHeaders: function (headers) {
            headers = headers || {};
            headers['X-CSRF-Token'] = this.get();
            return headers;
        }
    };

    // ============================================
    // Alert / Notification Helpers
    // ============================================
    const Notify = {
        container: null,

        init: function () {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'notification-container';
                this.container.style.cssText = 'position:fixed;top:80px;right:20px;z-index:9999;max-width:400px;width:100%;';
                document.body.appendChild(this.container);
            }
        },

        show: function (message, type, duration) {
            this.init();
            type = type || 'info';
            duration = duration || 4000;

            const icons = {
                success: 'fas fa-check-circle',
                danger: 'fas fa-exclamation-circle',
                warning: 'fas fa-exclamation-triangle',
                info: 'fas fa-info-circle'
            };

            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show animate-slide-down`;
            alert.setAttribute('role', 'alert');
            alert.innerHTML = `
                <i class="${icons[type] || icons.info} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            this.container.appendChild(alert);

            // Auto dismiss
            if (duration > 0) {
                setTimeout(function () {
                    if (alert.parentNode) {
                        alert.classList.remove('show');
                        setTimeout(function () {
                            if (alert.parentNode) alert.remove();
                        }, 300);
                    }
                }, duration);
            }

            return alert;
        },

        success: function (message, duration) {
            return this.show(message, 'success', duration);
        },

        error: function (message, duration) {
            return this.show(message, 'danger', duration);
        },

        warning: function (message, duration) {
            return this.show(message, 'warning', duration);
        },

        info: function (message, duration) {
            return this.show(message, 'info', duration);
        }
    };

    // Auto-dismiss existing alerts on page
    function initAutoDismissAlerts() {
        document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alert) {
            const duration = parseInt(alert.getAttribute('data-auto-dismiss')) || 4000;
            setTimeout(function () {
                if (alert.parentNode) {
                    alert.classList.remove('show');
                    setTimeout(function () {
                        if (alert.parentNode) alert.remove();
                    }, 300);
                }
            }, duration);
        });
    }

    // ============================================
    // Form Validation
    // ============================================
    const FormValidator = {
        rules: {
            required: function (value) {
                return value.trim() !== '';
            },
            email: function (value) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            },
            phone: function (value) {
                return /^(0[0-9]{9,10})$/.test(value.replace(/\s/g, ''));
            },
            minLength: function (value, min) {
                return value.length >= min;
            },
            maxLength: function (value, max) {
                return value.length <= max;
            },
            match: function (value, targetId) {
                const target = document.getElementById(targetId);
                return target && value === target.value;
            },
            username: function (value) {
                return /^[a-zA-Z0-9_]{3,30}$/.test(value);
            }
        },

        messages: {
            required: 'Trường này không được để trống',
            email: 'Email không hợp lệ',
            phone: 'Số điện thoại không hợp lệ',
            minLength: 'Tối thiểu {0} ký tự',
            maxLength: 'Tối đa {0} ký tự',
            match: 'Giá trị không khớp',
            username: 'Tên đăng nhập chỉ gồm chữ cái, số và dấu gạch dưới (3-30 ký tự)'
        },

        validate: function (form) {
            let isValid = true;
            const fields = form.querySelectorAll('[data-validate]');

            fields.forEach(function (field) {
                const rules = field.getAttribute('data-validate').split('|');
                const value = field.value;
                let fieldValid = true;
                let errorMessage = '';

                rules.forEach(function (rule) {
                    if (!fieldValid) return;

                    const parts = rule.split(':');
                    const ruleName = parts[0];
                    const ruleParam = parts[1];

                    let valid = false;
                    if (FormValidator.rules[ruleName]) {
                        valid = FormValidator.rules[ruleName](value, ruleParam);
                    }

                    if (!valid) {
                        fieldValid = false;
                        errorMessage = field.getAttribute('data-msg-' + ruleName) ||
                            FormValidator.messages[ruleName] || 'Giá trị không hợp lệ';
                        errorMessage = errorMessage.replace('{0}', ruleParam || '');
                    }
                });

                if (!fieldValid) {
                    isValid = false;
                    FormValidator.showError(field, errorMessage);
                } else {
                    FormValidator.clearError(field);
                }
            });

            return isValid;
        },

        showError: function (field, message) {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            let feedback = field.parentElement.querySelector('.invalid-feedback');
            if (!feedback) {
                feedback = document.createElement('div');
                feedback.className = 'invalid-feedback';
                field.parentElement.appendChild(feedback);
            }
            feedback.textContent = message;
        },

        clearError: function (field) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            const feedback = field.parentElement.querySelector('.invalid-feedback');
            if (feedback) feedback.textContent = '';
        },

        clearAll: function (form) {
            form.querySelectorAll('.is-invalid, .is-valid').forEach(function (el) {
                el.classList.remove('is-invalid', 'is-valid');
            });
        }
    };

    // Initialize form validation on submit
    function initFormValidation() {
        document.querySelectorAll('form[data-validate-form]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!FormValidator.validate(form)) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });

            // Real-time validation on blur
            form.querySelectorAll('[data-validate]').forEach(function (field) {
                field.addEventListener('blur', function () {
                    const tempForm = document.createElement('form');
                    tempForm.appendChild(field.cloneNode(true));
                    // Validate single field
                    const rules = field.getAttribute('data-validate').split('|');
                    const value = field.value;
                    let fieldValid = true;
                    let errorMessage = '';

                    rules.forEach(function (rule) {
                        if (!fieldValid) return;
                        const parts = rule.split(':');
                        const ruleName = parts[0];
                        const ruleParam = parts[1];
                        if (FormValidator.rules[ruleName]) {
                            if (!FormValidator.rules[ruleName](value, ruleParam)) {
                                fieldValid = false;
                                errorMessage = field.getAttribute('data-msg-' + ruleName) ||
                                    FormValidator.messages[ruleName] || 'Giá trị không hợp lệ';
                                errorMessage = errorMessage.replace('{0}', ruleParam || '');
                            }
                        }
                    });

                    if (!fieldValid) {
                        FormValidator.showError(field, errorMessage);
                    } else {
                        FormValidator.clearError(field);
                    }
                });
            });
        });
    }

    // ============================================
    // Confirm Delete Dialog
    // ============================================
    function confirmDelete(message, callback) {
        message = message || 'Bạn có chắc chắn muốn xóa? Hành động này không thể hoàn tác.';

        // Use Bootstrap modal if available
        let modal = document.getElementById('confirmDeleteModal');
        if (!modal) {
            const modalHTML = `
                <div class="modal fade" id="confirmDeleteModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h5 class="modal-title text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Xác nhận xóa
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="confirmDeleteMessage"></div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i>Hủy
                                </button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                                    <i class="fas fa-trash me-1"></i>Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            modal = document.getElementById('confirmDeleteModal');
        }

        document.getElementById('confirmDeleteMessage').textContent = message;
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
        newBtn.id = 'confirmDeleteBtn';

        newBtn.addEventListener('click', function () {
            bsModal.hide();
            if (typeof callback === 'function') callback();
        });
    }

    // Init delete buttons
    function initDeleteButtons() {
        document.querySelectorAll('[data-confirm-delete]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const message = this.getAttribute('data-confirm-delete') ||
                    'Bạn có chắc chắn muốn xóa?';
                const href = this.getAttribute('href') || this.getAttribute('data-href');
                const form = this.closest('form');

                confirmDelete(message, function () {
                    if (form) {
                        form.submit();
                    } else if (href) {
                        window.location.href = href;
                    }
                });
            });
        });
    }

    // ============================================
    // DataTable Initialization Helper
    // ============================================
    function initDataTable(selector, options) {
        if (typeof $ === 'undefined' || typeof $.fn.DataTable === 'undefined') {
            return null;
        }

        const defaults = {
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/vi.json',
                search: 'Tìm kiếm:',
                lengthMenu: 'Hiển thị _MENU_ dòng',
                info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
                infoEmpty: 'Không có dữ liệu',
                infoFiltered: '(lọc từ _MAX_ dòng)',
                paginate: {
                    first: 'Đầu',
                    last: 'Cuối',
                    next: '<i class="fas fa-chevron-right"></i>',
                    previous: '<i class="fas fa-chevron-left"></i>'
                },
                emptyTable: 'Không có dữ liệu',
                zeroRecords: 'Không tìm thấy kết quả phù hợp'
            },
            responsive: true,
            pageLength: 10,
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'Tất cả']],
            order: [[0, 'desc']],
            dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip'
        };

        const settings = Object.assign({}, defaults, options || {});
        return $(selector).DataTable(settings);
    }

    // ============================================
    // Sidebar Toggle
    // ============================================
    function initSidebarToggle() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');

        if (toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function (e) {
                e.preventDefault();
                // Mobile: show/hide sidebar overlay
                if (window.innerWidth < 992) {
                    sidebar.classList.toggle('show');
                    // Toggle overlay
                    let overlay = document.querySelector('.sidebar-overlay');
                    if (sidebar.classList.contains('show')) {
                        if (!overlay) {
                            overlay = document.createElement('div');
                            overlay.className = 'sidebar-overlay';
                            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);z-index:1025;';
                            document.body.appendChild(overlay);
                            overlay.addEventListener('click', function () {
                                sidebar.classList.remove('show');
                                overlay.remove();
                            });
                        }
                    } else if (overlay) {
                        overlay.remove();
                    }
                } else {
                    // Desktop: collapse sidebar
                    document.body.classList.toggle('sidebar-collapsed');
                }
            });
        }

        // Close sidebar on resize
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 992 && sidebar) {
                sidebar.classList.remove('show');
                const overlay = document.querySelector('.sidebar-overlay');
                if (overlay) overlay.remove();
            }
        });
    }

    // ============================================
    // Dynamic Search/Filter
    // ============================================
    function initSearchFilter() {
        document.querySelectorAll('[data-search-input]').forEach(function (input) {
            const targetSelector = input.getAttribute('data-search-input');
            const target = document.querySelector(targetSelector);
            if (!target) return;

            input.addEventListener('input', function () {
                const query = this.value.toLowerCase().trim();
                const items = target.querySelectorAll('[data-search-item]');

                items.forEach(function (item) {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(query) ? '' : 'none';
                });

                // Show no results message
                const visibleItems = target.querySelectorAll('[data-search-item]:not([style*="display: none"])');
                let noResults = target.querySelector('.no-search-results');
                if (visibleItems.length === 0) {
                    if (!noResults) {
                        noResults = document.createElement('div');
                        noResults.className = 'no-search-results text-center text-muted py-4';
                        noResults.innerHTML = '<i class="fas fa-search me-2"></i>Không tìm thấy kết quả';
                        target.appendChild(noResults);
                    }
                    noResults.style.display = '';
                } else if (noResults) {
                    noResults.style.display = 'none';
                }
            });
        });
    }

    // Filter by select dropdown
    function initSelectFilter() {
        document.querySelectorAll('[data-filter-select]').forEach(function (select) {
            const targetSelector = select.getAttribute('data-filter-select');
            const filterKey = select.getAttribute('data-filter-key');
            const target = document.querySelector(targetSelector);
            if (!target || !filterKey) return;

            select.addEventListener('change', function () {
                const value = this.value.toLowerCase();
                const items = target.querySelectorAll('[data-search-item]');

                items.forEach(function (item) {
                    if (!value) {
                        item.style.display = '';
                        return;
                    }
                    const itemValue = (item.getAttribute('data-' + filterKey) || '').toLowerCase();
                    item.style.display = itemValue === value ? '' : 'none';
                });
            });
        });
    }

    // ============================================
    // Date/Time Picker Helpers
    // ============================================
    const DateTimeHelper = {
        formatDate: function (date) {
            const d = new Date(date);
            const day = String(d.getDate()).padStart(2, '0');
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const year = d.getFullYear();
            return day + '/' + month + '/' + year;
        },

        formatTime: function (date) {
            const d = new Date(date);
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            return hours + ':' + minutes;
        },

        formatDateTime: function (date) {
            return this.formatDate(date) + ' ' + this.formatTime(date);
        },

        parseViDate: function (dateStr) {
            // Parse dd/mm/yyyy
            const parts = dateStr.split('/');
            if (parts.length === 3) {
                return new Date(parts[2], parts[1] - 1, parts[0]);
            }
            return new Date(dateStr);
        },

        getToday: function () {
            return new Date().toISOString().split('T')[0];
        },

        setMinDate: function (selector, minDate) {
            const el = document.querySelector(selector);
            if (el) {
                el.setAttribute('min', minDate || this.getToday());
            }
        },

        generateTimeSlots: function (startHour, endHour, intervalMinutes) {
            startHour = startHour || 8;
            endHour = endHour || 20;
            intervalMinutes = intervalMinutes || 30;
            const slots = [];
            for (let h = startHour; h < endHour; h++) {
                for (let m = 0; m < 60; m += intervalMinutes) {
                    slots.push(
                        String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0')
                    );
                }
            }
            return slots;
        },

        populateTimeSelect: function (selector, startHour, endHour, intervalMinutes) {
            const select = document.querySelector(selector);
            if (!select) return;
            const slots = this.generateTimeSlots(startHour, endHour, intervalMinutes);
            select.innerHTML = '<option value="">-- Chọn giờ --</option>';
            slots.forEach(function (slot) {
                const option = document.createElement('option');
                option.value = slot;
                option.textContent = slot;
                select.appendChild(option);
            });
        }
    };

    // ============================================
    // Utility Functions
    // ============================================
    function formatCurrency(amount) {
        return new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(amount);
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('vi-VN').format(num);
    }

    // Debounce helper
    function debounce(func, wait) {
        let timeout;
        return function () {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function () {
                func.apply(context, args);
            }, wait);
        };
    }

    // AJAX helper
    function ajaxRequest(url, method, data, callback) {
        const xhr = new XMLHttpRequest();
        xhr.open(method || 'GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-CSRF-Token', CSRFToken.get());

        if (method === 'POST' && !(data instanceof FormData)) {
            xhr.setRequestHeader('Content-Type', 'application/json');
            data = JSON.stringify(data);
        }

        xhr.onload = function () {
            let response;
            try {
                response = JSON.parse(xhr.responseText);
            } catch (e) {
                response = xhr.responseText;
            }
            if (typeof callback === 'function') {
                callback(xhr.status === 200, response, xhr);
            }
        };

        xhr.onerror = function () {
            if (typeof callback === 'function') {
                callback(false, null, xhr);
            }
        };

        xhr.send(data || null);
    }

    // Toggle password visibility
    function initPasswordToggle() {
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetId = this.getAttribute('data-toggle-password');
                const input = document.getElementById(targetId);
                if (!input) return;

                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    if (icon) {
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    }
                } else {
                    input.type = 'password';
                    if (icon) {
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                }
            });
        });
    }

    // ============================================
    // Tooltip & Popover Init
    // ============================================
    function initTooltips() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (el) {
            new bootstrap.Tooltip(el);
        });
    }

    function initPopovers() {
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.forEach(function (el) {
            new bootstrap.Popover(el);
        });
    }

    // ============================================
    // Image Preview
    // ============================================
    function initImagePreview() {
        document.querySelectorAll('[data-image-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                const previewId = this.getAttribute('data-image-preview');
                const preview = document.getElementById(previewId);
                if (!preview) return;

                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });
    }

    // ============================================
    // Initialize Everything on DOM Ready
    // ============================================
    document.addEventListener('DOMContentLoaded', function () {
        initAutoDismissAlerts();
        initFormValidation();
        initDeleteButtons();
        initSidebarToggle();
        initSearchFilter();
        initSelectFilter();
        initPasswordToggle();
        initTooltips();
        initPopovers();
        initImagePreview();
    });

    // ============================================
    // Expose Public API
    // ============================================
    window.BeautySalon = {
        Notify: Notify,
        FormValidator: FormValidator,
        CSRFToken: CSRFToken,
        DateTimeHelper: DateTimeHelper,
        confirmDelete: confirmDelete,
        initDataTable: initDataTable,
        formatCurrency: formatCurrency,
        formatNumber: formatNumber,
        debounce: debounce,
        ajaxRequest: ajaxRequest
    };

})();
