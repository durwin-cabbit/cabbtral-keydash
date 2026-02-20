// script.js - Debugging mode enabled
document.addEventListener('DOMContentLoaded', () => {
    const keysTbody = document.getElementById('keys-tbody');
    const generateBtn = document.getElementById('generate-btn');
    const totalKeysEl = document.getElementById('total-keys');
    const activeKeysEl = document.getElementById('active-keys');
    const inactiveKeysEl = document.getElementById('inactive-keys');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toast-message');

    // Use /api/api (Vercel routes this to /api/api.php)
    const apiEndPoint = '/api/api';

    async function fetchKeys() {
        try {
            const response = await fetch(`${apiEndPoint}?action=list`);
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
                if (Array.isArray(data)) {
                    renderKeys(data);
                    updateStats(data);
                } else if (data.message) {
                    showError(data.message);
                }
            } catch (e) {
                // SHOW THE RAW ERROR SO WE CAN FIX IT
                showError("Server returned non-JSON: " + text.substring(0, 100));
                console.error('Full response:', text);
            }
        } catch (error) {
            showError("Network unreachable: " + error.message);
        }
    }

    function showError(msg) {
        keysTbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: #ef4444; font-size: 0.8rem; padding: 2rem;">
            <strong>ERROR:</strong> ${msg}<br>
            <small>Check Vercel Environment Variables for DATABASE_URL</small>
        </td></tr>`;
    }

    function renderKeys(keys) {
        if (keys.length === 0) {
            keysTbody.innerHTML = '<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No keys found. Click generate to start!</td></tr>';
            return;
        }

        keysTbody.innerHTML = keys.map(key => `
            <tr>
                <td>#${key.id}</td>
                <td><span class="key-badge" onclick="copyToClipboard('${key.key_value}')" style="cursor: pointer;">${key.key_value}</span></td>
                <td><span class="status-badge status-${key.status}">${key.status}</span></td>
                <td>${formatDate(key.created_at)}</td>
                <td>
                    <div class="action-btns">
                        <button class="action-btn toggle" onclick="toggleStatus(${key.id}, '${key.status}')"><i data-lucide="power"></i></button>
                        <button class="action-btn delete" onclick="deleteKey(${key.id})"><i data-lucide="trash-2"></i></button>
                    </div>
                </td>
            </tr>
        `).join('');
        lucide.createIcons();
    }

    function updateStats(keys) {
        totalKeysEl.textContent = keys.length;
        activeKeysEl.textContent = keys.filter(k => k.status === 'active').length;
        inactiveKeysEl.textContent = keys.filter(k => k.status === 'inactive').length;
    }

    function formatDate(dateString) {
        if (!dateString) return 'Just now';
        const d = new Date(dateString);
        return isNaN(d) ? 'Recent' : d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    generateBtn.addEventListener('click', async () => {
        try {
            generateBtn.disabled = true;
            generateBtn.innerHTML = '<i data-lucide="loader" class="spin"></i>...';
            lucide.createIcons();
            const res = await fetch(`${apiEndPoint}?action=generate`, { method: 'POST' });
            const result = await res.json();
            if (result.success) {
                showToast('Key Created!');
                fetchKeys();
            } else {
                showToast(result.message, 'error');
            }
        } catch (error) {
            showToast('Generation failed', 'error');
        } finally {
            generateBtn.disabled = false;
            generateBtn.innerHTML = '<i data-lucide="plus"></i> Generate Key';
            lucide.createIcons();
        }
    });

    window.toggleStatus = async (id, currentStatus) => {
        try {
            await fetch(`${apiEndPoint}?action=toggle_status`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, status: currentStatus })
            });
            fetchKeys();
        } catch (e) { }
    };

    window.deleteKey = async (id) => {
        if (!confirm('Delete entry?')) return;
        try {
            await fetch(`${apiEndPoint}?action=delete`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            fetchKeys();
        } catch (e) { }
    };

    window.copyToClipboard = (text) => {
        navigator.clipboard.writeText(text).then(() => showToast('Copied!'));
    };

    function showToast(message, type = 'success') {
        toastMessage.textContent = message;
        toast.className = 'toast ' + (type === 'error' ? 'error' : 'success');
        lucide.createIcons();
        toast.classList.remove('hidden');
        setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    fetchKeys();
});
