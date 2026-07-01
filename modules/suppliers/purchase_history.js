// assets/js/pages/purchase_history.js
function toggleDetails(id) {
    const row = document.getElementById('detail-' + id);
    if (row) {
        row.classList.toggle('hidden');
    }
}
