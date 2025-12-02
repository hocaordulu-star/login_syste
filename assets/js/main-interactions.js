// Main Menu Interactions - Modern JavaScript
/**
 * Proje: Ana Menü Etkileşimleri (Front-End)
 * Açıklama (TR):
 *  - Bu dosya ana sayfadaki etkileşimlerin tamamını yönetir.
 *  - İçerik başlıkları: menü başlatma, ders kartları, modal sistemi,
 *    tema değiştirme, bildirimler ve kaydırma animasyonları.
 *  - Kod modern ES6+ standartları ile yazılmıştır ve global erişim gereken
 *    fonksiyonlar window üzerinden dışa aktarılmıştır.
 *
 * Geliştirici Notları:
 *  - Her bir ana fonksiyonun üstünde, amacı ve önemli kullanım notları
 *    açıklanmıştır (JSDoc formatında kısa TR açıklamalar).
 *  - İş mantığına dokunulmamış, sadece yorumlar eklenmiştir.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Initialize main menu functionality
    initializeMainMenu();
    initializeModals();
    initializeAnimations();
    // Dark-only: theme toggle disabled
    initializeThemeToggle();
});

// Main Menu Initialization
/**
 * initializeMainMenu()
 * Ana sayfa genel etkileşimlerini başlatır:
 *  - Anchor linkler için smooth scroll
 *  - Butonlar için kısa "loading" durumu
 *  - Ders kartlarının ilk yüklemesini yapar
 */
function initializeMainMenu() {
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Add loading states for buttons
    document.querySelectorAll('.btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (!this.classList.contains('loading')) {
                this.classList.add('loading');
                setTimeout(() => {
                    this.classList.remove('loading');
                }, 1000);
            }
        });
    });

    // Initialize subject cards
    initializeSubjectCards();
}

// Subject Cards Functionality
/**
 * initializeSubjectCards()
 * Ders kartlarına hover ve tıklama davranışlarını ekler.
 *  - coming-soon sınıfına sahip kartlar tıklandığında "yakında" modali açılır
 *  - Matematik kartında sınıf seçimi modalı gösterilir
 */
function initializeSubjectCards() {
    const subjectCards = document.querySelectorAll('.subject-card');
    
    subjectCards.forEach(card => {
        // Add hover effects
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
        });
        
        card.addEventListener('mouseleave', function() {
            if (!this.classList.contains('coming-soon')) {
                this.style.transform = 'translateY(0)';
            }
        });

        // Handle clicks
        card.addEventListener('click', function() {
            const subject = this.dataset.subject;
            
            if (this.classList.contains('coming-soon')) {
                showComingSoonModal(subject);
                return;
            }
            
            switch(subject) {
                case 'math':
                    showMathModal();
                    break;
                case 'turkish':
                    showComingSoonModal('Türkçe');
                    break;
                case 'science':
                    showComingSoonModal('Fen Bilimleri');
                    break;
                case 'social':
                    showComingSoonModal('Sosyal Bilgiler');
                    break;
                case 'english':
                    showComingSoonModal('İngilizce');
                    break;
                case 'art':
                    showComingSoonModal('Resim');
                    break;
                default:
                    console.log('Unknown subject:', subject);
            }
        });
    });
}

// Modal System
/**
 * initializeModals()
 * Global modal kapatma davranışlarını kurar:
 *  - Modal dışına tıklandığında kapanır
 *  - Escape tuşu ile kapanır
 *  - .modal-close butonları modalı kapatır
 */
function initializeModals() {
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });

    // Close modal with escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal);
            }
        }
    });

    // Initialize close buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });
}

// Show Math Modal with Grade Selection
/**
 * showMathModal()
 * Matematik için sınıf seçimi modalını gösterir.
 * Modal yoksa önce dinamik olarak oluşturur.
 */
function showMathModal() {
    const modal = document.getElementById('mathModal');
    if (!modal) {
        createMathModal();
        return;
    }
    showModal(modal);
}

// Create Math Modal Dynamically
/**
 * createMathModal()
 * Matematik sınıf seçimi modalını DOM'a enjekte eder ve olayları bağlar.
 */
