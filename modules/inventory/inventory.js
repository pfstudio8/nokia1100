// assets/js/pages/inventory.js
function toggleView(viewType) {
    const table = document.getElementById('view-table');
    const grid = document.getElementById('view-grid');
    const btnTable = document.getElementById('btn-view-table');
    const btnGrid = document.getElementById('btn-view-grid');

    if (!table || !grid || !btnTable || !btnGrid) return;

    if (viewType === 'grid') {
        table.classList.add('hidden');
        grid.classList.remove('hidden');
        btnGrid.classList.add('bg-primary/20', 'text-primary');
        btnGrid.classList.remove('text-text-muted');
        btnTable.classList.remove('bg-primary/20', 'text-primary');
        btnTable.classList.add('text-text-muted');
    } else {
        grid.classList.add('hidden');
        table.classList.remove('hidden');
        btnTable.classList.add('bg-primary/20', 'text-primary');
        btnTable.classList.remove('text-text-muted');
        btnGrid.classList.remove('bg-primary/20', 'text-primary');
        btnGrid.classList.add('text-text-muted');
    }
}
