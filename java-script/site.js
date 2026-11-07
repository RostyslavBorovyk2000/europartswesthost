// Спільне для всіх сторінок: перемикач Manitou/JCB, попап заявки, форма заявки на сторінці (#lead-inline).
import { initLeadForm, postLead } from "./lead-form.js?v=20261107b";

// Попап заявки (кнопка «Залишити заявку» в шапці)
initLeadForm([".js-open-lead"]);

// ---- Тема: Manitou — червона, JCB — жовта ----
const root = document.documentElement;
const brandButtons = document.querySelectorAll("[data-brand]");

function setBrand(brand, save = true) {
  const jcb = brand === "jcb";
  root.classList.toggle("th-j", jcb);
  brandButtons.forEach((b) => b.setAttribute("aria-pressed", String(b.dataset.brand === brand)));
  if (save) {
    try { localStorage.setItem("ep-brand", brand); } catch (e) {}
  }
}

brandButtons.forEach((b) => b.addEventListener("click", () => setBrand(b.dataset.brand)));
try {
  const saved = localStorage.getItem("ep-brand");
  if (saved === "jcb") setBrand("jcb", false);
} catch (e) {}

// ---- Форма заявки на сторінці ----
const form = document.getElementById("lead-inline");
if (form) {
  const loadedAt = Date.now();
  const nameEl = form.querySelector("[name=name]");
  const phoneEl = form.querySelector("[name=phone]");
  const msgEl = form.querySelector("[name=message]");
  const err = form.querySelector(".req-err");
  const btn = form.querySelector("button[type=submit]");
  const accept = document.querySelector(".accept-sign-up");
  const overflo = document.getElementById("overflo");
  let sending = false;

  const showErr = (t) => { err.textContent = t; err.style.display = "block"; };

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    if (sending) return;
    const name = nameEl.value.trim();
    const phone = phoneEl.value.trim();
    const digits = phone.replace(/\D/g, "");
    const nameOk = name.length > 1;
    const phoneOk = digits.length >= 9 && digits.length <= 13 && /^[\d\s()+-]+$/.test(phone);
    nameEl.classList.toggle("bad", !nameOk);
    phoneEl.classList.toggle("bad", !phoneOk);
    if (!nameOk || !phoneOk) {
      showErr(!phoneOk ? "Вкажіть, будь ласка, коректний номер телефону" : "Вкажіть, будь ласка, ваше ім’я");
      return;
    }

    sending = true;
    err.style.display = "none";
    btn.style.opacity = "0.6";
    try {
      const brand = root.classList.contains("th-j") ? "JCB" : "Manitou";
      const msg = msgEl.value.trim();
      await postLead({
        name,
        phone,
        message: msg ? msg : `Марка на сайті: ${brand}`,
        elapsed: Date.now() - loadedAt,
      });
      form.reset();
      if (accept) accept.style.display = "flex";
      if (overflo) overflo.style.display = "block";
    } catch (e2) {
      showErr("Не вдалося надіслати заявку. Зателефонуйте нам: +380 (67) 280 83 84");
    } finally {
      sending = false;
      btn.style.opacity = "";
    }
  });
}
