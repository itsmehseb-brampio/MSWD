@extends('layouts.barangay')

@section('title', 'Messages')
@section('headerTitle', 'Group Chat')

@section('head')
<style>
.content { padding: 0; }
.msg-layout {
    display: flex;
    height: calc(100vh - 72px);
    background: #f4f7f6;
    overflow: hidden;
}
.accounts-panel {
    width: 320px;
    flex-shrink: 0;
    background: #fff;
    border-right: 1px solid #e6e9e7;
    display: flex;
    flex-direction: column;
}
@media (max-width: 900px) { .accounts-panel { width: 240px; } }
.panel-header { padding: 18px 16px 12px; border-bottom: 1px solid #f0f3f1; }
.panel-header h3 { font-size: 1rem; color: #333; }
.panel-header p { font-size: 0.78rem; color: #999; margin-top: 3px; }
.acc-search {
    margin: 12px 16px;
    padding: 9px 13px;
    border: 1.5px solid #e2e8e4;
    border-radius: 8px;
    font-size: 0.85rem;
}
.acc-search:focus { outline: none; border-color: #11998e; }
.acc-items { flex: 1; overflow-y: auto; padding: 0 8px 12px; }
.acc-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 10px;
    border-radius: 10px;
    cursor: default;
}
.acc-item:hover { background: #f4f9f6; }
.acc-avatar { position: relative; width: 42px; height: 42px; flex-shrink: 0; }
.acc-avatar img { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.12); }
.acc-avatar .av-fallback {
    width: 42px; height: 42px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.95rem;
    background: #11998e; color: #fff;
}
.acc-dot {
    position: absolute;
    bottom: 1px;
    right: 1px;
    width: 11px;
    height: 11px;
    border-radius: 50%;
    background: #bbb;
    border: 2px solid #fff;
}
.acc-dot.on { background: #2ecc71; }
.acc-info { flex: 1; min-width: 0; }
.acc-name { font-size: 0.88rem; font-weight: 600; color: #333; display: flex; align-items: center; gap: 6px; }
.you-tag {
    background: #11998e;
    color: #fff;
    font-size: 0.6rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
}
.acc-sub { font-size: 0.74rem; color: #999; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.acc-empty { padding: 24px; text-align: center; color: #bbb; font-size: 0.85rem; }

.group-chat { flex: 1; display: flex; flex-direction: column; min-width: 0; }
.gc-head {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 13px 20px;
    background: #fff;
    border-bottom: 1px solid #e6e9e7;
}
.gc-head h3 { font-size: 0.98rem; color: #333; }
.gc-head .gc-sub { font-size: 0.75rem; color: #999; }
.gc-avatars { display: flex; align-items: center; margin-left: auto; }
.ga { width: 34px; height: 34px; margin-left: -8px; border-radius: 50%; border: 2px solid #fff; }
.ga img { width: 100%; height: 100%; border-radius: 50%; object-fit: cover; }
.ga.more { background: #eef2f0; display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 700; color: #666; }
.gc-messages {
    flex: 1;
    overflow-y: auto;
    padding: 22px 26px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    background: #f4f7f6;
}
.gc-empty { text-align: center; color: #aaa; margin: auto; font-size: 0.9rem; }
.gc-empty i { font-size: 2.4rem; color: #c8d3cd; display: block; margin-bottom: 8px; }
.gc-msg { display: flex; gap: 10px; max-width: 72%; align-self: flex-start; }
.gc-msg.own { align-self: flex-end; flex-direction: row-reverse; }
.gc-avatar { flex-shrink: 0; }
.gc-avatar img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
.gc-avatar .av-fallback {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 0.85rem; background: #11998e; color: #fff;
}
.gc-bubble {
    background: #fff;
    border-radius: 4px 14px 14px 14px;
    padding: 10px 14px;
    box-shadow: 0 1px 5px rgba(0,0,0,0.05);
    max-width: 100%;
}
.gc-msg.own .gc-bubble { background: linear-gradient(135deg, #11998e, #0b6e4f); color: #fff; border-radius: 14px 4px 14px 14px; }
.gc-name { font-size: 0.76rem; font-weight: 700; margin-bottom: 3px; }
.gc-text { font-size: 0.9rem; line-height: 1.5; word-break: break-word; white-space: pre-wrap; }
.gc-time { font-size: 0.68rem; opacity: 0.65; margin-top: 6px; text-align: right; }
.gc-msg.own .gc-time { color: #eafaf6; }
.gc-input-wrap {
    display: flex;
    gap: 10px;
    align-items: center;
    padding: 14px 20px;
    background: #fff;
    border-top: 1px solid #e6e9e7;
}
.gc-input {
    flex: 1;
    padding: 11px 16px;
    border: 1.5px solid #e2e8e4;
    border-radius: 24px;
    font-size: 0.9rem;
    resize: none;
}
.gc-input:focus { outline: none; border-color: #11998e; }
.send-btn {
    width: 46px;
    height: 46px;
    border: none;
    border-radius: 50%;
    background: linear-gradient(135deg, #11998e, #0b6e4f);
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}
.send-btn:hover { transform: scale(1.06); box-shadow: 0 4px 12px rgba(17,153,142,0.35); }
.send-btn:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
@endsection

@section('content')
<div class="msg-layout">
    <div class="accounts-panel">
        <div class="panel-header">
            <h3><i class="fas fa-users"></i> Accounts</h3>
            <p>DSWD &amp; all barangays in the group chat</p>
        </div>
        <input type="text" class="acc-search" id="accSearch" placeholder="Search accounts..." oninput="filterAccounts()">
        <div class="acc-items" id="accItems"></div>
    </div>

    <div class="group-chat">
        <div class="gc-head">
            <div>
                <h3><i class="fas fa-comments"></i> #general</h3>
                <div class="gc-sub" id="gcMembers">Loading...</div>
            </div>
            <div class="gc-avatars" id="gcAvatars"></div>
        </div>

        <div class="gc-messages" id="gcMessages"></div>

        <div class="gc-input-wrap">
            <textarea class="gc-input" id="msgInput" rows="1" placeholder="Type a message..." onkeydown="onEnter(event)"></textarea>
            <button class="send-btn" id="sendBtn" onclick="sendGroup()"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ROOT = '{{ url('/') }}';
const API_BASE = ROOT + '/barangay/api/messages';
const CSRFTOKEN = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const MY_NAME = {{ Illuminate\Support\Js::from($my_name) }};
const MY_LOGO = {{ Illuminate\Support\Js::from($my_logo) }};

let accounts = [];
let lastMsgCount = 0;

function escHtml(s) {
    return String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}
function assetUrl(p) {
    if (!p) return ROOT + '/yana.png';
    if (/^(https?:)?\/\//.test(p) || p.charAt(0) === '/' || p.indexOf('data:') === 0) return p;
    if (p.indexOf('uploads') === 0) return ROOT + '/' + p;
    if (p === 'mapa.png' || p === 'yana.png') return ROOT + '/' + p;
    return ROOT + '/uploads/' + p;
}
function avatarHtml(logo, name) {
    if (logo) {
        return '<img src="' + escHtml(assetUrl(logo)) + '" alt="" data-i="' + escHtml(String(name || '')) + '" onerror="this.insertAdjacentHTML(\'afterend\', fallbackAvatar(this.getAttribute(\'data-i\'))); this.remove();">';
    }
    return fallbackAvatar(name);
}
function fallbackAvatar(name) {
    return '<span class="av-fallback">' + escHtml(String(name || '?').charAt(0).toUpperCase()) + '</span>';
}
function nameColor(id) {
    let colors = ['#e8710a', '#0072C6', '#00875a', '#7c5cff', '#d93025', '#0b6e4f'];
    return colors[(id % colors.length + colors.length) % colors.length];
}
function fmtTime(s) {
    if (!s) return '';
    let d = new Date(s.replace(' ', 'T') + 'Z');
    if (isNaN(d.getTime())) return s;
    let now = new Date();
    let opts = { hour: 'numeric', minute: '2-digit', hour12: true };
    if (d.toDateString() !== now.toDateString()) opts.day = 'numeric', opts.month = 'short';
    return d.toLocaleString(undefined, opts);
}

function api(action, fd) {
    fd = fd || new FormData();
    return fetch(API_BASE + '/' + action, {
        method: 'POST',
        body: fd,
        headers: { 'X-CSRF-TOKEN': CSRFTOKEN, 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json(); });
}

/* ---- Accounts ---- */
function heartbeat() {
    api('heartbeat').catch(function () {});
}
function loadAccounts() {
    api('group_accounts')
        .then(function (data) {
            if (!data.ok) return;
            accounts = data.accounts || [];
            renderAccounts();
            renderHeaderAvatars();
        })
        .catch(function () {});
}
function filterAccounts() {
    renderAccounts();
}
function renderAccounts() {
    let key = (document.getElementById('accSearch').value || '').toLowerCase();
    let items = accounts.filter(function (a) {
        return !key || (a.name || '').toLowerCase().indexOf(key) > -1;
    });
    let el = document.getElementById('accItems');
    if (!items.length) {
        el.innerHTML = '<div class="acc-empty">No accounts found.</div>';
        return;
    }
    let mine = items.filter(function (a) { return a.is_me; });
    let others = items.filter(function (a) { return !a.is_me; });
    el.innerHTML = mine.map(accItemHtml).join('') + others.map(accItemHtml).join('');
}
function accItemHtml(a) {
    let you = a.is_me ? '<span class="you-tag">You</span>' : '';
    let dot = '<div class="acc-dot ' + (a.is_online ? 'on' : '') + '"></div>';
    return '<div class="acc-item">' +
        '<div class="acc-avatar">' + avatarHtml(a.logo, a.name, '#fff') + dot + '</div>' +
        '<div class="acc-info"><div class="acc-name">' + escHtml(a.name) + you + '</div>' +
        '<div class="acc-sub">' + escHtml(a.sub || '') + '</div></div></div>';
}
function renderHeaderAvatars() {
    let wrap = document.getElementById('gcAvatars');
    let shown = accounts.slice(0, 4);
    let html = shown.map(function (a) { return '<div class="ga">' + avatarHtml(a.logo, a.name, '#fff') + '</div>'; }).join('');
    let extra = accounts.length > 4 ? '<div class="ga more">+' + (accounts.length - 4) + '</div>' : '';
    wrap.innerHTML = html + extra;
    let count = accounts.length;
    document.getElementById('gcMembers').textContent = count + ' member' + (count === 1 ? '' : 's');
}

/* ---- Group chat ---- */
function loadGroup() {
    api('group_fetch')
        .then(function (data) {
            if (!data.ok) return;
            renderGroup(data.messages || []);
            if (typeof data.member_count !== 'undefined') {
                document.getElementById('gcMembers').textContent = data.member_count + ' member' + (data.member_count === 1 ? '' : 's');
            }
        })
        .catch(function () {});
}
function renderGroup(msgs) {
    let container = document.getElementById('gcMessages');
    let wasAtBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 80;

    if (!msgs.length) {
        container.innerHTML = '<div class="gc-empty"><i class="fas fa-users"></i><p>No messages yet. Start the group conversation!</p></div>';
    } else {
        container.innerHTML = msgs.map(function (m) {
            let own = m.own ? ' own' : '';
            let fg = nameColor(m.sender_id);
            let time = fmtTime(m.created_at);
            return '<div class="gc-msg' + own + '">' +
                '<div class="gc-avatar">' + avatarHtml(m.sender_logo, m.sender_name, '#fff') + '</div>' +
                '<div><div class="gc-bubble">' +
                (own ? '' : '<div class="gc-name" style="color:' + fg + '">' + escHtml(m.sender_name) + '</div>') +
                '<div class="gc-text">' + escHtml(m.message) + '</div>' +
                '<div class="gc-time">' + time + '</div>' +
                '</div></div></div>';
        }).join('');
    }
    let count = msgs.length;
    if (count > lastMsgCount) container.scrollTop = container.scrollHeight;
    lastMsgCount = count;
    if (wasAtBottom) container.scrollTop = container.scrollHeight;
}
function onEnter(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendGroup();
    }
}
function sendGroup() {
    let input = document.getElementById('msgInput');
    let msg = input.value.trim();
    if (!msg) return;
    input.value = '';
    document.getElementById('sendBtn').disabled = true;

    let fd = new FormData();
    fd.append('action', 'group_send');
    fd.append('message', msg);

    api('group_send', fd)
        .then(function (data) {
            document.getElementById('sendBtn').disabled = false;
            if (data.ok) { loadGroup(); loadAccounts(); }
            else { input.value = msg; }
        })
        .catch(function () {
            document.getElementById('sendBtn').disabled = false;
            input.value = msg;
        });
}

heartbeat();
setInterval(heartbeat, 30000);
loadAccounts();
loadGroup();
setInterval(loadAccounts, 15000);
setInterval(loadGroup, 3000);
</script>
@endpush