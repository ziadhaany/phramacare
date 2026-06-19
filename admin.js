
  
  // ============= SIMPLE SEARCH IN PRODUCTS TABLE =============
  const searchInput = document.getElementById('product-search');
const productRows = document.querySelectorAll('table.table tbody tr');

if (searchInput) {
  searchInput.addEventListener('input', function () {
    const term = this.value.toLowerCase().trim();

    productRows.forEach(row => {
      const nameCell = row.children[2]; 
      const nameText = nameCell.textContent.toLowerCase();

      row.style.display = nameText.includes(term) ? '' : 'none';
    });
  });
}

  
  // ============= TOGGLE ORDER STATUS BADGE =============
  const statusBadges = document.querySelectorAll('.status-badge');
  
  const statuses = ['pending', 'completed', 'cancelled'];
  
  function prettyStatus(s) {
    return s.charAt(0).toUpperCase() + s.slice(1);
  }
  
  statusBadges.forEach(badge => {
    badge.addEventListener('click', function () {
      const current = statuses.find(s => this.classList.contains(s)) || 'pending';
      let idx = statuses.indexOf(current);
      idx = (idx + 1) % statuses.length;
  
      statuses.forEach(s => this.classList.remove(s));
  
      const next = statuses[idx];
      this.classList.add(next);
      this.textContent = prettyStatus(next);
    });
  });