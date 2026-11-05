// Картка запчастини в каталозі.
// Кнопка «Замовити» відкриває спільну форму заявки (java-script/lead-form.js) з назвою запчастини.

const esc = (value) =>
  String(value ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

export default class Card {
  constructor(name, urlImg, artNumber) {
    this.name = name;
    this.urlImg = urlImg;
    this.artNumber = artNumber;
  }

  render() {
    const categoriesContainer = document.querySelector(".all-products");
    categoriesContainer.insertAdjacentHTML(
      "beforeend",
      ` <div class="product">
        <img src="${esc(this.urlImg)}" alt="${esc(this.name)}" loading="lazy">
        <div class="product-info">
         <h4 class="product-title">${esc(this.name)}</h4>
         <p class="product-price">${esc(this.artNumber)}</p>
         <button class="product-btn">Замовити</button>
        </div>
       </div>`
    );
  }
}
