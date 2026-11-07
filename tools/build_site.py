"""Генератор сторінок сайту Євро Партс Захід (підсторінки, 404, блог, sitemap).
Запуск з кореня репо:  python3 tools/build_site.py .
index.html правиться вручну. Статті блогу — blog/posts/*.md (див. blog/README.md)."""
import sys, os, re, json, glob, datetime, html as H
ROOT=sys.argv[1] if len(sys.argv)>1 else "."
V="20261107e"
SITE="https://europartswest.com.ua"
MAP="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d162.26824443719536!2d26.967763160480818!3d49.40338512278056!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x4732067d411f8ff3%3A0x9b2695110350dd7b!2sAvtozapchastyny%2C%20Mah.!5e0!3m2!1sru!2sua!4v1686254268805!5m2!1sru!2sua"

def head(title, extra_js="", meta=""):
    return f'''<!DOCTYPE html>
<html lang="uk">
  <head>
    <script>(function(w,d,s,l,i){{w[l]=w[l]||[];w[l].push({{'gtm.start':
      new Date().getTime(),event:'gtm.js'}});var f=d.getElementsByTagName(s)[0],
      j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
      'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
      }})(window,document,'script','dataLayer','GTM-MG9P6SGD');</script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="180x180" href="../images/favicon/apple-touch-icon.png" />
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon/favicon-32x32.png" />
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon/favicon-16x16.png" />
    <link rel="manifest" href="../images/favicon/site.webmanifest" />
    <meta name="msapplication-TileColor" content="#da532c" />
    <meta name="theme-color" content="#0e0e0d" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../css/home.css?v={V}" />
    <link rel="stylesheet" href="../css/pages.css?v={V}" />
    <script type="module" src="../java-script/site.js?v={V}"></script>{extra_js}
    <script async src="https://www.googletagmanager.com/gtag/js?id=AW-16474244921"></script> <script> window.dataLayer = window.dataLayer || []; function gtag(){{dataLayer.push(arguments);}} gtag('js', new Date()); gtag('config', 'AW-16474244921'); </script>
    <title>{title}</title>{meta}
  </head>
  <body>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-MG9P6SGD"
      height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <div id="overflo"></div>
'''

NAV=[("parts","../page-parts/parts.html","Каталог"),("service","../page-sirvice/service.html","Сервіс"),("restoration","../page-restoration/restoration.html","Реставрація"),("blog","../blog/index.html","Блог"),("contact","../page-contact/contact.html","Контакти")]

def callpick(label="Зателефонувати", cls="btn btn-ghost", up=False):
    return f'''<details class="callpick{' up' if up else ''}"><summary class="{cls}">{label}</summary><div class="callpick-menu" role="menu"><a role="menuitem" href="tel:+380672808384"><b>Юрій</b><span>менеджер · +380 (67) 280 83 84</span></a><a role="menuitem" href="tel:+380983982533"><b>Олександр</b><span>директор · +380 (98) 398 25 33</span></a></div></details>'''

def header(active):
    links="\n".join(f'          <a class="navlink{" active" if k==active else ""}" href="{h}"{" aria-current=\"page\"" if k==active else ""}>{t}</a>' for k,h,t in NAV)
    return f'''
    <header class="top">
      <div class="wrap top-in">
        <a class="brand" href="../index.html">
          <img src="../images/header/logo.svg" width="56" height="18" alt="" />
          ЄВРО ПАРТС ЗАХІД
        </a>
        <nav class="nav" aria-label="Головне меню">
{links}
        </nav>
        <div class="top-right">
          <div class="phones">
            <a href="tel:+380672808384">+38 (067) 280 83 84</a>
            <a href="tel:+380983982533">+38 (098) 398 25 33</a>
          </div>
          {callpick("Подзвонити","btn btn-red callpick-top")}
          <button type="button" class="btn btn-red shine js-open-lead">Залишити заявку<span class="gl"></span></button>
        </div>
      </div>
    </header>
'''

