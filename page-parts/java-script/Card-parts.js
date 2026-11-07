// Картка запчастини в каталозі.
// Кнопка «Замовити» відкриває спільну форму заявки (java-script/lead-form.js) з назвою і артикулом.
// Класи product / product-title / product-price / product-btn — на них спирається lead-form.js.

const esc = (value) =>
  String(value ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

export default class Card {
  constructor(name, urlImg, artNumber) {
    this.name = name;
    this.urlImg = urlImg;
    this.artNumber = artNumber;
  }

  render(index = 0) {
    const container = document.querySelector(".all-products");
    container.insertAdjacentHTML(
      "beforeend",
      `<article class="product" style="animation-delay:${Math.min(index, 12) * 40}ms">
        <div class="pimg"><img src="${esc(this.urlImg)}" alt="${esc(this.name)}" loading="lazy"></div>
        <div class="product-info">
          <h4 class="product-title">${esc(this.name)}</h4>
          <p class="product-price">${esc(this.artNumber)}</p>
          <button type="button" class="product-btn">Замовити</button>
        </div>
      </article>`
    );
  }
}
