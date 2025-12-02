/**
 * Admin Panel Interactive Features
 * Modern JavaScript for admin functionality
 */
/**
 * Dosya: assets/js/admin-interactions.js
 * Amaç (TR):
 *  - Admin panelindeki etkileşimleri yönetir (tema, arama, kullanıcı/video işlemleri,
 *    toplu işlemler, grafikler, gerçek zamanlı güncellemeler, bildirimler).
 * Notlar:
 *  - Yalnızca açıklayıcı yorumlar eklendi. İş mantığı değiştirilmedi.
 *  - Fonksiyonların üstünde kısa JSDoc açıklamaları bulunur.
 */

// Global variables
let currentSection = 'dashboard';
let selectedUsers = new Set();
let selectedVideos = new Set();
let dashboardCharts = {};
let refreshInterval = null;

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

/**
 * Initialize admin panel functionality
 */
/**
 * initializeAdmin()
 * Sayfada tanımlı section'a göre (body[data-section]) admin arayüzünü başlatır.
 *  - Tema, arama, toplu işlemler, grafikler ve real-time güncellemeleri kurar
 *  - Yükleme spinner'ını gizler
 */
function initializeAdmin() {
    currentSection = document.body.dataset.section || 'dashboard';
    
    // Initialize components
    initializeThemeToggle();
    initializeSearch();
    initializeBulkOperations();
    initializeCharts();
    initializeRealTimeUpdates();
    
    // Hide loading spinner
    hideLoadingSpinner();
    
    console.log('Admin panel initialized');
}

/**
 * Video management: Remove local file for a video that has a YouTube URL
 */
/**
 * removeLocalVideo(videoId)
 * YouTube linki bulunan videonun yerel dosyasını silmek için backend'e istek atar.
 * UI: İlgili satırdaki butonu devre dışı bırakır, rozetleri günceller.
 */
