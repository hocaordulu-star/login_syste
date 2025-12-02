/**
 * Öğretmen Paneli Etkileşimli JavaScript
 *
 * Bu dosya öğretmen kontrol panelindeki tüm arayüz etkileşimlerini yönetir.
 * Bölümler: Dashboard, Videolar, Yükleme, Analitik, Bildirimler, Tema, Yardımcılar.
 *
 * Notlar:
 * - AJAX istekleri backend'de `teacher_panel.php` uç noktasına yapılır.
 * - Hata toleransı için birden çok yerde graceful fallback uygulanır (DOM elemanı yoksa no-op).
 * - Uzun işlemlerde kullanıcıya geri bildirim için `showLoading()` ve bildirim sistemi kullanılır.
 */

/**
 * TeacherPanel sınıfı
 *
 * Durum (state):
 * - currentSection: aktif bölüm (dashboard, videos, upload, analytics)
 * - currentPage: video listesi için sayfa numarası
 * - currentFilters: video filtreleri (status, grade, tarih aralığı, arama)
 * - selectedVideos: toplu işlemler için seçili video ID'leri
 * - notifications: bildirim listesi (opsiyonel)
 */
class TeacherPanel {
    constructor() {
        this.currentSection = 'dashboard';
        this.currentPage = 1;
        this.currentFilters = {};
        this.selectedVideos = new Set();
        this.notifications = [];
        
        this.init();
    }
    
    /**
     * Bileşeni başlatır.
     * - Event listener'ları bağlar
     * - Dashboard ve ünite listesini yükler
     * - Tema ve bildirim sistemini hazırlar
     */
    init() {
        this.setupEventListeners();
        this.loadDashboard();
        this.setupThemeToggle();
        // Some builds may not define setupSearch separately; ensure it exists
        if (typeof this.setupSearch === 'function') {
            this.setupSearch();
        }
        this.setupNotifications();
        this.loadUnits();
    }
    
    // ==================== EVENT LISTENERS ====================
    
