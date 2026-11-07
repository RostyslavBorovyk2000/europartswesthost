// Картка запчастини в каталозі.
// Кнопка «Замовити» відкриває спільну форму заявки (java-script/lead-form.js) з назвою і артикулом.
// Класи product / product-title / product-price / product-btn — на них спирається lead-form.js.

const esc = (value) =>
  String(value ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

// Адреса сторінки товару — та сама транслітерація, що в zapchastyny/_lib.php (ep_slug)
const MAP = {а:"a",б:"b",в:"v",г:"h",ґ:"g",д:"d",е:"e",є:"ie",ж:"zh",з:"z",и:"y",і:"i",ї:"i",й:"i",к:"k",л:"l",м:"m",н:"n",о:"o",п:"p",р:"r",с:"s",т:"t",у:"u",ф:"f",х:"kh",ц:"ts",ч:"ch",ш:"sh",щ:"shch",ь:"",ю:"iu",я:"ia",ы:"y",э:"e",ё:"e",ъ:""};
export const slug = (t) => {
  let s = String(t).toLowerCase().split("").map((c) => (c in MAP ? MAP[c] : c)).join("");
  s = s.replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "");
  if (s.length > 70) s = s.slice(0, 70).replace(/-+$/, "");
  return s;
};

export default class Card {
  constructor(name, urlImg, artNumber, id) {
    this.name = name;
    this.urlImg = urlImg;
    this.artNumber = artNumber;
    this.id = id;
  }

  render(index = 0) {
    const container = document.querySelector(".all-products");
    const sl = slug(`${this.name} ${this.artNumber ?? ""}`);
    const url = this.id ? `/zapchastyny/${this.id}${sl ? "-" + sl : ""}` : "";
    const a = (inner) => (url ? `<a href="${url}">${inner}</a>` : inner);
    container.insertAdjacentHTML(
      "beforeend",
      `<article class="product" style="animation-delay:${Math.min(index, 12) * 40}ms">
        ${url ? `<a class="pimg" href="${url}">` : `<div class="pimg">`}<img src="${esc(this.urlImg)}" alt="${esc(this.name)}" loading="lazy">${url ? "</a>" : "</div>"}
        <div class="product-info">
          <h4 class="product-title">${a(esc(this.name))}</h4>
          <p class="product-price">${esc(this.artNumber)}</p>
          <button type="button" class="product-btn">Замовити</button>
        </div>
      </article>`
    );
  }
}
