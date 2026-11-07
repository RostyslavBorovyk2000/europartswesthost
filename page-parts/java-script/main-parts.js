// Каталог: список категорій або товари категорії (?categories=N). Дані — /catalog.php.
// Попап заявки і кнопки «Замовити» обробляє java-script/site.js → lead-form.js.
import Card from "./Card-parts.js?v=20261107k";

const catsView = document.querySelector(".js-cats");
const listView = document.querySelector(".block");
const container = document.querySelector(".all-products");
const input = document.querySelector(".input_seurch-card");
const titleEl = document.querySelector(".cat-title");
const countEl = document.querySelector(".cat-count");

const nameById = {};
document.querySelectorAll(".item_categoriys").forEach((a) => {
  nameById[a.dataset.id] = a.querySelector("b")?.textContent.trim() || "";
});

const plural = (n) => {
  const m10 = n % 10, m100 = n % 100;
  if (m10 === 1 && m100 !== 11) return "позиція";
  if (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14)) return "позиції";
  return "позицій";
};

const filterCards = () => {
  const q = input.value.toLowerCase().trim();
  let shown = 0;
  container.querySelectorAll(".product").forEach((card) => {
    const title = card.querySelector(".product-title").textContent.toLowerCase();
    const art = card.querySelector(".product-price").textContent.toLowerCase();
    const ok = title.includes(q) || art.includes(q);
    card.style.display = ok ? "" : "none";
    if (ok) shown++;
  });
  container.querySelector(".empty")?.remove();
  if (!shown && q) container.insertAdjacentHTML("beforeend", `<p class="empty">Нічого не знайдено. Залиште заявку — підберемо запчастину по артикулу.</p>`);
};
input.addEventListener("input", filterCards);

const showCategory = async (id) => {
  catsView.classList.add("hidden");
  listView.classList.remove("hidden");
  titleEl.textContent = nameById[id] || "Запчастини";
  countEl.textContent = "Завантаження…";
  container.innerHTML = '<div class="skel"></div>'.repeat(8);
  try {
    const res = await fetch(`/catalog.php?id=${encodeURIComponent(id)}`);
    const data = await res.json();
    if (!res.ok || String(data.id) !== String(id)) throw new Error("bad");
    const products = data.products || [];
    container.innerHTML = "";
    products.forEach(({ id, name, image_url, article }, i) => new Card(name, image_url, article, id).render(i));
    countEl.textContent = `${products.length} ${plural(products.length)}`;
    if (!products.length) container.innerHTML = `<p class="empty">У цій категорії поки порожньо. Зателефонуйте — підберемо під замовлення.</p>`;
  } catch (e) {
    container.innerHTML = `<p class="empty">Не вдалося завантажити каталог. Зателефонуйте: <a href="tel:+380672808384">Юрій +380 (67) 280 83 84</a> або <a href="tel:+380983982533">Олександр +380 (98) 398 25 33</a></p>`;
    countEl.textContent = "";
  }
};

const id = new URLSearchParams(location.search).get("categories");
if (id) showCategory(id);
else {
  catsView.classList.remove("hidden");
  listView.classList.add("hidden");
}
