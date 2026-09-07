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
    <title>Categories | Admin — Bean Rooted Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin.css">
    <style>
      .table-actions button { margin-right: 0.35rem; }
      .badge-status { cursor: pointer; }
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
          <a class="nav-link active" href="categories.php">Categories</a>
          <a class="nav-link text-muted" href="promos.php">Promos</a>
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
            <h3>Categories</h3>
            <p class="text-muted mb-0">Manage your menu categories and sync them with the client menu.</p>
          </div>
          <button class="btn btn-success" id="btn-add-category">+ Add Category</button>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <input type="search" id="search" class="form-control" placeholder="Search categories...">
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle" id="categories-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Products</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </main>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="categoryModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <form id="category-form">
            <div class="modal-header">
              <h5 class="modal-title">Add Category</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" id="category-id" name="id">
              <div class="mb-3">
                <label class="form-label">Category Name</label>
                <input type="text" id="category-name" name="name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select id="category-status" name="status" class="form-select">
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Delete Category</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete this category? This will not delete the products, but they will no longer be linked to an active category.
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
      const api = '/admin_panel/api/categories_admin.php';
      const tableBody = document.querySelector('#categories-table tbody');
      const searchInput = document.getElementById('search');
      const categoryModalEl = document.getElementById('categoryModal');
      const categoryModal = new bootstrap.Modal(categoryModalEl);
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
      const categoryForm = document.getElementById('category-form');
      const modalTitle = categoryModalEl.querySelector('.modal-title');
      let categories = [];
      let editingId = null;
      let deleteId = null;

      async function loadCategories() {
        const res = await fetch(api + '?action=list&search=' + encodeURIComponent(searchInput.value || ''));
        const data = await res.json();
        categories = data.categories || [];
        renderCategories();
      }

      function renderCategories() {
        tableBody.innerHTML = categories.map(cat => `
          <tr>
            <td>${escapeHtml(cat.name)}</nobr></td>
            <td>${cat.product_count}</td>
            <td><span class="badge badge-status ${cat.status === 'active' ? 'bg-success' : 'bg-secondary'}" data-action="toggle" data-id="${cat.id}">${cat.status === 'active' ? 'Active' : 'Inactive'}</span></td>
            <td class="table-actions">
              <button class="btn btn-sm btn-outline-primary btn-edit" data-id="${cat.id}">Edit</button>
              <button class="btn btn-sm btn-outline-danger btn-delete" data-id="${cat.id}">Delete</button>
            </td>
          </tr>
        `).join('');
        tableBody.querySelectorAll('.btn-edit').forEach(btn => btn.addEventListener('click', onEdit));
        tableBody.querySelectorAll('.btn-delete').forEach(btn => btn.addEventListener('click', onDelete));
        tableBody.querySelectorAll('.badge-status').forEach(badge => badge.addEventListener('click', onToggleStatus));
      }

      function escapeHtml(text) {
        return String(text || '').replace(/[&<>\"]/g, tag => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[tag]));
      }

      function resetForm() {
        editingId = null;
        categoryForm.reset();
        document.getElementById('category-id').value = '';
        modalTitle.textContent = 'Add Category';
      }

      document.getElementById('btn-add-category').addEventListener('click', () => {
        resetForm();
        categoryModal.show();
      });

      searchInput.addEventListener('input', () => loadCategories());

      async function onEdit(event) {
        const id = event.currentTarget.dataset.id;
        const row = categories.find(cat => String(cat.id) === String(id));
        if (!row) return;
        editingId = id;
        document.getElementById('category-id').value = row.id;
        document.getElementById('category-name').value = row.name;
        document.getElementById('category-status').value = row.status;
        modalTitle.textContent = 'Edit Category';
        categoryModal.show();
      }

      function onDelete(event) {
        deleteId = event.currentTarget.dataset.id;
        deleteModal.show();
      }

      async function onToggleStatus(event) {
        const id = event.currentTarget.dataset.id;
        const row = categories.find(cat => String(cat.id) === String(id));
        if (!row) return;
        const nextStatus = row.status === 'active' ? 'inactive' : 'active';
        const formData = new FormData();
        formData.append('action', 'toggle');
        formData.append('id', id);
        formData.append('status', nextStatus);
        const res = await fetch(api, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) loadCategories();
        else alert(data.message || 'Failed to toggle status');
      }

      document.getElementById('confirm-delete').addEventListener('click', async () => {
        if (!deleteId) return;
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', deleteId);
        const res = await fetch(api, { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          deleteModal.hide();
          await loadCategories();
        } else {
          alert(data.message || 'Failed to delete');
        }
      });

      categoryForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const formData = new FormData(categoryForm);
        formData.append('action', editingId ? 'edit' : 'add');
        const res = await fetch(api, { method: 'POST', body: formData });
        const data = await res.json();
        if (!data.success) {
          alert(data.message || 'Failed to save');
          return;
        }
        categoryModal.hide();
        await loadCategories();
      });

      loadCategories();
    </script>
  </body>
</html>
