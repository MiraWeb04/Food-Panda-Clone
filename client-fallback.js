(function(){
  async function fetchJson(url){ try{ const r=await fetch(url); return await r.json(); }catch(e){return null;} }
  function escapeHtml(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
  function normalizeCategory(value){ return String(value||'').toLowerCase().replace(/\s*&\s*/g,'-').replace(/[()]/g,'').replace(/\s+/g,'-').replace(/[^a-z0-9-]/g,'').replace(/-+/g,'-').replace(/^-|-$/g, ''); }

  async function renderCategories(){
    const el = document.getElementById('category-bar');
    if(!el) return;
    const data = await fetchJson('/api/categories.php');
    if(!data || !data.success) return;
    const cats = data.categories || [];
    el.innerHTML = `<button class="category active" data-category="all">All</button>` + cats.map(cat=>`<button class="category" data-category="${normalizeCategory(cat.name)}" data-status="${cat.status}">${escapeHtml(cat.name)}</button>`).join('');
    el.querySelectorAll('.category').forEach(btn=>btn.addEventListener('click', ()=>{
      el.querySelectorAll('.category').forEach(b=>b.classList.remove('active'));
      btn.classList.add('active');
      const category = btn.dataset.category;
      filterProducts(category);
    }));
  }

  let allProducts = [];
  function renderProductsList(list){
    const wrap = document.getElementById('products');
    if(!wrap) return;
    wrap.innerHTML = list.map(p=>`
      <article class="card">
        <div class="card-media">
          <img src="${p.image||'/images/bean_rooted_logo.png'}" alt="${escapeHtml(p.name)}" class="product-img" />
        </div>
        <div class="card-body">
          <div class="card-topline">
            <span class="badge">${escapeHtml(p.category || 'Menu')}</span>
            <span class="card-price">₱ ${Number(p.price).toFixed(2)}</span>
          </div>
          <h3>${escapeHtml(p.name)}</h3>
          <p>${escapeHtml(p.description)}</p>
          <div class="card-footer">
            <span class="card-meta">Freshly prepared</span>
            <button class="add-btn">Add</button>
          </div>
        </div>
      </article>
    `).join('');
  }
  function filterProducts(category){ if(category==='all'){ renderProductsList(allProducts); return; } const filtered = allProducts.filter(p=>{ const slug = String(p.category||'').toLowerCase().replace(/\s+/g,'-'); return slug===category; }); renderProductsList(filtered); }

  async function loadProducts(){ const data = await fetchJson('/api/products.php'); if(!data || !data.success) return; allProducts = data.products || []; renderProductsList(allProducts); }

  // run after DOM loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', ()=>{ renderCategories(); loadProducts(); });
  } else {
    renderCategories(); loadProducts();
  }
})();