def phero(img, crumb, title_html, lead, buttons, alt):
    return f'''
      <section class="phero">
        <img class="hero-img man" src="../images/home/{img}" alt="{alt}" />
        <img class="hero-img jcb" src="../images/home/hero-jcb.jpg" alt="Телескопічний навантажувач JCB Loadall" loading="lazy" />
        <div class="hero-shade" aria-hidden="true"></div>
        <div class="beam" aria-hidden="true"></div>
        <div class="stripes" aria-hidden="true"></div>
        <div class="wrap phero-in">
          <div class="phero-box">
            <div class="crumbs in d1"><a href="../index.html">Головна</a><i>/</i><span>{crumb}</span></div>
            <div class="row in d1" style="gap:10px">
              <button type="button" class="pill" data-brand="manitou" aria-pressed="true">MANITOU</button>
              <button type="button" class="pill" data-brand="jcb" aria-pressed="false">JCB LOADALL</button>
            </div>
            <h1 class="h1">{title_html}</h1>
            <p class="lead in d4">{lead}</p>
            <div class="row in d5">{buttons}</div>
          </div>
        </div>
      </section>
'''

def words(text, grad=None):
    out=[];d=.2
    for w in text.split():
        cls="word grad" if w==grad else "word"
        out.append(f'<span class="{cls}" style="animation-delay:{d:.2f}s">{w}</span>');d+=.1
    return " ".join(out)

def ctaband(title, text):
    return f'''
      <section class="wrap" style="padding-top:60px;padding-bottom:100px">
        <div class="ctaband rv">
          <div class="glow" aria-hidden="true"></div>
          <div>
            <h2>{title}</h2>
            <p>{text}</p>
          </div>
          <div class="row">
            <button type="button" class="btn cta shine js-open-lead">Залишити заявку<span class="gl"></span></button>
            {callpick("Зателефонувати","btn btn-ghost",up=True)}
          </div>
        </div>
      </section>
'''

def footer():
    return f'''
    <footer class="foot" id="contacts">
      <div class="wrap grid3 foot-grid">
        <div>
          <strong>ЄВРО ПАРТС ЗАХІД</strong>
          <span>Запчастини, сервіс і реставрація телескопічних навантажувачів Manitou та JCB.</span>
        </div>
        <div>
          <span style="color:#F2EFEA;font-weight:700">м. Хмельницький, вул. Київська, 4</span>
          <span>Пн–Пт: 8:00–17:00</span><span>Сб: 8:00–15:00</span><span>Безготівковий розрахунок</span>
        </div>
        <div>
          <a href="tel:+380672808384">+380 (67) 280 83 84</a>
          <a href="tel:+380983982533">+380 (98) 398 25 33</a>
          <a href="mailto:europartswest1@gmail.com" style="color:var(--g)">europartswest1@gmail.com</a>
        </div>
      </div>
      <nav class="wrap foot-links" aria-label="Сторінки сайту">
        <a class="navlink" href="../index.html">Головна</a>
        <a class="navlink" href="../page-parts/parts.html">Каталог товарів</a>
        <a class="navlink" href="../page-sirvice/service.html">Сервіс</a>
        <a class="navlink" href="../page-restoration/restoration.html">Реставрація</a>
        <a class="navlink" href="../blog/index.html">Блог</a>
        <a class="navlink" href="../page-contact/contact.html">Контакти</a>
      </nav>
      <div style="height:40px"></div>
    </footer>

    <section class="sign-up" role="dialog" aria-label="Заявка">
      <div class="centered">
        <div class="wrapper_img-close"><img class="close-img" src="../images/sing-up/close.svg" alt="Закрити" /></div>
        <h3 class="sing_up-title">Залишити заявку</h3>
        <p class="sing_up-text">Менеджер зв’яжеться з вами та підкаже ціну й наявність</p>
        <input class="sing_up_input_name" type="text" placeholder="Ваше ім’я" />
        <input class="sing_up_input_phone" type="tel" placeholder="Ваш телефон" />
        <input class="sing_up_input_inform" type="text" placeholder="Марка та модель техніки" />
        <div class="wrapper-send">
          <a class="send-link-btn">Надіслати запит</a>
          <img class="send-link-img" src="../images/sing-up/planer.svg" alt="" />
        </div>
      </div>
    </section>
    <section class="accept-sign-up" role="dialog" aria-label="Заявку прийнято">
      <div class="container">
        <div class="wrapper_img-close"><img class="accept-close-img" src="../images/sing-up/close.svg" alt="Закрити" /></div>
        <h3 class="accept-sing_up-title">Ваш запит прийнято!</h3>
        <p class="accept-sing_up-text">Наш менеджер зв’яжеться з вами в найближчий час</p>
        <div class="wrapper_img-sing-up"><img class="img-sing-up" src="../images/sing-up/man.svg" alt="" /></div>
      </div>
    </section>
  </body>
</html>
'''

