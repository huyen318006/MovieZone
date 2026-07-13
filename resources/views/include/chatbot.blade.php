{{-- Chatbot Bubble - Menu-based (Giai đoạn 1) --}}
<style>
    .chat-bubble-wrapper {
        position: fixed;
        bottom: 25px;
        right: 25px;
        z-index: 9999;
        font-family: inherit;
    }
    .chat-toggle { display: none; }
    .chat-toggle-label {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 60px;
        height: 60px;
        background-color: #0d6efd;
        color: white;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        transition: transform 0.3s ease, background-color 0.3s;
        position: absolute;
        bottom: 0;
        right: 0;
        z-index: 2;
    }
    .chat-toggle-label:hover {
        transform: scale(1.05);
        background-color: #0b5ed7;
    }
    .chat-window {
        position: absolute;
        bottom: 75px;
        right: 0;
        width: 370px;
        max-width: calc(100vw - 50px);
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        opacity: 0;
        transform: translateY(20px) scale(0.95);
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        transform-origin: bottom right;
        z-index: 1;
        border: 1px solid rgba(0,0,0,0.1);
    }
    .chat-toggle:checked ~ .chat-window {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }
    .chat-toggle:checked ~ .chat-toggle-label .fa-comment-dots { display: none; }
    .chat-toggle:not(:checked) ~ .chat-toggle-label .fa-xmark { display: none; }
    .chat-header {
        background: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
        color: white;
        padding: 15px 20px;
        font-weight: bold;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .chat-body {
        height: 400px;
        padding: 15px;
        overflow-y: auto;
        background-color: #f0f2f5;
    }
    .chat-footer {
        padding: 12px 15px;
        background-color: white;
        border-top: 1px solid #dee2e6;
    }
    .cb-bot-msg {
        background: white;
        color: #333;
        padding: 10px 14px;
        border-radius: 12px 12px 12px 2px;
        max-width: 88%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        font-size: 13.5px;
        line-height: 1.5;
        align-self: flex-start;
    }
    .cb-bot-msg strong, .cb-bot-msg b { color: #0d6efd; }
    .cb-user-msg {
        background: #0d6efd;
        color: white;
        padding: 10px 14px;
        border-radius: 12px 12px 2px 12px;
        max-width: 85%;
        font-size: 13.5px;
        align-self: flex-end;
    }
    .cb-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
        align-self: flex-start;
        max-width: 95%;
    }
    .cb-btn {
        background: white;
        color: #0d6efd;
        border: 1.5px solid #0d6efd;
        border-radius: 18px;
        padding: 6px 14px;
        font-size: 12.5px;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .cb-btn:hover { background: #0d6efd; color: white; }
    .cb-movie-card {
        background: white;
        border-radius: 10px;
        padding: 10px;
        margin-bottom: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        display: flex;
        gap: 10px;
        align-items: flex-start;
        font-size: 12.5px;
    }
    .cb-movie-card img {
        width: 50px;
        height: 70px;
        object-fit: cover;
        border-radius: 6px;
        flex-shrink: 0;
    }
    .cb-movie-card .cb-movie-info { flex: 1; }
    .cb-movie-card .cb-movie-title { font-weight: 600; color: #0d6efd; margin-bottom: 3px; }
    .cb-movie-card .cb-movie-meta { color: #666; font-size: 11.5px; line-height: 1.4; }
    .cb-showtime-item {
        background: white;
        border-radius: 8px;
        padding: 8px 12px;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12.5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .cb-showtime-time { font-weight: 700; color: #0d6efd; font-size: 14px; min-width: 50px; }
    .cb-showtime-meta { color: #555; }
    .cb-loading { display: flex; gap: 4px; padding: 12px 16px; align-self: flex-start; }
    .cb-loading span {
        width: 8px; height: 8px; border-radius: 50%; background: #0d6efd;
        animation: cbBounce 1.4s infinite ease-in-out both;
    }
    .cb-loading span:nth-child(1) { animation-delay: -0.32s; }
    .cb-loading span:nth-child(2) { animation-delay: -0.16s; }
    @keyframes cbBounce {
        0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
        40% { transform: scale(1); opacity: 1; }
    }
</style>

<div class="chat-bubble-wrapper">
    <input type="checkbox" id="chat-toggle" class="chat-toggle">
    <label for="chat-toggle" class="chat-toggle-label">
        <i class="fa-solid fa-comment-dots fs-4"></i>
        <i class="fa-solid fa-xmark fs-4"></i>
    </label>

    <div class="chat-window">
        <div class="chat-header">
            <span><i class="fa-solid fa-robot me-2"></i> Trợ lý MovieZone</span>
            <label for="chat-toggle" class="text-white m-0" style="cursor: pointer;" title="Đóng">
                <i class="fa-solid fa-minus"></i>
            </label>
        </div>
        <div class="chat-body" id="chatbot-body">
            <div class="d-flex flex-column gap-2" id="chatbot-messages"></div>
        </div>
        <div class="chat-footer">
            <form id="chatbot-form" class="input-group" style="display: none;">
                <input type="text" id="chatbot-input" class="form-control form-control-sm shadow-none border-secondary-subtle" placeholder="Nhập tên phim cần tìm..." required>
                <button class="btn btn-primary btn-sm" type="submit" id="chatbot-btn"><i class="fa-solid fa-paper-plane"></i></button>
            </form>
            <div id="chatbot-footer-hint" class="text-center text-muted" style="font-size: 12px;">
                👆 Chọn một mục ở trên để bắt đầu
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const messagesEl = document.getElementById('chatbot-messages');
    const bodyEl = document.getElementById('chatbot-body');
    const formEl = document.getElementById('chatbot-form');
    const inputEl = document.getElementById('chatbot-input');
    const hintEl = document.getElementById('chatbot-footer-hint');
    const apiUrl = '{{ route("api.chatbot") }}';
    const csrfToken = '{{ csrf_token() }}';
    let pendingInputAction = null;

    async function sendAction(action, value) {
        showLoading();
        try {
            const res = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ action: action, value: value })
            });
            const data = await res.json();
            removeLoading();
            renderResponse(data);
        } catch (err) {
            removeLoading();
            addBotMsg('Lỗi kết nối, vui lòng thử lại! 😢');
            addButtons([{ label: '🔙 Quay lại menu', action: 'menu' }]);
        }
    }

    function renderResponse(data) {
        if (data.message) addBotMsg(data.message);

        if (data.type === 'movie_list' && data.data) renderMovieList(data.data);
        else if (data.type === 'movie_detail' && data.data) renderMovieDetail(data.data);
        else if (data.type === 'showtime_list' && data.data) renderShowtimeList(data.data);

        if (data.type === 'prompt_input' && data.input_action) {
            pendingInputAction = data.input_action;
            formEl.style.display = 'flex';
            hintEl.style.display = 'none';
            inputEl.focus();
        } else {
            pendingInputAction = null;
            formEl.style.display = 'none';
            hintEl.style.display = 'block';
        }

        if (data.buttons && data.buttons.length > 0) addButtons(data.buttons);
        scrollToBottom();
    }

    function addBotMsg(text) {
        const div = document.createElement('div');
        div.className = 'cb-bot-msg';
        div.innerHTML = simpleMarkdown(text);
        messagesEl.appendChild(div);
    }

    function addUserMsg(text) {
        const div = document.createElement('div');
        div.className = 'cb-user-msg';
        div.textContent = text;
        messagesEl.appendChild(div);
    }

    function renderMovieList(movies) {
        const container = document.createElement('div');
        container.className = 'align-self-start';
        container.style.maxWidth = '95%';
        movies.forEach(function(movie) {
            const card = document.createElement('div');
            card.className = 'cb-movie-card';
            const posterSrc = movie.poster_url
                ? (movie.poster_url.startsWith('http') ? movie.poster_url : '/storage/' + movie.poster_url)
                : 'https://via.placeholder.com/50x70/0d6efd/fff?text=🎬';
            const genres = movie.genres ? movie.genres.join(', ') : '';
            card.innerHTML =
                '<img src="' + posterSrc + '" alt="' + movie.title + '" onerror="this.src=\'https://via.placeholder.com/50x70/0d6efd/fff?text=🎬\'">' +
                '<div class="cb-movie-info">' +
                    '<div class="cb-movie-title">' + movie.title + '</div>' +
                    '<div class="cb-movie-meta">' +
                        (genres ? '🎭 ' + genres + '<br>' : '') +
                        (movie.duration_minutes ? '⏱️ ' + movie.duration_minutes + ' phút' : '') +
                        (movie.age_rating ? ' • 🔞 ' + movie.age_rating : '') +
                        (movie.rating ? ' • ⭐ ' + movie.rating : '') +
                    '</div>' +
                '</div>';
            container.appendChild(card);
        });
        messagesEl.appendChild(container);
    }

    function renderMovieDetail(movie) {
        const container = document.createElement('div');
        container.className = 'cb-bot-msg';
        container.style.maxWidth = '95%';
        const posterSrc = movie.poster_url
            ? (movie.poster_url.startsWith('http') ? movie.poster_url : '/storage/' + movie.poster_url)
            : '';
        let html = '<div style="text-align:center;margin-bottom:8px;">';
        if (posterSrc) html += '<img src="' + posterSrc + '" style="width:120px;border-radius:8px;margin-bottom:8px;" onerror="this.style.display=\'none\'"><br>';
        html += '<strong style="font-size:15px;">' + movie.title + '</strong>';
        if (movie.original_title) html += '<br><small style="color:#888;">' + movie.original_title + '</small>';
        html += '</div><div style="font-size:12.5px;line-height:1.6;">';
        if (movie.genres && movie.genres.length) html += '🎭 <b>Thể loại:</b> ' + movie.genres.join(', ') + '<br>';
        if (movie.duration_minutes) html += '⏱️ <b>Thời lượng:</b> ' + movie.duration_minutes + ' phút<br>';
        if (movie.age_rating) html += '🔞 <b>Độ tuổi:</b> ' + movie.age_rating + '<br>';
        if (movie.director) html += '🎬 <b>Đạo diễn:</b> ' + movie.director + '<br>';
        if (movie.cast) html += '🌟 <b>Diễn viên:</b> ' + movie.cast + '<br>';
        if (movie.language) html += '🗣️ <b>Ngôn ngữ:</b> ' + movie.language + '<br>';
        if (movie.country) html += '🌍 <b>Quốc gia:</b> ' + movie.country + '<br>';
        if (movie.rating) html += '⭐ <b>Đánh giá:</b> ' + movie.rating + '/10<br>';
        html += '</div>';
        if (movie.description) html += '<div style="margin-top:6px;font-size:12px;color:#555;border-top:1px solid #eee;padding-top:6px;">📝 ' + movie.description + '</div>';
        if (movie.trailer_url) html += '<div style="margin-top:6px;"><a href="' + movie.trailer_url + '" target="_blank" style="color:#0d6efd;font-size:12px;">▶️ Xem trailer</a></div>';
        container.innerHTML = html;
        messagesEl.appendChild(container);
    }

    function renderShowtimeList(showtimes) {
        const container = document.createElement('div');
        container.className = 'align-self-start';
        container.style.maxWidth = '95%';
        showtimes.forEach(function(st) {
            const item = document.createElement('div');
            item.className = 'cb-showtime-item';
            item.innerHTML =
                '<div class="cb-showtime-time">' + st.start_time + '</div>' +
                '<div class="cb-showtime-meta">🏠 ' + st.room + ' • 📺 ' + st.format +
                (st.end_time ? ' • đến ' + st.end_time : '') + '</div>';
            container.appendChild(item);
        });
        messagesEl.appendChild(container);
    }

    function addButtons(buttons) {
        const container = document.createElement('div');
        container.className = 'cb-buttons';
        buttons.forEach(function(btn) {
            const el = document.createElement('button');
            el.className = 'cb-btn';
            el.textContent = btn.label;
            el.addEventListener('click', function() {
                addUserMsg(btn.label);
                document.querySelectorAll('.cb-btn').forEach(function(b) {
                    b.disabled = true;
                    b.style.opacity = '0.5';
                    b.style.cursor = 'default';
                });
                sendAction(btn.action, btn.value || '');
            });
            container.appendChild(el);
        });
        messagesEl.appendChild(container);
        scrollToBottom();
    }

    function showLoading() {
        const div = document.createElement('div');
        div.className = 'cb-loading';
        div.id = 'cb-loading';
        div.innerHTML = '<span></span><span></span><span></span>';
        messagesEl.appendChild(div);
        scrollToBottom();
    }

    function removeLoading() {
        const el = document.getElementById('cb-loading');
        if (el) el.remove();
    }

    function simpleMarkdown(text) {
        return text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>')
            .replace(/• /g, '&bull; ');
    }

    function scrollToBottom() {
        setTimeout(function() { bodyEl.scrollTop = bodyEl.scrollHeight; }, 50);
    }

    formEl.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = inputEl.value.trim();
        if (!text || !pendingInputAction) return;
        addUserMsg(text);
        inputEl.value = '';
        sendAction(pendingInputAction, text);
    });

    document.getElementById('chat-toggle').addEventListener('change', function() {
        if (this.checked && messagesEl.children.length === 0) {
            sendAction('menu', '');
        }
    });
});
</script>
