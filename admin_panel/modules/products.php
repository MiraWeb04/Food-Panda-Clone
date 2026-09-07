<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /login.php');
    exit;
}

// Fetch categories for filter
$cats = [];
try {
    $stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC');
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $cats = [];
}

?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Products | Admin — Bean Rooted Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin.css">
    <style>
      .product-img{width:56px;height:40px;object-fit:cover;border-radius:6px}
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
          <a class="nav-link active" href="products.php">Products</a>
          <a class="nav-link text-muted" href="categories.php">Categories</a>
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
        <div class="d-flex justify-content-between mb-3">
          <h3>Products</h3>
          <div>
            <button class="btn btn-success" id="btn-add">+ Add Product</button>
          </div>
        </div>

        <div class="row mb-3 g-2">
          <div class="col-md-4">
            <input id="search" class="form-control" placeholder="Search products...">
          </div>
          <div class="col-md-3">
            <select id="filter-category" class="form-select">
              <option value="">All categories</option>
              <?php foreach ($cats as $c): ?>
                <option value="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-5 text-end">
            <nav id="pager" aria-label="Page navigation"></nav>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle" id="products-table">
            <thead>
              <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Description</th>
                <th>Price</th>
                <th>Availability</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

      </main>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="product-form" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title">Add Product</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="id" id="product-id">
              <div class="mb-3">
                <label class="form-label">Name</label>
                <input name="name" id="product-name" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Category</label>
                <select name="category" id="product-category" class="form-select" required>
                  <?php foreach ($cats as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['name']); ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" id="product-desc" class="form-control"></textarea>
              </div>
              <div class="mb-3 row">
                <div class="col-md-6">
                  <label class="form-label">Price</label>
                  <input name="price" id="product-price" type="number" step="0.01" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Availability</label>
                  <select name="available" id="product-available" class="form-select">
                    <option value="1">Available</option>
                    <option value="0">Unavailable</option>
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Image</label>
                <input type="file" name="image" id="product-image" class="form-control" accept="image/*">
                <div class="mt-2"><img id="preview-img" src="#" class="product-img d-none"></div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button class="btn btn-primary" type="submit">Save</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Delete Confirm -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header"><h5 class="modal-title">Delete Product</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body">Are you sure you want to delete this product?</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button id="confirm-delete" type="button" class="btn btn-danger">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      const api = '/admin_panel/api/products_admin.php';
      let currentPage = 1, perPage = 10, totalCount = 0, editingId = null, deleteId = null;
      const tbody = document.querySelector('#products-table tbody');
      const searchInput = document.getElementById('search');
      const filterCat = document.getElementById('filter-category');
      const productModalEl = document.getElementById('productModal');
      const productModal = new bootstrap.Modal(productModalEl);
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
      const productForm = document.getElementById('product-form');
      const modalTitle = productModalEl.querySelector('.modal-title');
      const previewImg = document.getElementById('preview-img');

      async function loadProducts() {
        const q = new URLSearchParams({action:'list', page: currentPage, per_page: perPage, search: searchInput.value || '', category: filterCat.value || ''});
        const res = await fetch(api + '?' + q.toString());
        const data = await res.json();
        totalCount = data.total || 0;
        renderTable(data.products || []);
        renderPager();
      }

      function renderTable(rows) {
        tbody.innerHTML = '';
        for (const r of rows) {
          const imageUrl = r.image ? (r.image.match(/^(https?:\/\/|\/)/i) ? r.image : '/uploads/products/' + r.image) : '/images/bean_rooted_logo.png';
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td><img src="${imageUrl}" class="product-img"></td>
            <td>${escapeHtml(r.name)}</td>
            <td>${escapeHtml(r.category)}</td>
            <td>${escapeHtml(r.description || '')}</td>
            <td>₱ ${Number(r.price).toFixed(2)}</td>
            <td>${r.available == 1 ? '<span class="badge bg-success">Available</span>' : '<span class="badge bg-secondary">Unavailable</span>'}</td>
            <td>
              <button class="btn btn-sm btn-primary btn-edit" data-id="${r.id}">Edit</button>
              <button class="btn btn-sm btn-danger btn-delete" data-id="${r.id}">Delete</button>
            </td>
          `;
          tbody.appendChild(tr);
        }
        document.querySelectorAll('.btn-edit').forEach(b=>b.addEventListener('click', onEdit));
        document.querySelectorAll('.btn-delete').forEach(b=>b.addEventListener('click', onDelete));
      }

      function renderPager(){
        const pages = Math.ceil(totalCount / perPage) || 1;
        const pager = document.getElementById('pager');
        let html = '<ul class="pagination justify-content-end mb-0">';
        for(let i=1;i<=pages;i++){
          html += `<li class="page-item ${i===currentPage?'active':''}"><a class="page-link" href="#" data-page="${i}">${i}</a></li>`;
        }
        html += '</ul>';
        pager.innerHTML = html;
        pager.querySelectorAll('.page-link').forEach(a=>a.addEventListener('click', (e)=>{e.preventDefault(); currentPage = parseInt(a.dataset.page); loadProducts();}));
      }

      function escapeHtml(s){ return String(s||'').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

      function resetForm() {
        editingId = null;
        productForm.reset();
        document.getElementById('product-id').value = '';
        modalTitle.textContent = 'Add Product';
        previewImg.src = '#';
        previewImg.classList.add('d-none');
      }

      document.getElementById('btn-add').addEventListener('click', ()=>{
        resetForm();
        productModal.show();
      });

      searchInput.addEventListener('input', ()=>{currentPage=1; loadProducts();});
      filterCat.addEventListener('change', ()=>{currentPage=1; loadProducts();});
      document.getElementById('product-image').addEventListener('change', event => {
        const file = event.target.files[0];
        if (file) {
          previewImg.src = URL.createObjectURL(file);
          previewImg.classList.remove('d-none');
        }
      });
      productModalEl.addEventListener('hidden.bs.modal', resetForm);

      async function onEdit(e){
        const id = e.currentTarget.dataset.id; editingId = id;
        const res = await fetch(api+'?action=get&id='+id);
        const data = await res.json();
        if(!data.success) return alert(data.message||'Failed');
        const p = data.product;
        document.getElementById('product-id').value = p.id;
        document.getElementById('product-name').value = p.name;
        document.getElementById('product-category').value = p.category;
        document.getElementById('product-desc').value = p.description;
        document.getElementById('product-price').value = p.price;
        document.getElementById('product-available').value = p.available;
        if(p.image){ const img = document.getElementById('preview-img'); img.src = p.image.match(/^(https?:\/\/|\/)/i) ? p.image : '/uploads/products/' + p.image; img.classList.remove('d-none'); }
        new bootstrap.Modal(document.getElementById('productModal')).show();
      }

      function onDelete(e){ deleteId = e.currentTarget.dataset.id; new bootstrap.Modal(document.getElementById('deleteModal')).show(); }
      document.getElementById('confirm-delete').addEventListener('click', async ()=>{
        if(!deleteId) return; const fd = new FormData(); fd.append('action','delete'); fd.append('id',deleteId);
        const res = await fetch(api,{method:'POST',body:fd}); const data = await res.json(); if(!data.success) return alert(data.message||'Failed');
        new bootstrap.Modal(document.getElementById('deleteModal')).hide(); loadProducts(); // also client will pick up changes
      });

      document.getElementById('product-form').addEventListener('submit', async (ev)=>{
        ev.preventDefault(); const fd = new FormData(ev.target); fd.append('action', editingId? 'edit':'add');
        const res = await fetch(api,{method:'POST',body:fd}); const data = await res.json();
        if(!data.success){ alert(data.message||'Failed'); return; }
        bootstrap.Modal.getInstance(document.getElementById('productModal')).hide(); loadProducts();
      });

      loadProducts();
    </script>
  </body>
</html>