BTN_LEAD='<button type="button" class="btn cta shine js-open-lead">{}<span class="gl"></span></button>'
CALL=callpick()

def steps(items, cls="grid4"):
    return f'<div class="{cls}" style="gap:22px">'+"".join(f'<div class="step rv"><b>{i+1:02d}</b><h3>{t}</h3>{f"<p>{p}</p>" if p else ""}</div>' for i,(t,p) in enumerate(items))+'</div>'

# ---------- КАТАЛОГ ----------
cats=[(5,"Двигун"),(6,"КПП"),(7,"Кутовий редуктор"),(8,"Мости"),(9,"Стріла"),(10,"Електросистема"),(11,"Паливна система"),(12,"Гідравлічна система"),(13,"Запчастини для ТО"),(14,"Інші запчастини")]
cat_html="\n".join(f'          <a class="catx item_categoriys rv" data-id="{i}" href="?categories={i}"><small>{n_:02d}</small><b>{t}</b><span class="go" aria-hidden="true">→</span></a>' for n_,(i,t) in enumerate(cats,1))
parts=head("Каталог запчастин — Євро Партс Захід",f'\n    <script type="module" src="./java-script/main-parts.js?v={V}"></script>')+header("parts")+"<main>"+phero(
 "page-parts.jpg","Каталог",words("Запчастини Маніту та JCB","Маніту"),
 "Наші кваліфіковані менеджери завжди готові порадити та допомогти клієнтам у виборі необхідних запчастин.",
 BTN_LEAD.format("Дізнатися ціну та наявність")+CALL,"Ківш телескопічного навантажувача Manitou")+f'''
      <section class="wrap sec js-cats">
        <div class="two" style="margin-bottom:56px">
          <div>
            <p class="eyebrow rv">КАТАЛОГ</p>
            <h2 class="h2 rv" style="margin-bottom:0">Оберіть групу запчастин</h2>
          </div>
          <div class="prose rv"><p>Ми розуміємо, що кожен клієнт має свої унікальні потреби та бюджет, тому ми докладаємо максимум зусиль, щоб знайти оптимальне рішення та допомогти зекономити кошти.</p></div>
        </div>
        <div class="cats list_categoriys">
{cat_html}
        </div>
      </section>

      <section class="wrap sec block hidden">
        <div class="cat-bar">
          <div>
            <h2 class="cat-title">Запчастини</h2>
            <p class="cat-count"></p>
          </div>
          <a class="back" href="./parts.html">← Усі категорії</a>
        </div>
        <div class="cat-bar">
          <label class="search">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input class="input_seurch-card" type="search" placeholder="Пошук за назвою або артикулом" aria-label="Пошук запчастин" />
          </label>
        </div>
        <div id="wrapper-card" class="all-products"></div>
      </section>
'''+ctaband("Не знайшли потрібну запчастину?","Надішліть артикул або модель техніки — підберемо та назвемо ціну й строки.")+"</main>"+footer()

