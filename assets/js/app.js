const ROOT = document.body.dataset.root;
const CSRF = document.body.dataset.csrf;

// Avisos flotantes creados desde JavaScript (abajo a la derecha, 3 segundos)
function toast(msg, err) {
  const box = document.getElementById('toasts');
  if (!box) return;
  const t = document.createElement('div');
  t.className = 'flash' + (err ? ' err' : '');
  t.textContent = msg;
  box.appendChild(t);
  setTimeout(() => { t.classList.add('leaving'); setTimeout(() => t.remove(), 400); }, 3000);
}
function setBadge(id, n, parentSel) {
  let b = document.getElementById(id);
  if (!b && n > 0 && parentSel) {
    b = document.createElement('span');
    b.className = 'badge'; b.id = id;
    document.querySelector(parentSel)?.append(' ', b);
  }
  if (!b) return;
  b.textContent = n;
  b.hidden = n <= 0;
}

// Carrito
const cartTimers = {};
async function updateCartQty(form) {
  const key = form.querySelector('[name="key"]').value;
  clearTimeout(cartTimers[key]);
  cartTimers[key] = setTimeout(async () => {
    const r = await fetch(location.href, { method: 'POST', body: new FormData(form) });
    if (!r.ok) { toast('No se pudo actualizar el carrito.', true); return; }
    const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
    const nuevo = doc.querySelector('.cart-layout');
    const actual = document.querySelector('.cart-layout');
    if (nuevo && actual) actual.replaceWith(nuevo);
    setBadge('cartcount', parseInt(doc.getElementById('cartcount')?.textContent) || 0);
  }, 350);
}
document.addEventListener('click', (ev) => {
  const b = ev.target.closest('.ci-qty .step');
  if (!b) return;
  const inp = b.parentNode.querySelector('input');
  const v = Math.min(+inp.max, Math.max(+inp.min, (parseInt(inp.value) || 1) + +b.dataset.d));
  if (v === +inp.value) return;
  inp.value = v;
  updateCartQty(b.closest('form'));
});
document.addEventListener('change', (ev) => {
  const inp = ev.target.closest('.ci-qty input[name="qty"]');
  if (!inp) return;
  inp.value = Math.min(+inp.max, Math.max(+inp.min, parseInt(inp.value) || 1));
  updateCartQty(inp.form);
});

// Agregar al carrito
document.addEventListener('click', async (ev) => {
  const b = ev.target.closest('.addcart');
  if (!b) return;
  const qty = document.getElementById('qty')?.value || 1;
  const r = await fetch(ROOT + 'api/carrito.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF': CSRF },
    body: 'id=' + encodeURIComponent(b.dataset.id) + '&qty=' + encodeURIComponent(qty) + '&color=' + encodeURIComponent(color) + (colg ? '&colg=' + encodeURIComponent(colg.id) : '')
  });
  if (!r.ok) { toast('No se pudo agregar al carrito.', true); return; }
  const d = await r.json();
  setBadge('cartcount', d.count);
  toast('Agregado al carrito: ' + d.name);
});

// Me gusta (corazón)
document.addEventListener('click', async (ev) => {
  const h = ev.target.closest('.heart');
  if (!h) return;
  ev.preventDefault();
  const r = await fetch(ROOT + 'api/favorito.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF': CSRF },
    body: 'id=' + encodeURIComponent(h.dataset.id)
  });
  if (!r.ok) return;
  const d = await r.json();
  h.classList.toggle('on', d.fav);
  const fc = parseInt(document.getElementById('favcount')?.textContent) || 0;
  setBadge('favcount', Math.max(0, fc + (d.fav ? 1 : -1)), '.menu a[href$="favoritos.php"]');
  const s = h.querySelector('span');
  if (s) s.textContent = d.fav ? 'En Me gusta' : 'Guardar';
  if (!d.fav && location.pathname.endsWith('favoritos.php')) h.closest('.card')?.remove();
});

