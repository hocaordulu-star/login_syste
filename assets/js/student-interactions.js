/**
 * ÖĞRENCİ PANELİ - TEMİZ VE MODERN JAVASCRIPT
 * Responsive, mobil uyumlu, koyu/açık tema destekli
 */

class StudentPanel {
    constructor() {
        this.currentSection = 'dashboard';
        this.init();
    }
    
    init() {
        this.setupSidebar();
        this.setupNavigation();
        this.setupSearch();
        this.setupThemeToggle();
        this.loadInitialData();
    }
    
    /**
     * Sidebar toggle functionality
     */
    setupSidebar() {
        const toggleBtn = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('studentSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (!toggleBtn || !sidebar || !overlay) return;
        
        toggleBtn.addEventListener('click', () => {
            this.toggleSidebar();
        });
        
        overlay.addEventListener('click', () => {
            this.closeSidebar();
        });
        
        // Close sidebar on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                this.closeSidebar();
            }
        });
    }
    
    toggleSidebar() {
        const sidebar = document.getElementById('studentSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        if (sidebar.classList.contains('is-open')) {
            this.closeSidebar();
        } else {
            this.openSidebar();
        }
    }
    
    openSidebar() {
        const sidebar = document.getElementById('studentSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        sidebar.classList.add('is-open');
        sidebar.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-show');
        overlay.hidden = false;
        document.body.classList.add('no-scroll');
        toggleBtn.setAttribute('aria-expanded', 'true');
    }
    
    closeSidebar() {
        const sidebar = document.getElementById('studentSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        sidebar.classList.remove('is-open');
        sidebar.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-show');
        overlay.hidden = true;
        document.body.classList.remove('no-scroll');
        toggleBtn.setAttribute('aria-expanded', 'false');
    }
    
    /**
     * Navigation between sections
     */
    setupNavigation() {
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                const section = link.dataset.section;
                
                // Only prevent default for links with data-section attribute
                if (section) {
                    e.preventDefault();
                    
                    // If user clicked the 'Videolar' item, redirect to math.php
                    if (section === 'videos') {
                        const gradeEl = document.getElementById('gradeSelect');
                        const grade = gradeEl && gradeEl.value ? String(gradeEl.value) : '';
                        const target = grade ? `math.php?grade=${encodeURIComponent(grade)}` : 'math.php';
                        window.location.href = target;
                        return;
                    }
                    
                    this.switchSection(section);
                    // Close sidebar on mobile after navigation
                    this.closeSidebar();
                }
                // If no data-section, let the link work normally (e.g., Anasayfa)
            });
        });
    }
    
    switchSection(section) {
        // Update navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        document.querySelector(`[data-section="${section}"]`)?.classList.add('active');
        
        // Hide all sections
        document.querySelectorAll('.content-section').forEach(sec => {
            sec.classList.remove('active');
        });
        
        // Show target section
        const targetSection = document.getElementById(`${section}Section`);
        if (targetSection) {
            targetSection.classList.add('active');
        }
        
        // Update page title
        const pageTitle = document.getElementById('pageTitle');
        if (pageTitle) {
            const titles = {
                dashboard: 'Dashboard',
                videos: 'Videolar',
                assignments: 'Ödevlerim',
                progress: 'İlerleme',
                bookmarks: 'Favorilerim'
            };
            pageTitle.textContent = titles[section] || 'Dashboard';
        }
        
        this.currentSection = section;
        this.loadSectionData(section);
    }
    
    /**
     * Load data for specific section
     */
    async loadSectionData(section) {
        switch (section) {
            case 'dashboard':
                await this.loadDashboardData();
                break;
            case 'videos':
                await this.loadVideos();
                break;
            case 'assignments':
                await this.loadAssignments();
                break;
            case 'progress':
                await this.loadProgress();
                break;
            case 'bookmarks':
                await this.loadBookmarks();
                break;
        }
    }
    
    /**
     * Search functionality
     */
    setupSearch() {
        const searchInput = document.getElementById('globalSearch');
        if (!searchInput) return;
        
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performSearch(e.target.value);
            }, 300);
        });
    }
    
    performSearch(query) {
        if (!query.trim()) return;
        
        console.log('Searching for:', query);
        // Implement search logic based on current section
        switch (this.currentSection) {
            case 'videos':
                this.searchVideos(query);
                break;
            case 'assignments':
                this.searchAssignments(query);
                break;
            case 'bookmarks':
                this.searchBookmarks(query);
                break;
        }
    }
    
    /**
     * Single Theme - No Toggle Needed
     */
    setupThemeToggle() {
        // Single theme - no toggle needed
        document.documentElement.setAttribute('data-theme', 'light');
    }
    
    /**
     * Load initial data
     */
    async loadInitialData() {
        await this.loadDashboardData();
    }
    
    /**
     * Load dashboard data
     */
    async loadDashboardData() {
        try {
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Update stats (mock data for now)
            this.updateStats({
                watchedVideos: 12,
                completedVideos: 8,
                bookmarkedVideos: 5,
                totalWatchTime: 1800 // seconds
            });
            
            // Load recent activity
            this.loadRecentActivity();
            
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }
    
    updateStats(stats) {
        // Update stat numbers
        const statNumbers = document.querySelectorAll('.stat-number');
        if (statNumbers.length >= 4) {
            statNumbers[0].textContent = stats.watchedVideos || 0;
            statNumbers[1].textContent = stats.completedVideos || 0;
            statNumbers[2].textContent = stats.bookmarkedVideos || 0;
            statNumbers[3].textContent = this.formatTime(stats.totalWatchTime || 0);
        }
    }
    
    formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        
        if (hours > 0) {
            return `${hours}s ${minutes}dk`;
        }
        return `${minutes}dk`;
    }
    
    loadRecentActivity() {
        // Mock recent activity data
        const activities = [
            {
                icon: 'fas fa-video',
                text: 'Matematik videosu izlendi',
                time: '2 saat önce'
            },
            {
                icon: 'fas fa-bookmark',
                text: 'Video favorilere eklendi',
                time: '1 gün önce'
            },
            {
                icon: 'fas fa-check-circle',
                text: 'Quiz tamamlandı',
                time: '2 gün önce'
            }
        ];
        
        const activityList = document.querySelector('.activity-list');
        if (activityList) {
            activityList.innerHTML = activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="${activity.icon}"></i>
                    </div>
                    <div class="activity-content">
                        <p>${activity.text}</p>
                        <span class="activity-time">${activity.time}</span>
                    </div>
                </div>
            `).join('');
        }
    }
    
    /**
     * Load videos
     */
    async loadVideos() {
        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) return;
        
        // Show loading state
        videoGrid.innerHTML = `
            <div class="loading-state">
                <div class="spinner"></div>
                <p>Videolar yükleniyor...</p>
            </div>
        `;
        
        try {
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 1000));
            
            // Mock video data
            const videos = [
                {
                    id: 1,
                    title: 'Kesirler ve Ondalık Sayılar',
                    thumbnail: 'https://via.placeholder.com/320x180',
                    duration: '15:30',
                    progress: 75,
                    isBookmarked: false
                },
                {
                    id: 2,
                    title: 'Geometrik Şekiller',
                    thumbnail: 'https://via.placeholder.com/320x180',
                    duration: '12:45',
                    progress: 0,
                    isBookmarked: true
                },
                {
                    id: 3,
                    title: 'Cebirsel İfadeler',
                    thumbnail: 'https://via.placeholder.com/320x180',
                    duration: '18:20',
                    progress: 100,
                    isBookmarked: false
                }
            ];
            
            this.renderVideos(videos);
            
        } catch (error) {
            console.error('Error loading videos:', error);
            videoGrid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Hata oluştu</h3>
                    <p>Videolar yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin.</p>
                </div>
            `;
        }
    }
    
    renderVideos(videos) {
        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) return;
        
        if (videos.length === 0) {
            videoGrid.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-video"></i>
                    <h3>Henüz video yok</h3>
                    <p>Bu sınıf için henüz onaylanmış video bulunmuyor.</p>
                </div>
            `;
            return;
        }
        
        videoGrid.innerHTML = videos.map(video => `
            <div class="video-card" data-video-id="${video.id}">
                <div class="video-thumbnail">
                    <img src="${video.thumbnail}" alt="${video.title}">
                    <div class="video-play-button" onclick="studentPanel.playVideo(${video.id})">
                        <i class="fas fa-play"></i>
                    </div>
                    ${video.progress > 0 ? `
                        <div class="video-progress-bar">
                            <div class="video-progress-fill" style="width: ${video.progress}%"></div>
                        </div>
                    ` : ''}
                </div>
                <div class="video-card-content">
                        <h3 class="video-title">${video.title}</h3>
                    <div class="video-meta">
                        <span><i class="fas fa-clock"></i> ${video.duration}</span>
                        <span><i class="fas fa-graduation-cap"></i> 5. Sınıf</span>
                    </div>
                    <div class="video-actions">
                        <button class="btn btn-primary" onclick="studentPanel.playVideo(${video.id})">
                            <i class="fas fa-play"></i>
                            ${video.progress > 0 ? 'Devam Et' : 'İzle'}
                        </button>
                        <button class="btn btn-secondary" onclick="studentPanel.toggleBookmark(${video.id})">
                            <i class="fas fa-bookmark ${video.isBookmarked ? 'bookmarked' : ''}"></i>
                            </button>
                    </div>
                    ${video.progress > 0 ? `
                        <div class="mt-2">
                            <small class="text-muted">
                                ${video.progress === 100 ? 'Tamamlandı' : `%${video.progress} tamamlandı`}
                            </small>
                        </div>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }
    
    /**
     * Play video
     */
    playVideo(videoId) {
        // Instead of opening the single video page, redirect to math.php
        // Use the currently selected grade if available
        const gradeEl = document.getElementById('gradeSelect');
        const grade = gradeEl && gradeEl.value ? String(gradeEl.value) : '';
        const target = grade ? `math.php?grade=${encodeURIComponent(grade)}` : 'math.php';
        window.location.href = target;
    }
    
    /**
     * Toggle bookmark
     */
    async toggleBookmark(videoId) {
        try {
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 300));
            
            // Toggle bookmark state
            const bookmarkBtns = document.querySelectorAll(`[data-video-id="${videoId}"] .btn-secondary`);
                bookmarkBtns.forEach(btn => {
                const icon = btn.querySelector('i');
                if (icon.classList.contains('bookmarked')) {
                    icon.classList.remove('bookmarked');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    } else {
                    icon.classList.add('bookmarked');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    }
                });
                
            this.showNotification('Favori durumu güncellendi', 'success');
            
        } catch (error) {
            console.error('Error toggling bookmark:', error);
            this.showNotification('Favori durumu güncellenirken hata oluştu', 'error');
        }
    }
    
    /**
     * Load assignments
     */
    async loadAssignments() {
        console.log('Loading assignments...');
        // Implementation for assignments
    }
    
    /**
     * Load progress
     */
    async loadProgress() {
        console.log('Loading progress...');
        // Implementation for progress
    }
    
    /**
     * Load bookmarks
     */
    async loadBookmarks() {
        console.log('Loading bookmarks...');
        // Implementation for bookmarks
    }
    
    /**
     * Search functions
     */
    searchVideos(query) {
        console.log('Searching videos:', query);
    }
    
    searchAssignments(query) {
        console.log('Searching assignments:', query);
    }
    
    searchBookmarks(query) {
        console.log('Searching bookmarks:', query);
    }
    
    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                    <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
            </div>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Hide notification
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
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
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.studentPanel = new StudentPanel();
});

// Add notification styles
const notificationStyles = `
.notification {
    position: fixed;
    top: 100px;
    right: 20px;
    background: var(--panel-bg, #ffffff);
    border-radius: var(--radius-lg, 1rem);
    padding: var(--space-4, 1rem);
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    border: 1px solid var(--border, #e5e7eb);
    max-width: 400px;
    z-index: 10000;
    transform: translateX(100%);
    transition: transform var(--dur, 250ms) ease;
}

.notification.show {
    transform: translateX(0);
}

.notification-content {
    display: flex;
    align-items: center;
    gap: var(--space-3, 0.75rem);
}

.notification-success {
    border-left: 4px solid #10b981;
}

.notification-error {
    border-left: 4px solid #ef4444;
}

.notification-warning {
    border-left: 4px solid #f59e0b;
}

.notification-info {
    border-left: 4px solid #3b82f6;
}
`;

// Inject notification styles
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);