# ---------- СЕРВІС ----------
svc=[("Motor.svg","Ремонт моторів"),("KPP.svg","Ремонт КПП"),("Angle reducer.svg","Ремонт кутових редукторів"),("hydraulics.svg","Ремонт гідравлічних систем"),("Diagnostics.svg","Діагностика"),("Fuel system.svg","Ремонт паливних систем"),("axel.svg","Ремонт мостів")]
tiles="\n".join(f'          <div class="tile rv"><span class="ico"><img src="./img-service/{f.replace(" ","%20")}" alt="" /></span><h3>{t}</h3></div>' for f,t in svc)
tiles+='\n          <button type="button" class="tile rv js-open-lead" style="font-family:inherit;text-align:left;cursor:pointer;color:var(--on);background:var(--r);border-color:var(--r)"><span class="ico" style="background:rgba(255,255,255,.18);color:var(--on);font-size:28px;font-weight:800">→</span><h3>Запис на ремонт</h3></button>'
service=head("Сервіс Маніту — Євро Партс Захід")+header("service")+"<main>"+phero(
 "page-service.jpg","Сервіс",words("Сервіс Маніту на вашій локації","Маніту"),
 "Наші бригади складаються з двох висококваліфікованих інженерів, які швидко та ефективно вирішують будь-які проблеми на місці у клієнта.",
 BTN_LEAD.format("Запис на ремонт")+CALL,"Телескопічний навантажувач Manitou працює в ангарі")+f'''
      <section class="wrap sec">
        <div class="two">
          <div>
            <p class="eyebrow rv">ВИЇЗНИЙ СЕРВІС</p>
            <h2 class="h2 rv">Ремонтуємо там, де працює ваша техніка</h2>
            <div class="prose rv">
              <p>Ми розуміємо, що час — це гроші, тому ми докладаємо максимум зусиль, щоб забезпечити швидке та якісне виконання робіт.</p>
              <p>Сервісна бригада виїжджає на локацію з асортиментом запчастин, які можуть знадобитись для ремонту на місці. Після огляду одразу розпочинає роботу.</p>
            </div>
          </div>
          <div class="facts">
            <div class="fact rv"><b>2</b><span>висококваліфіковані інженери в кожній бригаді</span></div>
            <div class="fact rv"><b>1000+</b><span>запчастин на складі — беремо потрібні з собою</span></div>
            <div class="fact rv"><b>На місці</b><span>ремонт без перевезення техніки в сервіс</span></div>
            <div class="fact rv"><b>Manitou · JCB</b><span>телескопічні навантажувачі</span></div>
          </div>
        </div>
      </section>

      <section class="wrap sec">
        <p class="eyebrow rv">ЩО РЕМОНТУЄМО</p>
        <h2 class="h2 rv">Усі вузли навантажувача</h2>
        <div class="tiles">
{tiles}
        </div>
      </section>

      <section class="divider" aria-label="Наша мета">
        <img class="par" src="../images/home/divider.jpg" alt="" loading="lazy" />
        <div class="divider-shade" aria-hidden="true"></div>
        <div class="divider-text"><p class="rv">Наша мета — швидкий та якісний ремонт <span style="color:var(--g)">без додаткових затрат</span> на запчастини та робочу силу.</p></div>
      </section>

      <section class="wrap sec">
        <p class="eyebrow rv">ЯК ЦЕ ПРАЦЮЄ</p>
        <h2 class="h2 rv">Від дзвінка до працюючої техніки</h2>
        {steps([("Заявка","Телефонуєте або залишаєте заявку — узгоджуємо час виїзду."),("Виїзд","Бригада приїжджає із запчастинами, які можуть знадобитись."),("Огляд","Інженери знаходять причину несправності."),("Ремонт","Одразу ремонтуємо на місці — без простою техніки.")])}
      </section>
'''+ctaband("Техніка стоїть? Запишіться на ремонт","Менеджер зв’яжеться з вами та узгодить запис.")+"</main>"+footer()

