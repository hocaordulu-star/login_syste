/**
 * =====================================================
 * MATEMATİK VİDEO SİSTEMİ - İNTERAKTİF JAVASCRIPT
 * Tüm kullanıcı etkileşimlerini yöneten ana script
 * =====================================================
 */

// Global değişkenler
let currentVideoId = null;
let progressUpdateTimer = null;

/**
 * Sayfa yüklendiğinde çalışacak fonksiyonlar
 */
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    initializeVideoTracking();
    initializeLazyLoading();
});

/**
 * Event listener'ları başlat
 *
 * Amaç:
 * - Modal kapatma davranışını yönetmek (arka plan tıklaması ve Escape tuşu)
 * - Uygulama genelinde tek noktadan klavye kısayollarını dinlemek
 *
 * Notlar:
 * - `.modal` arka planına tıklandığında ilgili modal `closeModal()` ile kapatılır.
 * - Escape tuşu aktif modal varsa kapatır.
 * - DOM üzerinde ilgili elemanlar yoksa no-op olacak şekilde güvenli yazılmıştır.
 */
function initializeEventListeners() {
    // Modal kapatma
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const activeModal = document.querySelector('.modal.active');
            if (activeModal) {
                closeModal(activeModal.id);
            }
        }
    });
}

/**
 * Video izleme takibi başlat
 *
 * - Sayfadaki tüm `.video-player iframe` elemanlarını tarar.
 * - Her bir iframe'in bağlı olduğu `data-video-id` bilgisini alır ve
 *   `trackVideoLoad(videoId)` ile örnek (mock) ilerleme takibini tetikler.
 * - Burada gerçek zamanlı oyuncu API'leri (YouTube Player API vb.)
 *   entegre edilerek gerçek süreler toplanabilir.
 */
function initializeVideoTracking() {
    const videoIframes = document.querySelectorAll('.video-player iframe');
    
    videoIframes.forEach(iframe => {
        const videoCard = iframe.closest('.video-card');
        if (videoCard) {
            const videoId = parseInt(videoCard.dataset.videoId);
            trackVideoLoad(videoId);
        }
    });
}

/**
 * Video yüklenme takibi
 *
 * - Şu an için DEMO amaçlı rastgele bir izleme süresi üretir ve
 *   `updateVideoProgress()` ile backend'e gönderir.
 * - Gerçek senaryoda oynatıcıdan alınan süreler kullanılmalıdır.
 */
function trackVideoLoad(videoId) {
    // Simulated progress tracking
    setTimeout(() => {
        updateVideoProgress(videoId, Math.floor(Math.random() * 300), 600);
    }, 2000);
}

/**
 * Video progress güncelle
 *
 * Backend:
 * - İstek `math.php` dosyasına POST ile gider.
 * - action=update_progress, video_id, watch_duration, total_duration alanları gönderilir.
 * - Başarılı olduğunda ekrandaki progress bar güncellenir.
 */
function updateVideoProgress(videoId, watchDuration, totalDuration) {
    fetch('math.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'update_progress',
            video_id: videoId,
            watch_duration: watchDuration,
            total_duration: totalDuration
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateProgressBar(videoId, {watch_duration: watchDuration, total_duration: totalDuration});
        }
    })
    .catch(error => console.error('Progress update error:', error));
}

/**
 * Progress bar güncelle
 *
 * - İlgili video kartında `.progress-indicator` yoksa oluşturur.
 * - Yüzdelik hesap: watch_duration / total_duration * 100
 * - Yüzde değeri %100'ü aşmaması için `Math.min(percentage, 100)` kullanılır.
 */
function updateProgressBar(videoId, progressData) {
    const videoCard = document.querySelector(`[data-video-id="${videoId}"]`);
    if (videoCard) {
        let progressIndicator = videoCard.querySelector('.progress-indicator');
        
        if (!progressIndicator) {
            progressIndicator = document.createElement('div');
            progressIndicator.className = 'progress-indicator';
            progressIndicator.innerHTML = '<div class="progress-bar"></div>';
            
            const videoPlayer = videoCard.querySelector('.video-player');
            videoPlayer.appendChild(progressIndicator);
        }
        
        const percentage = (progressData.watch_duration / progressData.total_duration) * 100;
        const progressBar = progressIndicator.querySelector('.progress-bar');
        progressBar.style.width = `${Math.min(percentage, 100)}%`;
        progressIndicator.title = `İlerleme: %${Math.round(percentage)}`;
    }
}