// Validación de formularios: aviso flotante abajo a la derecha
function fieldName(el) {
  const l = el.closest('label');
  if (!l) return el.name || 'Este campo';
  const c = l.cloneNode(true);
  c.querySelectorAll('input,select,textarea').forEach(x => x.remove());
  return c.textContent.trim().replace(/\s*\(.*\)\s*$/, '') || 'Este campo';
}
function fieldMessage(el) {
  if (el.dataset.msg) return el.dataset.msg;
  const n = fieldName(el), v = el.validity;
  if (v.valueMissing) return el.type === 'checkbox' ? 'Debes marcar la casilla para continuar.' : 'Completa el campo «' + n + '».';
  if (v.typeMismatch && el.type === 'email') return 'Escribe un correo electrónico válido.';
  if (v.tooShort) return '«' + n + '» debe tener al menos ' + el.minLength + ' caracteres.';
  if (v.rangeUnderflow) return '«' + n + '» debe ser al menos ' + el.min + '.';
  if (v.rangeOverflow) return '«' + n + '» no puede ser mayor a ' + el.max + '.';
  return el.validationMessage || 'Revisa el campo «' + n + '».';
}
document.querySelectorAll('form').forEach(f => { f.noValidate = true; });
document.addEventListener('submit', (ev) => {
  const f = ev.target;
  if (!f.checkValidity()) {
    ev.preventDefault();
    const bad = f.querySelector(':invalid');
    toast(fieldMessage(bad), true);
    bad.focus();
  }
}, true);

// Menú móvil, quitar color en admin y confirmaciones (sin scripts en línea por la CSP)
const closeMenu = () => document.body.classList.remove('menu-open');
document.querySelector('.burger')?.addEventListener('click', () => document.body.classList.toggle('menu-open'));
document.querySelector('.menu-close')?.addEventListener('click', closeMenu);
document.querySelector('.menu-backdrop')?.addEventListener('click', closeMenu);
document.querySelectorAll('.menu a').forEach(a => a.addEventListener('click', closeMenu));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeMenu(); });
addEventListener('resize', () => { if (innerWidth > 800) closeMenu(); });
document.addEventListener('click', (ev) => {
  const x = ev.target.closest('.crow .x');
  if (x) x.parentNode.remove();
});
// Cuadro de confirmación con el estilo de la página (en lugar del aviso del navegador)
function confirmar(msg, okText, danger) {
  return new Promise((resolve) => {
    const ov = document.createElement('div');
    ov.className = 'modal-overlay';
    ov.innerHTML = '<div class="modal" role="dialog" aria-modal="true"><p></p><div class="modal-actions">' +
      '<button type="button" class="btn small ghost" data-r="0">Cancelar</button>' +
      '<button type="button" class="btn small' + (danger ? ' danger' : '') + '" data-r="1"></button></div></div>';
    ov.querySelector('p').textContent = msg;
    ov.querySelector('[data-r="1"]').textContent = okText || 'Aceptar';
    const prevFocus = document.activeElement;
    const close = (r) => { document.removeEventListener('keydown', onKey); ov.remove(); prevFocus?.focus?.(); resolve(r); };
    const onKey = (e) => { if (e.key === 'Escape') close(false); };
    ov.addEventListener('click', (e) => {
      if (e.target === ov) close(false);
      const b = e.target.closest('[data-r]');
      if (b) close(b.dataset.r === '1');
    });
    document.addEventListener('keydown', onKey);
    document.body.appendChild(ov);
    ov.querySelector('[data-r="0"]').focus();
  });
}
document.addEventListener('submit', async (ev) => {
  const f = ev.target;
  const m = f.dataset?.confirm;
  if (!m || f.dataset.confirmed) return;
  ev.preventDefault();
  if (await confirmar(m, f.dataset.ok, f.dataset.danger !== '0')) {
    f.dataset.confirmed = '1';
    f.submit();
  }
});

// Aviso de cookies (solo informativo: únicamente usamos la cookie técnica de sesión)
const cbar = document.getElementById('cookiebar');
try { if (cbar && !localStorage.getItem('ph_cookies_ok')) cbar.hidden = false; } catch (e) { if (cbar) cbar.hidden = false; }
document.getElementById('cookieok')?.addEventListener('click', () => {
  try { localStorage.setItem('ph_cookies_ok', '1'); } catch (e) {}
  cbar.hidden = true;
});

