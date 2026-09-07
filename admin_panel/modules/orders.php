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
    <title>Orders | Admin — Bean Rooted Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin.css">
    <style>
      .table-actions button { margin-right: 0.35rem; }
      .order-row .small { font-size: .8rem; }
      .modal-lg { max-width: 1000px; }
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
          <a class="nav-link text-muted" href="promos.php">Promos</a>
          <a class="nav-link active" href="orders.php">Orders</a>
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
            <h3>Orders</h3>
            <p class="text-muted mb-0">Monitor customer orders submitted from the client side.</p>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <input type="search" id="search" class="form-control" placeholder="Search orders by tracking, name, contact or email...">
          </div>
          <div class="col-md-2">
            <select id="filter-status" class="form-select">
              <option value="">All statuses</option>
              <option value="Pending">Pending</option>
              <option value="Preparing">Preparing</option>
              <option value="Out for Delivery">Out for Delivery</option>
              <option value="Delivered">Delivered</option>
              <option value="Rejected">Rejected</option>
            </select>
          </div>
          <div class="col-md-2">
            <select id="filter-payment" class="form-select">
              <option value="">All payments</option>
              <option value="cash">Cash on Delivery</option>
              <option value="online">Online Payment</option>
            </select>
          </div>
          <div class="col-md-2">
            <input type="date" id="start-date" class="form-control" placeholder="Start date">
          </div>
          <div class="col-md-2">
            <input type="date" id="end-date" class="form-control" placeholder="End date">
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle" id="orders-table">
            <thead>
              <tr>
                <th>Tracking #</th>
                <th>Customer</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Payment</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date Ordered</th>
                <th>Rider</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <nav aria-label="Page navigation" class="mt-3">
          <ul class="pagination" id="pagination"></ul>
        </nav>

      </main>
    </div>

    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Order Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body" id="order-details-body">
            <!-- populated by JS -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Proof Modal (payment/delivery) -->
    <div class="modal fade" id="proofModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Proof</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center" id="proof-body">
            <!-- image goes here -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      const api = '/admin_panel/api/orders_admin.php';
      const tbody = document.querySelector('#orders-table tbody');
      const searchEl = document.getElementById('search');
      const filterStatus = document.getElementById('filter-status');
      const filterPayment = document.getElementById('filter-payment');
      const startDate = document.getElementById('start-date');
      const endDate = document.getElementById('end-date');
      const paginationEl = document.getElementById('pagination');
      const orderDetailsModal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
      const proofModal = new bootstrap.Modal(document.getElementById('proofModal'));
      let currentPage = 1;
      let totalPages = 1;

      function formatCurrency(v){ return '₱ ' + Number(v || 0).toFixed(2); }
      function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

      async function loadOrders(page=1){
        currentPage = page;
        const q = new URLSearchParams({
          action: 'list',
          page: page,
          perPage: 20,
          search: searchEl.value.trim(),
          status: filterStatus.value,
          payment: filterPayment.value,
          start_date: startDate.value,
          end_date: endDate.value
        });
        const res = await fetch(`${api}?${q}`);
        const data = await res.json();
        if (!data.success) return alert(data.message || 'Failed to load orders');
        renderTable(data.orders || []);
        totalPages = data.totalPages || 1;
        renderPagination();
      }

      function badgeForStatus(s){
        const map = {
          'Pending':'bg-secondary',
          'Preparing':'bg-info text-dark',
          'Out for Delivery':'bg-warning text-dark',
          'Delivered':'bg-success',
          'Rejected':'bg-danger'
        };
        return `<span class="badge ${map[s] || 'bg-secondary'}">${escapeHtml(s)}</span>`;
      }

      function renderTable(rows){
        tbody.innerHTML = rows.map(r=>{
          return `
            <tr class="order-row">
              <td>${escapeHtml(r.tracking_code || r.order_number || r.id)}</td>
              <td>${escapeHtml(r.customer_name || r.customer)}</td>
              <td>${escapeHtml(r.contact_number || r.phone)}</td>
              <td>${escapeHtml(r.address || r.delivery_address || r.order_notes || '')}</td>
              <td>${escapeHtml(r.payment_method || '')}</td>
              <td>${formatCurrency(r.discounted_total ?? r.total_amount ?? r.order_total ?? 0)}</td>
              <td>${badgeForStatus(r.status || r.order_status || 'Pending')}</td>
              <td>${escapeHtml((r.created_at||r.ordered_at||'').split(' ')[0])}</td>
              <td>${escapeHtml(r.assigned_rider_id ? r.assigned_rider_id : '')}</td>
              <td class="table-actions">
                <button class="btn btn-sm btn-outline-primary btn-view" data-id="${r.id}">View</button>
                <button class="btn btn-sm btn-outline-secondary btn-proof-payment" data-id="${r.id}">Proof</button>
                <button class="btn btn-sm btn-outline-success btn-proof-delivery" data-id="${r.id}">Delivery</button>
              </td>
            </tr>
          `;
        }).join('');

        tbody.querySelectorAll('.btn-view').forEach(b=>b.addEventListener('click', onView));
        tbody.querySelectorAll('.btn-proof-payment').forEach(b=>b.addEventListener('click', onProofPayment));
        tbody.querySelectorAll('.btn-proof-delivery').forEach(b=>b.addEventListener('click', onProofDelivery));
      }

      function renderPagination(){
        const pages = [];
        for(let i=1;i<=totalPages;i++){
          pages.push(`<li class="page-item ${i===currentPage?'active':''}"><button class="page-link" data-page="${i}">${i}</button></li>`);
        }
        paginationEl.innerHTML = pages.join('');
        paginationEl.querySelectorAll('button.page-link').forEach(b=>b.addEventListener('click', e=> loadOrders(Number(e.currentTarget.dataset.page))));
      }

      async function onView(e){
        const id = e.currentTarget.dataset.id;
        const res = await fetch(`${api}?action=get&id=${id}`);
        const data = await res.json();
        if (!data.success) return alert(data.message || 'Unable to load order');
        const o = data.order;
        const items = data.items || [];
        const itemsHtml = items.map(it=>`<tr><td>${escapeHtml(it.product_name)}</td><td>${escapeHtml(it.quantity)}</td><td>${formatCurrency(it.unit_price)}</td><td>${formatCurrency(it.line_total)}</td></tr>`).join('');
        const html = `
          <h5>Tracking: ${escapeHtml(o.tracking_code || o.order_number || '')}</h5>
          <p><strong>Customer:</strong> ${escapeHtml(o.customer_name||o.customer||'')}</p>
          <p><strong>Contact:</strong> ${escapeHtml(o.contact_number||o.phone||'')}</p>
          <p><strong>Address:</strong> ${escapeHtml(o.address||o.delivery_address||'')}</p>
          <p><strong>Payment:</strong> ${escapeHtml(o.payment_method||'')}</p>
          <p><strong>Status:</strong> ${escapeHtml(o.status||o.order_status||'')}</p>
          <p><strong>Date:</strong> ${escapeHtml(o.created_at||o.ordered_at||'')}</p>
          <hr>
          <h6>Items</h6>
          <div class="table-responsive"><table class="table table-sm"><thead><tr><th>Product</th><th>Qty</th><th>Unit</th><th>Line</th></tr></thead><tbody>${itemsHtml}</tbody></table></div>
          <div class="text-end"><strong>Total: ${formatCurrency(o.discounted_total ?? o.total_amount ?? o.order_total ?? 0)}</strong></div>
        `;
        document.getElementById('order-details-body').innerHTML = html;
        orderDetailsModal.show();
      }

      async function onProofPayment(e){
        const id = e.currentTarget.dataset.id;
        const res = await fetch(`${api}?action=get&id=${id}`);
        const data = await res.json();
        if (!data.success) return alert(data.message || 'Unable to load proof');
        const o = data.order;
        const img = o.receipt_image || o.payment_receipt_url || o.payment_receipt || '';
        const html = img ? `<img src="/${escapeHtml(img)}" class="img-fluid" alt="Proof of payment">` : '<p class="text-muted">No proof uploaded.</p>';
        document.getElementById('proof-body').innerHTML = html;
        proofModal.show();
      }

      async function onProofDelivery(e){
        const id = e.currentTarget.dataset.id;
        const res = await fetch(`${api}?action=get&id=${id}`);
        const data = await res.json();
        if (!data.success) return alert(data.message || 'Unable to load proof');
        const o = data.order;
        const img = o.delivery_proof_image || o.delivery_proof_url || '';
        const html = img ? `<img src="/${escapeHtml(img)}" class="img-fluid" alt="Proof of delivery">` : '<p class="text-muted">No delivery proof uploaded yet.</p>';
        document.getElementById('proof-body').innerHTML = html;
        proofModal.show();
      }

      searchEl.addEventListener('input', () => loadOrders(1));
      filterStatus.addEventListener('change', () => loadOrders(1));
      filterPayment.addEventListener('change', () => loadOrders(1));
      startDate.addEventListener('change', () => loadOrders(1));
      endDate.addEventListener('change', () => loadOrders(1));

      // initial load
      loadOrders(1);

      // Server-Sent Events for live updates
      if (window.EventSource) {
        try {
          const es = new EventSource('/admin_panel/api/orders_events.php');
          es.addEventListener('orders_updated', e => {
            try {
              const payload = JSON.parse(e.data);
              loadOrders(currentPage);
            } catch (err) { loadOrders(currentPage); }
          });
        } catch (err) {
          // fall back to polling
          setInterval(() => loadOrders(currentPage), 10000);
        }
      } else {
        setInterval(() => loadOrders(currentPage), 10000);
      }
    </script>
  </body>
</html>