function removeLocalVideo(videoId) {
    if (!videoId) return;
    if (!confirm('Bu videonun yerel dosyasını silmek istediğinizden emin misiniz?\nNot: YouTube bağlantısı korunacaktır.')) {
        return;
    }

    // Disable button to avoid double-clicks
    const row = document.querySelector(`tr[data-video-id="${videoId}"]`);
    const btn = row ? row.querySelector('button.btn-delete[onclick*="removeLocalVideo"]') : null;
    if (btn) btn.disabled = true;

    showLoadingSpinner();
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=remove_local_video&video_id=${encodeURIComponent(videoId)}`
    })
    .then(r => r.json())
    .then(data => {
        hideLoadingSpinner();
        if (data.success) {
            showNotification(data.message || 'Yerel video dosyası kaldırıldı', 'success');
            // Update UI: Remove Local badge and disable the button
            if (row) {
                const sourceCell = row.children[3]; // Başlık, Yükleyen, Durum, Kaynak, Tarih, İşlemler
                if (sourceCell) {
                    const localBadge = sourceCell.querySelector('.badge-success');
                    if (localBadge) localBadge.remove();
                    // If no YouTube badge either, show warning
                    const hasYoutube = sourceCell.querySelector('.badge-info');
                    const hasLocal = sourceCell.querySelector('.badge-success');
                    if (!hasYoutube && !hasLocal) {
                        const span = document.createElement('span');
                        span.className = 'badge badge-warning';
                        span.textContent = 'Kaynak yok';
                        sourceCell.appendChild(span);
                    }
                }
                if (btn) btn.disabled = true;
            }
        } else {
            showNotification(data.message || 'İşlem başarısız', 'error');
            if (btn) btn.disabled = false;
        }
    })
    .catch(err => {
        hideLoadingSpinner();
        console.error('removeLocalVideo failed:', err);
        showNotification('Bir hata oluştu', 'error');
        if (btn) btn.disabled = false;
    });
}

/**
 * showVideoRequestModal(request)
 * Video düzenleme/silme talebinin detaylarını gösteren modal.
 * Admin, buradan onayla/reddet işlemlerini tetikleyebilir.
 */
function showVideoRequestModal(request){
    if (!request) { showNotification('Talep verisi bulunamadı', 'error'); return; }

    // Ensure base styles once
    if (!document.getElementById('videoReqModalStyles')){
        const styles = document.createElement('style');
        styles.id = 'videoReqModalStyles';
        styles.textContent = `
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:10000}
        .modal-card{background:#fff;border-radius:12px;max-width:720px;width:95%;box-shadow:0 10px 30px rgba(0,0,0,.25);overflow:hidden}
        .modal-header{padding:16px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between}
        .modal-body{padding:16px 20px}
        .modal-footer{padding:12px 20px;border-top:1px solid #eee;display:flex;gap:10px;justify-content:flex-end}
        .modal-close{background:none;border:none;font-size:18px;cursor:pointer;color:#666}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .grid .full{grid-column:1 / -1}
        .field{display:flex;flex-direction:column}
        .field label{font-size:12px;color:#555;margin-bottom:6px}
        .badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:12px}
        .btn{display:inline-flex;gap:8px;align-items:center;border:none;border-radius:8px;padding:10px 14px;cursor:pointer}
        .btn-primary{background:#10b981;color:#fff}
        .btn-secondary{background:#e5e7eb;color:#111}
        .btn-danger{background:#ef4444;color:#fff}
        `;
        document.head.appendChild(styles);
    }

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });

    const card = document.createElement('div');
    card.className = 'modal-card';
    overlay.appendChild(card);

    const header = document.createElement('div');
    header.className = 'modal-header';
    const title = request.request_type === 'delete' ? 'Video Silme Talebi' : 'Video Düzenleme Talebi';
    header.innerHTML = `
        <h3>${title} #${request.id || ''}</h3>
        <button class="modal-close" title="Kapat">✕</button>
    `;
    header.querySelector('.modal-close').addEventListener('click', () => overlay.remove());
    card.appendChild(header);

    const body = document.createElement('div');
    body.className = 'modal-body';
    const safe = (v)=> escapeHtml(v ?? '');
    body.innerHTML = `
      <div class="grid">
        <div class="field">
          <label>Video ID</label>
          <div>${request.video_id}</div>
        </div>
        <div class="field">
          <label>Talep Sahibi</label>
          <div>${safe(request.requester_name || request.requester_id)}</div>
        </div>
        <div class="field">
          <label>Talep Türü</label>
          <div><span class="badge">${safe(request.request_type)}</span></div>
        </div>
        <div class="field">
          <label>Durum</label>
          <div><span class="badge">${safe(request.status)}</span></div>
        </div>
        ${request.request_type === 'edit' ? `
        <div class="field full">
          <label>Önerilen Başlık</label>
          <div>${safe(request.proposed_title)}</div>
        </div>
        <div class="field full">
          <label>Önerilen Açıklama</label>
          <div style="white-space:pre-wrap;">${safe(request.proposed_description)}</div>
        </div>
        <div class="field">
          <label>Önerilen Sınıf</label>
          <div>${safe(request.proposed_grade)}</div>
        </div>
        <div class="field">
          <label>Önerilen Ünite</label>
          <div>${safe(request.proposed_unit_id)}</div>
        </div>
        <div class="field full">
          <label>Önerilen Alt Konu</label>
          <div>${safe(request.proposed_topic)}</div>
        </div>
        <div class="field full">
          <label>Önerilen YouTube URL</label>
          <div><a href="${safe(request.proposed_youtube_url||'')}" target="_blank">${safe(request.proposed_youtube_url||'')}</a></div>
        </div>
        ` : `
        <div class="field full">
          <label>Silme Gerekçesi</label>
          <div style="white-space:pre-wrap;">${safe(request.review_note || request.delete_reason || '')}</div>
        </div>
        `}
        <div class="field full">
          <label>Admin Notu</label>
          <textarea id="adminNote_${request.id}" rows="3" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px"></textarea>
        </div>
      </div>
    `;
    card.appendChild(body);

    const footer = document.createElement('div');
    footer.className = 'modal-footer';
    footer.innerHTML = `
      <button class="btn btn-secondary" type="button">Kapat</button>
      <button class="btn btn-danger" type="button">Reddet</button>
      <button class="btn btn-primary" type="button">Onayla</button>
    `;
    const [btnClose, btnReject, btnApprove] = footer.querySelectorAll('button');
    btnClose.addEventListener('click', ()=> overlay.remove());
    btnApprove.addEventListener('click', ()=> {
        const note = document.getElementById(`adminNote_${request.id}`).value || '';
        fetch('admin.php', {
            method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `action=approve_video_request&request_id=${encodeURIComponent(request.id)}&admin_note=${encodeURIComponent(note)}`
        })
        .then(r=> r.json().catch(()=>({success:false, message:'Geçersiz JSON yanıt'})))
        .then(data=>{
            console.log('approve_video_request response:', data);
            if (data.success){
                showNotification(data.message || 'Talep onaylandı','success');
                overlay.remove();
                setTimeout(()=>location.reload(), 600);
            } else {
                showNotification('Hata: ' + (data.message || 'İşlem başarısız'),'error');
            }
        })
        .catch(err=>{ console.error('approve_video_request failed:', err); showNotification('Hata oluştu','error'); });
    });
    btnReject.addEventListener('click', ()=> {
        const note = document.getElementById(`adminNote_${request.id}`).value || prompt('Red nedeni:','');
        if (note === null || note.trim()==='') { showNotification('Red için gerekçe zorunlu','warning'); return; }
        fetch('admin.php', {
            method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: `action=reject_video_request&request_id=${encodeURIComponent(request.id)}&admin_note=${encodeURIComponent(note)}`
        })
        .then(r=> r.json().catch(()=>({success:false, message:'Geçersiz JSON yanıt'})))
        .then(data=>{
            console.log('reject_video_request response:', data);
            if (data.success){
                showNotification(data.message || 'Talep reddedildi','success');
                overlay.remove();
                setTimeout(()=>location.reload(), 600);
            } else {
                showNotification('Hata: ' + (data.message || 'İşlem başarısız'),'error');
            }
        })
        .catch(err=>{ console.error('reject_video_request failed:', err); showNotification('Hata oluştu','error'); });
    });
    card.appendChild(footer);

    document.body.appendChild(overlay);
}

/**
 * Theme Toggle Functionality
 */
/**
 * initializeThemeToggle()
 * Admin teması için (light/dark) başlangıç durumunu yükler ve toggle butonuna
 * tıklama olayını bağlar. Tercih localStorage('admin-theme')'de saklanır.
 */
function initializeThemeToggle() {
    // Single theme - no toggle needed
    document.documentElement.setAttribute('data-theme', 'light');
}

/**
 * updateThemeIcon(theme)
 * Tema değiştirici butonundaki ikonu aktif temaya göre günceller.
 */
function updateThemeIcon(theme) { /* no-op in dark-only mode */ }

/**
 * Global Search Functionality
 */
/**
 * initializeSearch()
 * Üst arama kutusunu dinler; 2+ karakterde gecikmeli (debounce) global arama tetikler.
 */
function initializeSearch() {
    const searchInput = document.getElementById('globalSearch');
    
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            const query = e.target.value.trim();
            if (query.length >= 2) {
                performGlobalSearch(query);
            }
        }, 300));
    }
}

/**
 * performGlobalSearch(query)
 * Uygulama genelinde arama yapılması için iskelet fonksiyon (UI/geri bildirim sağlar).
 */
function performGlobalSearch(query) {
    // Implement global search across all sections
    console.log('Searching for:', query);
    
    // Show search results in dropdown or navigate to results page
    showNotification(`"${query}" için arama yapılıyor...`, 'info');
}

// Notification system removed with legacy messaging decommissioned

/**
 * Bulk Operations
 */
/**
 * initializeBulkOperations()
 * Kullanıcı tablosunda toplu seçim (select-all ve tekil checkbox) davranışlarını kurar.
 */
function initializeBulkOperations() {
    // Initialize bulk selection for users table
    const selectAllUsers = document.getElementById('selectAllUsers');
    if (selectAllUsers) {
        selectAllUsers.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
                if (this.checked) {
                    selectedUsers.add(parseInt(checkbox.value));
                } else {
                    selectedUsers.delete(parseInt(checkbox.value));
                }
            });
            updateBulkActionsBar();
        });
    }
    
    // Individual user checkboxes
    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                selectedUsers.add(parseInt(this.value));
            } else {
                selectedUsers.delete(parseInt(this.value));
            }
            updateBulkActionsBar();
        });
    });
}

/**
 * toggleBulkMode()
 * Kullanıcı tablosu için toplu işlem moduna girer/çıkar ve UI sütunlarını gösterir/gizler.
 */
function toggleBulkMode() {
    const table = document.getElementById('usersTable');
    const bulkHeaders = document.querySelectorAll('.bulk-header');
    const bulkCells = document.querySelectorAll('.bulk-cell');
    
    if (table.classList.contains('bulk-mode')) {
        // Exit bulk mode
        table.classList.remove('bulk-mode');
        bulkHeaders.forEach(header => header.style.display = 'none');
        bulkCells.forEach(cell => cell.style.display = 'none');
        selectedUsers.clear();
        updateBulkActionsBar();
    } else {
        // Enter bulk mode
        table.classList.add('bulk-mode');
        bulkHeaders.forEach(header => header.style.display = 'table-cell');
        bulkCells.forEach(cell => cell.style.display = 'table-cell');
    }
}

/**
 * updateBulkActionsBar()
 * Seçili kullanıcı sayısına göre toplu işlem barını ve sayaç bilgisini yönetir.
 */
function updateBulkActionsBar() {
    const bulkActionsBar = document.getElementById('bulkActionsBar');
    const selectedCount = document.getElementById('selectedCount');
    
    if (bulkActionsBar && selectedCount) {
        if (selectedUsers.size > 0) {
            bulkActionsBar.style.display = 'flex';
            selectedCount.textContent = selectedUsers.size;
        } else {
            bulkActionsBar.style.display = 'none';
        }
    }
}

/**
 * User Management Functions
 */
/** approveUser(userId): Kullanıcıyı onaylamak için onay diyaloğu sonrasında işlem yapar. */
function approveUser(userId) {
    if (confirm('Bu kullanıcıyı onaylamak istediğinizden emin misiniz?')) {
        performUserAction(userId, 'approve');
    }
}

/** rejectUser(userId): Kullanıcıyı reddetmek için onay alır ve işlem yapar. */
function rejectUser(userId) {
    if (confirm('Bu kullanıcıyı reddetmek istediğinizden emin misiniz?')) {
        performUserAction(userId, 'reject');
    }
}

/** editUser(userId): Backend'den kullanıcıyı çeker ve düzenleme modalını açar. */
function editUser(userId) {
    if (!userId) return;
    showLoadingSpinner();
    fetch(`admin.php?action=get_user&user_id=${userId}`)
        .then(r => r.json())
        .then(data => {
            hideLoadingSpinner();
            if (!data.success) { showNotification(data.error || 'Kullanıcı yüklenemedi', 'error'); return; }
            showUserEditModal(data.user);
        })
        .catch(err => { hideLoadingSpinner(); console.error(err); showNotification('Hata oluştu', 'error'); });
}

/**
 * showUserEditModal(user)
 * Kullanıcı bilgilerini düzenlemek için modern bir modal oluşturur ve DOM'a ekler.
 * Not: Stil ve form doğrulama temel seviyede uygulanmıştır.
 */
function showUserEditModal(user) {
    // Ensure styles once
    if (!document.getElementById('userEditModalStyles')) {
        const styles = document.createElement('style');
        styles.id = 'userEditModalStyles';
        styles.textContent = `
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;z-index:10000}
        .modal-card{background:#fff;border-radius:12px;max-width:720px;width:95%;box-shadow:0 10px 30px rgba(0,0,0,.25);overflow:hidden}
        .modal-header{padding:16px 20px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between}
        .modal-body{padding:16px 20px}
        .modal-footer{padding:12px 20px;border-top:1px solid #eee;display:flex;gap:10px;justify-content:flex-end}
        .modal-close{background:none;border:none;font-size:18px;cursor:pointer;color:#666}
        .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
        .form-group{display:flex;flex-direction:column}
        .form-group.full{grid-column:1 / -1}
        .form-group label{font-size:12px;color:#555;margin-bottom:6px}
        .form-group input, .form-group select{padding:10px 12px;border:1px solid #ddd;border-radius:8px}
        .badge{display:inline-block;padding:4px 8px;border-radius:999px;background:#f3f4f6;color:#374151;font-size:12px}
        .btn{display:inline-flex;gap:8px;align-items:center;border:none;border-radius:8px;padding:10px 14px;cursor:pointer}
        .btn-primary{background:#667eea;color:#fff}
        .btn-secondary{background:#e5e7eb;color:#111}
        .btn-danger{background:#ef4444;color:#fff}
        `;
        document.head.appendChild(styles);
    }

    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });

    const card = document.createElement('div');
    card.className = 'modal-card';
    overlay.appendChild(card);

    const header = document.createElement('div');
    header.className = 'modal-header';
    header.innerHTML = `
        <h3>Kullanıcı Düzenle</h3>
        <button class="modal-close" title="Kapat">✕</button>
    `;
    header.querySelector('.modal-close').addEventListener('click', () => overlay.remove());
    card.appendChild(header);

    const body = document.createElement('div');
    body.className = 'modal-body';
    const profile = user.profile || {};
    body.innerHTML = `
      <form id="userEditForm">
        <input type="hidden" name="user_id" value="${user.id}">
        <input type="hidden" name="role" value="${user.role}">
        <div class="form-grid">
          <div class="form-group">
            <label>Ad</label>
            <input name="first_name" value="${escapeHtml(user.first_name||'')}" required>
          </div>
          <div class="form-group">
            <label>Soyad</label>
            <input name="last_name" value="${escapeHtml(user.last_name||'')}">
          </div>
          <div class="form-group">
            <label>E‑posta</label>
            <input type="email" name="email" value="${escapeHtml(user.email||'')}" required>
          </div>
          <div class="form-group">
            <label>Telefon</label>
            <input type="tel" name="phone" pattern="^[0-9+\-()\s]{7,20}$" value="${escapeHtml(user.phone||'')}">
          </div>
          <div class="form-group">
            <label>Rol</label>
            <div class="badge">${user.role}</div>
          </div>
          <div class="form-group">
            <label>Durum</label>
            <select name="status">
              ${['pending','approved','rejected'].map(s=>`<option value="${s}" ${user.status===s?'selected':''}>${ucfirst(s)}</option>`).join('')}
            </select>
          </div>
          ${user.role==='student' ? `
            <div class="form-group full">
              <label>Okul</label>
              <input name="school" value="${escapeHtml(profile.school||'')}">
            </div>
            <div class="form-group">
              <label>Sınıf</label>
              <input name="grade" value="${escapeHtml(profile.grade||'')}">
            </div>
          ` : ''}
          ${user.role==='teacher' ? `
            <div class="form-group full">
              <label>Okul</label>
              <input name="school" value="${escapeHtml(profile.school||'')}">
            </div>
            <div class="form-group">
              <label>Bölüm</label>
              <input name="department" value="${escapeHtml(profile.department||'')}">
            </div>
            <div class="form-group">
              <label>Deneyim (yıl)</label>
              <input name="experience_years" type="number" min="0" value="${Number(profile.experience_years||0)}">
            </div>
          ` : ''}
        </div>
      </form>
    `;
    card.appendChild(body);

    const footer = document.createElement('div');
    footer.className = 'modal-footer';
    footer.innerHTML = `
      <button class="btn btn-secondary" type="button">İptal</button>
      <button class="btn btn-primary" type="button"><i class="fas fa-save"></i> Kaydet</button>
    `;
    const [btnCancel, btnSave] = footer.querySelectorAll('button');
    btnCancel.addEventListener('click', ()=> overlay.remove());
    btnSave.addEventListener('click', ()=> submitUserEditForm(document.getElementById('userEditForm'), overlay));
    card.appendChild(footer);

    document.body.appendChild(overlay);
}

/**
 * submitUserEditForm(form, overlay)
 * Form verilerini admin.php?action=update_user'a gönderir. Başarı halinde sayfayı yeniler.
 */
function submitUserEditForm(form, overlay){
    if (!form) return;
    const fd = new FormData(form);
    // basic validation
    if (!fd.get('first_name') || !fd.get('email')) { showNotification('Ad ve e‑posta zorunludur', 'warning'); return; }
    showLoadingSpinner();
    fetch('admin.php?action=update_user', { method: 'POST', body: fd })
      .then(r=>r.json())
      .then(data=>{
        hideLoadingSpinner();
        if (data.success){
            showNotification('Kullanıcı güncellendi', 'success');
            if (overlay) overlay.remove();
            setTimeout(()=> location.reload(), 800);
        } else {
            showNotification(data.error || 'Güncelleme başarısız', 'error');
        }
      })
      .catch(err=>{ hideLoadingSpinner(); console.error(err); showNotification('Hata oluştu', 'error'); });
}

function escapeHtml(str){
    return String(str).replace(/[&<>"]+/g, s=>({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;"}[s]||s));
}

function ucfirst(s){ return (s||'').charAt(0).toUpperCase() + (s||'').slice(1); }

/** deleteUser(userId): Geri alınamaz silme işlemi için onay alır ve uygular. */
function deleteUser(userId) {
    if (confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        performUserAction(userId, 'delete');
    }
}

/**
 * performUserAction(userId, action)
 * Tekil kullanıcı işlemlerini (approve/reject/delete) backend'e iletir ve UI'ı günceller.
 */
function performUserAction(userId, action) {
    showLoadingSpinner();
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=user_action&user_id=${userId}&operation=${action}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingSpinner();
        if (data.success) {
            showNotification('İşlem başarıyla tamamlandı', 'success');
            // Refresh the page or update the row
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('İşlem başarısız: ' + data.error, 'error');
        }
    })
    .catch(error => {
        hideLoadingSpinner();
        showNotification('Bir hata oluştu', 'error');
        console.error('User action failed:', error);
    });
}

/**
 * Bulk User Operations
 */
/** bulkApproveUsers(): Seçili kullanıcıları onaylamak için toplu işlem akışı. */
function bulkApproveUsers() {
    if (selectedUsers.size === 0) return;
    
    if (confirm(`${selectedUsers.size} kullanıcıyı onaylamak istediğinizden emin misiniz?`)) {
        performBulkUserOperation('approve');
    }
}

/** bulkRejectUsers(): Seçili kullanıcıları reddetmek için toplu işlem akışı. */
function bulkRejectUsers() {
    if (selectedUsers.size === 0) return;
    
    if (confirm(`${selectedUsers.size} kullanıcıyı reddetmek istediğinizden emin misiniz?`)) {
        performBulkUserOperation('reject');
    }
}

/** bulkDeleteUsers(): Seçili kullanıcıları silmek için toplu işlem akışı. */
function bulkDeleteUsers() {
    if (selectedUsers.size === 0) return;
    
    if (confirm(`${selectedUsers.size} kullanıcıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.`)) {
        performBulkUserOperation('delete');
    }
}

/**
 * performBulkUserOperation(operation)
 * Seçili kullanıcı ID'lerini JSON olarak backend'e gönderir; işlem sonucu UI geri bildirimi sağlar.
 */
function performBulkUserOperation(operation) {
    showLoadingSpinner();
    
    const userIds = Array.from(selectedUsers);
    
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=bulk_user_operation&user_ids=${JSON.stringify(userIds)}&operation=${operation}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoadingSpinner();
        if (data.success) {
            showNotification(`${userIds.length} kullanıcı için işlem tamamlandı`, 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Toplu işlem başarısız: ' + data.error, 'error');
        }
    })
    .catch(error => {
        hideLoadingSpinner();
        showNotification('Bir hata oluştu', 'error');
        console.error('Bulk operation failed:', error);
    });
}

function cancelBulkMode() {
    selectedUsers.clear();
    toggleBulkMode();
}

/**
 * Dashboard Charts
 */
/**
 * initializeCharts()
 * Dashboard sayfasında kullanıcı aktivitesi ve video performansı grafiklerini başlatır.
 */
function initializeCharts() {
    if (currentSection === 'dashboard') {
        initializeUserActivityChart();
        initializeVideoPerformanceChart();
    }
}

/**
 * initializeUserActivityChart()
 * Son 30 gün için kullanıcı aktivitesi verisini çeker ve çizgi grafik oluşturur.
 */
function initializeUserActivityChart() {
    const ctx = document.getElementById('userActivityChart');
    if (!ctx) return;
    
    // Fetch user activity data
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=1&action=get_user_activity&days=30'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            createUserActivityChart(ctx, data.data);
        }
    })
    .catch(error => console.error('Failed to load user activity data:', error));
}

/**
 * createUserActivityChart(ctx, data)
 * Chart.js ile kullanıcı aktivite çizgi grafiğini oluşturur.
 */
function createUserActivityChart(ctx, data) {
    dashboardCharts.userActivity = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'Aktif Kullanıcılar',
                data: data.values || [],
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * initializeVideoPerformanceChart()
 * Son 30 gün için video performans verisini çeker ve sütun grafik oluşturur.
 */
function initializeVideoPerformanceChart() {
    const ctx = document.getElementById('videoPerformanceChart');
    if (!ctx) return;
    
    // Fetch video performance data
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=1&action=get_video_analytics&days=30'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            createVideoPerformanceChart(ctx, data.data);
        }
    })
    .catch(error => console.error('Failed to load video performance data:', error));
}

// Video edit request functions
/** approveVideoRequest(requestId): Video düzenleme talebini onaylar, not alabilir. */
function approveVideoRequest(requestId) {
    const note = prompt('Onay notu (opsiyonel):');
    if (note !== null) {
        fetch('admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve_video_request&request_id=${requestId}&admin_note=${encodeURIComponent(note)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Video düzenleme talebi onaylandı', 'success');
                location.reload();
            } else {
                showNotification('Hata: ' + data.message, 'error');
            }
        });
    }
}

/** rejectVideoRequest(requestId): Video düzenleme talebini reddeder; gerekçe zorunlu. */
function rejectVideoRequest(requestId) {
    const note = prompt('Red nedeni:');
    if (note !== null && note.trim() !== '') {
        fetch('admin.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject_video_request&request_id=${requestId}&admin_note=${encodeURIComponent(note)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Video düzenleme talebi reddedildi', 'success');
                location.reload();
            } else {
                showNotification('Hata: ' + data.message, 'error');
            }
        });
    }
}

/** viewVideoRequest(requestId): Talep detayını çekip modalda gösterir. */
function viewVideoRequest(requestId) {
    fetch(`admin.php?action=get_video_request&request_id=${requestId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showVideoRequestModal(data.request);
            } else {
                showNotification('Hata: ' + data.message, 'error');
            }
        });
}

// Message moderation functions removed as legacy chat system is decommissioned.

/**
 * createVideoPerformanceChart(ctx, data)
 * Chart.js ile video performans sütun grafiğini oluşturur.
 */
function createVideoPerformanceChart(ctx, data) {
    dashboardCharts.videoPerformance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.labels || [],
            datasets: [{
                label: 'İzlenme Sayısı',
                data: data.views || [],
                backgroundColor: 'rgba(240, 147, 251, 0.8)',
                borderColor: '#f093fb',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

/**
 * Real-time Updates
 */
/**
 * initializeRealTimeUpdates()
 * Dashboard için belirli aralıklarla istatistik ve sistem durumunu günceller.
 */
function initializeRealTimeUpdates() {
    // Update dashboard stats every 60 seconds
    if (currentSection === 'dashboard') {
        refreshInterval = setInterval(updateDashboardStats, 60000);
    }
    
    // Update system status every 30 seconds
    setInterval(updateSystemStatus, 30000);
}

/** updateDashboardStats(): Panel kartlarını güncel istatistiklerle tazeler. */
function updateDashboardStats() {
    fetch('admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'ajax=1&action=get_dashboard_stats'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateStatsCards(data.data);
        }
    })
    .catch(error => console.error('Failed to update dashboard stats:', error));
}

/** updateStatsCards(stats): Kartlardaki sayısal değerleri güvenli şekilde günceller. */
function updateStatsCards(stats) {
    // Update stat card values
    const totalUsers = document.getElementById('totalUsers');
    const totalVideos = document.getElementById('totalVideos');
    const pendingItems = document.getElementById('pendingItems');
    const activeUsers = document.getElementById('activeUsers');
    
    if (totalUsers) totalUsers.textContent = stats.users?.total_users || 0;
    if (totalVideos) totalVideos.textContent = stats.videos?.total_videos || 0;
    if (pendingItems) {
        const pending = (stats.users?.pending_users || 0) + (stats.videos?.pending_videos || 0);
        pendingItems.textContent = pending;
    }
    if (activeUsers) activeUsers.textContent = stats.users?.active_24h || 0;
}

/** updateSystemStatus(): "son güncelleme" saatini kullanıcıya gösterir. */
function updateSystemStatus() {
    const statusElement = document.getElementById('systemStatus');
    const lastUpdateElement = document.getElementById('lastUpdate');
    
    if (statusElement && lastUpdateElement) {
        const now = new Date();
        lastUpdateElement.textContent = now.toLocaleTimeString('tr-TR', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }
}

/**
 * Utility Functions
 */
/** showLoadingSpinner(): Global yükleme göstergesini aktif eder. */
function showLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.add('active');
    }
}

/** hideLoadingSpinner(): Global yükleme göstergesini pasif eder. */
function hideLoadingSpinner() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) {
        spinner.classList.remove('active');
    }
}

/**
 * showNotification(message, type)
 * Sayfanın sağ üstünde kısa süre görünen bir bildirim üretir.
 */
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to page
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.className = 'notification-container';
        document.body.appendChild(container);
    }
    
    container.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
    
    // Add CSS if not exists
    if (!document.getElementById('notificationStyles')) {
        const styles = document.createElement('style');
        styles.id = 'notificationStyles';
        styles.textContent = `
            .notification-container {
                position: fixed;
                top: 80px;
                right: 20px;
                z-index: 10000;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .notification {
                background: white;
                border-radius: 8px;
                padding: 16px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                display: flex;
                align-items: center;
                justify-content: space-between;
                min-width: 300px;
                animation: slideIn 0.3s ease-out;
            }
            .notification-success { border-left: 4px solid #48bb78; }
            .notification-error { border-left: 4px solid #f56565; }
            .notification-warning { border-left: 4px solid #ed8936; }
            .notification-info { border-left: 4px solid #4299e1; }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            .notification-close {
                background: none;
                border: none;
                cursor: pointer;
                color: #718096;
                padding: 4px;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(styles);
    }
}

/** getNotificationIcon(type): Bildirim tipi için uygun FontAwesome ikon sınıfını döndürür. */
function getNotificationIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-circle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/** debounce(func, wait): Gecikmeli tetikleme yardımcı fonksiyonu. */
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

/** formatNumber(num): TR yerelleştirmesi ile sayı formatlar. */
function formatNumber(num) {
    return new Intl.NumberFormat('tr-TR').format(num);
}

/** formatDate(date): TR yerelleştirmesi ile tarih/saat formatlar. */
function formatDate(date) {
    return new Intl.DateTimeFormat('tr-TR', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(date));
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// Messaging notification renderer removed with legacy system
function updateNotificationList(notifications) {
    const menu = document.getElementById('notificationMenu');
    if (!menu) return;
    menu.innerHTML = '';
    if (!Array.isArray(notifications) || notifications.length === 0) {
        const empty = document.createElement('div');
        empty.className = 'notification-empty';
        empty.textContent = 'Bildirim yok';
        menu.appendChild(empty);
        return;
    }
    notifications.forEach(n => {
        const item = document.createElement('div');
        item.className = 'notification-item';
        item.textContent = n.text || 'Bildirim';
        menu.appendChild(item);
    });
}

// Export functions for global access
window.adminFunctions = {
    approveUser,
    rejectUser,
    editUser,
    deleteUser,
    bulkApproveUsers,
    bulkRejectUsers,
    bulkDeleteUsers,
    cancelBulkMode,
    toggleBulkMode,
    removeLocalVideo
};

// Chat admin UI removed with legacy messaging decommissioned