// La alerta desaparece a los 3 segundos
// Todas las alertas (avisos, errores, notificaciones) se muestran abajo a la derecha
const toasts = document.getElementById('toasts');
if (toasts) {
  document.querySelectorAll('main .error, main .notice').forEach(n => toasts.appendChild(n));
  toasts.querySelectorAll('.flash, .error, .notice').forEach(t => {
    const ms = t.classList.contains('flash') && !t.classList.contains('err') ? 3000 : 5000; // errores: un poco más de tiempo para leerlos
    setTimeout(() => { t.classList.add('leaving'); setTimeout(() => t.remove(), 400); }, ms);
  });
}

// Menú transparente sobre la portada; sólido cuando la imagen ya pasó por completo
const hero = document.getElementById('hero');
const header = document.querySelector('.site-header');
if (hero && header) {
  const check = () => header.classList.toggle('solid', hero.getBoundingClientRect().bottom <= header.offsetHeight);
  addEventListener('scroll', check, { passive: true });
  addEventListener('resize', check);
  check();
}

// Selector de color en la página de producto
const sws = document.querySelectorAll('.sw');
const wa = document.getElementById('wa');
let color = sws[0]?.dataset.name || '';
// Colgadera elegida (solo en hamacas): por defecto la primera
const colgBox = document.getElementById('colg-carousel');
let colg = colgBox ? { id: colgBox.dataset.default, name: colgBox.dataset.defaultName } : null;
// "Pedir por WhatsApp" lleva primero a elegir la dirección de entrega (pedido.php)
function updateWa() {
  if (!wa) return;
  const qty = Math.max(1, Math.min(20, parseInt(document.getElementById('qty')?.value) || 1));
  wa.href = ROOT + 'pedido.php?producto=' + encodeURIComponent(wa.dataset.id) + '&color=' + encodeURIComponent(color) + '&qty=' + qty + (colg ? '&colg=' + encodeURIComponent(colg.id) : '');
}
document.getElementById('qty')?.addEventListener('input', () => updateWa());
sws.forEach(b => b.addEventListener('click', () => {
  sws.forEach(x => x.classList.remove('on'));
  b.classList.add('on');
  color = b.dataset.name;
  document.getElementById('colname').textContent = color;
  document.querySelectorAll('#pmedia .hsvg [fill]').forEach(el => {
    if (el.getAttribute('fill') !== 'none') el.setAttribute('fill', b.dataset.hex);
  });
  document.querySelectorAll('#pmedia .hsvg [stroke]').forEach(el => {
    if (el.getAttribute('stroke') !== '#8a7a62' && el.getAttribute('stroke') !== 'none') el.setAttribute('stroke', b.dataset.hex);
  });
  updateWa();
}));
updateWa();

// Producto: elegir el tipo de colgadera en el carrusel
document.addEventListener('click', (ev) => {
  const b = ev.target.closest('.pick-colg');
  if (!b) return;
  colg = { id: b.dataset.id, name: b.dataset.name };
  document.querySelectorAll('.colg-card').forEach((card) => {
    const on = card.dataset.id === colg.id;
    card.classList.toggle('sel', on);
    card.querySelector('.pick-colg').textContent = on ? '✓ Elegida' : 'Elegir';
  });
  const nm = document.getElementById('colgname');
  if (nm) nm.textContent = colg.name;
  updateWa();
  toast('Colgadera elegida: ' + colg.name);
});

// Producto: mostrar 6 colores y un botón para ver todos
const moreBtn = document.getElementById('morecolors');
moreBtn?.addEventListener('click', () => {
  const box = moreBtn.closest('.swatches');
  const open = box.classList.toggle('open');
  moreBtn.textContent = open ? moreBtn.dataset.less : moreBtn.dataset.more;
  moreBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
});

// Admin: nueva categoría
const catsel = document.getElementById('catsel');
const catnew = document.getElementById('catnew');
if (catsel && catnew) catsel.addEventListener('change', () => {
  const nueva = catsel.value === '__nueva';
  catnew.hidden = !nueva;
  catnew.required = nueva;
  if (nueva) catnew.focus();
});

