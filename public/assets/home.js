const result=document.getElementById('result');
const loader=document.getElementById('loader');
const scanBtn=document.getElementById('scanBtn');
const loaderCap=document.querySelector('#loader .cap');
const originInput=document.getElementById('origin');
const destinationInput=document.getElementById('destination');
const originAc=document.getElementById('originAc');
const destinationAc=document.getElementById('destinationAc');
const subscriptionForm=document.getElementById('subscriptionForm');
const subscribeBtn=document.getElementById('subscribeBtn');
const subscriptionMessage=document.getElementById('subscriptionMessage');
const subscriptionLead=document.getElementById('subscriptionLead');
const subscriptionProcess=document.getElementById('subscriptionProcess');
const subscriptionOrb=document.getElementById('subscriptionOrb');
const subscriptionProcessText=document.getElementById('subscriptionProcessText');
const subscriptionAgainBtn=document.getElementById('subscriptionAgainBtn');
const subscriptionCard=document.getElementById('subscriptionCard');
const cabinetShell=document.getElementById('cabinetShell');
const cabinetToggle=document.getElementById('cabinetToggle');
const cabinetState=window.__CABINET_STATE__||null;
const cabinetRoutes=window.__HOME_ROUTES__||{};
const csrfToken=document.querySelector('meta[name="csrf-token"]')?.content||'';
const subUserIdInput=subscriptionForm.querySelector('input[name="user_id"]');
const subOriginInput=document.getElementById('subOrigin');
const subDestinationInput=document.getElementById('subDestination');
const subOriginAc=document.getElementById('subOriginAc');
const subDestinationAc=document.getElementById('subDestinationAc');
const subSwapBtn=document.getElementById('subSwapBtn');
const subTripType=document.getElementById('subTripType');
const subChannel=document.getElementById('subChannel');
const subMaxPrice=document.getElementById('subMaxPrice');
const subMinStay=document.getElementById('subMinStay');
const subMaxStay=document.getElementById('subMaxStay');
const subDateFrom=document.getElementById('subDateFrom');
const subDateTo=document.getElementById('subDateTo');
const signalsTrack=document.getElementById('signalsTrack');
const signalsCurrent=document.getElementById('signalsCurrent');
const signalsNext=document.getElementById('signalsNext');
const initialView=(document.body.dataset.pageView||cabinetState?.view||'search')==='cabinet'?'cabinet':'search';

let currentView=initialView;
let cabinetSection='subscriptions';