# ---------- РЕСТАВРАЦІЯ ----------
rs=[("restoration-cylinder.svg","Циліндри","Гідроциліндри стріли, ковша, нахилу"),("restoration-axel.svg","Мости","Чулки мостів"),("restoration-hose.svg","Шланги","Шланги високого тиску"),("restoration-equipment.svg","Навіска","Навісне обладнання")]
rtiles="\n".join(f'          <div class="tile rv"><span class="ico"><img src="./restoration-images/{f}" alt="" /></span><h3>{t}</h3><p>{p}</p></div>' for f,t,p in rs)
restoration=head("Реставрація — Євро Партс Захід")+header("restoration")+"<main>"+phero(
 "page-restoration.jpg","Реставрація",words("Послуги реставрації","реставрації"),
 "Сервісна бригада надає послуги реставрації зношених або пошкоджених запчастин. Реставрація включає в себе обов’язкові етапи, які гарантують якісний результат.",
 BTN_LEAD.format("Запис на ремонт")+CALL,"Телескопічний навантажувач на фермі")+f'''
      <section class="wrap sec">
        <p class="eyebrow rv">ЩО ВІДНОВЛЮЄМО</p>
        <h2 class="h2 rv">Друге життя для зношених вузлів</h2>
        <div class="tiles">
{rtiles}
        </div>
      </section>

      <section class="wrap sec">
        <div class="two" style="align-items:end;margin-bottom:44px">
          <div>
            <p class="eyebrow rv">ЕТАПИ</p>
            <h2 class="h2 rv" style="margin:0">Як ми реставруємо</h2>
          </div>
          <div class="prose rv"><p>Реставрація включає в себе обов’язкові етапи, які гарантують якісний результат.</p></div>
        </div>
        {steps([("Огляд та виявлення проблем",""),("Очищення обладнання",""),("Ремонт або заміна пошкоджених деталей",""),("Відновлення геометрії та точності",""),("Монтаж та перевірка","")],"grid5s")}
      </section>
'''+ctaband("Потрібна реставрація?","Опишіть деталь — менеджер підкаже вартість і строки.")+"</main>"+footer()

# ---------- КОНТАКТИ ----------
contact=head("Контакти — Євро Партс Захід")+header("contact")+"<main>"+phero(
 "page-contact.jpg","Контакти",words("Наші контакти","контакти"),
 "Хмельницький, вул. Київська, 4. Телефонуйте, пишіть або приїжджайте на офіс-склад.",
 callpick("Зателефонувати","btn cta")+'<a class="btn btn-ghost" href="#request">Залишити заявку</a>',"Телескопічний навантажувач Manitou на фермі")+f'''
      <section class="wrap sec">
        <div class="cgrid">
          <div class="ccard rv"><small>ТЕЛЕФОНИ</small><a href="tel:+380672808384">+380 (67) 280 83 84</a><span>Менеджер Юрій</span><a href="tel:+380983982533">+380 (98) 398 25 33</a><span>Директор Олександр</span></div>
          <div class="ccard rv"><small>АДРЕСА</small><b>м. Хмельницький, вул. Київська, 4</b><span>Офіс-склад. Доставка Новою поштою по всій Україні.</span></div>
          <div class="ccard rv"><small>ГРАФІК</small><b>Пн–Пт: 8:00–17:00</b><b>Сб: 8:00–15:00</b><span>Безготівковий розрахунок</span></div>
        </div>
      </section>

      <section class="wrap bigmap rv" style="padding-top:20px">
        <iframe title="Євро Партс Захід на карті" src="{MAP}" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </section>

      <section id="request" class="wrap" style="padding-top:90px;padding-bottom:110px">
        <div class="req rv">
          <div class="glow" aria-hidden="true"></div>
          <div class="req-text">
            <h2>Напишіть нам</h2>
            <p>Залиште заявку — менеджер передзвонить і підкаже ціну та наявність. Або пишіть на <a href="mailto:europartswest1@gmail.com">europartswest1@gmail.com</a></p>
          </div>
          <form id="lead-inline" class="req-form" novalidate>
            <label>Ваше ім’я<input class="field" name="name" type="text" autocomplete="name" placeholder="Олександр" /></label>
            <label>Телефон<input class="field" name="phone" type="tel" autocomplete="tel" placeholder="+380" /></label>
            <label>Техніка або запчастина<input class="field" name="message" type="text" placeholder="Напр. Manitou MLT 735 або JCB 531-70" /></label>
            <p class="req-err" role="alert"></p>
            <button class="btn cta shine" type="submit">Надіслати заявку<span class="gl"></span></button>
          </form>
        </div>
      </section>
'''+"</main>"+footer()