    /**
     * Global event listener kurulumları
     * - Navigasyon, formlar, filtreler, arama, seçim kutuları, modal kapatma, dosya yükleme
     */
    setupEventListeners() {
        // Navigation (only links that control a section)
        document.querySelectorAll('.teacher-nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.dataset.section;
                if (!section) {
                    // Links without a data-section are normal navigations
                    const href = link.getAttribute('href') || '#';
                    // Prevent invalid navigation to video_edit_request.php without a video_id
                    if (href.includes('video_edit_request.php') && !href.includes('video_id=')) {
                        this.showNotification('Lütfen bir video üzerinden talep başlatın.', 'warning');
                        return;
                    }
                    window.location.href = href;
                    return;
                }
                this.showSection(section);
            });
        });
        
        // Form submissions
        const uploadForm = document.getElementById('videoUploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', (e) => this.handleVideoUpload(e));
        }
        
        // Bulk operations
        const bulkForm = document.getElementById('bulkOperationsForm');
        if (bulkForm) {
            bulkForm.addEventListener('submit', (e) => this.handleBulkOperation(e));
        }
        
        // Filters
        document.querySelectorAll('.filter-input').forEach(input => {
            input.addEventListener('change', () => this.applyFilters());
        });
        
        // Search
        const searchInput = document.getElementById('teacherSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.currentFilters.search = e.target.value;
                    this.loadVideos();
                }, 500);
            });
        }
        
        // Video selection
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('video-checkbox')) {
                this.handleVideoSelection(e.target);
            }
        });
        
        // Select all checkbox
        const selectAllCheckbox = document.getElementById('selectAllVideos');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.handleSelectAll(e.target.checked);
            });
        }
        
        // Modal handlers
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal')) {
                this.closeModal(e.target.closest('.modal'));
            }
        });
        
        // File upload drag & drop
        this.setupFileUpload();
    }
    
    // ==================== NAVIGATION ====================
    
    /**
     * Bölüm görünürlüğünü yönetir ve ilgili verileri yükler.
     * @param {string} section 'dashboard' | 'videos' | 'upload' | 'analytics'
     */
    showSection(section) {
        // Update navigation
        document.querySelectorAll('.teacher-nav-link').forEach(link => {
            link.classList.remove('active');
        });
        const activeLink = document.querySelector(`[data-section="${section}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }
        
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(sec => {
            sec.style.display = 'none';
        });
        
        // Show target section
        const targetSection = document.getElementById(`${section}Section`);
        if (targetSection) {
            targetSection.style.display = 'block';
        }
        
        this.currentSection = section;
        
        // Load section data
        switch (section) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'videos':
                this.loadVideos();
                break;
            case 'upload':
                this.resetUploadForm();
                break;
            case 'analytics':
                this.loadAnalytics();
                break;
        }
    }

    // Optional separate search setup for some templates
    /**
     * Bazı şablonlarda arama alanı bağımsız tanımlanabilir.
     * Debounce ile 500ms sonra videoları filtreler.
     */
    setupSearch() {
        const searchInput = document.getElementById('teacherSearch');
        if (!searchInput) return;
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.currentFilters.search = e.target.value;
                this.loadVideos();
            }, 500);
        });
    }
    
    // ==================== DASHBOARD ====================
    
    /**
     * Dashboard istatistiklerini yükler.
     * Başarılıysa kartları günceller ve son aktiviteyi gösterir.
     */
    async loadDashboard() {
        try {
            this.showLoading('dashboardStats');
            
            const response = await fetch('teacher_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_dashboard_stats'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.renderDashboardStats(data.stats);
                this.loadRecentActivity();
            } else {
                this.showNotification('Dashboard yüklenemedi: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Dashboard loading error:', error);
            this.showNotification('Dashboard yüklenirken hata oluştu', 'error');
        } finally {
            this.hideLoading('dashboardStats');
        }
    }
    
    /**
     * Dashboard kartlarını güvenli biçimde render eder.
     * Eksik alanlar için varsayılan değerlerle çalışır.
     */
    renderDashboardStats(stats) {
        const safe = (obj, fallback) => (obj && typeof obj === 'object') ? obj : fallback;
        const videos = safe(stats && stats.videos, {
            total_videos: 0,
            pending_videos: 0,
            approved_videos: 0,
            rejected_videos: 0,
            videos_this_week: 0,
            videos_this_month: 0,
        });
        const engagement = safe(stats && stats.engagement, {
            total_views: 0,
            avg_completion_rate: 0,
            total_likes: 0,
            unique_viewers: 0,
        });
        const activity = safe(stats && stats.activity, {
            pending_requests: 0,
            messages_week: 0,
        });

        // Video statistics
        const elTotal = document.getElementById('totalVideos'); if (elTotal) elTotal.textContent = videos.total_videos || 0;
        const elPending = document.getElementById('pendingVideos'); if (elPending) elPending.textContent = videos.pending_videos || 0;
        const elApproved = document.getElementById('approvedVideos'); if (elApproved) elApproved.textContent = videos.approved_videos || 0;
        const elRejected = document.getElementById('rejectedVideos'); if (elRejected) elRejected.textContent = videos.rejected_videos || 0;
        
        // Engagement statistics
        const elViews = document.getElementById('totalViews'); if (elViews) elViews.textContent = this.formatNumber(engagement.total_views || 0);
        const elAvg = document.getElementById('avgCompletion'); if (elAvg) elAvg.textContent = Math.round(engagement.avg_completion_rate || 0) + '%';
        const elLikes = document.getElementById('totalLikes'); if (elLikes) elLikes.textContent = engagement.total_likes || 0;
        const elUnique = document.getElementById('uniqueViewers'); if (elUnique) elUnique.textContent = engagement.unique_viewers || 0;
        
        // Activity statistics
        const elWeekly = document.getElementById('weeklyUploads'); if (elWeekly) elWeekly.textContent = videos.videos_this_week || 0;
        const elMonthly = document.getElementById('monthlyUploads'); if (elMonthly) elMonthly.textContent = videos.videos_this_month || 0;
        const elPendingReq = document.getElementById('pendingRequests'); if (elPendingReq) elPendingReq.textContent = activity.pending_requests || 0;
        const elWeeklyMsg = document.getElementById('weeklyMessages'); if (elWeeklyMsg) elWeeklyMsg.textContent = activity.messages_week || 0;
        
        // Update progress bars and charts
        this.updateProgressBars({ videos, engagement, activity });
        if (typeof this.renderMiniCharts === 'function') {
            this.renderMiniCharts({ videos, engagement, activity });
        }
    }

    // Graceful fallback stubs to prevent runtime errors
    /**
     * Son aktiviteler bileşeni (opsiyonel). Mevcut değilse no-op.
     */
    loadRecentActivity() {
        const container = document.getElementById('recentActivity');
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Aktivite yok</h3>
                    <p>Henüz herhangi bir hareket bulunmuyor.</p>
                </div>
            `;
        }
    }

    /**
     * Video listesi sayfalama butonlarını oluşturur.
     */
    renderPagination(pagination) {
        const el = document.getElementById('videosPagination');
        if (!el || !pagination) return;
        if ((pagination.pages || 0) <= 1) { el.innerHTML = ''; return; }
        const page = pagination.page || 1;
        const pages = pagination.pages || 1;
        let html = '';
        for (let p = 1; p <= pages; p++) {
            html += `<button class="btn ${p === page ? 'btn-primary' : 'btn-secondary'} btn-sm" data-page="${p}">${p}</button>`;
        }
        el.innerHTML = html;
        el.querySelectorAll('button[data-page]').forEach(btn => {
            btn.addEventListener('click', () => this.loadVideos(parseInt(btn.dataset.page, 10)));
        });
    }

    /**
     * Sınıf dağılımı grafiği (opsiyonel). Container yoksa no-op.
     */
    renderGradeDistributionChart(data) {
        // No-op if chart containers are not present
        return;
    }

    /**
     * Yükleme trendleri grafiği (opsiyonel). Container yoksa no-op.
     */
    renderUploadTrendsChart(data) {
        // No-op if chart containers are not present
        return;
    }

    /**
     * Dashboard mini grafikleri için placeholder.
     */
    renderMiniCharts(stats) {
        // No-op placeholder for small charts on dashboard
        return;
    }
    
    /**
     * Onay/Bekleyen/Reddedilen video oranlarını progress bar'lara uygular.
     */
    updateProgressBars(stats) {
        const v = (stats && stats.videos) ? stats.videos : {};
        const total = v.total_videos || 1;
        const approved = v.approved_videos || 0;
        const pending = v.pending_videos || 0;
        const rejected = v.rejected_videos || 0;
        
        this.updateProgressBar('approvalProgress', (approved / total) * 100);
        this.updateProgressBar('pendingProgress', (pending / total) * 100);
        this.updateProgressBar('rejectionProgress', (rejected / total) * 100);
    }
    
    /**
     * Tekil progress bar genişliğini ayarlar.
     */
    updateProgressBar(id, percentage) {
        const progressBar = document.getElementById(id);
        if (progressBar) {
            progressBar.style.width = percentage + '%';
        }
    }
    
    // ==================== VIDEO MANAGEMENT ====================
    
    /**
     * Video listesini filtre ve sayfa parametreleriyle yükler.
     * Yanıtı `renderVideosList()` ve `renderPagination()` ile ekrana basar.
     */
    async loadVideos(page = 1) {
        try {
            this.showLoading('videosList');
            
            const params = new URLSearchParams({
                action: 'get_videos',
                page: page,
                ...this.currentFilters
            });
            
            const response = await fetch('teacher_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Keep a local cache of the last videos payload for detail rendering
                this.lastVideos = Array.isArray(data.videos && data.videos.videos) ? data.videos.videos : [];
                this.renderVideosList(data.videos);
                this.renderPagination(data.pagination);
                this.updateBulkActions();
            } else {
                this.showNotification('Videolar yüklenemedi: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Videos loading error:', error);
            this.showNotification('Videolar yüklenirken hata oluştu', 'error');
        } finally {
            this.hideLoading('videosList');
        }
    }
    
    /**
     * Video tablosunu render eder.
     * - YouTube/Yerel kaynak durumunu badge ile gösterir
     * - Durumlara (approved/pending/rejected) uygun rozet ve ikonları kullanır
     * - İşlem butonları: görüntüle/düzenleme talebi/silme talebi
     * @param {{videos: Array}} videos Sunucudan gelen { videos: [...]} yapısı
     */
    renderVideosList(videos) {
        const container = document.getElementById('videosTableBody');
        if (!container) return;
        
        if (videos.videos.length === 0) {
            container.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <i class="fas fa-video"></i>
                        <h3>Henüz video yok</h3>
                        <p>İlk videonuzu yüklemek için "Video Yükle" bölümünü kullanın</p>
                    </td>
                </tr>
            `;
            return;
        }
        
        // Her video için satır HTML'i üret
        container.innerHTML = videos.videos.map(video => {
            const hasYouTube = !!(video.youtube_url && String(video.youtube_url).trim());
            const hasLocal = !!(video.file_path && String(video.file_path).trim());
            // Kaynak rozeti: YouTube öncelikli, ardından Yerel; ikisi de yoksa Yok
            const sourceBadge = hasYouTube
                ? '<span class="badge badge-youtube" title="YouTube üzerinden oynatılır">YouTube</span>'
                : (hasLocal ? '<span class="badge badge-local" title="Yerel dosya">Yerel</span>' : '<span class="badge badge-missing" title="Kaynak bulunamadı">Yok</span>');
            return `
            <tr>
                <td>
                    <input type="checkbox" class="video-checkbox" value="${video.id}" 
                           ${this.selectedVideos.has(video.id.toString()) ? 'checked' : ''}>
                </td>
                <td>
                    <div class="video-info">
                        <strong>${this.escapeHtml(video.title)}</strong>
                        ${video.description ? `<br><small class="text-muted">${this.escapeHtml(video.description.substring(0, 100))}${video.description.length > 100 ? '...' : ''}</small>` : ''}
                        <div class="video-source">${sourceBadge}</div>
                    </div>
                </td>
                <td><span class="badge badge-grade">${video.grade}. Sınıf</span></td>
                <td>${this.escapeHtml(video.unit || '')}</td>
                <td>${this.escapeHtml(video.topic || '-')}</td>
                <td>
                    <span class="badge badge-${video.status}">
                        <i class="fas fa-${this.getStatusIcon(video.status)}"></i>
                        ${this.getStatusText(video.status)}
                    </span>
                </td>
                <td>
                    <div class="video-stats">
                        <small><i class="fas fa-eye"></i> ${video.view_count || 0}</small>
                        <small><i class="fas fa-heart"></i> ${video.like_count || 0}</small>
                        <small><i class="fas fa-users"></i> ${video.progress_count || 0}</small>
                    </div>
                </td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn btn-view" onclick="teacherPanel.viewVideo(${video.id})" title="Görüntüle">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn btn-edit" onclick="teacherPanel.requestEdit(${video.id})" title="Düzenleme Talebi">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn btn-delete" onclick="teacherPanel.requestDelete(${video.id})" title="Silme Talebi">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
            `;
        }).join('');
    }
    
    // ==================== VIDEO UPLOAD ====================
    
    /**
     * Video yükleme formunu işler.
     * Başarılı yüklemede istatistikleri yeniler ve videolar sekmesine geçer.
     */
    async handleVideoUpload(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        formData.append('action', 'upload_video');
        
        try {
            this.showLoading('uploadButton');
            
            const response = await fetch('teacher_panel.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                this.resetUploadForm();
                this.loadDashboard(); // Refresh stats
                
                // Switch to videos section to see the uploaded video
                setTimeout(() => {
                    this.showSection('videos');
                }, 1500);
            } else {
                this.showNotification('Upload hatası: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Upload error:', error);
            this.showNotification('Video yüklenirken hata oluştu', 'error');
        } finally {
            this.hideLoading('uploadButton');
        }
    }
    
    /**
     * Yükleme formunu sıfırlar ve seçilen dosya bilgisini temizler.
     */
    resetUploadForm() {
        const form = document.getElementById('videoUploadForm');
        if (form) {
            form.reset();
            this.updateFileUploadDisplay();
        }
    }
    
    /**
     * Dosya yükleme alanı için drag&drop ve change event'lerini ayarlar.
     */
    setupFileUpload() {
        const fileInput = document.getElementById('videoFile');
        const fileUpload = document.querySelector('.file-upload');
        const fileUploadText = document.querySelector('.file-upload-text');
        
        if (!fileInput || !fileUpload) return;
        
        // Drag & drop
        fileUpload.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });
        
        fileUpload.addEventListener('dragleave', () => {
            fileUpload.classList.remove('dragover');
        });
        
        fileUpload.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                this.updateFileUploadDisplay();
            }
        });
        
        // File selection
        fileInput.addEventListener('change', () => {
            this.updateFileUploadDisplay();
        });
    }
    
    /**
     * Seçilen dosyanın adını ve boyutunu kullanıcıya gösterir.
     */
    updateFileUploadDisplay() {
        const fileInput = document.getElementById('videoFile');
        const fileUploadText = document.querySelector('.file-upload-text');
        
        if (!fileInput || !fileUploadText) return;
        
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const size = this.formatFileSize(file.size);
            fileUploadText.innerHTML = `
                <strong>${file.name}</strong><br>
                <small>${size}</small>
            `;
        } else {
            fileUploadText.innerHTML = `
                <strong>Dosya seçin veya sürükleyip bırakın</strong><br>
                <small>MP4, MOV, WEBM formatları desteklenir</small>
            `;
        }
    }
    
    // ==================== BULK OPERATIONS ====================
    
    /**
     * Tablo satırındaki checkbox ile seçili video kümesini günceller.
     */
    handleVideoSelection(checkbox) {
        const videoId = checkbox.value;
        
        if (checkbox.checked) {
            this.selectedVideos.add(videoId);
        } else {
            this.selectedVideos.delete(videoId);
        }
        
        this.updateBulkActions();
        this.updateSelectAllCheckbox();
    }
    
    /**
     * Tüm videoları seç/temizle kontrolü.
     */
    handleSelectAll(checked) {
        const checkboxes = document.querySelectorAll('.video-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
            const videoId = checkbox.value;
            
            if (checked) {
                this.selectedVideos.add(videoId);
            } else {
                this.selectedVideos.delete(videoId);
            }
        });
        
        this.updateBulkActions();
    }
    
    /**
     * Üstteki "tümünü seç" kutucuğunun indeterminate/checked durumunu günceller.
     */
    updateSelectAllCheckbox() {
        const selectAllCheckbox = document.getElementById('selectAllVideos');
        const checkboxes = document.querySelectorAll('.video-checkbox');
        
        if (!selectAllCheckbox || checkboxes.length === 0) return;
        
        const checkedCount = document.querySelectorAll('.video-checkbox:checked').length;
        
        selectAllCheckbox.checked = checkedCount === checkboxes.length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
    }
    
    /**
     * Seçili video sayısına göre toplu işlem panelinin görünürlüğünü yönetir.
     */
    updateBulkActions() {
        const bulkActions = document.getElementById('bulkActions');
        const selectedCount = document.getElementById('selectedCount');
        
        if (!bulkActions) return;
        
        if (this.selectedVideos.size > 0) {
            bulkActions.style.display = 'flex';
            if (selectedCount) {
                selectedCount.textContent = this.selectedVideos.size;
            }
        } else {
            bulkActions.style.display = 'none';
        }
    }
    
    /**
     * Toplu işlem formunu gönderir (ör. approve/reject/delete).
     * İşlem sonrası liste ve dashboard'ı yeniler.
     */
    async handleBulkOperation(e) {
        e.preventDefault();
        
        if (this.selectedVideos.size === 0) {
            this.showNotification('Lütfen en az bir video seçin', 'warning');
            return;
        }
        
        const operation = e.submitter.value;
        const videoIds = Array.from(this.selectedVideos);
        
        if (!confirm(`Seçili ${videoIds.length} video için ${operation} işlemini gerçekleştirmek istediğinizden emin misiniz?`)) {
            return;
        }
        
        try {
            this.showLoading('bulkOperations');
            
            const response = await fetch('teacher_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'bulk_operation',
                    operation: operation,
                    video_ids: videoIds
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(data.message, 'success');
                this.selectedVideos.clear();
                this.loadVideos();
                this.loadDashboard();
            } else {
                this.showNotification('İşlem hatası: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Bulk operation error:', error);
            this.showNotification('İşlem sırasında hata oluştu', 'error');
        } finally {
            this.hideLoading('bulkOperations');
        }
    }
    
    // ==================== ANALYTICS ====================
    
    /**
     * Analitik verileri (en çok izlenen, sınıf dağılımı, yükleme trendi) yükler.
     */
    async loadAnalytics() {
        try {
            this.showLoading('analyticsContent');
            
            const response = await fetch('teacher_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_analytics&days=30'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.renderAnalytics(data.analytics);
            } else {
                this.showNotification('Analytics yüklenemedi: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Analytics loading error:', error);
            this.showNotification('Analytics yüklenirken hata oluştu', 'error');
        } finally {
            this.hideLoading('analyticsContent');
        }
    }
    
    /**
     * Analitik bölümü alt bileşenlerini render eder.
     */
    renderAnalytics(analytics) {
        // Most viewed videos
        this.renderMostViewedVideos(analytics.most_viewed);
        
        // Grade distribution chart
        this.renderGradeDistributionChart(analytics.grade_distribution);
        
        // Upload trends chart
        this.renderUploadTrendsChart(analytics.upload_trends);
    }
    
    /**
     * En çok izlenen video listesini render eder.
     */
    renderMostViewedVideos(videos) {
        const container = document.getElementById('mostViewedVideos');
        if (!container) return;
        
        container.innerHTML = videos.map((video, index) => `
            <div class="analytics-item">
                <div class="analytics-rank">#${index + 1}</div>
                <div class="analytics-info">
                    <strong>${this.escapeHtml(video.title)}</strong>
                    <small>${video.grade}. Sınıf</small>
                </div>
                <div class="analytics-stats">
                    <span class="stat-item">
                        <i class="fas fa-eye"></i> ${video.view_count}
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-percentage"></i> ${Math.round(video.completion_rate)}%
                    </span>
                    <span class="stat-item">
                        <i class="fas fa-heart"></i> ${video.like_count}
                    </span>
                </div>
            </div>
        `).join('');
    }
    
    // ==================== UNITS API ====================
    
    /**
     * Ünite seçim listesini sınıf (grade) değişimine göre dinamik doldurur.
     * API: `units_api.php?grade=...&subject=math`
     */
    async loadUnits() {
        const gradeSelect = document.getElementById('grade');
        const unitSelect = document.getElementById('unit_id');
        
        if (!gradeSelect || !unitSelect) return;
        
        gradeSelect.addEventListener('change', async () => {
            const grade = gradeSelect.value;
            
            if (!grade) {
                unitSelect.innerHTML = '<option value="">Önce sınıf seçin</option>';
                return;
            }
            
            try {
                unitSelect.innerHTML = '<option value="">Yükleniyor...</option>';
                
                const response = await fetch(`units_api.php?grade=${grade}&subject=math`);
                const payload = await response.json();
                // API returns { subject, grade, units: [...] }
                const units = Array.isArray(payload) ? payload : (Array.isArray(payload.units) ? payload.units : []);
                
                unitSelect.innerHTML = '<option value="">Ünite seçin</option>' +
                    units.map(unit => `<option value="${unit.id}">${unit.name || unit.unit_name || unit.unit || ''}</option>`).join('');
                
            } catch (error) {
                console.error('Units loading error:', error);
                unitSelect.innerHTML = '<option value="">Hata oluştu</option>';
            }
        });
    }
    
    // ==================== FILTERS ====================
    
    /**
     * Filtre inputlarından değerleri okuyup video listesini yeniler.
     */
    applyFilters() {
        this.currentFilters = {};
        
        // Get filter values
        const statusFilter = document.getElementById('statusFilter');
        const gradeFilter = document.getElementById('gradeFilter');
        const dateFromFilter = document.getElementById('dateFromFilter');
        const dateToFilter = document.getElementById('dateToFilter');
        
        if (statusFilter && statusFilter.value) {
            this.currentFilters.status = statusFilter.value;
        }
        
        if (gradeFilter && gradeFilter.value) {
            this.currentFilters.grade = gradeFilter.value;
        }
        
        if (dateFromFilter && dateFromFilter.value) {
            this.currentFilters.date_from = dateFromFilter.value;
        }
        
        if (dateToFilter && dateToFilter.value) {
            this.currentFilters.date_to = dateToFilter.value;
        }
        
        this.currentPage = 1;
        this.loadVideos();
    }
    
    /**
     * Tüm filtre inputlarını temizler ve listeyi yeniler.
     */
    clearFilters() {
        this.currentFilters = {};
        
        // Reset filter inputs
        document.querySelectorAll('.filter-input').forEach(input => {
            input.value = '';
        });
        
        this.loadVideos();
    }
    
    // ==================== NOTIFICATIONS ====================
    
    /**
     * Bildirim sistemini başlatır ve 30 sn'de bir yeniler.
     */
    setupNotifications() {
        this.loadNotifications();
        
        // Auto-refresh notifications every 30 seconds
        setInterval(() => {
            this.loadNotifications();
        }, 30000);
    }
    
    /**
     * Bildirimleri backend'den çeker ve rozeti/görüntüyü günceller.
     */
    async loadNotifications() {
        try {
            const response = await fetch('teacher_panel.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=get_notifications'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.updateNotificationBadge(data.notifications.length);
                this.renderNotificationsList(data.notifications);
            }
        } catch (error) {
            console.error('Notifications loading error:', error);
        }
    }
    
    /**
     * Bildirim rozeti sayısını günceller (0 ise gizler).
     */
    updateNotificationBadge(count) {
        const badge = document.querySelector('.nav-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
    }
    
    /**
     * Bildirim listesini render eder (opsiyonel container varsa).
     */
    renderNotificationsList(notifications) {
        // Optional container in some templates
        const container = document.getElementById('notificationsList');
        if (!container) return; // Graceful no-op if not present
        
        if (!Array.isArray(notifications) || notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>Bildirim yok</h3>
                    <p>Yeni bir bildirim olduğunda burada göreceksiniz.</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = notifications.map(n => `
            <div class="notification-item ${this.escapeHtml(n.type || '')}">
                <div class="notification-icon"><i class="fas fa-${this.getNotificationIcon(n.type || 'info')}"></i></div>
                <div class="notification-text">
                    <div class="title">${this.escapeHtml(n.title || 'Bildirim')}</div>
                    <div class="message">${this.escapeHtml(n.message || '')}</div>
                    ${n.created_at ? `<small class="time">${this.escapeHtml(n.created_at)}</small>` : ''}
                </div>
            </div>
        `).join('');
    }
    
    // ==================== THEME TOGGLE ====================
    
    /**
     * Single Theme - No Toggle Needed
     */
    setupThemeToggle() {
        // Single theme - no toggle needed
        document.documentElement.setAttribute('data-theme', 'light');
    }
    
    // ==================== UTILITY FUNCTIONS ====================
    
    /**
     * Hedef alan için yükleniyor göstergesi basar.
     */
    showLoading(elementId) {
        const element = document.getElementById(elementId);
        if (element) {
            element.innerHTML = '<div class="loading"><div class="spinner"></div> Yükleniyor...</div>';
        }
    }
    
    hideLoading(elementId) {
        // Loading will be replaced by actual content
    }
    
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type} show`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
    }
    
    getNotificationIcon(type) {
        const icons = {
            success: 'check-circle',
            error: 'exclamation-circle',
            warning: 'exclamation-triangle',
            info: 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    getStatusIcon(status) {
        const icons = {
            pending: 'clock',
            approved: 'check',
            rejected: 'times'
        };
        return icons[status] || 'question';
    }
    
    getStatusText(status) {
        const texts = {
            pending: 'Bekliyor',
            approved: 'Onaylandı',
            rejected: 'Reddedildi'
        };
        return texts[status] || status;
    }
    
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }
    
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // ==================== MODAL FUNCTIONS ====================
    
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    // ==================== VIDEO ACTIONS ====================
    
    // Prefer YouTube embed if available, otherwise show local info or fallback
    async viewVideo(videoId) {
        // Redirect to dedicated video view page
        if (!videoId) {
            this.showNotification('Geçersiz video.', 'error');
            return;
        }
        window.location.href = `video_view.php?id=${encodeURIComponent(videoId)}`;
    }

    // Convert common YouTube links to embed URL
    getYouTubeEmbedUrl(url) {
        try {
            const u = new URL(url);
            // youtu.be/<id>
            if (u.hostname.includes('youtu.be')) {
                const id = u.pathname.replace('/', '');
                return `https://www.youtube.com/embed/${id}`;
            }
            // youtube.com/watch?v=<id>
            if (u.searchParams.get('v')) {
                return `https://www.youtube.com/embed/${u.searchParams.get('v')}`;
            }
            // Already an embed or other path
            if (u.pathname.startsWith('/embed/')) return url;
        } catch (e) { /* ignore */ }
        return url; // Fallback
    }
    
    async requestEdit(videoId) {
        // Backend expects a GET to render the form; pass video_id and type
        if (!videoId) {
            this.showNotification('Geçersiz video.', 'error');
            return;
        }
        window.location.href = `video_edit_request.php?video_id=${encodeURIComponent(videoId)}&type=edit`;
    }
    
    async requestDelete(videoId) {
        // Navigate to delete request form with proper params
        if (!videoId) {
            this.showNotification('Geçersiz video.', 'error');
            return;
        }
        if (confirm('Bu video için silme talebi göndermek istediğinizden emin misiniz?')) {
            window.location.href = `video_edit_request.php?video_id=${encodeURIComponent(videoId)}&type=delete`;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.teacherPanel = new TeacherPanel();
});

// Export for global access
window.TeacherPanel = TeacherPanel;