/**
 * Bookmark toggle fonksiyonu
 *
 * - Favori ekleme/çıkarma işlemini tetikler.
 * - Butonu işlemler süresince disable eder ve spinner gösterir.
 * - Backend yanıtına göre UI güncellenir, hata durumunda bildirim verilir.
 */
function toggleBookmark(videoId) {
    const bookmarkBtn = document.querySelector(`[data-video-id="${videoId}"] .bookmark-btn`);
    
    if (!bookmarkBtn) return;
    
    bookmarkBtn.disabled = true;
    bookmarkBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('math.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'toggle_bookmark',
            video_id: videoId,
            notes: ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateBookmarkButton(bookmarkBtn, data.action);
            showNotification(data.message, 'success');
        } else {
            showNotification(data.message || 'Bir hata oluştu', 'error');
        }
    })
    .catch(error => {
        console.error('Bookmark error:', error);
        showNotification('Bağlantı hatası', 'error');
    })
    .finally(() => {
        bookmarkBtn.disabled = false;
        updateBookmarkButton(bookmarkBtn, bookmarkBtn.classList.contains('bookmarked') ? 'added' : 'removed');
    });
}

/**
 * Bookmark butonunu güncelle
 *
 * - `action` değeri `added` ise dolu ikon ve çıkarma başlığı,
 *   aksi halde boş ikon ve ekleme başlığı gösterir.
 */
function updateBookmarkButton(button, action) {
    if (action === 'added') {
        button.classList.add('bookmarked');
        button.innerHTML = '<i class="fas fa-bookmark"></i>';
        button.title = 'Favorilerden çıkar';
    } else {
        button.classList.remove('bookmarked');
        button.innerHTML = '<i class="far fa-bookmark"></i>';
        button.title = 'Favorilere ekle';
    }
}

/**
 * Quiz modalını aç
 *
 * - Şu an mock quiz verisi ile modal içeriklerini yükler.
 * - Gerçekleştirilecek entegrasyonda backend'den quiz verisi çekilmelidir.
 */
function openQuiz(videoId) {
    currentVideoId = videoId;
    showLoading(true);
    
    // Mock quiz data
    const mockQuiz = {
        id: 1,
        title: 'Video Testi',
        description: 'Bu videoya ait kısa test',
        time_limit_minutes: 10,
        passing_score: 70,
        questions: [
            {
                id: 1,
                question_text: 'Bu videodaki ana konu nedir?',
                question_type: 'multiple_choice',
                option_a: 'Toplama işlemi',
                option_b: 'Çıkarma işlemi',
                option_c: 'Çarpma işlemi',
                option_d: 'Bölme işlemi'
            },
            {
                id: 2,
                question_text: 'Videodaki örnek doğru çözülmüş müdür?',
                question_type: 'true_false'
            }
        ]
    };
    
    setTimeout(() => {
        loadQuizContent(mockQuiz);
        showModal('quiz-modal');
        showLoading(false);
    }, 1000);
}

/**
 * Quiz içeriğini yükle
 *
 * - Verilen quizData ile dinamik form oluşturur.
 * - Form submit edildiğinde örnek sonuçlar ile `showQuizResults()` çağrılır.
 */
function loadQuizContent(quizData) {
    const quizContent = document.getElementById('quiz-content');
    
    let html = `
        <div class="quiz-header">
            <h4>${quizData.title}</h4>
            <p>${quizData.description}</p>
        </div>
        <form id="quiz-form" class="quiz-form">
    `;
    
    quizData.questions.forEach((question, index) => {
        html += `
            <div class="question-block">
                <h5>Soru ${index + 1}</h5>
                <p>${question.question_text}</p>
        `;
        
        if (question.question_type === 'multiple_choice') {
            html += `
                <div class="options">
                    <label><input type="radio" name="q${question.id}" value="A"> ${question.option_a}</label>
                    <label><input type="radio" name="q${question.id}" value="B"> ${question.option_b}</label>
                    <label><input type="radio" name="q${question.id}" value="C"> ${question.option_c}</label>
                    <label><input type="radio" name="q${question.id}" value="D"> ${question.option_d}</label>
                </div>
            `;
        } else if (question.question_type === 'true_false') {
            html += `
                <div class="options">
                    <label><input type="radio" name="q${question.id}" value="true"> Doğru</label>
                    <label><input type="radio" name="q${question.id}" value="false"> Yanlış</label>
                </div>
            `;
        }
        
        html += '</div>';
    });
    
    html += `
            <div class="quiz-actions">
                <button type="submit" class="quiz-submit-btn">Testi Bitir</button>
                <button type="button" onclick="closeModal('quiz-modal')" class="quiz-cancel-btn">İptal</button>
            </div>
        </form>
    `;
    
    quizContent.innerHTML = html;
    
    document.getElementById('quiz-form').addEventListener('submit', function(e) {
        e.preventDefault();
        showQuizResults({
            score: 85,
            max_score: 100,
            percentage: 85,
            is_passed: true,
            time_taken: 5
        });
    });
}

