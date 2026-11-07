// Спільна форма заявки для всіх сторінок.
// Заявка йде на /send.php (той самий домен, https) — звідти в Telegram і на пошту.
// «Ваш запит прийнято» показується лише після реальної успішної відправки.

const PHONE_FALLBACK = "+380 (67) 280 83 84";
const ADS_CONVERSION = "AW-16474244921/LJ50CIX51JYZELmOxK89";

// Відправка заявки на /send.php. Конверсія Google Ads — тільки після успішної відправки.
// Використовується попапом нижче і формою на новій головній (java-script/home.js).
export async function postLead({ name, phone, message = "", product = "", elapsed = 0 }) {
  const res = await fetch("/send.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      name,
      phone,
      message,
      product,
      page: document.title + " — " + location.pathname,
      elapsed,
    }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok || !data.ok) throw new Error(data.error || "send_failed");

  if (typeof window.gtag === "function") {
    window.gtag("event", "conversion", { send_to: ADS_CONVERSION });
  }
  return data;
}

export function initLeadForm(openSelectors = []) {
  const form = document.querySelector(".sign-up");
  const closeForm = document.querySelector(".close-img");
  const send = document.querySelector(".wrapper-send");
  const sendAccept = document.querySelector(".accept-sign-up");
  const closeAccept = document.querySelector(".accept-close-img");
  const overflo = document.getElementById("overflo");
  const phoneInput = document.querySelector(".sing_up_input_phone");
  const nameInput = document.querySelector(".sing_up_input_name");
  const textInput = document.querySelector(".sing_up_input_inform");
  if (!form || !send || !phoneInput || !nameInput) return;

  let product = "";
  let openedAt = 0;
  let sending = false;

  const errorBox = document.createElement("p");
  errorBox.style.cssText = "color:#C00000;font-size:14px;margin-top:10px;text-align:center;display:none";
  send.insertAdjacentElement("afterend", errorBox);

  const showError = (text) => {
    errorBox.textContent = text;
    errorBox.style.display = "block";
  };

  const updateButtonColor = () => {
    send.style.background =
      nameInput.value.trim() && phoneInput.value.trim() ? "#C00000" : "";
  };
  nameInput.addEventListener("input", updateButtonColor);
  phoneInput.addEventListener("input", updateButtonColor);

  const open = (productName = "") => {
    product = productName;
    phoneInput.value = "";
    nameInput.value = "";
    if (textInput) textInput.value = productName ? `Запчастина: ${productName}` : "";
    [nameInput, phoneInput, textInput].forEach((el) => el && (el.style.borderColor = "grey"));
    errorBox.style.display = "none";
    send.style.background = "";
    openedAt = Date.now();
    form.style.display = "flex";
    if (overflo) overflo.style.display = "block";
  };

  const closeAll = () => {
    form.style.display = "none";
    if (sendAccept) sendAccept.style.display = "none";
    if (overflo) overflo.style.display = "none";
  };

  openSelectors.forEach((selector) => {
    document.querySelectorAll(selector).forEach((btn) =>
      btn.addEventListener("click", () => open())
    );
  });

  // Кнопки «Замовити» в каталозі (з'являються динамічно) — підставляємо назву запчастини
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".product-btn");
    if (!btn) return;
    const card = btn.closest(".product");
    const title = card?.querySelector(".product-title")?.textContent.trim() || "";
    const article = card?.querySelector(".product-price")?.textContent.trim() || "";
    open([title, article && `арт. ${article}`].filter(Boolean).join(", "));
  });

  if (closeForm) closeForm.addEventListener("click", closeAll);
  if (closeAccept) closeAccept.addEventListener("click", closeAll);

  send.addEventListener("click", async () => {
    if (sending) return;
    const name = nameInput.value.trim();
    const phone = phoneInput.value.trim();
    const digits = phone.replace(/\D/g, "");
    const nameOk = name.length > 1;
    const phoneOk = digits.length >= 9 && digits.length <= 13 && /^[\d\s()+-]+$/.test(phone);

    nameInput.style.borderColor = nameOk ? "grey" : "red";
    phoneInput.style.borderColor = phoneOk ? "grey" : "red";
    if (!nameOk || !phoneOk) {
      showError(!phoneOk ? "Вкажіть, будь ласка, коректний номер телефону" : "Вкажіть, будь ласка, ваше ім’я");
      return;
    }

    sending = true;
    errorBox.style.display = "none";
    send.style.opacity = "0.6";

    try {
      await postLead({
        name,
        phone,
        message: textInput ? textInput.value.trim() : "",
        product,
        elapsed: Date.now() - openedAt,
      });
      form.style.display = "none";
      if (sendAccept) sendAccept.style.display = "flex";
      if (overflo) overflo.style.display = "block";
    } catch (err) {
      showError(`Не вдалося надіслати заявку. Зателефонуйте нам: ${PHONE_FALLBACK}`);
    } finally {
      sending = false;
      send.style.opacity = "";
    }
  });
}