// Admin: ocultar "Capacidad de peso" en categorías donde no aplica (Accesorios, Lámparas)
const caplabel = document.getElementById('caplabel');
if (catsel && caplabel) {
  const hideFor = caplabel.dataset.hideFor.split(',');
  const toggleCap = () => {
    const ocultar = hideFor.includes(catsel.value);
    caplabel.hidden = ocultar;
    if (ocultar) caplabel.querySelector('input').value = '';
  };
  catsel.addEventListener('change', toggleCap);
  toggleCap();
}

// Admin: vista previa de la foto
const imgfile = document.getElementById('imgfile');
if (imgfile) {
  const prev = document.getElementById('preview');
  const previmg = document.getElementById('previmg');
  const showPrev = () => {
    const f = imgfile.files[0];
    if (previmg.src.startsWith('blob:')) URL.revokeObjectURL(previmg.src);
    if (f) { previmg.src = URL.createObjectURL(f); prev.hidden = false; }
    else { previmg.removeAttribute('src'); prev.hidden = true; }
  };
  imgfile.addEventListener('change', showPrev);
  document.getElementById('clearimg').addEventListener('click', () => { imgfile.value = ''; showPrev(); });
  const quitar = document.getElementById('quitar');
  quitar?.addEventListener('change', () => {
    document.querySelector('#curimg img').style.opacity = quitar.checked ? '.3' : '1';
  });
}

// Admin: colores rápidos
document.addEventListener('click', (ev) => {
  const chip = ev.target.closest('.palette .chip');
  if (!chip) return;
  const cols = document.getElementById('cols');
  const ya = [...cols.querySelectorAll('input[name="c_nombre[]"]')].some(i => i.value.trim().toLowerCase() === chip.dataset.name.toLowerCase());
  if (ya) { toast('«' + chip.dataset.name + '» ya está en la lista.', true); return; }
  // Reutiliza la primera fila vacía si existe
  let row = [...cols.querySelectorAll('.crow')].find(r => r.querySelector('input[name="c_nombre[]"]').value.trim() === '');
  if (!row) { document.getElementById('addcol').click(); row = cols.lastElementChild; }
  row.querySelector('input[name="c_nombre[]"]').value = chip.dataset.name;
  row.querySelector('input[type=color]').value = chip.dataset.hex;
});

// Admin: agregar color
document.getElementById('addcol')?.addEventListener('click', () => {
  const d = document.createElement('div');
  d.className = 'crow';
  d.innerHTML = '<input name="c_nombre[]" placeholder="Nombre del color"><input type="color" name="c_hex[]" value="#c99a2e"><button type="button" class="x">×</button>';
  document.getElementById('cols').appendChild(d);
});