function createMathModal() {
    const modalHTML = `
        <div id="mathModal" class="modal">
            <div class="modal-content">
                <button class="modal-close" onclick="closeModal(document.getElementById('mathModal'))">&times;</button>
                <div class="modal-title">
                    <i class="fas fa-calculator" style="color: var(--primary); margin-right: 10px;"></i>
                    Matematik Dersi - Sınıf Seçin
                </div>
                <div class="grade-buttons">
                    <button class="grade-btn" onclick="goToMath(5)">
                        <i class="fas fa-star"></i> 5. Sınıf
                    </button>
                    <button class="grade-btn" onclick="goToMath(6)">
                        <i class="fas fa-star"></i> 6. Sınıf
                    </button>
                    <button class="grade-btn" onclick="goToMath(7)">
                        <i class="fas fa-star"></i> 7. Sınıf
                    </button>
                    <button class="grade-btn" onclick="goToMath(8)">
                        <i class="fas fa-star"></i> 8. Sınıf
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = document.getElementById('mathModal');
    
    // Add event listeners
    modal.querySelector('.modal-close').addEventListener('click', function() {
        closeModal(modal);
    });
    
    showModal(modal);
}

// Navigate to Math Page with Grade
/**
 * goToMath(grade)
 * Parametreler:
 *  - grade: 5/6/7/8 gibi sınıf seviyesi
 * Davranış:
 *  - Butonda kısa süreli "yükleniyor" durumu gösterir
 *  - 500ms gecikme sonrası math.php?grade=... adresine yönlendirir
 */
function goToMath(grade) {
    // Show loading state
    const button = event.target;
    const originalContent = button.innerHTML;
    button.innerHTML = '<div class="spinner"></div> Yükleniyor...';
    button.disabled = true;
    
    // Add small delay for better UX
    setTimeout(() => {
        window.location.href = `math.php?grade=${grade}`;
    }, 500);
}

// Show Coming Soon Modal
/**
 * showComingSoonModal(subject)
 * Verilen ders adı için (örn. Türkçe) "Yakında" bilgilendirme modalını günceller ve açar.
 */
function showComingSoonModal(subject) {
    const modal = document.getElementById('comingSoonModal');
    if (!modal) {
        createComingSoonModal(subject);
        return;
    }
    
    // Update modal content
    modal.querySelector('.modal-title').innerHTML = `
        <i class="fas fa-clock" style="color: var(--warning); margin-right: 10px;"></i>
        ${subject} - Yakında Geliyor!
    `;
    
    showModal(modal);
}

// Create Coming Soon Modal
/**
 * createComingSoonModal(subject)
 * "Yakında" modalını oluşturur ve DOM'a ekler. Bildirim alma butonunu
 * subscribeToNotifications(subject) ile bağlar.
 */
function createComingSoonModal(subject) {
    const modalHTML = `
        <div id="comingSoonModal" class="modal">
            <div class="modal-content">
                <button class="modal-close">&times;</button>
                <div class="modal-title">
                    <i class="fas fa-clock" style="color: var(--warning); margin-right: 10px;"></i>
                    ${subject} - Yakında Geliyor!
                </div>
                <div style="text-align: center; padding: 20px 0;">
                    <div style="font-size: 4rem; color: var(--warning); margin-bottom: 20px;">
                        <i class="fas fa-tools"></i>
                    </div>
                    <p style="font-size: 1.1rem; color: var(--text-light); margin-bottom: 20px;">
                        ${subject} dersi şu anda geliştirilme aşamasında. 
                        Çok yakında sizlerle buluşacak!
                    </p>
                    <div style="background: var(--surface-2); padding: 15px; border-radius: var(--radius-lg); margin: 20px 0;">
                        <h4 style="color: var(--text); margin-bottom: 10px;">
                            <i class="fas fa-bell"></i> Bildirim Almak İster misiniz?
                        </h4>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">
                            Bu ders hazır olduğunda size haber verebiliriz.
                        </p>
                        <button class="btn btn-secondary" style="margin-top: 10px;" onclick="subscribeToNotifications('${subject}')">
                            <i class="fas fa-bell"></i> Bildirim Al
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = document.getElementById('comingSoonModal');
    
    // Add event listeners
    modal.querySelector('.modal-close').addEventListener('click', function() {
        closeModal(modal);
    });
    
    showModal(modal);
}

// Subscribe to Notifications
/**
 * subscribeToNotifications(subject)
 * Demo amaçlı sahte bir API çağrısı simüle eder.
 * Başarılı durumda kullanıcıya bildirim gösterir ve modalı kapatır.
 */
