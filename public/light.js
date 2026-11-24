// OWNER: Claude Rollins | live traffic light: counts down every second and follows the server
(function () {
  var lampIds = { RED: 'lamp-red', YELLOW: 'lamp-amber', GREEN: 'lamp-green' };
  var lampClass = { RED: 'red', YELLOW: 'amber', GREEN: 'green' };
  var advice = { RED: 'Stop.', YELLOW: 'Get ready to stop.', GREEN: 'Go.' };
  var phase = null, endsAt = null, emergency = false, waiting = false, last = {};

  function el(id) { return document.getElementById(id); }
  function setText(id, text) { var e = el(id); if (e) e.textContent = text; }   // skips elements a page does not have
  if (!el('light')) return;                         // the login pages have no light

  function draw() {
    if (phase === null) return;
    for (var p in lampIds) {                        // switch the right lamp on, the others off
      el(lampIds[p]).className = 'lamp' + (p === phase ? ' ' + lampClass[p] : '');
    }
    setText('stat-signal', phase);
    if (emergency) {                                // BR-02: no countdown during an emergency
      setText('count', 'EMERGENCY');
      setText('count-label', 'Override active');
      setText('advice', 'Emergency vehicle priority.');
      return;
    }
    var left = Math.max(0, Math.ceil((endsAt - Date.now()) / 1000));
    setText('count', left + 's');
    setText('count-label', phase.charAt(0) + phase.slice(1).toLowerCase() + ' time left');
    setText('advice', advice[phase]);
    if (left === 0 && !waiting) { waiting = true; sync(); }   // time is up: ask for the next light
  }

  function swap(id, html) {                         // replace a block's content, only when it has changed
    var node = el(id);
    if (node && typeof html === 'string' && html !== last[id]) { last[id] = html; node.innerHTML = html; }
  }

  function showExtras(s) {                          // parts only the motorist page has
    setText('stat-queue', s.queue + ' m');
    var c = el('condition');
    if (c) { c.textContent = s.condition; c.className = 'cond ' + s.conditionClass; }
    swap('incidents', s.incidents);                 // operator: incidents table
    swap('open-incidents', s.openIncidents);        // motorist: incidents on the road
    swap('my-reports', s.myReports);                // motorist: my own reports
    var banner = el('emergency-banner');
    if (banner) banner.hidden = !s.emergency;
    var list = el('alerts-list');
    if (list) {
      list.innerHTML = '';
      var items = s.alerts && s.alerts.length ? s.alerts : ['No alerts right now.'];
      items.forEach(function (text) { var li = document.createElement('li'); li.textContent = text; list.appendChild(li); });
    }
  }

  function sync() {                                 // ask the server which light is on and for how long
    fetch('index.php?state=1', { cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (s) {
        if (!s) return;
        phase = s.phase;
        emergency = s.emergency;
        endsAt = emergency ? null : Date.now() + s.remaining * 1000;
        setText('stat-green', s.green + 's');
        showExtras(s);
        draw();
      })
      .catch(function () {})
      .then(function () { waiting = false; });
  }

  setInterval(draw, 200);                           // redraw the countdown 5 times a second
  setInterval(sync, 2000);                          // re-check the server every 2 seconds
  sync();
})();