/**
 * Quiz sonuçlarını göster
 *
 * - Başarı/başarısız durumuna göre ikon ve renkler değişir.
 * - Kullanıcıya özet istatistikler sunulur.
 */
function showQuizResults(result) {
    const quizContent = document.getElementById('quiz-content');
    
    const passedClass = result.is_passed ? 'passed' : 'failed';
    const passedIcon = result.is_passed ? 'fa-check-circle' : 'fa-times-circle';
    const passedText = result.is_passed ? 'Tebrikler! Testi geçtiniz.' : 'Maalesef testi geçemediniz.';
    
    quizContent.innerHTML = `
        <div class="quiz-result ${passedClass}">
            <div class="result-icon">
                <i class="fas ${passedIcon}"></i>
            </div>
            <h3>${passedText}</h3>
            <div class="result-stats">
                <div class="stat-item">
                    <span>Puanınız: ${result.score}/${result.max_score}</span>
                </div>
                <div class="stat-item">
                    <span>Yüzde: %${result.percentage}</span>
                </div>
            </div>
            <button onclick="closeModal('quiz-modal')" class="primary-btn">Tamam</button>
        </div>
    `;
}

/**
 * Not alma modalını aç
 *
 * - Mock veri ile notlar modalini hazırlar.
 * - Gerçek kullanımda ilgili videoya ait notlar backend'den çekilebilir.
 */
function openNotes(videoId) {
    currentVideoId = videoId;
    showLoading(true);
    
    setTimeout(() => {
        loadNotesContent([]);
        showModal('notes-modal');
        showLoading(false);
    }, 500);
}

/**
 * Not alma içeriğini yükle
 *
 * - Basit bir form ve mevcut notlar listesini render eder.
 * - Submit'te şimdilik bildirim gösterip modal kapatır.
 */
function loadNotesContent(notes) {
    const notesContent = document.getElementById('notes-content');
    
    let html = `
        <div class="notes-header">
            <h4>Video Notları</h4>
        </div>
        
        <div class="add-note-section">
            <h5>Yeni Not Ekle</h5>
            <form id="add-note-form">
                <div class="form-group">
                    <label>Zaman (dakika:saniye):</label>
                    <input type="text" name="timestamp" placeholder="00:00" pattern="[0-9]{1,2}:[0-9]{2}">
                </div>
                <div class="form-group">
                    <label>Not:</label>
                    <textarea name="note_text" rows="3" placeholder="Notunuzu yazın..." required></textarea>
                </div>
                <button type="submit">Not Ekle</button>
            </form>
        </div>
        
        <div class="notes-list">
            <h5>Mevcut Notlar</h5>
            ${notes.length > 0 ? notes.map(note => `<div class="note-item">${note.note_text}</div>`).join('') : '<div class="empty-notes">Henüz not eklenmemiş.</div>'}
        </div>
    `;
    
    notesContent.innerHTML = html;
    
    document.getElementById('add-note-form').addEventListener('submit', function(e) {
        e.preventDefault();
        showNotification('Not başarıyla eklendi', 'success');
        closeModal('notes-modal');
    });
}

/**
 * Sekme göster/gizle
 *
 * - Tüm sekmeleri ve tab butonlarını pasif hale getirir, hedef sekmeyi aktif yapar.
 * - Not: `event.target` kullanımı global event'e dayanır; fonksiyon dışarıdan manuel
 *   çağrılırsa `event` tanımsız olabilir. Mevcut davranış korunmuştur.
 */
