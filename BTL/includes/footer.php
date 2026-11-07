    </div><!-- /.page-content -->
</div><!-- /.main-content -->

<!-- Ambient Spa Audio Player (Inspired by Goldust Spa Bali) -->
<div class="spa-audio-player shadow-lg" id="spaAudioContainer" title="Âm nhạc thư giãn - Goldust Spa Lounge Soundtrack">
    <button class="spa-audio-btn" id="spaAudioBtn" type="button" aria-label="Phát / Dừng nhạc nền Spa">
        <i class="bi bi-play-fill" id="spaAudioIcon"></i>
    </button>
    <div class="d-none d-sm-flex flex-column">
        <span class="spa-audio-label">Goldust Spa Ambient</span>
        <small class="text-white-50" style="font-size: 0.65rem;">Thư giãn & Tĩnh lặng</small>
    </div>
    <div class="sound-waves" id="soundWaves">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>
    <audio id="spaAudioTrack" loop preload="metadata">
        <source src="https://goldust.cdn.prismic.io/goldust/Z4E0LJbqstJ99Uif_Sequence01.mp3" type="audio/mpeg">
    </audio>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
// Sidebar toggle for mobile
function toggleSidebar() {
    document.getElementById('sidebar')?.classList.toggle('show');
    document.getElementById('sidebarOverlay')?.classList.toggle('show');
}
document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
    document.getElementById('sidebar')?.classList.remove('show');
    this.classList.remove('show');
});

// Auto-dismiss alerts after 5 seconds
document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
    setTimeout(function() {
        var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
        bsAlert.close();
    }, 5000);
});

// Ambient Audio Controller
(function() {
    const audio = document.getElementById('spaAudioTrack');
    const btn = document.getElementById('spaAudioBtn');
    const icon = document.getElementById('spaAudioIcon');
    const waves = document.getElementById('soundWaves');

    if (!audio || !btn) return;

    // Set subtle volume
    audio.volume = 0.45;

    function updateAudioUI(isPlaying) {
        if (isPlaying) {
            icon.className = 'bi bi-pause-fill';
            waves.classList.add('playing');
        } else {
            icon.className = 'bi bi-play-fill';
            waves.classList.remove('playing');
        }
    }

    btn.addEventListener('click', function() {
        if (audio.paused) {
            audio.play().then(function() {
                updateAudioUI(true);
                sessionStorage.setItem('spa_audio_playing', 'true');
            }).catch(function(e) {
                console.log('Audio autoplay prevented or error:', e);
            });
        } else {
            audio.pause();
            updateAudioUI(false);
            sessionStorage.setItem('spa_audio_playing', 'false');
        }
    });

    // Check previous preference
    if (sessionStorage.getItem('spa_audio_playing') === 'true') {
        audio.play().then(function() {
            updateAudioUI(true);
        }).catch(function() {
            // Autoplay policies require user interaction
            updateAudioUI(false);
        });
    }
})();
</script>
</body>
</html>
