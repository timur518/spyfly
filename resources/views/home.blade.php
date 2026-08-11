<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>SpyFly — когда лететь дешевле</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/home.css') }}">
</head>
<body>

<header class="stage" id="stage">
  <div class="stagewrap">
    <div class="stagehead">
      <div class="stagebrand">
        <div class="logo" aria-hidden="true">✈</div>
        <div class="brand">SpyFly</div>
      </div>
      <div class="stagecopy">
        <h1>Когда лететь <span class="hl">дешевле</span></h1>
        <p class="sub">Находим даты, когда билет стоит сильно ниже обычного</p>
      </div>
    </div>
    <section class="panel searchpanel">
      <form id="form" class="search">
        <div class="fgroup grp-route">
        <div class="fld">
          <label for="origin">Откуда</label>
          <input name="origin" id="origin" autocomplete="off" spellcheck="false" placeholder="По названию города или аэропорту" required>
          <div class="ac" id="originAc" hidden></div>
        </div>
          <button type="button" class="swap" id="swapBtn" aria-label="Поменять аэропорты местами" title="Поменять местами">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M4.5 12V3M4.5 3 2 5.5M4.5 3 7 5.5M9.5 2v9M9.5 11 7 8.5M9.5 11l2.5-2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        <div class="fld fld-to">
          <label for="destination">Куда</label>
          <input name="destination" id="destination" autocomplete="off" spellcheck="false" placeholder="По названию города или аэропорту" required>
          <div class="ac" id="destinationAc" hidden></div>
        </div>
        </div>
        <div class="fgroup grp-dates">
          <div class="fld"><label for="fromDate">Вылет с</label><input id="fromDate" name="from" type="date" value="2026-09-01" required></div>
          <div class="fld"><label for="toDate">Вылет по</label><input id="toDate" name="to" type="date" value="2026-12-31" required></div>
        </div>
        <div class="fgroup grp-trip">
          <div class="fld"><label for="oneWaySel">Тип поездки</label><select name="one_way" id="oneWaySel"><option value="0">Туда и обратно</option><option value="1">В одну сторону</option></select></div>
          <div class="fld" id="lenFld"><label for="lenInput">Кол-во дней</label><input id="lenInput" name="length" type="number" min="1" max="30" value="7"></div>
        </div>
        <label class="dtoggle" for="directChk">
          <span class="dtoggle-tx">
            <span class="dtoggle-t">Только прямые рейсы</span>
            <span class="dtoggle-s">Искать рейсы без пересадок</span>
          </span>
          <input type="checkbox" id="directChk" name="direct" value="1">
          <span class="dtoggle-sw" aria-hidden="true"></span>
        </label>
        <button class="btn" id="scanBtn">Сканировать</button>
      </form>
    </section>
    <div class="signals signals-inline" id="signalsTicker" aria-live="polite">
      <div class="signals-track" id="signalsTrack">
        <span class="signals-line signals-current" id="signalsCurrent"></span>
        <span class="signals-line signals-next" id="signalsNext" aria-hidden="true"></span>
      </div>
    </div>
  </div>
</header>

<main class="wrap">

<div id="loader" class="loader" hidden>
  <div class="ring" aria-hidden="true"></div>
  <div class="cap">Выискиваем лучший вариант</div>
</div>

<div id="result" class="result" aria-live="polite"></div>