# ---------- 404 (абсолютні шляхи: віддається з будь-якої адреси) ----------
p404=head("Сторінку не знайдено — Євро Партс Захід")+header("")+'''<main>
      <section class="phero" style="min-height:calc(100vh - 70px);align-items:center">
        <img class="hero-img man" src="../images/home/divider.jpg" alt="" />
        <img class="hero-img jcb" src="../images/home/hero-jcb.jpg" alt="" loading="lazy" />
        <div class="hero-shade" aria-hidden="true"></div>
        <div class="beam" aria-hidden="true"></div>
        <div class="stripes" aria-hidden="true"></div>
        <div class="wrap phero-in" style="padding-top:80px;padding-bottom:80px">
          <div class="phero-box">
            <p class="e404 in d1" aria-hidden="true">404</p>
            <h1 class="h1">'''+words("Такої сторінки немає","немає")+'''</h1>
            <p class="lead in d4">Можливо, посилання застаріло або в адресі помилка. Але запчастини, сервіс і реставрація — на місці.</p>
            <div class="row in d5">
              <a class="btn cta shine" href="../index.html">На головну<span class="gl"></span></a>
              <a class="btn btn-ghost" href="../page-parts/parts.html">Каталог запчастин</a>
            </div>
            <div class="row in d5" style="gap:12px 26px;font-size:15px;color:#cfcac2;align-items:center">
              <span>Шукаєте конкретну деталь?</span>
              <button type="button" class="linkbtn js-open-lead">Залиште заявку →</button>
              '''+callpick("Подзвонити →","linkbtn")+'''
            </div>
          </div>
        </div>
      </section>
</main>'''+footer()
p404=p404.replace('"../','"/').replace('<meta name="viewport"','<meta name="robots" content="noindex" />\n    <meta name="viewport"')

# ---------- БЛОГ ----------
import markdown as MD
MONTHS=["січня","лютого","березня","квітня","травня","червня","липня","серпня","вересня","жовтня","листопада","грудня"]
def udate(d): return f"{d.day} {MONTHS[d.month-1]} {d.year}"
CATS={"to":("../page-parts/parts.html?categories=13","Запчастини для ТО"),"engine":("../page-parts/parts.html?categories=5","Двигун"),
      "kpp":("../page-parts/parts.html?categories=6","КПП"),"reducer":("../page-parts/parts.html?categories=7","Кутовий редуктор"),
      "axle":("../page-parts/parts.html?categories=8","Мости"),"boom":("../page-parts/parts.html?categories=9","Стріла"),
      "electric":("../page-parts/parts.html?categories=10","Електросистема"),"fuel":("../page-parts/parts.html?categories=11","Паливна система"),
      "hydraulic":("../page-parts/parts.html?categories=12","Гідравлічна система"),"other":("../page-parts/parts.html?categories=14","Інші запчастини"),
      "service":("../page-sirvice/service.html","Виїзний сервіс"),"restoration":("../page-restoration/restoration.html","Реставрація")}

