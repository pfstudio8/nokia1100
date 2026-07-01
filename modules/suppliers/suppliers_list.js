// assets/js/pages/suppliers_list.js
function toggleHistory(id) {
    const row = document.getElementById('history-' + id);
    if (row) {
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    }
}