function showTab(tabName) {
    // Tüm sekmeleri gizle
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Tüm tab butonlarından active sınıfını kaldır
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // İlgili sekmeyi göster
    const targetContent = document.getElementById(tabName + '-content');
    if (targetContent) {
        targetContent.classList.add('active');
    }
    
    // İlgili tab butonunu aktif yap
    event.target.classList.add('active');
}

/**
 * Modal göster
 *
 * - Verilen `modalId` için `.active` sınıfını ekler ve body scroll'u kilitler.
 */
function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Modal kapat
 *
 * - Verilen `modalId` için `.active` sınıfını kaldırır ve body scroll'u açar.
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

/**
 * Loading göster/gizle
 *
 * - `#loading-spinner` öğesini `.active` sınıfıyla kontrol eder.
 */
function showLoading(show) {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        if (show) {
            spinner.classList.add('active');
        } else {
            spinner.classList.remove('active');
        }
    }
}

/**
 * Bildirim göster
 *
 * - Yoksa `#notification-container` oluşturur.
 * - Türüne göre (success, error, info) arka plan rengi belirler.
 * - Tıklayınca kapanır veya 5 sn sonra otomatik silinir.
 */
function showNotification(message, type = 'info') {
    let container = document.getElementById('notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notification-container';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(container);
    }
    
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.style.cssText = `
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
        color: white;
        padding: 16px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 300px;
        cursor: pointer;
        animation: slideIn 0.3s ease;
    `;
    
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    notification.innerHTML = `<i class="fas ${icon}"></i> ${message}`;
    
    container.appendChild(notification);
    
    notification.addEventListener('click', () => notification.remove());
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Lazy loading başlat
 *
 * - `IntersectionObserver` ile görünür olduğunda iframe'lerin `data-src` değerini `src`'ye taşır.
 * - Basit ve performans dostu bir yaklaşım; destek yoksa no-op.
 */
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const iframe = entry.target;
                    if (iframe.dataset.src) {
                        iframe.src = iframe.dataset.src;
                        iframe.removeAttribute('data-src');
                        observer.unobserve(iframe);
                    }
                }
            });
        });
        
        document.querySelectorAll('iframe[data-src]').forEach(iframe => {
            imageObserver.observe(iframe);
        });
    }
}

/**
 * Filtreleri temizle
 *
 * - URL parametrelerinden q, unit_id, topic, status değerlerini kaldırır ve sayfayı yeniler.
 */
function clearFilters() {
    const url = new URL(window.location);
    url.searchParams.delete('q');
    url.searchParams.delete('unit_id');
    url.searchParams.delete('topic');
    url.searchParams.delete('status');
    window.location.href = url.toString();
}

// CSS animasyonları
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .quiz-form .question-block {
        margin-bottom: 20px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    
    .quiz-form .options {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 12px;
    }
    
    .quiz-form .options label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }
    
    .quiz-form .options label:hover {
        background: #e5e7eb;
    }
    
    .quiz-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 24px;
    }
    
    .quiz-submit-btn, .quiz-cancel-btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .quiz-submit-btn {
        background: #667eea;
        color: white;
    }
    
    .quiz-cancel-btn {
        background: #6b7280;
        color: white;
    }
    
    .quiz-result {
        text-align: center;
        padding: 32px;
    }
    
    .quiz-result.passed .result-icon {
        color: #10b981;
        font-size: 4rem;
        margin-bottom: 16px;
    }
    
    .quiz-result.failed .result-icon {
        color: #ef4444;
        font-size: 4rem;
        margin-bottom: 16px;
    }
    
    .result-stats {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 20px 0;
        padding: 16px;
        background: #f8fafc;
        border-radius: 8px;
    }
    
    .add-note-section {
        margin-bottom: 24px;
        padding: 16px;
        background: #f8fafc;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 4px;
        font-weight: 500;
        color: #374151;
    }
    
    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-family: inherit;
    }
    
    .notes-list {
        max-height: 300px;
        overflow-y: auto;
    }
    
    .note-item {
        padding: 12px;
        margin-bottom: 8px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
    }
    
    .empty-notes {
        text-align: center;
        color: #6b7280;
        padding: 20px;
        font-style: italic;
    }
`;
document.head.appendChild(style);