const money=v=>v==null?'—':new Intl.NumberFormat('ru-RU',{maximumFractionDigits:0}).format(v)+' ₽';
const moneyShort=v=>{
  if(v==null)return'—';
  if(v>=1000)return new Intl.NumberFormat('ru-RU',{maximumFractionDigits:1}).format(v/1000)+'k';
  return String(Math.round(v));
};
const pct=v=>v==null?'—':Number(v).toFixed(1)+'%';
const esc=s=>String(s??'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
const dLong=s=>s?new Date(s+'T12:00:00').toLocaleDateString('ru-RU',{weekday:'short',day:'numeric',month:'long'}):'—';
const dShort=s=>s?new Date(s+'T12:00:00').toLocaleDateString('ru-RU',{day:'numeric',month:'short'}):'—';
const monthName=ym=>new Date(ym+'-01T12:00:00').toLocaleDateString('ru-RU',{month:'long',year:'numeric'});
const monthShort=ym=>new Date(ym+'-01T12:00:00').toLocaleDateString('ru-RU',{month:'short'});
const plural=(n,one,few,many)=>{const a=Math.abs(n)%10,b=Math.abs(n)%100;if(a===1&&b!==11)return one;if(a>=2&&a<=4&&(b<12||b>14))return few;return many;};
const timeOf=s=>{if(typeof s!=='string'||!s.includes('T'))return null;const hm=(s.split('T')[1]||'').slice(0,5);return /^\d{2}:\d{2}$/.test(hm)?hm:null;};
const foundLabel=s=>{
  if(!s)return null;
  const d=new Date(s);
  if(Number.isNaN(d.getTime()))return null;
  const pad=n=>String(n).padStart(2,'0');
  const now=new Date();
  const ageMs=now.getTime()-d.getTime();
  const day=x=>new Date(x.getFullYear(),x.getMonth(),x.getDate()).getTime();
  const diff=Math.round((day(now)-day(d))/864e5);
  if(ageMs>=0&&ageMs<3*60*60*1000)return {text:'Найден только что',today:true};
  if(diff===0)return {text:`Найден сегодня`,today:true};
  if(diff===1)return {text:`Найден вчера`,today:false};
  return {text:`Найден ${pad(d.getDate())}.${pad(d.getMonth()+1)}.${d.getFullYear()}`,today:false};
};
const transferLabel=n=>n==null?'—':n===0?'Прямой рейс':'С пересадками';
const arrivalCell=f=>{
  const t=timeOf(f.arrival_at);
  if(!t)return null;
  const d1=String(f.arrival_at).slice(0,10);
  const days=f.date&&d1>f.date?Math.round((new Date(d1+'T12:00:00')-new Date(f.date+'T12:00:00'))/864e5):0;
  return t+(days>0?`<span class="plus">+${days}</span>`:'');
};
const airportCode=a=>Array.isArray(a)?a[0]:(a&&(a.code||a.iata_code)?(a.code||a.iata_code):null);
const airportLabel=a=>Array.isArray(a)?a[1]:(a&&a.label?a.label:null);
const airportByCode=code=>AIRPORTS.find(x=>airportCode(x)===code)||null;
const POPULAR_CODES=['SVO','DME','VKO','LED','AER','KZN','SVX','UFA','KGD','OVB','KRR','IST','DXB','AYT','TBS','BKK','CDG','JFK','LHR','BCN'];
const POPULAR_RANK=new Map(POPULAR_CODES.map((c,i)=>[c,i]));
const airportCity=code=>{
  const a=airportByCode(code);
  if(!a)return code;
  const label=airportLabel(a)||code;
  const city=label.includes(' — ')?label.split(' — ')[0]:label.replace(/\s*\(\w+\)$/,'');
  return city||code;
};
const AIRLINES={SU:'Аэрофлот',S7:'S7 Airlines',DP:'Победа',FV:'Россия',U6:'Уральские авиалинии',UT:'Utair',N4:'Nordwind',WZ:'Red Wings','5N':'Smartavia',A4:'Азимут',Y7:'NordStar',YC:'Ямал',IO:'ИрАэро',I8:'Ижавиа',KV:'КрасАвиа',EO:'Ikar',ZF:'Azur Air',TK:'Turkish Airlines',EK:'Emirates',B2:'Белавиа'};
const airlineName=code=>code?(AIRLINES[String(code).toUpperCase()]||String(code).toUpperCase()):null;

let AIRPORTS=[
 ['SVO','Москва — Шереметьево'],['LED','Санкт-Петербург — Пулково'],['AER','Сочи'],['DME','Москва — Домодедово'],
 ['VKO','Москва — Внуково'],['KZN','Казань'],['SVX','Екатеринбург — Кольцово'],['ROV','Ростов-на-Дону — Платов'],
 ['KUF','Самара — Курумоч'],['UFA','Уфа'],['KGD','Калининград — Храброво'],['MRV','Минеральные Воды'],
 ['OVB','Новосибирск — Толмачево'],['CEK','Челябинск — Баландино'],['IKT','Иркутск'],['VVO','Владивосток — Кневичи'],
 ['KRR','Краснодар — Пашковский'],['NOZ','Новокузнецк'],['GOJ','Нижний Новгород — Стригино'],['PEE','Пермь — Большое Савино'],
 ['OMS','Омск — Центральный'],['KJA','Красноярск — Емельяново'],['TJM','Тюмень — Рощино'],['MMK','Мурманск'],
 ['ARH','Архангельск — Талаги'],['HTA','Чита — Кадала'],['ABA','Абакан'],['BQS','Благовещенск'],['YKS','Якутск'],
 ['PKC','Петропавловск-Камчатский — Елизово']
];

function fillAirportSelects(){
  originInput.value=airportLabel(airportByCode('KZN'))||'KZN';
  destinationInput.value=airportLabel(airportByCode('KGD'))||'KGD';
}

function syncSubscriptionDefaults(){
  if(subOriginInput) subOriginInput.value=originInput.value;
  if(subDestinationInput) subDestinationInput.value=destinationInput.value;
  if(subTripType) subTripType.value=oneWaySel.value==='1'?'one_way':'round_trip';
  if(subDateFrom && !subDateFrom.value){
    const fromEl=document.getElementById('fromDate');
    if(fromEl) subDateFrom.value=fromEl.value;
  }
  if(subDateTo && !subDateTo.value){
    const toEl=document.getElementById('toDate');
    if(toEl) subDateTo.value=toEl.value;
  }
}

function extractApiError(payload, fallback){
  if(payload&&typeof payload==='object'){
    if(typeof payload.error==='string'&&payload.error.trim()) return payload.error;
    if(typeof payload.message==='string'&&payload.message.trim()) return payload.message;
    if(payload.errors&&typeof payload.errors==='object'){
      const first=Object.values(payload.errors).flat().find(v=>typeof v==='string'&&v.trim());
      if(first) return first;
    }
  }
  return fallback;
}

function signalHtml(signal){
  return `<span class="signal-route">${esc(signal.route_label||'—')}</span> - <span class="signal-price">${esc(signal.price_label||money(signal.price))}</span> <span class="signal-pct">(${esc(signal.deviation_label||signalPct(signal.deviation_percent))})</span>`;
}

function signalPct(value){
  if(value==null||value==='') return '—';
  const n=Number(value);
  if(Number.isNaN(n)) return '—';
  const num=Math.abs(n).toFixed(1).replace(/\.0$/,'');
  return `${n>0?'+':'-'}${num}%`;
}

let signalsItems=[];
let signalsIndex=0;
let signalsTimer=null;
let signalsTransitionTimer=null;

function setSignalsEmpty(message='Пока ищем первые выгодные цены'){
  if(!signalsCurrent || !signalsTrack) return;
  signalsTrack.classList.remove('is-switching');
  signalsCurrent.textContent=`${message}`;
  if(signalsNext) signalsNext.textContent='';
}

function switchSignal(){
  if(!signalsTrack || !signalsCurrent || !signalsNext || signalsItems.length < 2){
    return;
  }

  const nextItem=signalsItems[signalsIndex % signalsItems.length];
  signalsIndex=(signalsIndex + 1) % signalsItems.length;

  signalsNext.innerHTML=signalHtml(nextItem);
  signalsTrack.classList.add('is-switching');

  const finish=()=>{
    const prevCurrentTransition=signalsCurrent.style.transition;
    const prevNextTransition=signalsNext.style.transition;
    signalsCurrent.style.transition='none';
    signalsNext.style.transition='none';
    signalsCurrent.innerHTML=signalHtml(nextItem);
    signalsTrack.classList.remove('is-switching');
    signalsCurrent.offsetHeight;
    signalsCurrent.style.transition=prevCurrentTransition;
    signalsNext.innerHTML='';
    signalsNext.style.transition=prevNextTransition;
  };

  const onEnd=e=>{
    if(e.propertyName!=='transform' || e.target!==signalsNext) return;
    signalsNext.removeEventListener('transitionend',onEnd);
    finish();
  };

  signalsNext.addEventListener('transitionend',onEnd);

  if(signalsTransitionTimer) clearTimeout(signalsTransitionTimer);
  signalsTransitionTimer=setTimeout(()=>{
    signalsNext.removeEventListener('transitionend',onEnd);
    const prevCurrentTransition=signalsCurrent.style.transition;
    const prevNextTransition=signalsNext.style.transition;
    signalsCurrent.style.transition='none';
    signalsNext.style.transition='none';
    signalsCurrent.innerHTML=signalHtml(nextItem);
    signalsTrack.classList.remove('is-switching');
    signalsCurrent.offsetHeight;
    signalsCurrent.style.transition=prevCurrentTransition;
    signalsNext.innerHTML='';
    signalsNext.style.transition=prevNextTransition;
  }, 360);
}

async function loadSignalsTicker(){
  if(!signalsTrack || !signalsCurrent || !signalsNext) return;

  try{
    const r=await fetch('/api/signals?limit=8',{headers:{Accept:'application/json'}});
    if(!r.ok) throw new Error(`HTTP ${r.status}`);
    const d=await r.json();
    if(!d.success || !Array.isArray(d.data) || !d.data.length){
      signalsItems=[];
      if(signalsTimer) clearInterval(signalsTimer);
      signalsTimer=null;
      setSignalsEmpty();
      return;
    }

    signalsItems=d.data;
    signalsIndex=1;
    signalsCurrent.innerHTML=signalHtml(signalsItems[0]);
    signalsNext.innerHTML='';
    signalsTrack.classList.remove('is-switching');

    if(signalsTimer) clearInterval(signalsTimer);
    signalsTimer=null;
    if(signalsItems.length > 1){
      signalsTimer=setInterval(switchSignal, 3400);
    }
  }catch(err){
    console.error('Не удалось загрузить сигналы:', err);
    if(!signalsItems.length){
      setSignalsEmpty('Сигналы появятся здесь, как только найдём хорошие цены');
    }
  }
}

function subscriptionSuccessText(payload){
  const originCity=esc(airportCity(payload.origin_iata));
  const destCity=payload.destination_iata?esc(airportCity(payload.destination_iata)):null;
  const priceTx=payload.max_desired_price?`с ценой до <strong>${money(payload.max_desired_price)}</strong> `:'';
  const routeTx=destCity?`из <strong>${originCity}</strong> в <strong>${destCity}</strong>`:`из <strong>${originCity}</strong>`;
  return `Можно расслабиться!<br>Мы начали искать билеты ${priceTx}${routeTx}.<br>Сообщим, как только найдём подходящий вариант!`;
}

function resetSubscriptionForm(){
  subscriptionProcess.hidden=true;
  subscriptionOrb.classList.remove('success');
  subscriptionProcessText.innerHTML='';
  subscriptionAgainBtn.hidden=true;
  if(subscriptionLead) subscriptionLead.hidden=false;
  subscriptionForm.hidden=false;
  subscriptionMessage.dataset.state='';
  subscriptionMessage.textContent='';
}
if(subscriptionAgainBtn) subscriptionAgainBtn.addEventListener('click',resetSubscriptionForm);

function cabinetDate(value){
  if(!value) return '—';
  const d=new Date(value);
  if(Number.isNaN(d.getTime())) return '—';
  return d.toLocaleDateString('ru-RU',{day:'numeric',month:'short',year:'numeric'});
}

function splitSummary(value){
  return String(value||'')
    .split('\n')
    .map(s=>s.trim())
    .filter(Boolean);
}

function providerLabel(provider){
  return ({
    yandex:'Яндекс',
    vkontakte:'VK',
    odnoklassniki:'Одноклассники',
  })[provider]||'Email';
}

function cabinetAvatarSvg(){
  return `<svg width="26" height="26" viewBox="0 0 24 24" fill="none" aria-hidden="true">
    <path d="M12 13.2c3.1 0 5.6-2.5 5.6-5.6S15.1 2 12 2 6.4 4.5 6.4 7.6s2.5 5.6 5.6 5.6Z" stroke="currentColor" stroke-width="1.7"/>
    <path d="M4.8 21c1.3-3.7 4.3-5.7 7.2-5.7S17.9 17.3 19.2 21" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
  </svg>`;
}

function cabinetAvatarMarkup(user){
  return user?.avatar_url
    ? `<img class="cabinet-avatar-img" src="${esc(user.avatar_url)}" alt="" aria-hidden="true">`
    : cabinetAvatarSvg();
}

function subscriptionCardMarkup(item,{history=false}={}){
  const lines=splitSummary(item.route_summary);
  const title=lines[0]||`Подписка #${item.id}`;
  const subtitle=lines.slice(1).join(' · ');
  const status=item.is_active?'Активна':'В архиве';
  const statusClass=item.is_active?'active':'muted';
  const extra=history&&item.updated_at?`Обновлена ${cabinetDate(item.updated_at)}`:`Создана ${cabinetDate(item.created_at)}`;
  return `
    <article class="cabinet-card">
      <div class="cabinet-card-head">
        <div class="cabinet-card-title">
          <h3>${esc(title)}</h3>
          <p>${esc(subtitle||'Маршрут и параметры подписки')}</p>
        </div>
        <div class="cabinet-card-meta">
          <span class="cabinet-tag ${statusClass}">${esc(status)}</span>
          <span class="cabinet-tag muted">#${item.id}</span>
        </div>
      </div>
      <div class="cabinet-card-body">
        <div class="cabinet-field"><span class="k">Порог</span><span class="v">${esc(item.price_summary||'—')}</span></div>
        <div class="cabinet-field"><span class="k">Период</span><span class="v">${esc(item.date_range_summary||'—')}</span></div>
        <div class="cabinet-field"><span class="k">Длительность</span><span class="v">${esc(item.stay_summary||'—')}</span></div>
        <div class="cabinet-field"><span class="k">Канал</span><span class="v">${esc(item.channel_summary||'—')}</span></div>
      </div>
      <div class="cabinet-section-divider"></div>
      <div class="cabinet-field"><span class="k">Статус</span><span class="v">${esc(extra)}</span></div>
    </article>`;
}

function cabinetMenuClass(section){
  return section===cabinetSection?'is-active':'';
}

function cabinetSidebar(){
  const user=cabinetState?.user||{};
  const activeCount=Number(cabinetState?.active_count||0);
  const totalCount=Number(cabinetState?.subscriptions?.length||0);
  return `
    <aside class="cabinet-sidebar">
      <div class="cabinet-profilebox">
        <div class="cabinet-userrow">
          <div class="cabinet-avatar">${cabinetAvatarMarkup(user)}</div>
          <div>
            <div class="cabinet-name">${esc(user.name||'Профиль')}</div>
            <div class="cabinet-email">${esc(user.email||'—')}</div>
          </div>
        </div>
        <div class="cabinet-pillrow">
          <span class="cabinet-pill">Активных <strong>${activeCount}</strong></span>
          <span class="cabinet-pill">Всего <strong>${totalCount}</strong></span>
        </div>
      </div>
      <nav class="cabinet-nav" aria-label="Меню кабинета">
        <button type="button" data-cabinet-target="profile" class="${cabinetMenuClass('profile')}">Мой профиль</button>
        <button type="button" data-cabinet-target="subscriptions" class="${cabinetMenuClass('subscriptions')}">Мои подписки <span class="cabinet-nav-count">${activeCount}</span></button>
        <button type="button" data-cabinet-target="history" class="${cabinetMenuClass('history')}">История подписок</button>
        <form method="POST" action="${esc(cabinetRoutes.logout||'/logout')}" data-cabinet-logout-form>
          <input type="hidden" name="_token" value="${esc(csrfToken)}">
          <button type="submit" class="cabinet-logout-btn" data-cabinet-target="logout"><span>Выйти</span></button>
        </form>
      </nav>
    </aside>`;
}

function cabinetProfilePanel(){
  const user=cabinetState?.user||{};
  const activeCount=Number(cabinetState?.active_count||0);
  const totalCount=Number(cabinetState?.subscriptions?.length||0);
  return `
    <section class="cabinet-hero">
      <div class="cabinet-hero-top">
        <div>
          <p class="cabinet-kicker">Личный кабинет</p>
          <h1 class="cabinet-title">Мой профиль</h1>
          <p class="cabinet-subtitle">Управляйте подписками, следите за архивом и возвращайтесь к поиску в один клик.</p>
        </div>
        <div class="cabinet-hero-stat">
          <span class="k">Активных подписок</span>
          <span class="v">${activeCount}</span>
          <span class="s">${activeCount ? 'под контролем' : 'пока пусто'}</span>
        </div>
      </div>
      <div class="cabinet-hero-actions">
        <a href="#" class="cabinet-action" data-cabinet-target="subscriptions">Активные подписки</a>
        <a href="#" class="cabinet-action" data-cabinet-target="history">История подписок</a>
      </div>
    </section>
    <section class="cabinet-panel">
      <div class="cabinet-panel-head">
        <div>
          <h2>${esc(user.name||'Пользователь')}</h2>
          <p>${esc(user.email||'—')}</p>
        </div>
        <div class="cabinet-tag muted">${esc(providerLabel(user.provider))}</div>
      </div>
      <div class="cabinet-profile-card">
        <div class="cabinet-avatar">${cabinetAvatarMarkup(user)}</div>
        <div class="cabinet-profile-stack">
          <div>
            <div class="cabinet-name">${esc(user.name||'Пользователь')}</div>
            <div class="cabinet-subtitle" style="margin:4px 0 0">${esc(user.email||'—')}</div>
          </div>
          <div class="cabinet-subtitle" style="margin:0">Это основной профиль для управления ценовыми подписками и историей слежения.</div>
        </div>
      </div>
      <div class="cabinet-profile-stats" style="margin-top:16px">
        <div class="cabinet-stat"><div class="k">Активные</div><div class="v">${activeCount}</div><div class="s">подписок в работе</div></div>
        <div class="cabinet-stat"><div class="k">Всего</div><div class="v">${totalCount}</div><div class="s">подписок в архиве и работе</div></div>
        <div class="cabinet-stat"><div class="k">Вход</div><div class="v">${esc(providerLabel(user.provider))}</div><div class="s">текущий способ авторизации</div></div>
      </div>
    </section>`;
}

function cabinetSubscriptionsPanel(){
  const active=Array.isArray(cabinetState?.active_subscriptions)?cabinetState.active_subscriptions:[];
  return `
    <section class="cabinet-panel">
      <div class="cabinet-panel-head">
        <div>
          <h2>Активные подписки</h2>
          <p>${active.length ? 'Текущие фильтры, которые продолжают ловить подходящие цены.' : 'Подписок пока нет — можно оформить первую прямо отсюда.'}</p>
        </div>
        <div class="cabinet-tag active">${active.length} шт.</div>
      </div>
      ${active.length ? `<div class="cabinet-grid">${active.map(item=>subscriptionCardMarkup(item)).join('')}</div>` : `
        <div class="cabinet-empty">
          <h3>Пока нечего отслеживать</h3>
          <p>Добавьте маршрут, даты и желаемую цену — и SpyFly начнёт ловить выгодные перелёты за вас.</p>
          <div class="cabinet-subscription-mount" id="cabinetSubscriptionMount"></div>
        </div>
      `}
    </section>`;
}

function cabinetHistoryPanel(){
  const items=Array.isArray(cabinetState?.subscriptions)?cabinetState.subscriptions:[];
  return `
    <section class="cabinet-panel">
      <div class="cabinet-panel-head">
        <div>
          <h2>История подписок</h2>
          <p>${items.length ? 'Все оформленные подписки, включая архивные и отключённые.' : 'История появится после первой оформленной подписки.'}</p>
        </div>
        <div class="cabinet-tag muted">${items.length} шт.</div>
      </div>
      ${items.length ? `<div class="cabinet-grid">${items.map(item=>subscriptionCardMarkup(item,{history:true})).join('')}</div>` : `<div class="cabinet-empty"><h3>История пока пустая</h3><p>Когда вы оформите первую подписку, здесь появится полный список изменений и архив.</p></div>`}
    </section>`;
}

function renderCabinet(section='subscriptions'){
  if(!cabinetShell || !cabinetState) return;
  cabinetSection=section;
  cabinetShell.innerHTML=`<div class="cabinet-layout">${cabinetSidebar()}<div class="cabinet-main">${section==='profile'?cabinetProfilePanel():section==='history'?cabinetHistoryPanel():cabinetSubscriptionsPanel()}</div></div>`;
  const mount=cabinetShell.querySelector('#cabinetSubscriptionMount');
  if(mount && subscriptionCard){
    subscriptionCard.hidden=false;
    mount.appendChild(subscriptionCard);
  }else if(subscriptionCard){
    subscriptionCard.hidden=true;
  }
  cabinetShell.hidden=false;
  result.hidden=true;
}

function showSearchView(){
  currentView='search';
  cabinetShell.hidden=true;
  result.hidden=false;
  if(history.replaceState){
    history.replaceState(null,'',window.location.pathname);
  }
}

function showCabinet(section='subscriptions'){
  if(!cabinetState || !cabinetShell) return;
  currentView='cabinet';
  loader.hidden=true;
  document.body.classList.add('compact');
  if(history.replaceState){
    history.replaceState(null,'',`${window.location.pathname}?view=cabinet`);
  }
  renderCabinet(section);
  window.scrollTo({top:0,behavior:'smooth'});
}

if(cabinetToggle){
  cabinetToggle.addEventListener('click',e=>{
    e.preventDefault();
    showCabinet('subscriptions');
  });
}

if(cabinetShell){
  cabinetShell.addEventListener('click',e=>{
    const target=e.target.closest('[data-cabinet-target]');
    if(!target || !cabinetShell.contains(target)) return;
    const section=target.dataset.cabinetTarget;
    if(section==='logout') return;
    e.preventDefault();
    showCabinet(section);
  });
}

let airportsReady=null;
async function loadAirports(){
  if(airportsReady) return airportsReady;
  airportsReady=(async()=>{
  try{
      const sources=['/api/airports/search?limit=5000'];
      let data=null;
      for(const src of sources){
        const r=await fetch(src,{headers:{Accept:'application/json'}});
        if(!r.ok) continue;
        const d=await r.json();
        if(Array.isArray(d)){ data=d; break; }
        if(d&&Array.isArray(d.data)&&d.data.length){ data=d.data; break; }
        if(d&&Array.isArray(d.airports)&&d.airports.length){ data=d.airports; break; }
      }
      if(!data||!data.length) throw new Error('Пустой список аэропортов');
      AIRPORTS=data;
      fillAirportSelects();
  }catch(err){
    console.error('Не удалось загрузить список аэропортов:',err);
  }
  })();
  return airportsReady;
}

function normalizeAirportValue(value){
  const raw=String(value??'').trim();
  if(!raw)return raw;
  const up=raw.toUpperCase();
  if(/^[A-Z]{3}$/.test(up) && airportByCode(up)) {
    return up;
  }
  const tail=raw.match(/\(([A-Z]{3})\)\s*$/);
  if(tail && airportByCode(tail[1])) {
    return tail[1];
  }
  const exact=AIRPORTS.find(a=>String(airportLabel(a)||'').toLowerCase()===raw.toLowerCase());
  if(exact) {
    return airportCode(exact);
  }
  const city=AIRPORTS.find(a=>String(airportCity(airportCode(a))||'').toLowerCase()===raw.toLowerCase());
  if(city) {
    return airportCode(city);
  }
  const fuzzy=AIRPORTS.find(a=>{
    const label=String(airportLabel(a)||'').toLowerCase();
    const cityName=String(airportCity(airportCode(a))||'').toLowerCase();
    return label.includes(raw.toLowerCase()) || cityName.includes(raw.toLowerCase());
  });
  return fuzzy ? airportCode(fuzzy) : raw;
}

function airportView(a){
  const code=airportCode(a);
  const label=airportLabel(a)||code;
  const city=airportCity(code);
  return {
    code,
    label,
    city,
    text:(label+' '+code+' '+city).toLowerCase(),
    rank:POPULAR_RANK.has(code)?POPULAR_RANK.get(code):9999,
  };
}

function airportOptions(query){
  const q=String(query??'').trim().toLowerCase();
  const all=AIRPORTS.map(airportView);
  if(q.length<2){
    return all
      .filter(x=>POPULAR_RANK.has(x.code))
      .sort((a,b)=>a.rank-b.rank||a.label.localeCompare(b.label))
      .slice(0,6)
      .map(x=>({...x,score:x.rank}));
  }
  const scored=all.map(x=>{
    let score=9;
    if(x.code.toLowerCase()===q) score=0;
    else if(x.code.toLowerCase().startsWith(q)) score=1;
    else if(x.city.toLowerCase()===q) score=2;
    else if(x.city.toLowerCase().startsWith(q)) score=3;
    else if(x.label.toLowerCase()===q) score=4;
    else if(x.label.toLowerCase().startsWith(q)) score=5;
    else if(x.text.includes(q)) score=6;
    if(POPULAR_RANK.has(x.code)) score+=POPULAR_RANK.get(x.code)/100;
    return {...x,score};
  }).filter(x=>x.score<9);
  return scored.sort((a,b)=>a.score-b.score||a.label.localeCompare(b.label)).slice(0,20);
}

function renderAirportPanel(input,panel,query,forcePopular=false){
  const q=String(query??'').trim();
  const items=forcePopular?airportOptions(''):airportOptions(q);
  const heading=forcePopular||q.length<2?'Популярные направления':'По названию города / По коду';
  if(!items.length){
    panel.hidden=false;
    panel.innerHTML=`<div class="ac-head">${heading}</div><div class="ac-empty">${q.length<2?'Введите минимум 2 символа':'Ничего не найдено'}</div>`;
    return;
  }
  panel.hidden=false;
  panel.innerHTML=`<div class="ac-head">${heading}</div><div class="ac-list">${items.map(a=>`
    <button type="button" class="ac-item" data-code="${esc(a.code)}" data-label="${esc(a.label)}">
      <span class="ac-code">${esc(a.code)}</span>
      <span class="ac-main">
        <span class="ac-title">${esc(a.label)}</span>
        <span class="ac-sub">${esc(a.city)}</span>
      </span>
    </button>
  `).join('')}</div>`;
}

function hideAirportPanel(panel){
  panel.hidden=true;
  panel.innerHTML='';
}

function setupAirportAutocomplete(input,panel){
  const refresh=()=>renderAirportPanel(input,panel,input.value,false);
  const refreshPopular=()=>renderAirportPanel(input,panel,input.value,true);
  input.addEventListener('focus',()=>{(input.value.trim().length>=2?refresh():refreshPopular());});
  input.addEventListener('click',()=>{(input.value.trim().length>=2?refresh():refreshPopular());});
  input.addEventListener('input',()=>{(input.value.trim().length>=2?refresh():refreshPopular());});
  input.addEventListener('keydown',e=>{if(e.key==='Escape')hideAirportPanel(panel);});
  panel.addEventListener('mousedown',e=>e.preventDefault());
  panel.addEventListener('click',e=>{
    const item=e.target.closest('.ac-item');
    if(!item)return;
    input.value=item.dataset.label||item.dataset.code||input.value;
    hideAirportPanel(panel);
    input.focus();
    input.setSelectionRange(input.value.length,input.value.length);
  });
}

function combineBestByDate(d){
  const map={};
  [...(d.calendar||[]),...(d.month_matrix||[]),...(d.latest||[])].forEach(r=>{
    if(!r?.date||r?.price==null)return;
    const p=Number(r.price);
    if(!map[r.date]||p<map[r.date].price)map[r.date]={date:r.date,price:p};
  });
  return Object.values(map).sort((a,b)=>a.date.localeCompare(b.date));
}

/* ---------- Chart ---------- */
let chartState=null;

function buildChart(points,baseline){
  if(points.length<2)return '<div class="empty">Недостаточно данных для графика.</div>';

  const W=1060,H=340,padL=64,padR=18,padT=18,padB=44;
  const iw=W-padL-padR, ih=H-padT-padB;

  const t0=new Date(points[0].date+'T12:00:00').getTime();
  const t1=new Date(points[points.length-1].date+'T12:00:00').getTime();
  const vals=points.map(p=>p.price);
  let vMin=Math.min(...vals,baseline??Infinity);
  let vMax=Math.max(...vals,baseline??-Infinity);
  const vPad=(vMax-vMin)*0.12||vMin*0.1||1000;
  vMin=Math.max(0,vMin-vPad); vMax+=vPad;

  const X=d=>padL+iw*((new Date(d+'T12:00:00').getTime()-t0)/Math.max(1,t1-t0));
  const Y=v=>padT+ih*(1-(v-vMin)/Math.max(1,vMax-vMin));

  const yTicks=[];
  for(let i=0;i<=4;i++)yTicks.push(vMin+(vMax-vMin)*i/4);

  const monthsSet=[];
  const seen=new Set();
  points.forEach(p=>{const ym=p.date.slice(0,7);if(!seen.has(ym)){seen.add(ym);monthsSet.push(ym);}});

  const line=points.map((p,i)=>`${i?'L':'M'}${X(p.date).toFixed(1)},${Y(p.price).toFixed(1)}`).join('');
  const area=`M${X(points[0].date).toFixed(1)},${(padT+ih).toFixed(1)} `+points.map(p=>`L${X(p.date).toFixed(1)},${Y(p.price).toFixed(1)}`).join(' ')+` L${X(points[points.length-1].date).toFixed(1)},${(padT+ih).toFixed(1)} Z`;

  const minPrice=Math.min(...vals);
  const bestPts=points.filter(p=>p.price===minPrice);

  const axisFont=`font-family="'JetBrains Mono',monospace" font-size="11"`;
  const gridY=yTicks.map(v=>`<line x1="${padL}" y1="${Y(v).toFixed(1)}" x2="${W-padR}" y2="${Y(v).toFixed(1)}" stroke="#E7EDF4" stroke-width="1"/>`).join('');
  const labelsY=yTicks.map(v=>`<text x="${padL-10}" y="${(Y(v)+4).toFixed(1)}" text-anchor="end" ${axisFont} fill="#8598B0">${moneyShort(v)}</text>`).join('');
  const labelsX=monthsSet.map(ym=>{
    const first=points.find(p=>p.date.startsWith(ym));
    return `<text x="${X(first.date).toFixed(1)}" y="${H-14}" text-anchor="middle" ${axisFont} fill="#8598B0" style="text-transform:uppercase;letter-spacing:.08em">${monthShort(ym)}</text>`;
  }).join('');
  const monthLines=monthsSet.slice(1).map(ym=>{
    const first=points.find(p=>p.date.startsWith(ym));
    return `<line x1="${X(first.date).toFixed(1)}" y1="${padT}" x2="${X(first.date).toFixed(1)}" y2="${padT+ih}" stroke="#EEF3F8" stroke-width="1"/>`;
  }).join('');

  const baseLine=baseline?`<line x1="${padL}" y1="${Y(baseline).toFixed(1)}" x2="${W-padR}" y2="${Y(baseline).toFixed(1)}" stroke="#93A7C0" stroke-width="1.5" stroke-dasharray="6 6"/>`:'';

  const bestDots=bestPts.map(p=>`
    <circle cx="${X(p.date).toFixed(1)}" cy="${Y(p.price).toFixed(1)}" r="9" fill="#0C8A4F" opacity=".16"/>
    <circle cx="${X(p.date).toFixed(1)}" cy="${Y(p.price).toFixed(1)}" r="5" fill="#0C8A4F" stroke="#fff" stroke-width="2"/>
  `).join('');

  chartState={points,X,Y,padL,padT,iw,ih,W,H};

  // Вертикальный градиент, привязанный к средней цене: дорого → красный, около средней → серо-синий, дёшево → зелёный.
  // Серое плато — 25% высоты графика (±12.5% от средней), переходы короткие, красный и зелёный держатся сплошной заливкой.
  const avgY=baseline!=null?Y(baseline):padT+ih/2;
  const avgFrac=Math.min(1,Math.max(0,(avgY-padT)/ih));
  const gA=Math.max(0,avgFrac-0.125), gB=Math.min(1,avgFrac+0.125);
  const rEnd=Math.max(0,gA-0.08), gStart=Math.min(1,gB+0.08);

  return `
  <svg id="chartSvg" class="chart" viewBox="0 0 ${W} ${H}" preserveAspectRatio="xMidYMid meet" role="img" aria-label="График цен по датам вылета">
    <defs>
      <linearGradient id="lineGrad" gradientUnits="userSpaceOnUse" x1="0" y1="${padT}" x2="0" y2="${padT+ih}">
        <stop offset="0%" stop-color="#C0504A"/>
        <stop offset="${(rEnd*100).toFixed(1)}%" stop-color="#C0504A"/>
        <stop offset="${(gA*100).toFixed(1)}%" stop-color="#7C8DB0"/>
        <stop offset="${(gB*100).toFixed(1)}%" stop-color="#7C8DB0"/>
        <stop offset="${(gStart*100).toFixed(1)}%" stop-color="#0C8A4F"/>
        <stop offset="100%" stop-color="#0C8A4F"/>
      </linearGradient>
      <linearGradient id="areaFill" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#7C8DB0" stop-opacity=".12"/>
        <stop offset="100%" stop-color="#7C8DB0" stop-opacity="0"/>
      </linearGradient>
    </defs>
    ${gridY}${monthLines}
    <line x1="${padL}" y1="${padT+ih}" x2="${W-padR}" y2="${padT+ih}" stroke="#C9D5E2" stroke-width="1"/>
    ${labelsY}${labelsX}
    <path d="${area}" fill="url(#areaFill)"/>
    <path d="${line}" fill="none" stroke="url(#lineGrad)" stroke-width="2.4" stroke-linejoin="round" stroke-linecap="round"/>
    ${baseLine}
    ${bestDots}
    <circle id="hoverDot" r="4.5" fill="#16439C" stroke="#fff" stroke-width="2" opacity="0"/>
    <line id="hoverLine" y1="${padT}" y2="${padT+ih}" stroke="#B9C8DA" stroke-width="1" opacity="0"/>
    <rect x="${padL}" y="${padT}" width="${iw}" height="${ih}" fill="transparent" id="chartHit"/>
  </svg>`;
}

function attachChartEvents(){
  const svg=document.getElementById('chartSvg');
  if(!svg||!chartState)return;
  const tooltip=document.getElementById('tooltip');
  const hit=document.getElementById('chartHit');
  const dot=document.getElementById('hoverDot');
  const vline=document.getElementById('hoverLine');
  const {points,X,Y}=chartState;
  const xs=points.map(p=>X(p.date));

  function onMove(evt){
    const rect=svg.getBoundingClientRect();
    const scaleX=svg.viewBox.baseVal.width/rect.width;
    const mx=(evt.clientX-rect.left)*scaleX;
    let lo=0,hi=xs.length-1;
    while(hi-lo>1){const mid=(lo+hi)>>1;if(xs[mid]<mx)lo=mid;else hi=mid;}
    const idx=(mx-xs[lo]<=xs[hi]-mx)?lo:hi;
    const p=points[idx];
    const cx=X(p.date),cy=Y(p.price);

    dot.setAttribute('cx',cx);dot.setAttribute('cy',cy);dot.setAttribute('opacity','1');
    vline.setAttribute('x1',cx);vline.setAttribute('x2',cx);vline.setAttribute('opacity','1');

    const px=rect.left+cx/scaleX-rect.left;
    const py=cy/(svg.viewBox.baseVal.height/rect.height);
    tooltip.style.left=px+'px';
    tooltip.style.top=py+'px';
    tooltip.innerHTML=`<div class="tp">${money(p.price)}</div><div class="td">${esc(dLong(p.date))}</div>`;
    tooltip.style.opacity='1';
  }
  function onLeave(){
    tooltip.style.opacity='0';
    dot.setAttribute('opacity','0');
    vline.setAttribute('opacity','0');
  }
  hit.addEventListener('mousemove',onMove);
  hit.addEventListener('mouseleave',onLeave);
  hit.addEventListener('touchstart',e=>{if(e.touches[0])onMove(e.touches[0]);},{passive:true});
  hit.addEventListener('touchmove',e=>{if(e.touches[0])onMove(e.touches[0]);},{passive:true});
  hit.addEventListener('touchend',onLeave);
}

/* ---------- Best flights ---------- */
function collectBestFlights(d,minPrice){
  const server=d.analysis?.best_flights;
  if(Array.isArray(server)&&server.length)return server;
  if(minPrice==null)return[];
  const rows=[...(d.calendar||[]),...(d.month_matrix||[]),...(d.latest||[])].filter(r=>r&&Number(r.price)===Number(minPrice));
  const richness=r=>(timeOf(r.departure_at)?4:0)+(r.airline?2:0)+(r.transfers!=null?1:0);
  const byDate={};
  rows.forEach(r=>{if(!byDate[r.date]||richness(r)>richness(byDate[r.date]))byDate[r.date]=r;});
  return Object.values(byDate).sort((a,b)=>a.date.localeCompare(b.date));
}

const fmtDuration=min=>{
  if(min==null||!(min>0))return null;
  const h=Math.floor(min/60),mm=min%60;
  return (h?`${h} ч`:'')+(h&&mm?' ':'')+(mm?`${mm} мин`:'')||null;
};

// Линия маршрута: с пересадками — точки на линии с тултипом города
function pathLine(stops){
  if(!stops||!stops.length)return '<span class="bp-path" aria-hidden="true">✈</span>';
  const dots=stops.map(c=>{
    const city=airportCity(c);
    return `<b class="stopdot" tabindex="0" data-city="${esc(city)}" title="Пересадка: ${esc(city)}" aria-label="Пересадка: ${esc(city)}"></b>`;
  }).join('<i class="seg"></i>');
  return `<span class="bp-path has-stops"><span class="pl" aria-hidden="true">✈</span><span class="trk"><i class="seg"></i>${dots}<i class="seg"></i></span></span>`;
}

function pathBlock(stops,transfers,duration){
  const dur=fmtDuration(duration)||'—';
  return `<span class="bp-path-wrap"><span class="bp-over">${esc(transferLabel(transfers))}</span>${pathLine(stops)}<span class="bp-under">${esc(dur)}</span></span>`;
}

function legBlock(tag,leg){
  const dep=timeOf(leg.departure_at);
  const arr=arrivalCell(leg);
  const priceLine=leg.oneway_price!=null
    ?`<div class="leg-price"><b>${money(leg.oneway_price)}</b>в одну сторону</div>`:'';
  return `
  <div class="leg">
    <div class="leg-head"><span class="tag">${tag}</span><span class="d">${esc(leg.date?dLong(leg.date):'—')}</span></div>
    <div class="bp-route">
      <span class="iata">${dep}</span>
      ${pathBlock(leg.stops,leg.transfers,leg.duration)}
      <span class="iata">${arr}</span>
    </div>
    <div class="bp-cities">
      <span>${esc(airportCity(leg.origin))}${dep?` · <span class="t">${esc(leg.origin)}</span>`:''}</span>
      <span class="to">${esc(airportCity(leg.destination))}${arr?` · <span class="t">${esc(leg.destination)}</span>`:''}</span>
    </div>
    ${priceLine}
  </div>`;
}

const CART_ICON=`<svg width="20" height="20" viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M1.5 2h1.6l.5 2m0 0 1.1 5.4a1 1 0 0 0 1 .8h5.7a1 1 0 0 0 1-.77L13.5 5.5a.6.6 0 0 0-.58-.75H3.6Z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="6.2" cy="13.2" r="1.1" fill="currentColor"/><circle cx="11.4" cy="13.2" r="1.1" fill="currentColor"/></svg>`;

// Ссылка на поисковую выдачу Aviasales: ORIG DDMM DEST [DDMM] + 1 пассажир,
// обёрнутая в партнёрский редирект tp.media (meta.buy_prefix)
function buyLink(f,m){
  const dm=s=>{const p=String(s).slice(0,10).split('-');return p[2]+p[1];};
  if(!f.date||!m.origin||!m.destination)return null;
  let u='https://www.aviasales.ru/search/'+m.origin+dm(f.date)+m.destination;
  const back=(f.legs&&f.legs.back&&f.legs.back.date)||(f.return_at?String(f.return_at).slice(0,10):null);
  if(!m.one_way&&back)u+=dm(back);
  u+='1';
  return m.buy_prefix?m.buy_prefix+encodeURIComponent(u):u;
}

function buyStub(f,m,minPrice){
  const price=money(f.price??minPrice);
  const sub=m.one_way?'в одну сторону':'туда и обратно';
  const url=buyLink(f,m);
  if(!url)return `<div class="bp-stub"><span class="buy" style="cursor:default">${CART_ICON}<span class="tx"><span class="pr">${price}</span><span class="s">${sub}</span></span></span></div>`;
  return `<div class="bp-stub">
    <a class="buy" href="${url}" target="_blank" rel="noopener" title="Открыть в поиске Aviasales">
      ${CART_ICON}
      <span class="tx">
        <span class="pr">${price}</span>
        <span class="s">${sub}</span>
      </span>
    </a>
  </div>`;
}

const ZOOM_ICON=`<svg width="14" height="14" viewBox="0 0 15 15" fill="none" aria-hidden="true"><circle cx="6.4" cy="6.4" r="4.6" stroke="currentColor" stroke-width="1.5"/><path d="m10 10 3.2 3.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>`;

// Поиск на конкретную дату с теми же параметрами (возврат = дата + length дней)
function zoomLink(date,m){
  let back=null;
  if(!m.one_way&&m.length){
    const d=new Date(date+'T12:00:00');
    d.setDate(d.getDate()+Number(m.length));
    back=d.toISOString().slice(0,10);
  }
  return buyLink({date:date,return_at:back},m);
}

function boardingPass(f,m,minPrice){
  const oneWay=!!m.one_way;
  const found=foundLabel(f.found_at);
  const airline=f.airline
    ?`${esc(airlineName(f.airline))} · <span class="code">${esc(String(f.airline))}${f.flight_number?' '+esc(String(f.flight_number)):''}</span>`
    :'—';
  const foundMarkup=found?`<div class="bp-found${found.today?' today':''}" title="${esc(found.text)}">${esc(found.text)}</div>`:'';

  if(!oneWay&&f.legs&&f.legs.out&&f.legs.back){
    return `
  <article class="bp">
    ${foundMarkup}
    <div class="bp-main">
      <div class="bp-top"><span class="d">${esc(dLong(f.date))}</span><span class="al">${airline}</span></div>
      <div class="bp-duo">
        ${legBlock('Туда',f.legs.out)}
        <div class="leg-div" aria-hidden="true"></div>
        ${legBlock('Обратно',f.legs.back)}
      </div>
    </div>
    <div class="bp-cut" aria-hidden="true"></div>
    ${buyStub(f,m,minPrice)}
  </article>`;
  }

  const dep=timeOf(f.departure_at);
  const arr=arrivalCell(f);
  const metaParts=[];
  if(!oneWay&&f.return_at)metaParts.push(`обратно ${esc(dShort(String(f.return_at).slice(0,10)))}`);
  const metaLine=metaParts.length?`<div class="bp-meta">${metaParts.join(' · ')}</div>`:'';
  return `
  <article class="bp">
    ${foundMarkup}
    <div class="bp-main">
      <div class="bp-top"><span class="d">${esc(dLong(f.date))}</span><span class="al">${airline}</span></div>
      <div class="bp-route">
        <span class="iata">${dep}</span>
        ${pathBlock(f.stops,f.transfers,f.duration)}
        <span class="iata">${arr}</span>
      </div>
      <div class="bp-cities">
        <span>${esc(airportCity(m.origin))}${dep?` · <span class="t">${esc(m.origin)}</span>`:''}</span>
        <span class="to">${esc(airportCity(m.destination))}${arr?` · <span class="t">${esc(m.destination)}</span>`:''}</span>
      </div>
      ${metaLine}
    </div>
    <div class="bp-cut" aria-hidden="true"></div>
    ${buyStub(f,m,minPrice)}
  </article>`;
}

/* ---------- Render ---------- */
function render(d){
  const a=d.analysis,m=d.meta;
  const points=combineBestByDate(d);
  const minPrice=a.best?.price??null;
  const bestFlights=collectBestFlights(d,minPrice);
  const cheapest=[...points].sort((x,y)=>x.price-y.price).slice(0,5);
  const scannedFlights=m.scanned_flights??((d.calendar?.length||0)+(d.month_matrix?.length||0)+(d.latest?.length||0));

  result.innerHTML=`
    <div class="scanline">Просканировано · ${m.total_days} ${plural(m.total_days,'день','дня','дней')} · ${scannedFlights} ${plural(scannedFlights,'рейс','рейса','рейсов')}</div>

    <section class="board" aria-label="Итоги анализа">
      <article class="cell"><div class="k">Лучшая цена</div><div class="v good">${money(a.best?.price)}</div><div class="s">${esc(airportCity(m.origin))} → ${esc(airportCity(m.destination))}</div></article>
      <article class="cell"><div class="k">Выгода</div><div class="v">${pct(a.saving_percent)}</div><div class="s">ниже средней цены</div></article>
      <article class="cell"><div class="k">Средняя цена</div><div class="v">${money(a.overall?.avg)}</div><div class="s"></div></article>
      <article class="cell"><div class="k">Самый дорогой</div><div class="v">${money(a.overall?.max)}</div><div class="s"></div></article>
    </section>

    ${bestFlights.length?`
    <section class="sect">
      <div class="shead">
        <h2>Рейсы по минимальной цене</h2>
        <p class="sub">${bestFlights.length>1?'Минимальная цена доступна сразу на несколько дат':'Самый выгодный вариант на ваши даты'}</p>
      </div>
      <div class="bps">${bestFlights.map(f=>boardingPass(f,m,minPrice)).join('')}</div>
      <p class="fnote">${m.one_way?'Время — местное для каждого аэропорта':'Время — местное для каждого аэропорта'}</p>
    </section>`:''}

    <div id="subscriptionMount"></div>

    <section class="sect">
      <div class="shead">
        <h2>Цены по датам вылета</h2>
        <p class="sub">Минимальные цены на авиабилеты в ваши даты</p>
      </div>
      <div class="panel chartpanel">
        <div class="chartbox">
          ${buildChart(points,a.baseline_avg??a.overall?.avg)}
          <div id="tooltip" class="tooltip"></div>
        </div>
        <div class="legend">
          <span class="li"><i class="sw dash"></i>Средняя цена периода</span>
          <span class="li"><i class="sw dot"></i>Самая выгодная цена</span>
          <span class="li"><i class="sw dot-red"></i>Самая дорогая цена</span>
        </div>
      </div>
    </section>

    <div class="duo">
      <section class="sect">
        <div class="shead">
          <h2>Топ-5 самых дешёвых дат</h2>
          <p class="sub">Ближайшие альтернативы</p>
        </div>
        <div class="panel listpanel">
          ${cheapest.length?cheapest.map(p=>{
            const zl=zoomLink(p.date,m);
            return `
            <div class="row">
              <span>${p.price===minPrice?'<i class="mindot"></i>':''}${esc(dLong(p.date))}</span>
              <span class="rgt">
                <span class="pr${p.price===minPrice?' good':''}">${money(p.price)}</span>
                ${zl?`<a class="zoom" href="${zl}" target="_blank" rel="noopener" title="Найти билеты на эту дату" aria-label="Найти билеты на ${esc(dLong(p.date))}">${ZOOM_ICON}</a>`:''}
              </span>
            </div>`;
          }).join(''):'<div class="empty">Нет данных.</div>'}
        </div>
      </section>
      <section class="sect">
        <div class="shead">
          <h2>Полнота данных по месяцам</h2>
          <p class="sub">Больше данных — точнее цены</p>
        </div>
        <div class="panel monthspanel">
          <div class="months">
            ${(d.coverage||[]).map(x=>`
              <div class="mrow">
                <span class="mname">${esc(monthName(x.month).replace(' г.',''))}</span>
                <span class="track"><i style="width:${x.coverage_percent}%"></i></span>
                <span class="mval">${x.covered_days} из ${x.days} дн.</span>
              </div>
            `).join('')}
          </div>
        </div>
      </section>
    </div>
  `;

  const mount=document.getElementById('subscriptionMount');
  if(mount && subscriptionCard){
    subscriptionCard.hidden=false;
    mount.appendChild(subscriptionCard);
  }

  attachChartEvents();
}

form.addEventListener('submit',async e=>{
  e.preventDefault();
  showSearchView();
  document.body.classList.add('compact'); // сцена плавно поднимается и становится шапкой
  window.scrollTo({top:0,behavior:'smooth'});
  scanBtn.disabled=true;
  result.innerHTML='';
  if(subscriptionCard) subscriptionCard.hidden=true;
  loader.hidden=false;
  loaderCap.innerHTML='Выискиваем лучший вариант';
  const delay=ms=>new Promise(r=>setTimeout(r,ms));
  await loadAirports();
  originInput.value=normalizeAirportValue(originInput.value);
  destinationInput.value=normalizeAirportValue(destinationInput.value);
  const q=new URLSearchParams(new FormData(form));
  const fetchData=async()=>{
    const r=await fetch('/api/flights/search?'+q,{headers:{Accept:'application/json'}});
    if(!r.ok)throw new Error(`HTTP ${r.status}`);
    const d=await r.json();
    if(!d.success)throw new Error(d.error||'Ошибка API');
    return d;
  };
  const retryCap=()=>{ loaderCap.innerHTML='Еще чуть-чуть <br> Надо поискать глубже'; };
  const runSearch=async()=>{
    let attempt=0;
    const minWait=delay(900);
    while(true){
      try{
        const [d]=await Promise.all([fetchData(),attempt===0?minWait:Promise.resolve()]);
        return d;
      }catch(err){
        attempt++;
        retryCap();
        await delay(Math.min(1000*Math.pow(2,attempt-1),10000));
      }
    }
  };
  try{
    // Даём анимации шапки завершиться, даже если API ответил мгновенно.
    const d=await runSearch();
    loader.hidden=true;
    render(d);
    syncSubscriptionDefaults();
    if(subMaxPrice && !subMaxPrice.value && d.analysis?.best?.price!=null){
      subMaxPrice.value=String(Math.round(Number(d.analysis.best.price)));
    }
  }catch(err){
    loader.hidden=true;
    result.innerHTML=`<section class="panel error"><h2>Не удалось получить результат</h2><p>${esc(err.message)}</p></section>`;
  }finally{
    scanBtn.disabled=false;
  }
});

fillAirportSelects();
loadAirports();
loadSignalsTicker();
setupAirportAutocomplete(originInput,originAc);
setupAirportAutocomplete(destinationInput,destinationAc);
setupAirportAutocomplete(subOriginInput,subOriginAc);
setupAirportAutocomplete(subDestinationInput,subDestinationAc);

subSwapBtn.addEventListener('click',()=>{
  const a=subOriginInput.value;
  subOriginInput.value=subDestinationInput.value;
  subDestinationInput.value=a;
  subSwapBtn.classList.toggle('flip');
});

document.addEventListener('click',e=>{
  if(e.target!==originInput && !originAc.contains(e.target)) hideAirportPanel(originAc);
  if(e.target!==destinationInput && !destinationAc.contains(e.target)) hideAirportPanel(destinationAc);
  if(e.target!==subOriginInput && !subOriginAc.contains(e.target)) hideAirportPanel(subOriginAc);
  if(e.target!==subDestinationInput && !subDestinationAc.contains(e.target)) hideAirportPanel(subDestinationAc);
});

// Поменять "откуда" и "куда" местами
const swapBtn=document.getElementById('swapBtn');
swapBtn.addEventListener('click',()=>{
  const a=originInput.value;
  originInput.value=destinationInput.value;
  destinationInput.value=a;
  swapBtn.classList.toggle('flip');
});

// "Дней в поездке" не участвует в поиске в одну сторону
const oneWaySel=document.getElementById('oneWaySel');
const lenFld=document.getElementById('lenFld');
const lenInput=document.getElementById('lenInput');
const syncTripFields=()=>{
  const ow=oneWaySel.value==='1';
  lenFld.classList.toggle('dim',ow);
  lenInput.disabled=ow;
};
oneWaySel.addEventListener('change',syncTripFields);
oneWaySel.addEventListener('change',syncSubscriptionDefaults);
syncTripFields();
syncSubscriptionDefaults();
if(currentView==='cabinet' && cabinetState){
  showCabinet(cabinetSection);
}

subscriptionForm.addEventListener('submit',async e=>{
  e.preventDefault();
  subscribeBtn.disabled=true;
  subscriptionMessage.dataset.state='';
  subscriptionMessage.textContent='';

  const payload={
    user_id:subUserIdInput?.value?Number(subUserIdInput.value):null,
    origin_iata:normalizeAirportValue(subOriginInput.value),
    destination_iata:normalizeAirportValue(subDestinationInput.value)||null,
    date_from:subDateFrom.value||null,
    date_to:subDateTo.value||null,
    trip_type:subTripType.value,
    max_desired_price:subMaxPrice.value?Number(subMaxPrice.value):null,
    min_stay_days:subMinStay.value?Number(subMinStay.value):null,
    max_stay_days:subMaxStay.value?Number(subMaxStay.value):null,
    channel:subChannel.value,
    is_active:true,
  };

  subscriptionOrb.classList.remove('success');
  subscriptionProcessText.innerHTML='';
  subscriptionAgainBtn.hidden=true;
  if(subscriptionLead) subscriptionLead.hidden=true;
  subscriptionForm.hidden=true;
  subscriptionProcess.hidden=false;

  const minDelay=new Promise(res=>setTimeout(res,600));

  try{
    const [r]=await Promise.all([
      fetch('/api/subscriptions',{
        method:'POST',
        headers:{Accept:'application/json','Content-Type':'application/json'},
        body:JSON.stringify(payload),
      }),
      minDelay,
    ]);
    const data=await r.json().catch(()=>null);
    if(!r.ok || !data?.success){
      throw new Error(extractApiError(data,'Не удалось создать подписку'));
    }
    subscriptionOrb.classList.add('success');
    subscriptionProcessText.innerHTML=subscriptionSuccessText(payload);
    subscriptionAgainBtn.hidden=false;
  }catch(err){
    subscriptionProcess.hidden=true;
    if(subscriptionLead) subscriptionLead.hidden=false;
    subscriptionForm.hidden=false;
    subscriptionMessage.dataset.state='error';
    subscriptionMessage.textContent=err.message||'Не удалось создать подписку';
  }finally{
    subscribeBtn.disabled=false;
  }
});
