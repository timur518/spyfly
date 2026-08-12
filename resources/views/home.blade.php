<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">

@php
    $seoTitle = 'Дешевые авиабилеты — находим авиабилеты с большой выгодой | SpyFly';
    $seoDescription = 'Дешевые авиабилеты по всем направлениям: Сравнение цен и подписка на сигналы о дешевых авиабилетах. Скидки на авиабилеты, поиск дешевых билетов на самолет, отслеживание цен на авиабилеты. SpyFly — ваш надежный помощник в поиске выгодных авиаперелетов.';
    $canonicalUrl = url('/');
    $ogImageUrl = asset('assets/og-cover.png');
@endphp

<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta name="keywords" content="дешевые авиабилеты, скидки на авиабилеты, авиабилеты онлайн, купить дешевые авиабилеты, отслеживание цен на авиабилеты, поиск дешевых билетов на самолет">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="author" content="SpyFly">
<link rel="canonical" href="{{ $canonicalUrl }}">
<meta name="theme-color" content="#0b0e14">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="SpyFly">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $ogImageUrl }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Golos+Text:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('assets/home.css') }}">

<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@graph' => [
        [
            '@type' => 'WebSite',
            '@id' => $canonicalUrl . '#website',
            'url' => $canonicalUrl,
            'name' => 'SpyFly',
            'description' => $seoDescription,
            'inLanguage' => 'ru-RU',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $canonicalUrl . '?origin={origin}&destination={destination}',
                ],
                'query-input' => [
                    'required name=origin',
                    'required name=destination',
                ],
            ],
        ],
        [
            '@type' => 'Organization',
            '@id' => $canonicalUrl . '#organization',
            'name' => 'SpyFly',
            'url' => $canonicalUrl,
            'logo' => asset('assets/logo-512.png'),
        ],
        [
            '@type' => 'WebPage',
            '@id' => $canonicalUrl . '#webpage',
            'url' => $canonicalUrl,
            'name' => $seoTitle,
            'description' => $seoDescription,
            'inLanguage' => 'ru-RU',
            'isPartOf' => ['@id' => $canonicalUrl . '#website'],
            'about' => ['@id' => $canonicalUrl . '#organization'],
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
</script>
<meta name="google-site-verification" content="ski-VqjEviW7u_ltGW_uoUTkE2r8lJxSMRtjHLZV9g0" />
<meta name="yandex-verification" content="2a6bdbb82888251d" />
<!-- Yandex.Metrika counter -->
<script type="text/javascript">
    (function(m,e,t,r,i,k,a){
        m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
    })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=111493334', 'ym');

    ym(111493334, 'init', {ssr:true, webvisor:true, clickmap:true, ecommerce:"dataLayer", referrer: document.referrer, url: location.href, accurateTrackBounce:true, trackLinks:true});
</script>
<noscript><div><img src="https://mc.yandex.ru/watch/111493334" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
<!-- /Yandex.Metrika counter -->
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
        <p class="sub">Находим дни, когда билеты стоят сильно ниже обычного</p>
      </div>
    </div>
    <div class="searchstack">
      <div class="authtab" role="group" aria-label="Вход через социальные сети">
        @auth
          <span class="authtab-profile">
            <span class="authtab-avatar" aria-hidden="true">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none">
                <path d="M12 13.2c3.1 0 5.6-2.5 5.6-5.6S15.1 2 12 2 6.4 4.5 6.4 7.6s2.5 5.6 5.6 5.6Z" stroke="currentColor" stroke-width="1.7"/>
                <path d="M4.8 21c1.3-3.7 4.3-5.7 7.2-5.7S17.9 17.3 19.2 21" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                <path d="M16.8 5.5c1.4.7 2.4 2 2.8 3.6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" opacity=".85"/>
              </svg>
            </span>
            <span class="authtab-user">{{ auth()->user()->name }}</span>
          </span>
          <form method="POST" action="{{ route('logout') }}" class="authtab-logout">
            @csrf
            <button type="submit" class="authchip authchip-logout" title="Выйти из аккаунта" aria-label="Выйти из аккаунта">
              <svg width="15" height="15" viewBox="0 0 24 24" aria-hidden="true" fill="none">
                <path d="M10 17l5-5-5-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M15 12H4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                <path d="M14 4h4.2A1.8 1.8 0 0 1 20 5.8v12.4A1.8 1.8 0 0 1 18.2 20H14" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" opacity=".9"/>
              </svg>
            </button>
          </form>
        @else
          <span class="authtab-label">Войдите, чтобы найти большие скидки:</span>
          <a href="{{ route('social.redirect', 'yandex') }}" class="authchip authchip-ya" title="Войти через Яндекс" aria-label="Войти через Яндекс">
            <svg width="18" height="18" viewBox="0 0 14 14" aria-hidden="true"><text x="7" y="10.4" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" font-weight="700" fill="#fff">Я</text></svg>
          </a>
          <a href="{{ route('social.redirect', 'vkontakte') }}" class="authchip authchip-vk" title="Войти через VK" aria-label="Войти через VK">
            <svg width="18" height="18" viewBox="0 0 14 14" aria-hidden="true"><text x="7" y="10" text-anchor="middle" font-family="Arial, sans-serif" font-size="7.5" font-weight="800" fill="#fff">VK</text></svg>
          </a>
          <a href="{{ route('social.redirect', 'odnoklassniki') }}" class="authchip authchip-ok" title="Войти через OK" aria-label="Войти через OK">
            <svg width="18" height="18" viewBox="0 0 14 14" aria-hidden="true"><text x="7" y="10" text-anchor="middle" font-family="Arial, sans-serif" font-size="7" font-weight="800" fill="#fff">OK</text></svg>
          </a>
        @endauth
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
    </div>
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
      <input type="hidden" name="user_id" value="{{ auth()->id() ?? '' }}">
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