def parse_post(path):
    raw=open(path,encoding="utf-8").read()
    m=re.match(r"^---\n(.*?)\n---\n(.*)$",raw,re.S)
    meta={}
    for line in m.group(1).splitlines():
        if ":" in line:
            k,v=line.split(":",1); meta[k.strip()]=v.strip()
    meta["slug"]=os.path.splitext(os.path.basename(path))[0]
    meta["date"]=datetime.date.fromisoformat(meta["date"])
    meta["tags"]=[t.strip() for t in meta.get("tags","").split(",") if t.strip()]
    meta["links"]=[t.strip() for t in meta.get("links","").split(",") if t.strip()]
    body=m.group(2)
    meta["words"]=len(re.findall(r"\w+",body))
    html_body=MD.markdown(body,extensions=["extra","sane_lists"])
    # «> **Порада:** …» → блок-порада
    html_body=html_body.replace("<blockquote>","<blockquote class=\"tip\">")
    meta["html"]=html_body
    return meta

def post_card(p, prefix=""):
    tags="".join(f'<span class="tag">{H.escape(t)}</span>' for t in p["tags"])
    return f'''<a class="pcard rv" href="{prefix}{p["slug"]}.html">
            <span class="pcard-img"><img src="../images/blog/{p["cover"]}" alt="" loading="lazy" /></span>
            <span class="pcard-body"><span class="pmeta">{udate(p["date"])}{tags}</span><b>{H.escape(p["title"])}</b><span class="pdesc">{H.escape(p["description"])}</span><span class="pmore">Читати <span class="arrow">→</span></span></span>
          </a>'''

today=datetime.date.today()
posts=[parse_post(f) for f in glob.glob(f"{ROOT}/blog/posts/*.md")]
posts=[p for p in posts if p["date"]<=today]
posts.sort(key=lambda p:p["date"],reverse=True)
pages_out={}

def og(title,desc,url,img):
    return f'''
    <meta name="description" content="{H.escape(desc)}" />
    <link rel="canonical" href="{url}" />
    <meta property="og:type" content="article" />
    <meta property="og:title" content="{H.escape(title)}" />
    <meta property="og:description" content="{H.escape(desc)}" />
    <meta property="og:url" content="{url}" />
    <meta property="og:image" content="{img}" />'''

for idx,p in enumerate(posts):
    url=f"{SITE}/blog/{p['slug']}.html"
    img=f"{SITE}/images/blog/{p['cover']}"
    ld=json.dumps({"@context":"https://schema.org","@type":"BlogPosting","headline":p["title"],"description":p["description"],"image":img,
        "datePublished":p["date"].isoformat(),"author":{"@type":"Organization","name":"Євро Партс Захід"},
        "publisher":{"@type":"Organization","name":"Євро Партс Захід","url":SITE},"mainEntityOfPage":url},ensure_ascii=False)
    meta=og(p["title"],p["description"],url,img)+f'\n    <script type="application/ld+json">{ld}</script>'
    tags="".join(f'<span class="tag">{H.escape(t)}</span>' for t in p["tags"])
    links="".join(f'<a class="chiplink" href="{CATS[k][0]}">{CATS[k][1]} →</a>' for k in p["links"] if k in CATS)
    others=[q for q in posts if q is not p][:3]
    more=("""
      <section class="wrap sec-sm">
        <p class="eyebrow rv">ЧИТАЙТЕ ТАКОЖ</p>
        <div class="pgrid">"""+"".join(post_card(q) for q in others)+"""</div>
      </section>""") if others else ""
    mins=max(2,round(p["words"]/180))
    html_=head(f"{p['title']} — Блог Євро Партс Захід",meta=meta)+header("blog")+f'''<main>
      <section class="ahero">
        <img class="ahero-img" src="../images/blog/{p["cover"]}" alt="{H.escape(p.get("cover_alt",""))}" />
        <div class="ahero-shade" aria-hidden="true"></div>
        <div class="wrap ahero-in">
          <div class="crumbs in d1"><a href="../index.html">Головна</a><i>/</i><a href="./index.html">Блог</a></div>
          <div class="pmeta in d1">{udate(p["date"])}<span class="dot">·</span>{mins} хв читання{tags}</div>
          <h1 class="h1 atitle in d1">{H.escape(p["title"])}</h1>
          <p class="lead in d4" style="max-width:760px">{H.escape(p["description"])}</p>
        </div>
      </section>
      <article class="wrap article">
        <div class="prose-a">
{p["html"]}
        </div>
        <aside class="aside">
          <div class="aside-box">
            <b>Потрібна запчастина чи ремонт?</b>
            <p>Підберемо по артикулу або моделі, бригада приїде на вашу локацію.</p>
            <button type="button" class="btn btn-red js-open-lead" style="width:100%">Залишити заявку</button>
            {callpick("Зателефонувати","btn btn-ghost callpick-full")}
          </div>
          {f'<div class="aside-links"><small>У КАТАЛОЗІ ТА СЕРВІСІ</small>{links}</div>' if links else ""}
        </aside>
      </article>'''+more+ctaband("Маєте питання щодо вашої техніки?","Напишіть модель і проблему — менеджер підкаже, що перевірити і що замовити.")+"</main>"+footer()
    pages_out[f"blog/{p['slug']}.html"]=html_

