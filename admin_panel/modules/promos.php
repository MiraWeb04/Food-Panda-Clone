<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Promos | Admin — Bean Rooted Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin.css">
    <style>
      .promo-banner { width: 100px; height: 60px; object-fit: cover; border-radius: 8px; }
      .table-actions button { margin-right: 0.35rem; }
      .badge-status { cursor: pointer; }
      .form-label small { color: #6c757d; }
    </style>
  </head>
  <body>
    <div class="d-flex admin-root">
      <aside class="admin-sidebar">
        <div class="brand px-3 py-4">
          <img src="/images/bean_rooted_logo.png" alt="logo" class="sidebar-logo">
          <h4>Bean Rooted</h4>
        </div>
        <nav class="nav flex-column p-2">
          <a class="nav-link" href="../dashboard.php">Dashboard</a>
          <a class="nav-link text-muted" href="products.php">Products</a>
          <a class="nav-link text-muted" href="categories.php">Categories</a>
          <a class="nav-link active" href="promos.php">Promos</a>
          <a class="nav-link text-muted" href="#">Orders</a>
          <a class="nav-link text-muted" href="#">Staff</a>
          <a class="nav-link text-muted" href="#">Riders</a>
          <a class="nav-link text-muted" href="#">Sales Reports</a>
          <a class="nav-link text-muted" href="#">Settings</a>
          <a class="nav-link text-danger mt-auto" href="../logout.php">Logout</a>
        </nav>
      </aside>
      <main class="admin-main p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h3>Promos</h3>
            <p class="text-muted mb-0">Create and manage current promo banners, discount offers, and expiry rules.</p>
          </div>
          <button class="btn btn-success" id="btn-add-promo">+ Add Promo</button>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-5">
            <input type="search" id="search" class="form-control" placeholder="Search promos...">
          </div>
          <div class="col-md-4">
            <select id="filter-status" class="form-select">
              <option value="">All statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle" id="promos-table">
            <thead>
              <tr>
                <th>Banner</th>
                <th>Title</th>
                <th>Description</th>
                <th>Type</th>
                <th>Value</th>
                <th>Start</th>
                <th>End</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </main>
    </div>

    <div class="modal fade" id="promoModal" tabindex="-1">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <form id="promo-form" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title">Add Promo</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="promo-id" name="id">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Promo Title</label>
                  <input type="text" id="promo-title" name="title" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Status</label>
                  <select id="promo-status" name="status" class="form-select" required>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Description</label>
                  <textarea id="promo-description" name="description" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Discount Type</label>
                  <select id="promo-discount-type" name="discount_type" class="form-select" required>
                    <option value="percentage">Percentage</option>
                    <option value="fixed">Fixed Amount</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Discount Value</label>
                  <input type="number" id="promo-discount-value" name="discount_value" class="form-control" min="0" step="0.01" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Banner Image</label>
                  <input type="file" id="promo-banner" name="banner" class="form-control" accept="image/*">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Start Date</label>
                  <input type="date" id="promo-start-date" name="start_date" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">End Date</label>
                  <input type="date" id="promo-end-date" name="end_date" class="form-control" required>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save Promo</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Delete Promo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete this promo? This action cannot be undone.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirm-delete">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      const api = '/admin_panel/api/promos_admin.php';
      const promosTableBody = document.querySelector('#promos-table tbody');
      const searchInput = document.getElementById('search');
      const statusFilter = document.getElementById('filter-status');
      const promoModalEl = document.getElementById('promoModal');
      const promoModal = new bootstrap.Modal(promoModalEl);
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
      const promoForm = document.getElementById('promo-form');
      const modalTitle = promoModalEl.querySelector('.modal-title');
      let promos = [];
      let currentPromoId = null;
      let deletePromoId = null;

      function escapeHtml(value) {
        return String(value || '')
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#39;');
      }

      function formatDate(value) {
        if (!value) return '-';
        return new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
      }

      function getStatusBadge(status, isExpired) {
        if (isExpired) {
          return '<span class="badge bg-warning text-dark">Expired</span>';
        }
        return status === 'active'
          ? '<span class="badge bg-success">Active</span>'
          : '<span class="badge bg-secondary">Inactive</span>';
      }

      async function loadPromos() {
        const query = new URLSearchParams({
          action: 'list',
          search: searchInput.value.trim(),
          status: statusFilter.value,
        });
        const res = await fetch(`${api}?${query}`);
        const data = await res.json();
        promos = data.promos || [];
        renderPromos();
      }

      function renderPromos() {
        promosTableBody.innerHTML = promos.map(promo => {
          const toggleLabel = promo.status === 'active' ? 'Deactivate' : 'Activate';
          return `
          <tr>
            <td><img src="${promo.banner_url || '/images/bean_rooted_logo.png'}" alt="Promo banner" class="promo-banner"></td>
            <td>${escapeHtml(promo.title)}</td>
            <td>${escapeHtml(promo.description)}</td>
            <td>${escapeHtml(promo.discount_type)}</td>
            <td>${promo.discount_type === 'percentage' ? promo.discount_value + '%' : '₱ ' + Number(promo.discount_value).toFixed(2)}</td>
            <td>${formatDate(promo.start_date)}</td>
            <td>${formatDate(promo.end_date)}</td>
            <td>${getStatusBadge(promo.status, promo.is_expired)}</td>
            <td class="table-actions">
              <button class="btn btn-sm btn-outline-secondary btn-toggle-status" data-id="${promo.id}" data-status="${promo.status}">${toggleLabel}</button>
              <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${promo.id}">Edit</button>
              <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${promo.id}">Delete</button>
            </td>
          </tr>
        `;
        }).join('');

        promosTableBody.querySelectorAll('.btn-toggle-status').forEach(btn => btn.addEventListener('click', onTogglePromoStatus));
        promosTableBody.querySelectorAll('.btn-edit').forEach(btn => btn.addEventListener('click', onEditPromo));
        promosTableBody.querySelectorAll('.btn-delete').forEach(btn => btn.addEventListener('click', onDeletePromo));
      }

      function resetForm() {
        currentPromoId = null;
        promoForm.reset();
        document.getElementById('promo-id').value = '';
        modalTitle.textContent = 'Add Promo';
      }

      document.getElementById('btn-add-promo').addEventListener('click', () => {
        resetForm();
        promoModal.show();
      });

      searchInput.addEventListener('input', () => loadPromos());
      statusFilter.addEventListener('change', () => loadPromos());

      async function onEditPromo(event) {
        currentPromoId = event.currentTarget.dataset.id;
        const res = await fetch(`${api}?action=get&id=${currentPromoId}`);
        const data = await res.json();
        if (!data.success) {
          return alert(data.message || 'Unable to load promo.');
        }
        const promo = data.promo;
        document.getElementById('promo-id').value = promo.id;
        document.getElementById('promo-title').value = promo.title;
        document.getElementById('promo-description').value = promo.description;
        document.getElementById('promo-discount-type').value = promo.discount_type;
        document.getElementById('promo-discount-value').value = promo.discount_value;
        document.getElementById('promo-start-date').value = promo.start_date;
        document.getElementById('promo-end-date').value = promo.end_date;
        document.getElementById('promo-status').value = promo.status;
        modalTitle.textContent = 'Edit Promo';
        promoModal.show();
      }

      function onTogglePromoStatus(event) {
        const id = event.currentTarget.dataset.id;
        const status = event.currentTarget.dataset.status === 'active' ? 'inactive' : 'active';
        const formData = new FormData();
        formData.append('action', 'toggle');
        formData.append('id', id);
        formData.append('status', status);
        fetch(api, { method: 'POST', body: formData })
          .then(res => res.json())
          .then(data => {
            if (!data.success) throw new Error(data.message || 'Unable to update status.');
            loadPromos();
          })
          .catch(err => alert(err.message || 'Failed to update promo status.'));
      }

      function onDeletePromo(event) {
        deletePromoId = event.currentTarget.dataset.id;
        deleteModal.show();
      }

      document.getElementById('confirm-delete').addEventListener('click', async () => {
        if (!deletePromoId) {
          return;
        }
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deletePromoId);
        const res = await fetch(api, { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) {
          return alert(data.message || 'Failed to delete promo.');
        }
        deleteModal.hide();
        await loadPromos();
      });

      promoForm.addEventListener('submit', async event => {
        event.preventDefault();
        const formData = new FormData(promoForm);
        formData.append('action', currentPromoId ? 'edit' : 'add');
        const res = await fetch(api, { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) {
          return alert(data.message || 'Failed to save promo.');
        }
        promoModal.hide();
        await loadPromos();
      });

      loadPromos();
    </script>
  </body>
</html>