// Carrusel infinito estilo "coverflow": un producto al centro, los de los lados difuminados.
// Avanza solo; se pausa al pasar el cursor o tocar; también se puede deslizar o usar las flechas.
document.querySelectorAll('.carousel').forEach((c) => {
  const track = c.querySelector('.cf-track');
  const items = [...track.children];
  const n = items.length;
  if (!n) return;
  let cur = 0, timer = null, paused = false;
  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

  function layout() {
    const cw = c.clientWidth;
    const w = c.dataset.size === 'sm' ? Math.min(180, Math.round(cw * 0.46)) : Math.min(280, Math.round(cw * (cw < 600 ? 0.58 : 0.34)));
    track.style.setProperty('--cf-w', w + 'px');
    track.style.height = Math.max(...items.map(i => i.offsetHeight)) + 'px';
    place();
  }
  function place() {
    const w = items[0].offsetWidth;
    items.forEach((el, i) => {
      let off = ((i - cur) % n + n) % n;       // 0..n-1
      if (off > n / 2) off -= n;                // -n/2..n/2
      const abs = Math.abs(off);
      el.classList.toggle('cf-center', off === 0);
      el.classList.toggle('cf-side', abs === 1);
      el.classList.toggle('cf-hidden', abs > 1);
      el.dataset.off = off;
      const dir = Math.sign(off);
      const x = abs === 0 ? 0 : dir * (w * 0.86 + (abs - 1) * w * 0.3);
      const s = abs === 0 ? 1 : 0.82;
      el.style.transform = 'translateX(' + x + 'px) scale(' + s + ')';
      el.style.zIndex = String(10 - abs);
      el.setAttribute('aria-hidden', abs > 1 ? 'true' : 'false');
    });
  }
  const go = (d) => { cur = ((cur + d) % n + n) % n; place(); };
  const goTo = (i) => { cur = i; place(); };
  const start = () => { if (reduce || timer || c.dataset.auto === '0') return; timer = setInterval(() => { if (!paused && !document.hidden) go(1); }, 3200); };

  c.querySelector('.car-btn.prev').addEventListener('click', () => go(-1));
  c.querySelector('.car-btn.next').addEventListener('click', () => go(1));
  c.addEventListener('mouseenter', () => { paused = true; });
  c.addEventListener('mouseleave', () => { paused = false; });

  // Tocar una tarjeta lateral la trae al centro (sin abrir el producto ni marcar el corazón)
  track.addEventListener('click', (ev) => {
    const card = ev.target.closest('.card');
    if (!card || card.classList.contains('cf-center')) return;
    ev.preventDefault();
    ev.stopPropagation();
    if (card.classList.contains('cf-side')) goTo(items.indexOf(card));
  }, true);

  // Deslizar con el dedo
  let x0 = null, moved = false;
  c.addEventListener('touchstart', (e) => { x0 = e.touches[0].clientX; paused = true; moved = false; }, { passive: true });
  c.addEventListener('touchmove', (e) => { if (x0 !== null && Math.abs(e.touches[0].clientX - x0) > 40) moved = true; }, { passive: true });
  c.addEventListener('touchend', (e) => {
    if (x0 !== null && moved) { const dx = e.changedTouches[0].clientX - x0; go(dx < 0 ? 1 : -1); }
    x0 = null;
    setTimeout(() => { paused = false; }, 2500);
  });

  addEventListener('resize', layout);
  addEventListener('load', layout);
  layout();
  start();
});

// Reseñas: abrir/cerrar formulario, contador de caracteres, vista previa de fotos y ampliar fotos
const rvForm = document.getElementById('nueva-resena');
if (rvForm) {
  document.getElementById('openreview')?.addEventListener('click', (e) => {
    e.preventDefault();
    rvForm.hidden = false;
    rvForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  document.getElementById('cancelreview')?.addEventListener('click', () => { rvForm.hidden = true; });
  if (location.hash === '#nueva-resena') rvForm.hidden = false;

  const rvText = document.getElementById('rvtext');
  const rvCount = document.getElementById('rvcount');
  rvText.addEventListener('input', () => { rvCount.textContent = rvText.value.length; });

  const rvFotos = document.getElementById('rvfotos');
  const rvPrev = document.getElementById('rvprev');
  rvFotos.addEventListener('change', () => {
    const max = +rvFotos.dataset.max;
    rvPrev.querySelectorAll('img').forEach(i => URL.revokeObjectURL(i.src));
    rvPrev.innerHTML = '';
    if (rvFotos.files.length > max) {
      toast('Puedes subir máximo ' + max + ' fotos.', true);
      rvFotos.value = '';
      return;
    }
    [...rvFotos.files].forEach((f) => {
      if (f.size > 5 * 1024 * 1024) { toast('«' + f.name + '» pesa más de 5 MB.', true); rvFotos.value = ''; rvPrev.innerHTML = ''; return; }
      const img = document.createElement('img');
      img.src = URL.createObjectURL(f);
      img.alt = 'Vista previa';
      rvPrev.appendChild(img);
    });
  });
}
document.addEventListener('click', (ev) => {
  const img = ev.target.closest('.rv-photos img');
  if (!img) return;
  const lb = document.createElement('div');
  lb.className = 'lightbox';
  const big = document.createElement('img');
  big.src = img.src; big.alt = img.alt;
  lb.appendChild(big);
  const close = () => { lb.remove(); document.removeEventListener('keydown', esc); };
  const esc = (e) => { if (e.key === 'Escape') close(); };
  lb.addEventListener('click', close);
  document.addEventListener('keydown', esc);
  document.body.appendChild(lb);
});