cards="".join(post_card(p,"./") for p in posts) or '<p class="empty">Перші статті вже готуються.</p>'
blog_index=head("Блог — Євро Партс Захід",meta=og("Блог Євро Партс Захід","Поради з обслуговування та ремонту телескопічних навантажувачів Manitou і JCB.",f"{SITE}/blog/",f"{SITE}/images/home/hero-manitou.jpg"))+header("blog")+"<main>"+phero(
 "page-blog.jpg","Блог",words("Блог про Manitou та JCB","Manitou"),
 "Поради з обслуговування, типові несправності та запчастини для телескопічних навантажувачів — від інженерів, які щодня ремонтують цю техніку.",
 BTN_LEAD.format("Поставити питання")+CALL,"Навантажувач Manitou у корівнику")+f'''
      <section class="wrap sec">
        <div class="pgrid">
          {cards}
        </div>
      </section>
'''+ctaband("Не знайшли відповіді?","Опишіть проблему — інженер підкаже, що перевірити і які запчастини знадобляться.")+"</main>"+footer()
pages_out["blog/index.html"]=blog_index

# ---------- sitemap.xml + robots.txt ----------
urls=[(f"{SITE}/",today),(f"{SITE}/page-parts/parts.html",today),(f"{SITE}/page-sirvice/service.html",today),
      (f"{SITE}/page-restoration/restoration.html",today),(f"{SITE}/page-contact/contact.html",today),(f"{SITE}/blog/",posts[0]["date"] if posts else today)]
urls+=[(f"{SITE}/page-parts/parts.html?categories={i}",today) for i,_ in cats]
urls+=[(f"{SITE}/blog/{p['slug']}.html",p["date"]) for p in posts]
sm='<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n'+"".join(f"  <url><loc>{H.escape(u)}</loc><lastmod>{d.isoformat()}</lastmod></url>\n" for u,d in urls)+"</urlset>\n"
pages_out["sitemap.xml"]=sm
pages_out["robots.txt"]=f"User-agent: *\nAllow: /\nSitemap: {SITE}/sitemap.xml\n"
pages_out["404.html"]=p404
pages_out.update({"page-parts/parts.html":parts,"page-sirvice/service.html":service,"page-restoration/restoration.html":restoration,"page-contact/contact.html":contact})
os.makedirs(f"{ROOT}/blog",exist_ok=True)
for path,html in pages_out.items():
    open(f"{ROOT}/{path}","w",encoding="utf-8").write(html)
print("ok:", ", ".join(sorted(pages_out)))