function subscribeToNotifications(subject) {
    const button = event.target;
    const originalContent = button.innerHTML;
    
    button.innerHTML = '<div class="spinner"></div> Kaydediliyor...';
    button.disabled = true;
    
    // Simulate API call
    setTimeout(() => {
        button.innerHTML = '<i class="fas fa-check"></i> Kaydedildi!';
        button.style.background = 'var(--accent)';
        
        // Show success message
        showNotification(`${subject} dersi için bildirim kaydınız alındı!`, 'success');
        
        setTimeout(() => {
            closeModal(document.getElementById('comingSoonModal'));
        }, 1500);
    }, 1000);
}

// Modal Utilities
/**
 * showModal(modal)
 * Verilen modal elementini görünür yapar ve body scroll'unu engeller.
 */
function showModal(modal) {
    modal.style.display = 'flex';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

/**
 * closeModal(modal)
 * Modalı animasyonla kapatır ve body scroll'unu eski haline getirir.
 */
function closeModal(modal) {
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 300);
}

// Contact form was decommissioned. Any legacy initializer and submission logic
// were removed to avoid calling disabled endpoints.

// Notification System
/**
 * showNotification(message, type = 'info')
 * Sağ üstte otomatik kaybolan küçük bir bildirim gösterir.
 * type: 'success' | 'error' | 'info'
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? 'var(--accent)' : type === 'error' ? 'var(--danger)' : 'var(--info)'};
        color: white;
        padding: 15px 20px;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-lg);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 400px;
        animation: slideInRight 0.3s ease;
    `;
    
    const icon = type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle';
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; margin-left: 10px;">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }
    }, 5000);
}

// Animation System
/**
 * initializeAnimations()
 * IntersectionObserver ile sayfa içi öğelere girişte animasyon uygular.
 *  - .subject-card, .live-content, .contact-container ögeleri gözlemlenir
 *  - subject-card için gecikmeli (staggered) animasyon ayarlanır
 */
function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.subject-card, .live-content, .contact-container').forEach(el => {
        observer.observe(el);
    });
    
    // Add staggered animation for subject cards
    document.querySelectorAll('.subject-card').forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
    });
}

// Single Theme - No Toggle Needed
/**
 * initializeThemeToggle()
 * Tek tema kullanıldığı için tema değiştirme özelliği kaldırıldı.
 */
function initializeThemeToggle() {
    // Single theme - no toggle needed
    document.documentElement.setAttribute('data-theme', 'light');
}

/**
 * createThemeToggle()
 * Sabit bir konumda (sağ alt) tema değiştirici butonu oluşturur ve click
 * olayını toggleTheme() ile bağlar.
 */
function createThemeToggle() { /* no-op in dark-only mode */ }

/**
 * toggleTheme()
 * 'dark' ve 'light' temaları arasında geçiş yapar, tercihi localStorage'a yazar
 * ve kullanıcıya bilgi bildirimi gösterir.
 */
function toggleTheme() { /* no-op in dark-only mode */ }

/**
 * updateThemeToggle(theme)
 * Tema butonu ikon ve başlığını aktif temaya göre günceller.
 */
function updateThemeToggle(theme) { /* no-op in dark-only mode */ }

// Utility Functions
/**
 * debounce(func, wait)
 * Bir fonksiyonun çağrılmasını, ardışık tetiklemelerde en son çağrıdan
 * 'wait' ms sonra olacak şekilde erteleyen yardımcı fonksiyon.
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * throttle(func, limit)
 * Bir fonksiyonun en fazla her 'limit' ms'de bir çalışmasına izin veren
 * yardımcı fonksiyon. Scroll/resize gibi olaylar için uygundur.
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .form-input.error,
    .form-select.error,
    .form-textarea.error {
        border-color: var(--danger);
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }
    
    .theme-toggle:hover {
        transform: scale(1.1);
        box-shadow: var(--shadow-xl);
    }
`;
document.head.appendChild(style);

// Export functions for global access
window.goToMath = goToMath;
window.showMathModal = showMathModal;
window.showComingSoonModal = showComingSoonModal;
window.closeModal = closeModal;
window.subscribeToNotifications = subscribeToNotifications;
window.toggleTheme = toggleTheme;