<section class="ticket" id="subscriptionCard" aria-labelledby="subscriptionTitle" hidden>
  <div class="ticket-strip">
    <div class="ticket-strip-tx">
      <p class="ticket-kicker">Отслеживание цены</p>
      <h2 id="subscriptionTitle">Поймаем самые дешевые перелеты</h2>
    </div>
    <div class="ticket-live"><i aria-hidden="true"></i>329 маршрутов под слежкой</div>
  </div>

  <div class="ticket-body">
    <p class="ticket-lead" id="subscriptionLead">Запустите слежку за авиабилетами — сообщим, как только цена станет подходящей</p>

    <form id="subscriptionForm" class="subscribeform">
      <input type="hidden" name="user_id" value="1">
      <input type="hidden" name="is_active" value="1">

      <div class="subscribe-topline">
        <div class="fgroup subscribe-route">
          <div class="fld">
            <label for="subOrigin">Откуда</label>
            <input name="origin_iata" id="subOrigin" autocomplete="off" spellcheck="false" placeholder="По названию города или аэропорту" required>
            <div class="ac" id="subOriginAc" hidden></div>
          </div>
          <button type="button" class="swap" id="subSwapBtn" aria-label="Поменять аэропорты местами" title="Поменять местами">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true"><path d="M4.5 12V3M4.5 3 2 5.5M4.5 3 7 5.5M9.5 2v9M9.5 11 7 8.5M9.5 11l2.5-2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <div class="fld fld-to">
            <label for="subDestination">Куда</label>
            <input name="destination_iata" id="subDestination" autocomplete="off" spellcheck="false" placeholder="Можно оставить пустым">
            <div class="ac" id="subDestinationAc" hidden></div>
          </div>
        </div>

        <div class="fgroup grp-dates subscribe-daterange">
          <div class="fld">
            <label for="subDateFrom">Дата от</label>
            <input name="date_from" id="subDateFrom" type="date">
          </div>
          <div class="fld">
            <label for="subDateTo">Дата до</label>
            <input name="date_to" id="subDateTo" type="date">
          </div>
        </div>
      </div>

      <div class="subscribegrid">
        <div class="fgroup grp-dates subscribe-group">
          <div class="fld">
            <label for="subMinStay">Мин. дней</label>
            <input name="min_stay_days" id="subMinStay" type="number" min="0" step="1" placeholder="Необязательно">
          </div>
          <div class="fld">
            <label for="subMaxStay">Макс. дней</label>
            <input name="max_stay_days" id="subMaxStay" type="number" min="0" step="1" placeholder="Необязательно">
          </div>
        </div>

        <div class="fgroup grp-trip subscribe-group">
          <div class="fld">
            <label for="subTripType">Тип поездки</label>
            <select name="trip_type" id="subTripType" required>
              <option value="round_trip">Туда и обратно</option>
              <option value="one_way">В одну сторону</option>
            </select>
          </div>
          <div class="fld">
            <label for="subChannel">Уведомление в</label>
            <select name="channel" id="subChannel" required>
              <option value="email">Email</option>
              <option value="telegram">Telegram</option>
            </select>
          </div>
        </div>

        <div class="fgroup grp-signal subscribe-group">
          <div class="fld fld-price">
            <label for="subMaxPrice">Максимальная желаемая цена</label>
            <input name="max_desired_price" id="subMaxPrice" type="number" min="0" step="1" placeholder="Например, 18000" class="has-suffix">
            <span class="fld-suffix" aria-hidden="true">₽</span>
          </div>
        </div>
      </div>

      <div class="ticket-tear" aria-hidden="true"></div>

      <div class="ticket-footer">
        <button type="submit" class="btn" id="subscribeBtn">Подписаться</button>
        <div class="subhint">Сигнал придёт, только когда цена реально порадует.</div>
      </div>
    </form>

    <div class="ticket-process" id="subscriptionProcess" hidden>
      <div class="tproc-orb" id="subscriptionOrb">
        <div class="tproc-spinner" aria-hidden="true"></div>
        <div class="tproc-ok" aria-hidden="true">
          <svg viewBox="0 0 36 36" width="34" height="34">
            <path d="M9 19 L15 25 L27 11"/>
          </svg>
        </div>
      </div>
      <p class="tproc-text" id="subscriptionProcessText"></p>
      <button type="button" class="tproc-again" id="subscriptionAgainBtn" hidden>Оформить ещё одну подписку</button>
    </div>

    <div class="submessage" id="subscriptionMessage" aria-live="polite"></div>
  </div>
</section>
</main>

<script defer src="{{ asset('assets/home.js') }}"></script>
</body>
</html>
